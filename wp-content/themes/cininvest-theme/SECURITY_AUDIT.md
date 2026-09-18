# SECURITY_AUDIT.md — аудит безопасности темы CININVEST

Дата: 2026-09-10. Область: весь PHP-код темы (`inc/*.php`, `functions.php`, шаблоны,
`template-parts/*`, `assets/js/main.js`, `tools/*`). Код **не изменялся** — только анализ.

Методика: ручной обзор всех обработчиков форм (`admin_post_*`, `wp_ajax_*`, REST),
трассировка потоков `$_POST`/`$_GET`/`$_FILES`/`$_SERVER` до sink-точек (SQL, вывод в HTML,
запись в БД/meta, файловая система, редиректы), проверка nonce/capability на каждой мутации,
grep по опасным функциям.

## Сводка

| # | Severity | Категория | Кратко | Файл |
|---|---|---|---|---|
| 1 | **High** | Broken Access Control / IDOR | `app_id` из формы → перезапись **любого** поста | `inc/forms-application.php:28,35` |
| 2 | **High** | Broken Access Control / IDOR | `project_id` из формы → запись ACF-meta на **любой** объект | `inc/ajax-shares.php:83–88,138–140` |
| 3 | **High** | Sensitive Data Exposure | Паспорта/ИНН/банк. выписки грузятся в публичный `uploads/` без ограничения доступа | `inc/forms-verification.php:40`, `inc/forms-application.php:100` |
| 4 | **High** | Broken Access Control (funds) | `wallet_action=deposit` начисляет баланс без оплаты и без гейта окружения | `inc/ajax-shares.php:150–168` + `inc/payment-gateway.php:23–32` |
| 5 | Medium | Stored XSS | `the_sub_field()` (отзывы/команда/материалы) выводится без экранирования | `single-project.php`, `template-parts/home/reviews.php` |
| 6 | Medium | Broken Process / future-risk | Самоверификация без реальных проверок; `cininvest_verified` полностью управляется клиентом | `page-verification-done.php:2`, `inc/forms-verification.php` |
| 7 | Medium | Business Logic | buy-shares: нет проверки типа/статуса проекта, лимита долей | `inc/ajax-shares.php:42–94` |
| 8 | Low | Output escaping | `the_title()` без экранирования (штатный паттерн WP) | множество шаблонов |
| 9 | Low | DOM (не XSS) | `location.hash` → `querySelector` без экранирования → `SyntaxError` | `assets/js/main.js` |
| 10 | Low | Auth hardening | Мин. длина пароля 6; нет rate-limit на вход/регистрацию | `inc/forms-auth.php:28` |
| 11 | Low | Logic | `cininvest_login_error` — глобальный transient-ключ (не по юзеру) | `inc/forms-auth.php:79` |
| 12 | Info | Info disclosure | `wp_insert_user()->get_error_message()` показывается пользователю | `inc/forms-auth.php:47` |

**Проверено и уязвимостей НЕ найдено:** SQL-инъекции, CSRF, open redirect, reflected XSS,
RCE через загрузку файлов, опасные sink-функции (`eval`/`system`/`unserialize` пользовательских данных/динамический `include`).

---

## HIGH

### 1. IDOR: перезапись произвольного поста через `app_id` (подача заявки)

**Файл:** `inc/forms-application.php:28–36`

```php
$app_id = !empty($_POST['app_id']) ? (int) $_POST['app_id'] : 0;
$postarr = [ 'post_type' => 'application', 'post_title' => $project_name ?: 'Черновик заявки',
             'post_author' => $user_id, 'post_status' => 'publish' ];
if ($app_id) { $postarr['ID'] = $app_id; $app_id = wp_update_post($postarr); }
```

**Проблема.** `app_id` берётся прямо из `$_POST` и передаётся в `wp_update_post()` **без
проверки**, что этот пост:
- принадлежит текущему пользователю (`post_author == $user_id`);
- вообще имеет тип `application`.

Дальше по этому же `$app_id` идут `update_post_meta()` (≈50 полей),
`wp_set_object_terms($app_id, $status, 'application_status')`.

**Импакт.** Любой авторизованный пользователь (включая `subscriber`) может:
1. загрузить `/application/` и получить валидный nonce `cininvest_application`;
2. отправить POST с `app_id=<ID чужой заявки / страницы / проекта>`;
3. в результате целевой пост: получает `post_type=application` (становится непубличным →
   контент «пропадает» с сайта), `post_status=publish`, новый `post_author` (атакующий),
   новый `post_title` (из `project_name`, санитизирован — не XSS, но разрушение контента),
   и на него навешиваются произвольные meta-поля и терм таксономии.

