# BUTTONS_CHECK.md — аудит кнопок, ссылок, форм и `data-*` по всей теме

Дата: 2026-09-10. Сессия: разбор конфликта FINAL_CHECK + сплит style.css.
Метод: обход всех `*.php` (корень + `template-parts/**`), поиск `<a>`, `<button>`, `<form>`,
`data-open`, `data-tabs`, `data-clone`, `data-next/back`, `data-appnext/appback`,
`data-vnext/vback` и сверка каждого с обработчиком в `assets/js/main.js` либо с существующим
маршрутом WordPress / зарегистрированным `admin_post_*` экшеном.

Обработчики `main.js` (эталон): `.burger`+`#main-nav`; `[data-tabs]`→`.tabs__link`/`.project-tabs__link`
(`data-tab`→`[data-panel]`); `[data-clone]`; `.field__eye`; `.copy-btn[data-copy]`;
`[data-register]`→`[data-next]`/`[data-back]`; `[data-appflow]`→`[data-type]`/`[data-appnext]`/`[data-appback]`;
`[data-verify]`→`[data-vt]`/`[data-vnext]`/`[data-vback]`/`[data-vlater]`/`[data-pdl-answer]`;
`[data-open]`/`[data-close]`+Esc; `.project-hero__rounds .round-tab`; `.reward-block` (`.preset`,
`.reward-option input`, `[data-rewards-total]`, `[data-perks-total-input]`); `[data-buy-form]`
(`[data-calc-amount]`/`[data-calc-shares]`/`[data-out]`).

Зарегистрированные экшены: `admin_post(_nopriv)_cininvest_login`, `…_register`,
`admin_post_cininvest_application`, `…_verification`, `…_wallet`, `…_buy_shares`, `…_buy_perks`,
`wp_ajax(_nopriv)_cininvest_calc_shares`. (`buy_shares`, `buy_perks`, `application`,
`verification`, `wallet` — **только для авторизованных**, без `nopriv`.)

Критичность: **высокая** — контрол виден и заявлен, но не работает вовсе; **средняя** — ломается в
части сценариев (гость / нет данных) либо тихо не срабатывает; **низкая** — плейсхолдер-ссылка,
косметика, ограниченная функциональность «по задумке».

---

## Статус исправлений — сессия 2 (2026-09-10)

| # | Было | Исправлено |
|---|---|---|
| A1 | `.icon-edit` без обработчика | Карандаш → `[data-profile-edit]`; JS снимает `readonly`/`disabled` с полей, иконка меняется на галочку (`.icon-edit.is-editing`), повторный клик — `form.submit()`. Форма → `admin_post_cininvest_profile_save` (nonce, `inc/forms-auth.php`). |
| A2 | `.project-card__copy[data-copy]` не слушался | `main.js`: селектор `.copy-btn[data-copy]` → `[data-copy]` (любой элемент). |
| A3 | `.photo-picker` — `<div>` без input | Внутрь добавлен `<input type="file" name="avatar" hidden>` + `data-photo-picker`; JS открывает диалог по клику и рисует превью; аватар сохраняется в `cininvest_avatar_id`. |
| A4 | `<button data-appback>` no-op | Заменён на `<a href="/profile-cinematographer/">` (шаг «type» первый — предыдущего нет, «Назад» выходит в ЛК). |
| A5 | `.profile-form` без action/nonce/submit | `<form method=post action=admin-post.php enctype=multipart/form-data data-profile-form>` + `wp_nonce_field` + `name`-атрибуты на всех полях; обработчик `cininvest_handle_profile_save` (ФИО/телефон/email/пароль/аватар + расширенные поля кинематографиста, транзиент-сообщение `.profile-form__msg`). |
| B1 | Гость: `data-open="buy-shares"/"sponsor-modal"` без модалок | Для гостя кнопки → `<a href="/login/?redirect_to=…">`. |
| B2 | Гость: форма «Вознаграждения» → белый экран | Добавлен `admin_post_nopriv_cininvest_buy_perks` → редирект на `/register/`; `cininvest_handle_buy_perks` тоже редиректит гостя на регистрацию. |
| D1 | `/for-cinematographers/` без шаблона | Создан `page-for-cinematographers.php` (Template Name, `lp-hero type=cinema` + `path type=project`). |
| E1–E3 | Мёртвые файлы | Удалены `singleproject.php`, `for-investors.php`, `for-cinematographers.php`, `page-verification-checking.php`; из `theme-setup.php` убран slug `verification-checking`, `CININVEST_SETUP_VERSION` → `1.3.0`. |
| F3/F4 | Нет превью выбранного файла | `.photo-picker` теперь показывает превью (A3). `.file-upload__check` / `.dropzone` / `.file-attach` — **не трогалось** (вне блока задач сессии 2). |

