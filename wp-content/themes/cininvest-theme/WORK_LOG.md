# WORK_LOG.md — журнал Figma-синхронизации темы CININVEST

Сессия: 2026-09-09 → 2026-09-10. Задача — привести все страницы темы в точное соответствие
Figma-макету (десктоп ≥1024px + мобильная <1024px), используя Figma MCP как источник значений.

Figma: https://www.figma.com/design/AYfMRCtWCfNlb2oOv2uLLR/CININVEST--Copy-?node-id=1627-13262
`style.css`: v1.0.34 → **v1.0.41**. Навигация — `PROJECT_MAP.md`. Детали правок — `AUDIT.md` (разделы 1–8).

Проверка вёрстки шла через статические preview-страницы в `tools/preview/*.html`
(подключают реальный `style.css`) + локальный `python -m http.server` + браузер на 1440px.
**Полноценного WP-рендера не было** — везде помечено «осталась ручная проверка на 390/768/1440».

---

## Что сделано по страницам

### 1. Главная — `front-page.php`, `template-parts/home/{hero,directions,projects-preview,reviews}.php`
Figma-нода `928:4021`. Статус: ✅ приведено к макету.
- Включена секция «Отзывы» (была закомментирована).
- «Смотреть все» в projects-preview: текст-ссылка → красная кнопка `.btn--primary` внизу справа.
- `.stats__num` разбит на крупную/мелкую части (`<small>` для «от»/«₽»/«проектов»); «проектов» капсом.
- `:root` `--c-muted` `#8A8A8A` → `#808080` (Figma-токен «Серый текст»).
- `.stats__item` — `clip-path` скошенной карточки-графики.
- `.stats__num` — `font-style:italic`, убраны сломанные `font-family:'Palui SP Demo'` (нет глифов цифр) и `'High Speed'` (шрифт не подключён) → Montserrat 800 italic.
- directions / showcase-card / reviews — веса/размеры/line-height/цвета по таблице аудита; `.review__ava` 44→56, `.review__quote` без серой подложки.
- desktop: `.section-title` 34→32; `.reviews__scroll` wrap→scroll-ряд; `.hero__inner`/`.stats` max-width 1430→**1180** (убирает горизонт. скролл на 1440); `.projects-preview` боковой паддинг масштабирован под 1180.

### 2. Лендинги — `page-for-investors.php`, `page-for-cinematographers.php` → `template-parts/home/{lp-hero,path,coauthor}.php`
Figma-ноды `944:5171` (кинематографисты), `957:4184` (инвесторы). Статус: ✅.
- **Хиро инвесторов = только фото** (заголовок и статистика в макете `visible:false`) — убраны заголовок «Инвестируйте в перспективные кинопроекты» и CTA.
- Хиро кинематографистов — фото + белый заголовок Palui + 3 стеклянные карточки статистики (`backdrop-filter:blur(40px)`).
- `coauthor.php` — заголовок в 2 строки; «?» в `<span class="num">`; тексты карточек ролей выверены по макету.
- `path.php` — тексты шагов дополнены по макету (многострочные через `nl2br`).
- `style.css`: `.step` фон `#faf5f5`→`#f6f6f6`, radius 10, без тени, `padding:30px 30px 30px 0`; `.step__num` — плоская пилюля → красный градиентный бейдж у левого края (`radius:0 8px 8px 0`, 23px desktop / 19 mobile); `.step__text` `#808080` 16/500 lh21, отступ слева 30/20; `.path__cta` `align-self:flex-end` desktop / full-width mobile.
- `.role-card` — тень `0 0 30px #D4D4D4`, `__label` 13/600→**19/700**, `__list` 13px `#555` + буллеты → **16px `#333` без буллетов**.
- `.lp-hero__photo` desktop `min-height:620px` (инвестор 640); `.lp-hero__title` 52→48px; убраны мёртвые `.lp-hero__cta` / `.lp-hero--investor .lp-hero__stats{display:none}`.

