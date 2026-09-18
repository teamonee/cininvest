# Сравнение REFERENCE (`cininvest`) и OURS (`cininvest-theme`)

REFERENCE = `C:\Users\user\Desktop\cininvest` (ближе к оригинальному макету/Figma)
OURS = `C:\Users\user\Desktop\cininvest-theme` (наша тема)

Ниже — только факты с указанием `файл:строка` на обеих сторонах, без оценки сложности реализации.

---

## Итоги (самое важное, по убыванию влияния)

1. **Лендинги «Для инвесторов» / «Для кинематографистов» в OURS почти пустые.** `page-for-investors.php` и `page-for-cinematographers.php` (по 5-6 строк) рендерят **только** секцию «Путь» (`template-parts/home/path.php`). В REFERENCE это полноценные лендинги: `template-for-investors.php` (169 строк) = hero-фото с бликами/статистикой + блок «Кто может стать соавтором» (`cin-lp-roles`) + путь + CTA; `template-for-cinematographers.php` (123 строки) = hero-фото со статистикой + путь + CTA. У OURS полностью отсутствуют hero-секция с фото/статистикой и блок ролей на этих страницах.
2. **Блок «Кто может стать соавтором» перенесён не туда.** В REFERENCE этот блок (`cin-lp-roles`) существует только на `template-for-investors.php:46-104`. В OURS эквивалент (`template-parts/home/coauthor.php`) вызывается на **главной странице** (`front-page.php:12`), которой в REFERENCE такого блока не содержит вообще (`front-page.php` REFERENCE заканчивается секцией отзывов, `front-page.php:154`).
3. **Верификация: другой порядок шагов и пропущенный шаг.** OURS показывает шаг подтверждения распознанных данных ("почти готово") **до** сбора юр.данных/реквизитов и **до** проверки (`page-verification.php`, `main.js:118-119`); REFERENCE — наоборот, review идёт после проверки, предпоследним шагом (`template-parts/verification-flow.php:227-291`). У OURS полностью отсутствует шаг про «публичное должностное лицо» (ПДЛ) для физлиц, который есть в REFERENCE (`verification-flow.php:210-225`). Ярлык документа «Копия выписки из ЕГРИП» в OURS не меняется на «ЕГРЮЛ» для юрлиц (`page-verification.php:113` статичен), в REFERENCE подпись переключается (`verification-flow.php:104,119-122`).
4. **Подача заявки кинематографиста:** у физлиц в OURS нет поля телефона вообще (в REFERENCE `contact_phone` общий для обоих типов, `template-apply-project.php:87`); выпадающие списки Жанр/Тип/Вид/Образование в OURS — пустые плейсхолдеры без единого `<option>` (`section-project.php:5,7,8`, `section-data.php:9`), тогда как в REFERENCE у них реальные списки значений (`template-apply-project.php:93-104,67`); лишнее поле «КПП банка» требуется у физлиц в OURS (`section-requisites.php:4`), которого нет в REFERENCE для физлиц (`template-apply-project.php:199-207`). Переключатель Физлицо/Юрлицо в REFERENCE — мгновенный, на клиенте (`data-entity-only`, `template-apply-project.php:16,57,65,120,191,199`); в OURS требует перезагрузки страницы (`main.js:82`, комментарий "сервер решает... нужен реальный переход").
5. **Страница проекта:** вкладки «Творческие материалы» и «Документы» в OURS скрываются для гостей за `template-parts/project/locked.php` (`single-project.php:102,113`) — в REFERENCE такого ограничения нет вообще ни для одной вкладки (`single-cin_project.php:209-278`, всё видно без входа). Вкладка «Вознаграждения» в OURS не обёрнута в `<form>` — нет `action`/nonce (`single-project.php:123-142`), в REFERENCE это два реальных `<form>` с `admin-post.php` и nonce (`single-cin_project.php:291-296,318-322`). Отдельного экрана/формы «Спонсировать» в OURS не найдено (только кнопка-триггер, `single-project.php:35`) — в REFERENCE есть полноценный `data-project-view="sponsor"` экран (`single-cin_project.php:430-470`).
6. **Витрина проектов:** фильтры «В работе» / «Завершённые» в OURS — инертные кнопки без `href` и без реальной фильтрации по статусу (`archive-project.php:4-9`, обычный `have_posts()` без `WP_Query`/`meta_query`); счётчик «Завершённые» считает **все** опубликованные проекты (`wp_count_posts('project')->publish`, `archive-project.php:6`). В REFERENCE фильтры — рабочие ссылки с `add_query_arg('status', 'completed')`, запрос реально фильтруется по мета `project_status` (`archive-cin_project.php:13-38,49-55`). Также в OURS отсутствует плашка «20 000 инвесторов на платформе» в hero витрины, которая есть в REFERENCE (`archive-cin_project.php:43`).
7. **Подвал (footer) в OURS: пункты навигации — не ссылки.** `footer.php:12-17` (OURS) выводит «Для кинематографистов», «Для инвесторов», «Проекты», «FAQ», «Документация» как голый текст в `<li>` без `<a href>`. В REFERENCE это рабочие ссылки: `footer.php:19-24` (REFERENCE).
8. **Нет отдельного минимального auth-хедера/футера.** REFERENCE использует `header-auth.php`/`footer-auth.php` (только лого + крестик закрытия, без общей навигации) на экранах Вход/Регистрация (`template-login.php:17,62`). OURS переиспользует обычный `header.php`/`footer.php` с полным меню, burger-кнопкой и CTA (`page-login.php:1,18`, `page-register.php:9,54`).
9. **Нет `page.php`.** В REFERENCE `page.php` — специальный fallback-шаблон для любой WP-страницы без назначенного шаблона, с осмысленной вёрсткой и подсказкой редактору при пустом контенте (`page.php:1-39`). В OURS такого файла нет вообще — подобные страницы попадают в общий `index.php` (13 строк, минимальная безстилевая вёрстка, `index.php:1-12`).
10. **Ассеты:** у REFERENCE вообще нет файлов изображений — папки `assets/img` не существует; все «фото» — CSS-градиенты/inline SVG, а реальные фотографии проектов берутся из WP-медиатеки через `get_the_post_thumbnail_url()`. OURS хранит 4 JPG-заглушки (`hero-cinema.jpg`, `hero-investor.jpg`, `poster.jpg`, `project-hero.jpg`) и SVG-логотипы/иконки как файлы; один из них — `project-hero.jpg` (18 КБ, 1600×700) — нигде в коде не используется (проверено grep по всей теме).
11. **CSS-архитектура и класс-неймспейсы полностью разные.** REFERENCE: префикс `cin-`, мобильная база на токене `--container-w:430px`, десктоп добавлен позже отдельными `@media(min-width:1024px)` блоками с захардкоженными пиксельными max-width на каждую секцию (`assets/css/main.css:27`, `:954-1324`). OURS: классы без префикса, базовый токен `--container:1430px` (`style.css:23`), для главной переопределяется на `1180px` только в десктопе (`.home .container`, `style.css:582`). Из-за этого одинаковые компоненты (карточки, hero, табы) в двух темах не делят ни одного общего CSS-класса.
12. **Шрифты:** OURS хранит настоящий локальный файл дисплейного шрифта `Palui SP Demo Bold` (`assets/fonts/PaluiSPDemo-Bold.woff2`, `assets/fonts/fonts.css:5-9`) — но у него отсутствуют глифы цифр/`%`/`₽` (см. `CLAUDE.md`, "Font gotcha"). REFERENCE вообще не подключает Palui SP Demo — только Google Fonts Montserrat (`functions.php:32-37` REFERENCE), а `--font-display` в CSS указывает на тот же Montserrat с комментарием, что оригинальные платные шрифты недоступны (`assets/css/main.css:648-652`).