Это broken access control: массовая порча/угон контента и чужих заявок одним
авторизованным запросом.

**PoC (набросок).**
```
POST /wp-admin/admin-post.php
action=cininvest_application
cininvest_app_nonce=<взять из /application/>
applicant_type=fiz
project_name=hijacked
app_id=2        ← ID страницы "О нас" / чужой заявки
```

**Рекомендация.**
```php
$app_id = 0;
if (!empty($_POST['app_id'])) {
    $candidate = (int) $_POST['app_id'];
    $p = get_post($candidate);
    if ($p && $p->post_type === 'application' && (int) $p->post_author === $user_id) {
        $app_id = $candidate;
    } else {
        wp_die('Нет доступа к этой заявке');
    }
}
```
Плюс — не задавать `post_type`/`post_author` в `$postarr` при обновлении существующего поста.

---

### 2. IDOR: запись ACF-meta на произвольный объект через `project_id`

**Файл:** `inc/ajax-shares.php` — `cininvest_handle_buy_shares()` (стр. 82–89) и
`cininvest_handle_buy_perks()` (стр. 137–142)

```php
$project_id = (int) ($_POST['project_id'] ?? 0);
...
update_field('collected',        ... , $project_id);
update_field('investors_count',  ... , $project_id);
update_field('shares_available', ... , $project_id);   // buy_shares
update_field('sponsors_count',   ... , $project_id);   // buy_perks
```

**Проблема.** `project_id` не проверяется на:
- `get_post_type($project_id) === 'project'`;
- `get_post_status($project_id) === 'publish'`;
- активность/незавершённость сбора.

`update_field()` пишет post-meta на **любой** переданный ID.

**Импакт.**
- Атакующий (авторизованный) может писать meta `collected`/`investors_count`/`sponsors_count`/
  `shares_available` на произвольный пост (страницу, чужой проект, заявку) → замусоривание,
  накрутка счётчика «собрано» на проекте (создать вид «проект профинансирован»),
  обнуление `shares_available`.
- Операция и списание при этом проходят по «проекту», которого может не существовать
  (`cininvest_field` вернёт фолбэки: `share_price=5000`, `commission=4%`),
  `get_permalink($project_id)` в редиректе вернёт `false`.
- Нет верхней границы на `$shares` (`max(1, (int)$_POST['shares'])`), нет проверки
  `shares <= shares_available` — можно «купить» больше долей, чем доступно.

**Рекомендация.** В начале обоих хендлеров:
```php
if (get_post_type($project_id) !== 'project' || get_post_status($project_id) !== 'publish') {
    wp_die('Проект не найден');
}
if (cininvest_field('project_status', $project_id) === 'completed') { /* запретить */ }
```
и валидировать `$shares` против `shares_available`.

---

### 3. Публичное хранение KYC-документов (паспорта, ИНН, банковские выписки)

**Файлы:** `inc/forms-verification.php:29–43`, `inc/forms-application.php:89–103`

```php
$att_id = media_handle_upload($d, 0);                       // верификация: passport_photo, passport_reg, egrip, charter, authority
$att_id = media_handle_upload($ff, $app_id);                // заявка: script, presentation, sizzle, estimate, recommendations
update_user_meta($user_id, 'cininvest_v_' . $d, $att_id);
```

**Проблема.** `media_handle_upload()` кладёт файлы в стандартный
`/wp-content/uploads/ГГГГ/ММ/` как обычные вложения WordPress. Для них:
- URL предсказуем (последовательный ID вложения, датовый путь, имя файла);
- работает страница вложения (`?attachment_id=N`) и её перебор;
- по умолчанию отдаёт REST-эндпоинт `/wp-json/wp/v2/media` (список всех медиа);
- нет никакой проверки, что запрашивающий = владелец документа.

**Импакт.** Разворот паспорта, СНИЛС/ИНН, выписка из ЕГРЮЛ/ЕГРИП, банковские реквизиты,
рекомендательные письма любого пользователя платформы доступны на скачивание всякому,
кто знает или подберёт URL. Для платформы с KYC это критично (152-ФЗ «О персональных данных»,
115-ФЗ). Верификационные документы к тому же грузятся с `parent = 0` — не привязаны даже
к посту.

**Рекомендация.**
- Хранить приватные документы вне `uploads/` (отдельная директория с `deny from all` / вне webroot)
  или отдавать их только через авторизованный прокси-эндпоинт с проверкой владельца:
  `if (get_current_user_id() !== $owner && !current_user_can('manage_options')) wp_die(403);`
