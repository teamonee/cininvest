# PROJECT_MAP.md — навигация: страница ⇄ файлы ⇄ Figma-нода

Figma: https://www.figma.com/design/AYfMRCtWCfNlb2oOv2uLLR/CININVEST--Copy-?node-id=1627-13262
Единственная страница Figma: `Page 1` (`0:1`). Десктоп-макеты сгруппированы в SECTION-ы (y ≈ -10700…),
мобильные варианты — отдельные SECTION-ы ниже (y ≈ 13202…).

Правило: точные размеры/цвета/типографику брать только из Figma MCP
(`figma_get_design_context` / `figma_get_node` / `figma_get_variable_defs` / `figma_get_screenshot`),
не из скриншота на глаз и не из старых файлов. Контейнер унифицирован = 1180px. Проверка на 390 / 768 / 1440.

Скриншоты Figma большие — decode через:
`python -c "import base64,json;d=json.load(open(FILE));[open(SP+e['nodeId'].replace(':','_')+'.png','wb').write(base64.b64decode(e['base64'])) for e in d['exports']]"`

---

## SECTION «Основные экраны» — `972:6783`  (десктоп, 1920-wide фреймы «search-outline»)

| # (порядок работы) | Страница | Figma-нода фрейма | Файлы кода |
|---|---|---|---|
| 1 | **Главная** | `928:4021` | `front-page.php` → `template-parts/home/{hero,directions,projects-preview,reviews}.php`; `style.css` |
| 2 | **Лендинг «Для кинематографистов»** | `944:5171` | `page-for-cinematographers.php` → `template-parts/home/{lp-hero,path}.php` (`type=cinema`/`project`) |
| 2 | **Лендинг «Для инвесторов»** | `957:4184` | `page-for-investors.php` → `template-parts/home/{lp-hero,coauthor,path}.php` (`type=investor`) |
| 7 | **Витрина проектов** | `972:5115` | `archive-project.php`; классы `.showcase*`, `.filter-pill*`, `.pagination*` |
| 7 | **FAQ** | `972:6163` | `page-faq.php`; классы `.faq*`, `.faq-cta*` |
| 7 | **Документы** | `972:6947` | `page-docs.php`; классы `.docs`, `.doc-card*` |

Внутри каждого фрейма: дочерний `Header` (INSTANCE, 1920×100.15, drop-shadow r30 `#D4D4D4`) и `Footer` (INSTANCE, 1920×372, `#333`) — часто `visible:false` (общие для всех). Реальный контент — во вложенных `Frame …`.

## Прочие десктоп-SECTION-ы

| Порядок | Страница | Figma-нода | Файлы кода |
|---|---|---|---|
| 3 | **Страница проекта — инвестор** | `1153:13202` | `single-project.php` + `template-parts/project/{buy-shares-modal,sell-shares-modal}.php` |
| 3 | **Страница проекта — спонсор** | `1627:13219` | `single-project.php` (роль sponsor) + `template-parts/project/sponsor-modal.php` |
| 4 | **Аккаунт Инвестора** | `1033:4988` | `page-profile-investor.php` + `template-parts/{profile-hero,wallet,operation-modal}.php` |
| 4 | **Аккаунт Спонсора** | `1033:4987` | `page-profile-sponsor.php` + те же common parts |
| 4 | **Аккаунт Кинематографиста** | `1054:5590` | `page-profile-cinematographer.php` + те же common parts |
| 4 | **Операции** (модалка/список) | `1680:17199` | `template-parts/operation-modal.php`; классы `.op-modal*`, `.operation*` |
| 5 | **Подача заявки** | `1107:11125` | `page-application.php` + `template-parts/application/section-*.php` (applicant, project, creators, financing, schedule, rewards, requisites, data) |
| 6 | **Верификация** (общая) | `1088:6515` | `page-verification.php` (шаги `data-vstep`), `page-verification-checking.php`, `page-verification-done.php` |
| 6 | **Верификация физлица** | `1103:8264` | `page-verification.php` ветка fiz |
| 6 | **Верификация ИП** | `1103:8395` | `page-verification.php` ветка ip |
| 6 | **Верификация юрлица** | `1107:10087` | `page-verification.php` ветка ur |
| 7 | **Вход** | `1073:8700` | `page-login.php`; классы `.auth*`, `.auth-form` |
| 7 | **Регистрация** | `1073:10877` | `page-register.php`; классы `.register__*`, `.auth*`, `.field*` |
| — | **Квалификация** | `1073:7097` | `page-qualification.php`; классы `.qualify*` |

