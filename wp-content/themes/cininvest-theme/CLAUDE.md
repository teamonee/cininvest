# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

CININVEST is a WordPress theme (no plugin, no build step) implementing a film-investment crowdfunding platform: cinematographers submit projects, investors/sponsors buy shares, an in-house wallet tracks balances. Requires WordPress 6.0+, PHP 7.4+, and the free **ACF (Advanced Custom Fields)** plugin — project fields in `inc/acf-fields.php` will not register without it.

There is no `package.json`, `composer.json`, or bundler of any kind. All PHP, CSS (`style.css`, one file) and JS (`assets/js/main.js`, vanilla, one file) are hand-written and loaded directly — edit and reload, no compile step. There is likewise no automated test suite or linter configured; verification is manual (activate the theme in `wp-admin`, click through the flow).

## Setup / running

1. Copy this folder into `wp-content/themes/`, activate ACF, then activate the theme — activation creates the `wp_cininvest_operations` DB table (`inc/db-operations.php`, hooked on `after_switch_theme`, self-heals on `init` if `cininvest_db_version` option is stale).
2. Settings → Permalinks → Save (needed for the `project` CPT's rewrite slug to work).
3. Create WP pages and assign the templates listed at the top of `README.md` (Login, Register, Application, Verification, Qualification, FAQ, Docs, Projects, the three Profile templates) via Page Attributes → Template. The homepage uses `front-page.php` automatically once set as the static front page.
4. A user's role (`investor` / `sponsor` / `cinematographer`) is stored in user meta `cininvest_role`. The registration form (`inc/forms-auth.php`) only lets a user self-select `investor` or `cinematographer` — **`sponsor` has no self-registration path and must be set manually** via user meta until that flow exists.

## Architecture

### Module loading
`functions.php` require_once's a fixed, explicit list of `inc/*.php` files (no autoloading/PSR-4). When adding a new backend module, add its filename to the `$cininvest_inc` array in `functions.php` or it will never load.

### Payment layer — always go through the gateway interface
All balance-affecting code paths must call `cininvest_gateway()` (`inc/payment-gateway.php`), which returns whatever implements the `CININVEST_Payment_Gateway` interface (`deposit`/`withdraw`/`charge`/`get_status`). Never mutate balance or insert into `wp_cininvest_operations` directly from a template or handler. Currently the only implementation is `CININVEST_Mock_Gateway`, which completes operations synchronously (`status=completed` immediately); a real bank integration is meant to insert as `pending` and flip to `completed`/`failed` asynchronously via the REST webhook (`inc/rest-webhook.php`, route `POST /wp-json/cininvest/v1/payment-callback`, gated by `cininvest_webhook_secret` + `X-Cininvest-Secret` header, idempotent by operation id). Swap providers via the `cininvest_payment_provider` filter, not by editing call sites.

Operations are recorded in `wp_cininvest_operations` (`inc/db-operations.php`) with an idempotency guard on the `reference` column — `cininvest_add_operation()` returns the existing row id if `reference` already exists rather than duplicating.

### Forms and mutations: `admin-post` + nonce, not REST
User-facing mutations (register, login, submit application, verification, buy/sell shares, wallet deposit/withdraw) are handled via `admin_post_{action}` / `admin_post_nopriv_{action}` hooks (see `inc/forms-auth.php`, `inc/forms-application.php`, `inc/forms-verification.php`, `inc/ajax-shares.php`), each gated by `wp_verify_nonce`, with errors passed back through short-lived transients (`set_transient(..., 60)`) and read by the template that renders the form. The only true AJAX endpoint is the live share/price calculator (`wp_ajax_cininvest_calc_shares`), called from `main.js` and localized via `wp_localize_script('cininvest', 'CININVEST', ...)` in `functions.php`.

### Content model
- CPT `project` (`inc/cpt.php`, public, archive at `/projects/`) with ACF field group `group_project_fields` (`inc/acf-fields.php`) covering funding numbers, rounds (repeater), team (repeater), materials/docs (repeaters), rewards (repeater), and a `reviews` repeater used on the homepage.
- CPT `application` (`inc/cpt-application.php`, non-public, admin-only) for cinematographer submissions, with taxonomy `application_status` seeded with fixed terms (draft/submitted/pre_review/expert_review/approved/rejected).
- `inc/helpers.php` centralizes safe field access — always prefer `cininvest_field($name, $post_id, $fallback)` over raw `get_field()` in templates, since it fails gracefully (returns the fallback) when ACF isn't installed. Similarly use `cininvest_money()` for `"1 000 000 ₽"` formatting and `cininvest_poster()` for the thumbnail-with-fallback pattern.

### Templates
Standard WP template hierarchy: `page-*.php` files map to admin-selectable `Template Name:` headers (check the top comment of any `page-*.php` for its name), `single-project.php` for individual projects, `front-page.php` for the homepage. Shared markup lives in `template-parts/{home,application,project}/*` plus a few top-level shared parts (`wallet.php`, `profile-hero.php`, `operation-modal.php`). The header has no CTA button, matching the reference theme: `header.php` shows only the logo, nav, and an auth block — a "Кабинет" + "Выход" pair for logged-in users (via `cininvest_account_url_for_role(cininvest_user_role())`) or a "Вход" link for guests.

Multi-step flows (register, application submission, verification) are single-page: all steps render server-side inside the same PHP file, and `main.js` toggles `.is-active` on `[data-step]`/`[data-appstep]`/`[data-vstep]` elements based on `data-next`/`data-appnext`/`data-vnext` button clicks — there is no client-side routing or step persisted server-side until final submit.

### CSS (`style.css`)
Single file, mobile-first: unprefixed rules are the mobile/base layout, then one big `@media (min-width:1024px)` block ("DESKTOP") near the bottom overrides for desktop, plus two small edge-case queries (`max-width:400px` for the smallest phones, `max-width:1023px` for the stepper). There is no tablet-specific breakpoint — anything below 1024px gets the mobile layout. Design tokens live in `:root` custom properties (`--c-primary`, `--radius`, `--container`, etc.) — reuse these rather than hardcoding colors/radii. Home-page sections cap their container at 1180px via `.home .container{max-width:1180px}` inside the desktop block, distinct from the theme-wide `--container:1430px` used elsewhere (FAQ, docs, showcase, verification) — don't assume one container width applies everywhere.

**Font gotcha (important, easy to regress):** `Palui SP Demo` (`--font-display`, used for headings/caps titles) is a demo font missing digit glyphs and the `%`/`₽` symbols entirely — they render as empty boxes. Any numeric value that could end up inside a `--font-display` context must be forced back to Montserrat, either via the `.num`/`.amount`/`[data-num]` utility class or an explicit `font-family:var(--font-body)` on that element (see `.stats__num` for the pattern). When editing typography, check whether a heading-styled element could ever contain a number before leaving it unstyled.

### Assets
Fonts are local (`assets/fonts/`, loaded via `assets/fonts/fonts.css`, enqueued before `style.css` in `functions.php`): Montserrat (variable, body text) and Palui SP Demo Bold (display/caps headings — see font gotcha above). `assets/img/*` are currently solid-color placeholders, not final art — treat any styling that depends on real image content (crops, focal points) as provisional until real assets are swapped in.

## Design-diff edits (Figma sync)

Rules for any task that compares the site against the Figma design and fixes discrepancies:

- Figma source of truth: https://www.figma.com/design/AYfMRCtWCfNlb2oOv2uLLR/CININVEST--Copy-?node-id=1627-13262 — pull exact sizes, spacing, colors and font sizes from the design (`get_design_context`/`get_metadata`), never guess or eyeball them off the screenshot.
- Container is unified at 1180px; check layouts at 390 / 768 / 1440. No horizontal scroll is allowed at any of these widths — verify before and after every change.
- `.btn` is the shared button class for the whole site — never edit `.btn`/`.btn--primary` itself for a single-section fix. Add or change a scoped modifier class instead (e.g. `.path__cta`).
- Don't duplicate CSS rules: if a selector already exists in `style.css`, edit it in place rather than appending a second block with the same selector further down the file — there's no linter/build step here to catch duplicates.
- Take every value from Figma; don't invent or approximate numbers that aren't in the design.