---

## 1. Структура страниц и шаблонов

### 1.1. Соответствие файлов (naming)

| Назначение | REFERENCE | OURS |
|---|---|---|
| Главная | `front-page.php` (160 строк) | `front-page.php` (15 строк, только `get_template_part`) |
| Для инвесторов | `template-for-investors.php` (169 строк) | `page-for-investors.php` (6 строк) |
| Для кинематографистов | `template-for-cinematographers.php` (123 строки) | `page-for-cinematographers.php` (6 строк) |
| Вход | `template-login.php` + `header-auth.php`/`footer-auth.php` | `page-login.php` (обычный `header.php`/`footer.php`) |
| Регистрация | `template-register.php` | `page-register.php` |
| Верификация — выбор типа | `template-verification-choice.php` (отдельная страница) | встроено в шаг 1 `page-verification.php` |
| Верификация — физлицо/ИП/юрлицо | 3 тонких враппера (`template-verification-individual/ip/legal.php`) + общий `template-parts/verification-flow.php` | всё в одном `page-verification.php` (JS переключает вид полей) |
| Верификация — проверка данных | шаг внутри `verification-flow.php` (`data-step-panel`) | отдельная страница `page-verification-checking.php` |
| Верификация — готово | шаг внутри `verification-flow.php` | отдельная страница `page-verification-done.php` |
| Квалификация инвестора | `template-investor-qualification.php` | `page-qualification.php` |
| Кабинет — роутер по роли | `template-account-router.php` | нет отдельного шаблона; каждый `page-profile-*.php` сам делает `wp_safe_redirect` при отсутствии логина (напр. `page-profile-investor.php:2`) — нет единой точки маршрутизации по роли, как `cininvest_account_url_for_role()` в REFERENCE |
| Кабинет инвестора | `template-account-investor.php` | `page-profile-investor.php` |
| Кабинет спонсора | `template-account-sponsor.php` | `page-profile-sponsor.php` |
| Кабинет кинематографиста | `template-account-cinematographer.php` | `page-profile-cinematographer.php` |
| Подача заявки | `template-apply-project.php` (один файл, 18,5 КБ) | `page-application.php` + 8 партиалов `template-parts/application/section-*.php` |
| Заявка отправлена | внутри `template-apply-project.php` (последний шаг) | отдельная страница `page-application-sent.php` |
| FAQ | `template-faq.php` | `page-faq.php` |
| Документы | `template-documents.php` | `page-docs.php` |
| Страница проекта | `single-cin_project.php` (≈480 строк, CPT `cin_project`) | `single-project.php` (≈158 строк, CPT `project`) |
| Витрина проектов | `archive-cin_project.php` | `archive-project.php` |
| Generic-fallback для WP-страниц | `page.php` (+ `index.php` как доп. бэкап) | только `index.php` — `page.php` отсутствует |
| Модалка операции | `template-parts/operation-modal.php` | `template-parts/operation-modal.php` |
| Карточка баланса | `template-parts/balance-card.php` | `template-parts/wallet.php` |
| Приветствие кабинета | `template-parts/account-hero.php` | `template-parts/profile-hero.php` |
| Форма профиля | `template-parts/profile-form.php` (общая для всех 3 ролей) | нет отдельного партиала — форма продублирована внутри каждого `page-profile-*.php` |
| Список операций | `template-parts/history-list.php` | инлайн-цикл внутри каждого `page-profile-*.php` (нет общего партиала) |
| Отзывы (главная) | `template-parts/testimonials.php` (статичный PHP-массив из 3 отзывов) | `template-parts/home/reviews.php` (ACF-репитер с последнего проекта) |