Дополнительно (не из этого файла, по заданию сессии 2):
- **Уведомления**: колокольчик + счётчик в шапке для авторизованных (`header.php`), хранилище
  `user_meta cininvest_notifications`, хелперы в `inc/helpers.php`, события в `inc/notifications.php`
  (одобрение/доработка заявки, публикация проекта, покупка долей, пополнение), AJAX-отметка
  прочитанным `wp_ajax_cininvest_notif_read`.
- **Отзывы отключены** на главной (`front-page.php`) и на странице проекта (`single-project.php`) —
  до появления формы добавления и модерации; партиалы `template-parts/home/reviews.php` оставлены.
- **`.stats__item`** (блок «2%…») — desktop `clip-path` приведён к Figma-вектору
  (`polygon(0 18%,100% 0,100% 100%,0 100%)`), `gap:0`, `line-height` подписи 21px.
- На мобильном у авторизованного ролевая CTA-кнопка шапки скрыта (`body.logged-in .site-header__cta`),
  чтобы колокольчик не переполнял ряд; на десктопе кнопка на месте.

**Остаётся открытым:** F1 (`.round-tab` косметика), F2 (`javascript:history.back()`),
C1–C6 (ссылки-плейсхолдеры `href="#"` — контент/маршруты), доки в профиле инвестора.

---

## A. Мёртвые контролы — обработчика нет / no-op

| # | Файл : строка | Элемент | Проблема | Что делать | Крит. |
|---|---|---|---|---|---|
| A1 | `page-profile-investor.php:34`, `page-profile-cinematographer.php:20`, `page-profile-sponsor.php:30` | `<button class="icon-edit">` («Редактировать») | В `main.js` нет ни одного слушателя на `.icon-edit`. Клик ничего не делает. | Либо реализовать редактирование профиля (submit-флоу `admin-post` + nonce), либо убрать кнопку / пометить `disabled` с тултипом «скоро». | высокая |
| A2 | `page-profile-investor.php:83` | `<button class="project-card__copy" data-copy="…">` (копировать ссылку на проект) | `main.js` слушает только `.copy-btn[data-copy]`. Класс здесь другой → копирование не работает. | Добавить класс `copy-btn` к кнопке **или** расширить селектор в `main.js` до `[data-copy]`. | средняя |
| A3 | `page-profile-investor.php:35`, `page-profile-cinematographer.php:22`, `page-profile-sponsor.php:32` | `<div class="photo-picker"><span>Выбрать фото</span></div>` | Выглядит как контрол загрузки фото, но это `<div>` без `<input type=file>` и без JS. | Обернуть в `<label>` с `<input type="file" hidden>` (+ вывод превью), либо снять «кликабельный» вид. | средняя |
| A4 | `page-application.php:28` | `<button type="button" data-appback>` («Назад» на шаге выбора типа) | Атрибут `data-appback` без значения; обработчик `if (b.dataset.appback) show(...)` → пусто → no-op. Это первый шаг, назад некуда. | Сделать ссылкой на `/profile-cinematographer/` (или `history.back()`), либо убрать кнопку на первом шаге. | низкая |
| A5 | `page-profile-cinematographer.php:21`, `page-profile-investor.php:36`, `page-profile-sponsor.php:31` | `<form class="profile-form">` | Нет `method`/`action`/`wp_nonce_field` и нет submit-кнопки — введённые данные сохранить нельзя (чистый плейсхолдер). | Реализовать сохранение через `admin_post_cininvest_profile` + nonce + транзиент-ошибки, либо явно пометить блок «редактирование скоро». | средняя |

## B. Ломается для гостя (незалогиненного)