### 3. Страница проекта — `single-project.php`, `template-parts/project/{buy-shares-modal,sponsor-modal}.php`
Figma-нода `1170:11304`, карточка `1170:11351`. Статус: ✅.
- **Функциональные пункты — уже были исправлены в коде**, проверено:
  - вкладка «Вознаграждения» — рабочая (реальные `<form>` + nonce + обработчик `admin_post_cininvest_buy_perks`);
  - кнопка «Пополнить» в модалке покупки — ведёт в кабинет (`cininvest_account_url_for_role()`), не мёртвый `data-open`;
  - текст кнопки «Купить» — сумма в отдельный `<span data-out="total">`, слово не затирается;
  - блокировка вкладок для гостей — убрана (`locked.php` нет, `preventDefault` в `main.js`); удалены мёртвые CSS `.locked*`.
- hero переструктурирован: заголовок + описание **внутрь** `.project-hero__media` как белый оверлей; убран отдельный чёрный `.project-hero__info` + glow.
- `.project-hero__back` — голая SVG → **белый круг 44/50px**.
- `.round-tab` — стекло → **белый bookmark** `radius:0 10px 10px 0`, 16/23px, `margin-left:-16px`.
- `.fund-card` desktop — фон `rgba(229,229,229,.7)`→`.4`, radius 10, тень убрана, ширина 400→420, перекрытие hero −160→−200; `.fund-card__progress` белая под-карточка; `.fund-card__goal` рамка → залитый `#ECE4E4`; `.fund-card__action` белые под-карточки; `.fund-card__meta` gap 32→40.
- `.member` фон `#faf5f5`→`#fff` + тень `0 0 30px #D4D4D4`; `.video-placeholder` `height:200px` → `aspect-ratio:541/303`.
- `.project-tabs` — по центру → **слева**, gap 40→24, скроллбар скрыт.

### 4. Профили — `page-profile-{investor,sponsor,cinematographer}.php`, `template-parts/{profile-hero,wallet}.php`, `template-parts/project/sell-shares-modal.php`
Figma-ноды `1033:4988` / `1033:4987` / `1054:5590` / `1033:6623`. Статус: ✅.
- **profile-hero.php переписан**: имя + роль **одной строкой** (Montserrat Bold 35 desktop / 24 mobile, НЕ Palui, НЕ uppercase); аргументы `text` → `lead` + `note` (обновлены 3 вызова); фон плоский `#F6F6F6`.
- Баг мобильных табов кинематографиста / `.application__status--*` / `preventDefault` для `.operation__more` — **уже были исправлены** в коде, проверено.
- Блоки «Счета» и «Договоры и отчёты» у инвестора — **добавлены** (3 группы `.docs-block`, строка договора `.docs__row--file` с мета «PDF, 408 КБ, дата»).
- Прогресс-бар в «Мои инвестиции» — **добавлен**: `.project-card` перестроена в горизонт. строку (постер 73×49 · название `(раунд N)` + орг + иконка копирования · розовый блок `Запрашиваемая сумма` · «Собрано N%» + бар с маркером + «Осталось N дней» · `N долей`/сумма · «Продать доли проекта»).
- Модалка «Продажа долей» — текст/кнопка по макету, URL Telegram-чата вынесен в константу `CININVEST_MODERATOR_TG` / фильтр `cininvest_moderator_telegram` (был `href="#"`).
- `.num` — обёрнуты `(раунд N)`, «N долей», «Собрано N%», «Осталось N дней», «Долей: N».
- wallet — карточка radius 10 + тень `0 0 30px`; метки/значения 16px Medium; на desktop всё в один ряд + кнопки вторым рядом.
- tabs — по центру (было flex-start), активный = красный текст + подчёркивание `::after` + тень `0 0 30px #D4D4D4`; убран сплошной `border-bottom`.
- `.operation__more` вынесен из `.operation__side`; добавлен `.operation__shares` («Долей: N»).