### 1.2. Секции/блоки: что есть в одном и отсутствует в другом

**Отсутствует в OURS, есть в REFERENCE:**
- Hero-блок с фото/бликами и статистикой на лендингах для инвесторов/кинематографистов (`template-for-investors.php:14-44`, `template-for-cinematographers.php:14-42`).
- Блок «Кто может стать соавтором» **на своём месте** — на странице «Для инвесторов» (в OURS он есть, но на главной).
- Шаг про ПДЛ в верификации (`verification-flow.php:210-225`).
- Общий партиал формы профиля и списка операций (см. таблицу выше) — в OURS логика продублирована в 3 файлах вместо переиспользования одного партиала.
- `page.php` fallback-шаблон.
- Отдельный минимальный auth-хедер/футер.
- Реальная фильтрация витрины по статусу «завершён/в работе».
- Блокировка отсутствует (наоборот, REFERENCE ничего не блокирует гостям на странице проекта — см. п.5 ниже, это скорее «есть в OURS, нет в REFERENCE»).

**Есть в OURS, отсутствует в REFERENCE:**
- Отдельные страницы `page-verification-checking.php` и `page-verification-done.php` (в REFERENCE это шаги внутри одного файла).
- `template-parts/project/locked.php` — блокировка вкладок «Материалы»/«Документы» для неавторизованных на странице проекта (`single-project.php:102,113`); в REFERENCE эти вкладки открыты всем.
- Кнопка-переключатель бургер-меню в хедере реально работает (`header.php:29` OURS + JS `main.js:4-9`); в REFERENCE `.cin-burger` есть в разметке (`header.php:27-29` REFERENCE), но обработчика клика в `assets/js/main.js` не найдено вообще (проверено grep) — кнопка не открывает никакое меню, и самого списка пунктов меню (`wp_nav_menu`) в хедере REFERENCE нет в принципе.
- Отзывы на главной берутся динамически из ACF-репитера последнего проекта (`template-parts/home/reviews.php:9`) — в REFERENCE это захардкоженный статичный массив из 3 персон (`template-parts/testimonials.php:10-26`).
- Отдельный партиал `template-parts/project/buy-shares-modal.php` — покупка долей вынесена в модалку; в REFERENCE это отдельный полноэкранный `data-project-view="invest"` (не модалка).