- Скрыть приватные вложения из REST: `register_post_type` media фильтром / `rest_prepare_attachment` /
  плагин ограничения доступа к медиа.
- Рандомизировать имена файлов, убрать листинг директорий.
- Проставлять `post_parent` и приватный `post_status`.

---

### 4. Начисление баланса без оплаты (`deposit`) без гейта окружения

**Файлы:** `inc/ajax-shares.php:150–168`, `inc/payment-gateway.php:23–32`

```php
// admin_post_cininvest_wallet → cininvest_handle_wallet()
$action = $_POST['wallet_action'] ?? '';
$amount = max(0, (float) ($_POST['amount'] ?? 0));
if ($action === 'deposit') {
    $res = cininvest_gateway()->deposit($user_id, $amount);   // Mock: просто +$amount к балансу
}
```
```php
public function deposit($user_id, $amount, $meta = []) {
    ...
    cininvest_adjust_balance($user_id, +$amount);   // никакой реальной оплаты
    return $op;
}
```

**Проблема.** `CININVEST_Mock_Gateway` — единственная реализация, включена по умолчанию
(`cininvest_gateway()` → `default: return new CININVEST_Mock_Gateway()`). Хендлер
проверяет nonce и логин, но **не проверяет роль и не ограничен окружением** (нет
`if (!WP_DEBUG) wp_die()`, нет capability-гейта). Любой авторизованный пользователь
POST-запросом `wallet_action=deposit&amount=100000000` начисляет себе баланс.

**Импакт.**
- Сейчас деньги «ненастоящие», но: далее этим балансом «покупаются доли» (счётчики проекта,
  записи операций типа `buy`/`privileges`) и вызывается `withdraw` (запись операции `withdraw`,
  уменьшение `cininvest_balance`).
- Если перед запуском гейтвей не заменят на настоящий — прямой минтинг средств.
- Даже с mock: таблица `wp_cininvest_operations` наполняется фиктивными `deposit`/`withdraw`,
  которые будущая сверка с банком/бухгалтерский отчёт/`do_action('cininvest_payment_confirmed')`
  могут принять за реальные.
- В `withdraw`/`charge` есть проверка достаточности средств, но она бессмысленна при
  бесплатном `deposit`.

**Рекомендация.**
- Обернуть mock-`deposit` в гейт: доступен только при `defined('CININVEST_ALLOW_FAKE_DEPOSIT')`
  или `current_user_can('manage_options')`, иначе `wp_die()`.
- Логировать `deposit` как `pending` до подтверждения провайдером (как задумано для реального банка).
- Не отгружать mock-gateway на прод (фича-флаг/сборка).

---

## MEDIUM

### 5. Stored XSS через `the_sub_field()` в данных проекта

**Файлы:**
- `single-project.php:100–101` — `the_sub_field('name')`, `the_sub_field('role')`, `the_sub_field('text')` (отзывы);
- `single-project.php:115` — `the_sub_field('about')` (команда);
- `single-project.php:130,146` — `the_sub_field('title')` (материалы/документы);
- `template-parts/home/reviews.php:14,17` — те же поля отзывов на главной.

**Проблема.** `the_sub_field()` выводит значение ACF **без `esc_html`**. Для полей типа
`textarea` ACF применяет только `nl2br`/`wpautop`, но **не `wp_kses`**. Значение
`<script>…</script>` / `<img src=x onerror=…>` в поле отзыва или «Обо мне» исполнится
у каждого посетителя страницы проекта.

**Смягчающий фактор.** Поля живут на CPT `project`, редактирование которого требует
`edit_posts`. Регистрация даёт пользователям роль `subscriber` (не может править посты),
проекты сейчас создаются админом/сидером. Админы и так имеют `unfiltered_html`.
→ **Прямой эксплуатации сейчас нет.** Риск реализуется, если появятся редакторы/авторы
проектов с ролью ниже администратора (например, кинематографисты получат право
редактировать свой проект).

**Рекомендация.** Заменить `the_sub_field('x')` на
`echo esc_html(get_sub_field('x'))` для всех коротких текстов; для полей, где нужен
markup — `wp_kses_post()`. (Этот же пункт был отмечен как «низкий» в `AUDIT.md` раздел 2 —
здесь оценка чуть выше из-за отсутствия экранирования на нескольких страницах.)

---

### 6. Самоверификация без проверок; статус верификации управляется клиентом