### 5. Подача заявки — `page-application.php`, `template-parts/application/section-*.php`, `inc/forms-application.php`
Figma-нода `1141:9697`. Статус: ✅.
- `.app-form` `gap:32px` → **60px**; `.app-section` gap 14→**20**; `.app-section__title` 17→**19px uppercase bold**.
- Поле телефона физлица — уже было; лейбл → «Контактные данные\*», плейсхолдер → «+7 (800) 555-35-55» (то же в applicant).
- Выпадающие списки — расширены: Жанр (17), Тип (6), Вид (5), Образование (5); каждый `<option>` с `selected()` → **восстанавливается после ошибки**.
- Логика ошибок — показ + восстановление ввода уже работали; **доработано**: `<select>` тоже восстанавливаются; после ошибки форма открывается **того же типа** (`applicant_type` из сохранённого `$_POST`); ошибка — в плашке `.app-form__error` (`#FBEAEA` + рамка).
- Мёртвая секция `data-appstep="done"` — **уже удалена** (успех → редирект на `/application-sent/`).
- `section-requisites.php` — в физ-список добавлено «КПП банка\*» (есть в макете); класс `app-section--accent` уже стоял.
- `.file-attach` — `padding:10/12` → `18/20`, `min-height:76px`, radius 10.
- Подсказка «до 3 документов» → «до 5 документов и(или) файлов».

### 6. Верификация — `page-verification.php`, `assets/js/main.js`, `inc/forms-verification.php`, `page-verification-done.php`
Figma-ноды `1103:8264` (физлицо), `1103:8395` (ИП), `1107:10087` (юрлицо). Статус: ✅.
- **Порядок шагов исправлен**: добавлен шаг `data-vstep="checking"` («Проверка данных») **внутрь формы** между сбором данных и «Почти готово». Было: «Проверка данных» — отдельная страница ПОСЛЕ отправки (т.е. «почти готово» шло раньше проверки).
  - физлицо: `type → passport → pdl → checking → almost`;
  - ИП/юрлицо: `type → passport → legal → account → checking → almost`.
- **Шаг ПДЛ — только у физлица**. Было: ИП/юрлицо тоже шли через `pdl` (кнопка `account` → `pdl`). `main.js` проставляет `passport`-vnext и `checking`-vback по типу.
- Ярлык ЕГРИП/ЕГРЮЛ — уже переключался в `main.js` (`[data-egrip]`, `[data-ogrn-label]`), проверено.
- После отправки: `inc/forms-verification.php` редирект `/verification-checking/` → **`/verification-done/`** («Верификация завершена!»); статус пользователя → `completed`.
- `.file-upload__check` серый квадрат 36px → **кружок 22px с рамкой** (радиокружок); `.check-list__timer` («Осталось 20 ч» справа).
- `page-verification-checking.php` — теперь вне потока (контент продублирован как шаг), файл оставлен.

### 7. Витрина / FAQ / Документы / Вход / Регистрация / fallback / подвал
Figma-ноды: Витрина `972:5115`, FAQ `972:6163`, Документы `972:6947`, Вход `1073:8700`, Регистрация `1073:10877`. Статус: ✅.
- **Витрина** (`archive-project.php`): фильтры уже рабочие (`<a href>` + `WP_Query` по `project_status`); добавлена **плашка «20 000 инвесторов на платформе»** (`.page-head__badge` — белая пилюля с тенью).
- **FAQ** (`page-faq.php`): `.faq__item` рамка → тень `0 0 20px #D4D4D4`, radius 10; `.faq` grid gap 12/14; вес вопроса 600→700; иконка вниз-шеврон → **диагональная стрелка ↘**; `.faq-cta` padding 32→40; «Остались вопросы**?**» — `?` в `<span class="num">`.
- **Документы** (`page-docs.php`): контент уже по макету; реальных PDF нет → ссылки `href="#"` (см. открытые вопросы).
- **Вход / Регистрация**: созданы **`header-auth.php`** (только логотип) и **`footer-auth.php`** (без подвала); `page-login.php` + `page-register.php` → `get_header('auth')`/`get_footer('auth')`. Оформление ошибок унифицировано — общий класс **`.auth__error`** (плашка `#FBEAEA` + рамка). Ошибки регистрации уже показывались (токен `?reg_err=` + транзиент).
- **`page.php`** (fallback) — уже существует, правок не требовалось.
- **Подвал** (`footer.php`): пункты уже `<li><a href>`; «Наша платформа» из `<p>` обёрнута в `<a href="/">`.