| # | Файл : строка | Элемент | Проблема | Что делать | Крит. |
|---|---|---|---|---|---|
| B1 | `single-project.php:58,59` | `<button data-open="buy-shares">` / `data-open="sponsor-modal">` | Кнопки рендерятся всем (для гостя даже как primary), но сами модалки в конце файла выводятся `if (!$is_guest)`. `main.js`: `getElementById(...)` → `null` → клик гостя = тишина. | Для гостя вести на `/login/` (ссылкой) или рендерить модалку-заглушку «Войдите, чтобы инвестировать». | средняя |
| B2 | `single-project.php:159,168` | `<form … action="…cininvest_buy_perks">` (вкладка «Вознаграждения») | Формы видны гостю, но зарегистрирован только `admin_post_cininvest_buy_perks` (без `nopriv`). Сабмит гостя → пустой ответ `admin-post.php` / редирект в админку, не на логин. | Добавить `admin_post_nopriv_cininvest_buy_perks` с редиректом на `/login/`, либо скрывать обе формы `if ($is_guest)` и показывать CTA «Войти». | средняя |

## C. Ссылки-плейсхолдеры `href="#"` (нет реальной цели)

| # | Файл : строка | Элемент | Что делать | Крит. |
|---|---|---|---|---|
| C1 | `page-docs.php:40` | `.doc-card__link href="#"` — во всех карточках | Проставить URL реальных PDF (медиатека) либо убрать ссылочность до появления файлов. | средняя |
| C2 | `page-profile-investor.php:45,46,52,61` | `.docs__row a href="#"` («Посмотреть»/«Скачать») | Привязать к реальным документам пользователя; сейчас статические плейсхолдеры. | средняя |
| C3 | `page-profile-cinematographer.php:72` | `.application__action--red href="#"` («Узнать причину отказа →») | Нет маршрута и поля причины отказа. Вывести причину из meta заявки или вести на страницу заявки. | низкая |
| C4 | `footer.php:27,28` | Соц-иконки WhatsApp / Telegram `href="#"` | Проставить реальные ссылки соцсетей или скрыть до их появления. | низкая |
| C5 | `template-parts/project/buy-shares-modal.php:45,50,53–56` | «Подробнее» о комиссии + 4 ссылки на документы `href="#"` | Заменить на `/docs/` / конкретные PDF. | низкая |
| C6 | `page-verification.php:40,41` | `<a href="#">правилами платформы</a>` / `декларацией о рисках` | Вести на `/docs/` или конкретные документы. | низкая |

## D. Маршруты без шаблона

| # | Где ссылка | Маршрут | Проблема | Что делать | Крит. |
|---|---|---|---|---|---|
| D1 | `template-parts/home/directions.php:19`, `footer.php:12` | `/for-cinematographers/` | Нет `page-for-cinematographers.php` (у инвесторов аналог `page-for-investors.php` есть). Страница с таким слагом отрендерится generic-`page.php`, без лендинг-разметки. | Создать `page-for-cinematographers.php` (`Template Name`) по образцу investor (`lp-hero type=cinema` + `path type=project`), назначить странице. | средняя |

## E. Мёртвые / дублирующие файлы (не в иерархии шаблонов)

| # | Файл | Проблема | Что делать | Крит. |
|---|---|---|---|---|
| E1 | `singleproject.php` | Устаревшая копия; активен `single-project.php` (имя по иерархии CPT `project`). В иерархию не попадает. | Удалить. | низкая |
| E2 | `for-investors.php`, `for-cinematographers.php` | Сироты: `get_template_part('template-parts/lp/hero'/'path'/'coauthor')` — таких файлов нет (есть `template-parts/home/lp-hero.php` и т.д.). Нет `Template Name`, имя не матчит иерархию. | Удалить (реальные лендинги — `page-for-*.php` + `template-parts/home/*`). | низкая |
| E3 | `page-verification-checking.php` | `Template Name: Проверка данных`. После правки потока шаг проверки (`data-vstep="checking"`) живёт внутри формы `page-verification.php`; на этот шаблон ничего не редиректит. | Удалить шаблон (или осознанно оставить как отдельную страницу). | средняя |