## Мобильные SECTION-ы (y ≈ 13202+, проверять на 390px)

`Основные экраны` `1430:11813` · `Страница проекта для инвестора - сделал` `1581:10487` · `Аккаунт Спонсора` `1635:13363` · `Аккаунт Инвестора` `1650:14181` / `1650:20506` · `Операции` `1685:18558` · `Подача заявки` `1658:16115` · `Верификация` `1666:15866` · `Аккаунт Кинематографиста` `1650:16815` · `Страница проекта для спонсора` `1627:11253` · `Вход` `1556:11421` · `Регистрация` `1556:12865` · `Верификация физлица` `1666:17919` · `Верификация ИП` `1666:19370` · `Верификация юрлица` `1666:22332`

---

## Файлы кода — карта

### Шаблоны страниц (корень)
- `front-page.php` — Главная (авто, если задана статическая главная)
- `page-for-investors.php` / `page-for-cinematographers.php` — лендинги (Template Name)
- `archive-project.php` — витрина CPT `project` (`/projects/`)
- `single-project.php` — страница проекта
- `page-faq.php`, `page-docs.php`, `page-login.php`, `page-register.php`, `page-qualification.php`
- `page-application.php` (+ `page-application-sent.php`)
- `page-verification.php` (+ `-checking.php`, `-done.php`)
- `page-profile-investor.php` / `page-profile-sponsor.php` / `page-profile-cinematographer.php`
- `header.php` / `footer.php` / `page.php` / `index.php`
- ⚠️ мусор (не в иерархии WP, ссылаются на несуществующие parts): `for-investors.php`, `for-cinematographers.php`, `singleproject.php`

### template-parts
- `home/` — `hero.php` `directions.php` `projects-preview.php` `reviews.php` `path.php` `coauthor.php` `lp-hero.php`
- `project/` — `buy-shares-modal.php` `sell-shares-modal.php` `sponsor-modal.php`
- `application/` — `section-{applicant,project,creators,financing,schedule,rewards,requisites,data}.php`
- корень parts — `wallet.php` `profile-hero.php` `operation-modal.php`

### Стили / скрипты
- `style.css` — единственный. Mobile-first; десктоп-оверрайды в блоке `@media (min-width:1024px)` ближе к концу.
  Токены в `:root` (`--c-primary`, `--radius`, `--container:1430px`, `--font-display`, `--font-body`).
  Home-секции: `.home .container{max-width:1180px}` в десктоп-блоке.
- `assets/js/main.js` — единственный, ваниль. Шаговые флоу через `.is-active` + `data-*next`.
- `assets/fonts/fonts.css` — Montserrat (body), Palui SP Demo Bold (`--font-display`, БЕЗ глифов цифр и `% ₽` — числа форсить в Montserrat через `.num`/`[data-num]` или явный `font-family:var(--font-body)`).

### backend (inc/, подключается списком в functions.php)
`acf-fields.php` `ajax-shares.php` `cpt.php` `cpt-application.php` `db-operations.php`
`forms-application.php` `forms-auth.php` `forms-verification.php` `helpers.php`
`payment-gateway.php` `rest-webhook.php` `theme-setup.php` `user-profile.php`

Хелперы шаблонов (`inc/helpers.php`): `cininvest_field($name,$post_id,$fallback)`,
`cininvest_money()` («1 000 000 ₽»), `cininvest_poster()`.