**Файлы:** `page-verification-done.php:2`, `inc/forms-verification.php`

```php
// page-verification-done.php
if (is_user_logged_in()) cininvest_complete_verification(get_current_user_id());
// → update_user_meta(..., 'cininvest_verify_status', 'completed'); update_user_meta(..., 'cininvest_verified', 1);
```

**Проблема.** Экран `/verification-done/` при **простом открытии** помечает любого
залогиненного пользователя как `cininvest_verified = 1`. Сам флоу верификации
(`inc/forms-verification.php`) — тоже без реальной проверки: сохраняет присланные поля,
принимает файлы и сразу ставит `cininvest_verify_status = completed`, редиректит на
`/verification-done/`. Весь «мультистеп» — клиентский JS (`data-vstep`), сервер не
контролирует последовательность.

**Импакт сейчас.** `grep` показывает, что `cininvest_verified` / `cininvest_verify_status`
**нигде не читаются для допуска** к действиям (покупка долей и т.д. их не проверяют).
→ Прямого повышения привилегий нет. **Но** любой будущий гейт вида
`if (get_user_meta($uid,'cininvest_verified',true)) { … }` окажется заранее обойдён.

**Рекомендация.**
- Не менять статус на `completed` на самой странице результата — только по факту
  подтверждения от провайдера проверки (вебхук/админ).
- Промежуточный статус `checking`/`pending`; `verified` выставляет только доверенная сторона.
- Валидировать на сервере, что обязательные документы/поля реально присланы, прежде чем
  двигать статус.

---

### 7. buy-shares: отсутствуют бизнес-проверки