## F. Функционально ограничено (не баг, зафиксировать)

| # | Файл : строка | Элемент | Замечание | Что делать | Крит. |
|---|---|---|---|---|---|
| F1 | `single-project.php:30` | `.round-tab` | Обработчик только переключает `.round-tab--active`; данные `.fund-card` (цель/собрано/срок) не меняются. | По Figma вкладки раундов должны подменять цифры раунда — добавить смену данных (data-атрибуты + JS или отдельные панели). | низкая |
| F2 | `single-project.php:25` | `.project-hero__back href="javascript:history.back()"` | При прямом заходе (пустая история) кнопка бесполезна; `javascript:`-схема ломается при строгой CSP. | Фолбэк-ссылка на архив `/projects/` либо `<button>` + обработчик с проверкой `document.referrer`. | низкая |
| F3 | `template-parts/application/section-data.php:32`, `section-financing.php:7`, `section-project.php:37`, `section-data.php` `.file-attach` | `.dropzone` / `.file-attach` | Клик по `<label>` открывает выбор файла (нативно, ОК), но имя выбранного файла нигде не отображается, drag-drop из текста не реализован. | Добавить JS: показывать имя файла в `.file-attach__name` / `.dropzone` после `change`. | низкая |
| F4 | `page-verification.php:37,38,53–55` | `.file-upload__check` (кружок-индикатор) | Не переходит в состояние «загружено» после выбора файла (нет JS). | Навесить обработчик `change` на скрытый `input[type=file]`, добавлять класс-галочку. | низкая |
| F5 | `template-parts/profile-hero.php:17` | `.profile-hero__cta` | `href` дефолтится в `'#'`, если `cta` передан без `cta_href`. Сейчас все вызовы передают `cta_href`, не проявляется. | Оставить как есть / добавить `if (empty($cta_href)) не рисовать кнопку`. | низкая |

---

## Что работает корректно (проверено, без замечаний)

- **Табы** (`[data-tabs]`): профили инвестора/спонсора/кинематографиста, `single-project.php` — все
  `.tabs__link`/`.project-tabs__link` имеют парный `[data-panel]`, обработчик на месте, `#rewards`
  из хеша открывает нужную вкладку.
- **Модалки** (`[data-open]`/`[data-close]`): `sell-shares`, `op-<id>` (профиль), `buy-shares`,
  `sponsor-modal`, `pdl-info` — id совпадают, оверлей и Esc закрывают.
- **Регистрация** (`[data-register]`): `data-next`/`data-back`/`data-role`/`data-role-field` — весь набор.
- **Подача заявки** (`[data-appflow]`): `data-type`/`data-appnext="form"` (реальный переход с `?type=`)/
  `data-type-label`/`data-appform-role`/`data-applicant-type-field`, `data-clone` (×3) — клон
  предыдущего `.field` работает.
- **Верификация** (`[data-verify]`): `data-vt`/`data-vnext`/`data-vback`/`data-vlater`/`data-pdl-answer`,
  переключение меток `data-ogrn-label`/`data-egrip`/`data-vstep-title`, скрытие `data-ur-only`/
  `data-not-fiz`, динамическая правка `data-vnext` шага «passport» под тип — всё отрабатывает.
- **Формы с мутациями** — у всех есть `wp_nonce_field` и `action` на зарегистрированный экшен:
  `page-login.php`, `page-register.php`, `page-application.php`, `page-verification.php`,
  `template-parts/wallet.php`, `template-parts/project/buy-shares-modal.php`,
  `template-parts/project/sponsor-modal.php`, `single-project.php` (rewards).
- **Калькулятор долей** (`[data-buy-form]`) — nonce из `wp_localize_script`, эндпоинт
  `cininvest_calc_shares` зарегистрирован (в т.ч. `nopriv`).
- **Фильтры витрины** (`archive-project.php`) — `<a>` с реальными `add_query_arg`/`remove_query_arg`,
  фильтрация `WP_Query` по `project_status`, пагинация `paginate_links`.
- **FAQ** (`page-faq.php`) — нативный `<details>/<summary>`, JS не нужен.
- **Бургер-меню**, **`.field__eye`** (показ пароля), **`.copy-btn[data-copy]`** в верификации — ОК.