---

## 2. Порядок секций

### 2.1. Главная страница (front-page.php)

| # | REFERENCE (`front-page.php`) | OURS (`front-page.php` → `template-parts/home/*`) |
|---|---|---|
| 1 | HERO: пилюля «20 000 инвесторов», заголовок-градиент, подзаголовок, 3 карточки статистики (`:19-45`) | HERO: то же самое, идентичный текст (`hero.php:1-12`) |
| 2 | 2 карточки «Для Кинематографистов» / «Для Инвесторов» — на градиентном/SVG-фоне, без фото (`:48-84`, класс `cin-mfeature-card`) | DIRECTIONS: 2 карточки с тем же текстом, но на фоне **реальных JPG-фото** (`hero-cinema.jpg`/`hero-investor.jpg`, `style.css:210-211`), класс `direction` (`directions.php:1-18`) |
| 3 | ПРОЕКТЫ: горизонтальный скролл карточек проектов + «Смотреть все» (`:87-152`) | PROJECTS-PREVIEW: похожая секция, другая вёрстка карточки (`showcase-card` вместо `cin-mproject-card`) (`projects-preview.php:1-26`) |
| 4 | ОТЗЫВЫ: `template-parts/testimonials.php` (статичные 3 отзыва) (`:154`) | REVIEWS: `reviews.php` (ACF-репитер, до 1 отзыва с последнего проекта) |
| 5 | — (секции «путь» и «соавторы» на главной REFERENCE отсутствуют) | COAUTHOR: блок «Кто может стать соавтором» (`coauthor.php`) — **добавлен на главную, в REFERENCE такого блока на главной нет вообще** |

Вывод: OURS переставил секцию 2 с абстрактного фона на фотографии (изменение оформления) и **добавил** пятую секцию (`coauthor`), которой в REFERENCE на главной нет — в оригинале это содержимое живёт только на `template-for-investors.php`.

### 2.2. Страницы «Для инвесторов» / «Для кинематографистов»

| # | REFERENCE (`template-for-investors.php`) | OURS (`page-for-investors.php`) |
|---|---|---|
| 1 | HERO с фото/бликами, заголовком, CTA «Стать инвестором», 3 карточки статистики (`:14-44`) | — отсутствует |
| 2 | «Кто может стать соавтором» — 3 карточки ролей (Спонсор/Инвестор физлицо/Инвестор ИП или юрлицо) (`:46-104`) | — отсутствует |
| 3 | «Путь инвестора» — 5 шагов + CTA (`:106-165`) | «Путь инвестора» — те же 5 шагов + CTA (`path.php` с `type=investor`) — **единственная секция, которая есть** |

| # | REFERENCE (`template-for-cinematographers.php`) | OURS (`page-for-cinematographers.php`) |
|---|---|---|
| 1 | HERO с фото/бликами, заголовком, 3 карточки статистики, **без CTA-кнопки в hero** (`:14-42`) | — отсутствует |
| 2 | «Путь проекта» — 5 шагов + CTA (`:44-119`) | «Путь проекта» — те же 5 шагов + CTA (`path.php` с `type=project`) — **единственная секция** |

---

## 3. Вёрстка блоков (сетки/колонки/breakpoints)