---

## Новые / изменённые файлы

**Новые:**
- `PROJECT_MAP.md` — карта «страница ⇄ файлы ⇄ Figma-нода».
- `header-auth.php`, `footer-auth.php` — минимальная обвязка для входа/регистрации.
- `tools/preview/*.html` — статические preview для визуальной сверки (home-check, lp-check, proj-check, prof-check, app-check, verify-check, p7-check).

**Изменённые (кратко):** `style.css` (v1.0.34→1.0.41), `assets/js/main.js` (шаги верификации),
`front-page.php`, `archive-project.php`, `footer.php`, `page-faq.php`, `page-login.php`, `page-register.php`,
`page-application.php`, `page-verification.php`, `page-profile-*.php`, `single-project.php`,
`inc/forms-verification.php`, все `template-parts/{home,project,application}/*`, `template-parts/{profile-hero,wallet}.php`,
`AUDIT.md` (разделы 1–8 → статусы ✅).

**НЕ трогались:** `CLAUDE.md`, `COMPARE.md`, `REFERENCE` (по правилам задачи).

---

## Открытые вопросы / что осталось

1. **Ручная проверка рендера** — все правки сверялись через статические preview на 1440px.
   Нужен прогон в реальном wp-admin на **390 / 768 / 1440** для всех страниц (отсутствие горизонт. скролла,
   мобильные раскладки, работа степперов/табов/модалок).
2. **Аудит безопасности** — заявлен пользователем как следующий шаг после п.7.
3. **Документы** (`page-docs.php`): `.doc-card__link` = `href="#"` — в теме нет PDF-файлов.
   Решение: загрузить в WP-медиатеку и подставить URL, либо оставить как контент-TODO.
4. **FAQ-ответы** — все «Ответ на вопрос.» (заглушка). Контент-TODO.
5. **Плейсхолдер-ссылки `href="#"`** ещё в: соц-иконки подвала, ссылки согласий на шаге паспорта
   верификации, документы/«Продать доли» в профиле инвестора — реальных маршрутов пока нет.
6. **Шрифт «High Speed»** (hero-цифры на главной/лендингах) — недоступен в теме, заменён Montserrat 800 italic.
   Если нужен точный вид — добавить `@font-face` в `assets/fonts/`.
7. **Мёртвые файлы** — кандидаты на удаление: `for-investors.php`, `for-cinematographers.php`
   (без префикса `page-`, ссылаются на несуществующие `template-parts/lp/*`), `singleproject.php`
   (дубль `single-project.php`), `assets/img/project-hero.jpg` (нигде не используется).
8. **`page-verification-checking.php`** — после п.6 вне пользовательского потока; оставлена как
   отдельный `Template Name`. Решить: удалить или оставить.
9. **Контейнер проекта** — унифицирован в 1180px; макетные 1430 (880 контент + 500 карточка)
   масштабированы (контент ~730, карточка 420, gap 32). Компромисс по правилу CLAUDE.md.
10. **`backdrop-filter`** стеклянных карточек — 40px вместо макетных `BACKGROUND_BLUR 200` (200 непрактично).
11. **Хиро инвесторов** (лендинг) по макету = только фото ~640px. Сейчас `hero-investor.jpg` почти чёрный →
    выглядит пусто; станет ок после подмены реальным кадром.
12. **Декор-эллипсы** (`LAYER_BLUR 400` в макете) везде воспроизведены как `blur(200px)` или опущены,
    где эффект пренебрежимо мал.