**Файл:** `inc/ajax-shares.php:42–94` (частично пересекается с #2)

- Нет проверки `get_post_type()/get_post_status()` проекта (см. #2).
- Нет верхней границы `$shares` и проверки `<= shares_available`
  (`shares_left = max(0, available - shares)` лишь клампит остаток к нулю, но списание
  идёт на полную сумму).
- Нет проверки, что раунд/сбор не завершён (`project_status`).
- `cininvest_handle_buy_perks`: если не выбрано ни одно предустановленное вознаграждение,
  сумма берётся из клиентского `$_POST['total']` (стр. 112). Для блока «Поддержать на
  любую сумму» это by-design, но стоит валидировать разумный диапазон и что запрос
  действительно из этого блока.

**Рекомендация.** Добавить проверки существования/статуса проекта и лимитов долей;
для «любой суммы» — `min`/`max` границы.

---

## LOW / INFORMATIONAL

### 8. `the_title()` без экранирования
`single-project.php:35,72`, `template-parts/home/projects-preview.php:18`,
`archive-project.php:32`, `template-parts/project/{buy-shares,sponsor}-modal.php`,
`page.php`, `index.php`.
Штатный для тем WordPress паттерн: заголовки постов проходят `wp_kses` при сохранении
для пользователей без `unfiltered_html`. Риск минимальный; при желании — `echo esc_html(get_the_title())`.

### 9. `location.hash` → `querySelector` (DOM, не XSS)
`assets/js/main.js`, блок универсальных табов:
```js
const hashLink = tabs.querySelector(`[data-panel="${location.hash.replace('#', '')}"]`);
```
Значение хеша подставляется в CSS-селектор без экранирования. `#a"]` → невалидный
селектор → `querySelector` бросает `SyntaxError`. **Вставки HTML нет** — это не XSS,
а необработанное исключение, которое прерывает инициализацию табов на этой итерации.
Рекомендация: `CSS.escape()` или whitelist известных `data-panel`.

### 10. Слабые требования к паролю / нет rate-limit
`inc/forms-auth.php:28` — `strlen($pass) < 6`. Нет ограничения частоты попыток входа/
регистрации (в ядре WP их тоже нет). Рекомендация: минимум 8–10 символов, интеграция
с политикой ядра / плагином защиты от брутфорса.

### 11. Глобальный ключ transient ошибки входа
`inc/forms-auth.php:79` — `set_transient('cininvest_login_error', …)` без суффикса
пользователя/сессии. При одновременных неудачных входах разных гостей возможно показать
чужую (одинаковую) ошибку; не эксплуатируется, но лучше привязать к сессии/referer-хэшу.

### 12. Раскрытие текста ошибки `wp_insert_user`
`inc/forms-auth.php:45–49` — `$user_id->get_error_message()` кладётся в transient и
показывается пользователю. Может раскрыть внутреннюю деталь (например, конфликт логина).
Рекомендация: логировать полный текст, пользователю — обобщённое сообщение.

### 13. Хранение сырого `$_POST` в transient (заявка)
`inc/forms-application.php:22` — `set_transient('cininvest_app_data_' . $user_id, $_POST, 60)`.
Значение позже рендерится, но **через `esc_attr`/`esc_html`/`selected`/`checked`** —
XSS нет. Хранится с добавленными WP слэшами (косметика). Крупные `$_POST`/массивы полей
попадают в опции transient — незначительный оверхед. Можно фильтровать по whitelist ключей.

### 14. Инлайновые `style="color:…"` в сообщениях
`single-project.php` (вкладка «Вознаграждения»), ранее — в auth/verify (уже вынесено в
классы `.auth__error`/`.app-form__error` в ходе п.7). Не уязвимость; мешает строгому CSP.

---

## Что проверено и признано безопасным

| Класс | Вывод |
|---|---|
| **SQL-инъекции** | Все запросы к своей таблице (`wp_cininvest_operations`) — через `$wpdb->prepare()` с `%d`/`%s` (`inc/db-operations.php`, `inc/payment-gateway.php:70`, `inc/rest-webhook.php:33`, `tools/seed-demo*.php`). Имя таблицы — из `$wpdb->prefix`. Конкатенации пользовательского ввода в SQL нет. **Уязвимостей нет.** |
| **CSRF** | Каждый мутирующий `admin_post_*` хендлер проверяет `wp_verify_nonce` (register/login/application/verification/buy_shares/buy_perks/wallet). REST-вебхук — постоянный `hash_equals()` со `X-Cininvest-Secret`; при незаданном секрете эндпоинт закрыт. AJAX-калькулятор — `check_ajax_referer('cininvest_shares')` и при этом read-only. **Уязвимостей нет.** |
| **Open redirect** | Все редиректы — `wp_safe_redirect()` (ограничение по хосту). `add_query_arg(..., wp_get_referer())` → тоже в `wp_safe_redirect`. **Нет.** |
| **Reflected XSS** | `add_query_arg`/`remove_query_arg` в `archive-project.php:20–21` обёрнуты в `esc_url()`. `$_GET['status']` — строгое сравнение с `'completed'`, не выводится. `$_GET['type']` — строгое сравнение с `'ur'`. `$_GET['reg_err']` — `preg_match('/^[A-Za-z0-9]{20}$/')`, наружу не эхо. `$_SERVER` напрямую не используется. **Нет.** |
| **RCE через загрузку файлов** | Двойная фильтрация: allowlist расширений в теме (`pdf,jpg,jpeg,png,tiff[,xls,xlsx,doc,docx]`) + `media_handle_upload()` (проверка MIME/расширения ядром). `.php/.phtml/.php5/.svg/.html` не в allowlist. Двойное расширение (`x.php.jpg`) резолвится в безопасное `jpg`. Путь исполнения не найден. Проблема с файлами — их **экспозиция** (#3), а не выполнение. |
| **Опасные функции** | `grep` по `eval / create_function / assert / system / exec / passthru / popen / proc_open / unserialize / preg_replace(/e) / динамический include` — совпадений в коде темы нет. |
| **CLI-скрипты** (`tools/seed-demo*.php`) | В начале — `if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { … exit(1); }` → прямой HTTP-запрос к файлу завершается без выполнения. SQL — `$wpdb->prepare` + `(int)`. **Безопасно.** |
| **IDOR по user-meta** | `forms-verification` / `forms-auth` / кошелёк пишут строго в meta `get_current_user_id()` — чужие данные не трогаются. |
| **Доступ к кабинетам** | `cininvest_require_account_role()` — логин обязателен, роль сверяется, при несовпадении — редирект в свой кабинет; админам всё можно. Корректно. |
| **Nonce на загрузке файлов** | Формы заявки и верификации содержат `wp_nonce_field` и проверяются. |

---

## Приоритет исправления

1. **#1, #2** — добавить проверки владения/типа объекта для `app_id` и `project_id`
   (broken access control, эксплуатируется любым залогиненным пользователем, урон —
   порча/угон контента и накрутка счётчиков).
2. **#3** — вынести KYC-документы из публичного `uploads/` и закрыть REST-медиа
   (утечка ПДн; обязательно до реальных пользователей).
3. **#4** — гейт на mock-`deposit` (фича-флаг/capability), не отгружать mock на прод.
4. **#6** — статус верификации выставляет только доверенная сторона.
5. **#5** — экранировать `the_sub_field()` (страховка на случай появления не-админ редакторов).
6. **#7–#14** — по мере доводки.