Общий момент: у REFERENCE десктоп-стили — набор точечных `@media (min-width:1024px)` блоков, каждый со своим захардкоженным `max-width` (1430px для хедера/футера/кабинета/проекта, 1180px для главной, 1720px для декорированной секции проектов, 902px для формы заявки и т.д., `assets/css/main.css:950-1324`). У OURS всё сведено в один `@media (min-width:1024px)` блок (`style.css:529-676`), базовый токен контейнера — `1430px` (`style.css:23`), с локальным переопределением `1180px` только для `.home .container` (`style.css:582`).

- **Карточки статистики hero (главная):** REFERENCE — мобильный: колонка с наездом карточек друг на друга (`margin-top:-65px`, `cin-stat-card`, `main.css:663-669`); десктоп: ряд из 3, `flex-direction:row`, скошенный верх через `clip-path` (`main.css:965-969`). OURS — мобильный: `.cin-stat-stack{flex-direction:column}` без наложения карточек друг на друга и без `clip-path` (`style.css` секция `.stats`, строки 203-206); десктоп: `grid-template-columns:repeat(3,1fr)` (`style.css:587`) — иной механизм раскладки (grid у OURS против flex+clip-path у REFERENCE), визуально разный приём.
- **Карточки «Кинематографист/Инвестор» на главной:** REFERENCE — `.cin-mfeatures{flex-direction:column}` на мобильном, `flex-direction:row` с `clip-path` на скошенном фото на десктопе (`main.css:670-672,970-978`). OURS — `.directions{display:grid;gap:16px}` на мобильном, `grid-template-columns:1fr 1fr` на десктопе (`style.css:207,588`) — используется CSS Grid вместо flex, без `clip-path`-скоса.
- **Карточки проектов в превью на главной:** REFERENCE — горизонтальный скролл `.cin-mscroll{display:flex;overflow-x:auto}` на мобильном, `grid-template-columns:repeat(3,1fr)` внутри декорированной секции на десктопе (`main.css:690-691,991`). OURS — `.projects-preview__scroll{display:flex;overflow-x:auto}` на мобильном, `grid-template-columns:repeat(3,1fr)` на десктопе (`style.css:228,591`) — структурно то же самое, другие имена классов и без декоративных blur-кругов (`.cin-msection--decorated::before/::after`, `main.css:684-687`, — у OURS такого декоративного приёма на секции проектов нет).
- **Страница проекта, десктоп:** REFERENCE — `grid-template-columns:1fr 500px` (контент/карточка сбора), карточка сбора абсолютно позиционирована и «наезжает» на низ фото (`margin:0 0 -160px`, `main.css:1133-1148`). OURS — `.fund-card{position:absolute;top:400px;right:24px;width:470px}` (`style.css:667`) — фиксированные px-координаты вместо grid-раскладки; иной механизм позиционирования того же визуального эффекта.
- **Табы кабинета:** REFERENCE — `.cin-tabs{justify-content:center}` на десктопе (`main.css:1223`). OURS — `.tabs__nav{justify-content:center}` на десктопе, но для кабинета кинематографиста явно `justify-content:flex-start` (`.profile--cinema .tabs__nav`, `style.css:554`) — REFERENCE не делает по-разному для разных ролей кабинета (все три используют один и тот же `.cin-tabs`).
- **Форма профиля:** REFERENCE — `grid-template-columns:180px 1fr 1fr` (аватар + 2 колонки полей) на десктопе (`main.css:1229`), с явным разбиением полей на full-width/half через `.cin-field--half`. OURS — `.profile-form{grid-template-columns:1fr 1fr}` без выделенной колонки под аватар (`style.css:545`), при этом для кинематографиста/инвестора добавлены свои переопределения (`.profile--cinema .photo-picker`, `.profile--wide .photo-picker` — обе вручную задают `grid-column:1/-1;width:180px;height:180px`, `style.css:556,575`) — то есть OURS решает ту же задачу через раздельные модификаторы на 2 профильных класса вместо одной универсальной раскладки, как в REFERENCE.
- **Витрина проектов:** REFERENCE — `grid-template-columns:repeat(3,1fr)` на десктопе, `max-width:1430px` (`main.css:1103`). OURS — `.showcase__grid{grid-template-columns:repeat(3,1fr)}` на десктопе (`style.css:616`), но родительский контейнер `.showcase{max-width:1432px}` (`style.css:614`) — на 2px отличается от REFERENCE (несущественно, но не идентично).

---

## 4. Типографика и дизайн-токены

### 4.1. Токены `:root`

| Токен | REFERENCE (`assets/css/main.css:4-28`) | OURS (`style.css:10-28`) |
|---|---|---|
| Основной красный (CTA) | `--c-red-600:#C6161C` (hover `--c-red-600-hover:#A50E15`) | `--c-primary:#B70000` (hover `--c-primary-dark:#9A0000`) — **другой оттенок красного** |
| Тёмно-красный (роль-кнопки) | `--c-red-700:#8E0E12` | нет отдельного токена; используется `--c-primary-grad:linear-gradient(90deg,#B70000,#F93030)` |
| Светло-розовый фон | `--c-red-050:#FBEAEB` | нет прямого аналога (используются inline `#faf5f5`, `#f6eaea`, `#ECE4E4` напрямую в разных местах, не через токен) |
| Текст основной | `--c-ink:#1B1B1D` | `--c-ink:#333333` — разные оттенки чёрного |
| Текст вторичный | `--c-body:#55555A` | нет отдельного токена `--c-body`; используется `--c-muted:#8A8A8A` универсально и там, и там |
| Плейсхолдер/muted | `--c-muted:#8C8C90` | `--c-muted:#8A8A8A` |
| Граница | `--c-line:#DEDEE1`, `--c-line-soft:#ECECEE` (2 токена) | `--c-line:#E4E4E4` (1 токен, второго уровня границы нет) |
| Фон | `--c-bg:#FFFFFF`, `--c-bg-muted:#F6F6F7` | нет отдельных токенов фона; используется `--c-grey:#E5E5E5`, `--c-grey-2:#F5F5F5` — близкая, но не идентичная пара |
| Радиусы | 4 токена: `--radius-s:8px`, `--radius-m:12px`, `--radius-l:16px`, `--radius-pill:999px` (масштаб) | 2 токена: `--radius:10px`, `--radius-lg:10px` (**оба равны 10px** — фактически один радиус на весь сайт, нет шкалы) |
| Шрифты | `--font-display:'Montserrat',system-ui,sans-serif`; `--font-body:'Montserrat',system-ui,sans-serif` (**оба указывают на Montserrat** — Palui SP Demo не подключён) | `--font-body:'Montserrat',...`; `--font-display:'Palui SP Demo','Montserrat',sans-serif` (**реально другой шрифт для display**) |
| Контейнер | `--container-w:430px` (мобильная колонка; десктоп — захардкоженные px в каждой секции) | `--container:1430px` (десктопный по умолчанию; `.home .container` переопределяет на `1180px`) |
| Тени | нет глобальных токенов тени (значения `0 0 30px #D4D4D4` и т.п. прописаны инлайн в каждом правиле) | `--shadow-header:0 0 30px #D4D4D4`, `--shadow-card:0 0 15px #D4D4D4` — **вынесены в токены** (более развито, чем в REFERENCE) |
| `:focus-visible` | явно определён: `outline:2px solid var(--c-red-600)` (`main.css:47-50`) | не найден отдельный `:focus-visible` селектор в `style.css` |

### 4.2. Прочее
- Класс-неймспейс: REFERENCE — все классы с префиксом `cin-` (`cin-btn`, `cin-field`, `cin-hero` и т.д.); OURS — без префикса (`btn`, `field`, `hero`), что означает нулевое пересечение имён классов между темами — перенос стилей 1:1 невозможен, только переиспользование значений.
- Числа в дисплейном шрифте: OURS явно форсирует Montserrat для чисел через `.num,.amount,[data-num]{font-family:var(--font-body)!important}` (`style.css:38`) — обходной приём из-за отсутствия цифр в Palui SP Demo Bold (см. `CLAUDE.md`, "Font gotcha"). У REFERENCE такой проблемы нет, т.к. `--font-display` и так указывает на Montserrat.
- Брейкпоинты: у обеих тем один и тот же порог `min-width:1024px` для десктопа (`main.css:954` REFERENCE, `style.css:529` OURS), плюс у REFERENCE есть `max-width:400px` для самых узких экранов (`main.css`, не найден отдельный аналог в OURS) и `max-width:1023px` только под степпер подачи заявки; у OURS — `max-width:400px` для `.hero__title` (`style.css:678-680`) и `max-width:1023px` для `.stepper__num` (`style.css:681-684`) — похожий, но не идентичный набор edge-case правил.

---

## 5. Ассеты

### 5.1. Изображения

- **REFERENCE:** каталог `assets/img` физически отсутствует на диске (проверено рекурсивным поиском). В `assets/css/main.css` нет ни одного `url(...)`, указывающего на файл изображения — единственный `url(...)` во всём файле — это inline `data:image/svg+xml` для стрелки `<select>` (`main.css:385`). Все «фотографии» реализованы как CSS-градиенты (`.cin-mfeature-card__media`, `.cin-lp-hero__photo`, `.cin-project-hero` — все на `linear-gradient`/`radial-gradient`, см. `main.css:672,723,852`) или как реальные загруженные через WP Media Library изображения проектов (`has_post_thumbnail()`/`get_the_post_thumbnail_url()`, `single-cin_project.php:50`, `front-page.php:104` REFERENCE). Логотип и социальные иконки — inline SVG прямо в PHP (`cininvest_logo_icon()`, `functions.php:63-70` REFERENCE; WhatsApp/Telegram иконки — inline `<svg>` с брендовыми цветами прямо в `footer.php:37-42` REFERENCE).
- **OURS:** `assets/img/` содержит 8 файлов:
  - `hero-cinema.jpg`, `hero-investor.jpg` — JPEG 800×400, 5,6 КБ каждый, используются как `background-image` в `.direction--cinema`/`.direction--investor` (`style.css:210-211`).
  - `poster.jpg` — JPEG 800×600, 8,2 КБ, фолбэк-постер проекта (`inc/helpers.php:33`, функция `cininvest_poster()`).
  - `project-hero.jpg` — JPEG 1600×700, 18,2 КБ — **не найдено ни одного упоминания имени файла нигде в PHP/CSS/JS темы** (проверено grep по всей теме) — неиспользуемый (orphan) ассет.
  - `logo.svg` (7,7 КБ), `logo-white.svg` (4,7 КБ) — используются как файлы через `<img src=...>` (`header.php:15`, `footer.php:5` OURS) — в REFERENCE логотип не файл, а inline-SVG функция.
  - `tg.svg`, `wa.svg` — иконки соцсетей в подвале, тоже как файлы `<img src>` (`footer.php:27-28` OURS) — в REFERENCE это inline SVG с фирменными цветами прямо в разметке.

### 5.2. Шрифты

- REFERENCE подключает только один внешний шрифт — Google Fonts Montserrat, вес 400-900 (`functions.php:32-37` REFERENCE, `family=Montserrat:wght@400;500;600;700;800;900`). Локальных файлов шрифтов в теме нет.
- OURS хранит 3 локальных файла шрифтов в `assets/fonts/`: `Montserrat-Variable.woff2`, `Montserrat-Italic-Variable.woff2` (вариативные, вес 100-900) и `PaluiSPDemo-Bold.woff2` (вес 700), подключаются через `assets/fonts/fonts.css` (`fonts.css:1-24`), enqueue до `style.css` (`functions.php:35-36` OURS). Файл `fonts.css` явно комментирует ограничение "капс-заголовки" для Palui SP Demo.

### 5.3. JS-файл как ассет
- REFERENCE: `assets/js/main.js` — не содержит обработчика для `.cin-burger` (бургер-кнопка в хедере не открывает меню; в разметке `header.php` REFERENCE вообще нет самого меню/списка ссылок для открытия).
- OURS: `assets/js/main.js:4-9` — рабочий обработчик `.burger`, открывающий/закрывающий `#main-nav` (реальное меню `wp_nav_menu`, `header.php:18-19` OURS) — по этому конкретному узлу OURS функционально полнее REFERENCE.

---

*Отчёт подготовлен путём построчного чтения PHP-шаблонов и CSS обеих тем (REFERENCE: `front-page.php`, `template-for-investors.php`, `template-for-cinematographers.php`, `header.php`, `footer.php`, `functions.php`, `template-account-*.php`, `template-parts/*.php`, `assets/css/main.css` целиком (1324 строки); OURS: `front-page.php`, `page-for-investors.php`, `page-for-cinematographers.php`, `header.php`, `footer.php`, `functions.php`, `inc/theme-setup.php`, `page-profile-*.php`, `template-parts/**/*.php`, `style.css` целиком (684 строки), плюс верификация/заявка/логин/регистрация/FAQ/документы/страница проекта/витрина).*
