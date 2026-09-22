# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repository is

A classic (non-block, non-Composer) WordPress **theme** for Essex Muslim Centre (`emc-theme`). There is no
build step, no `package.json`, no `composer.json`, no bundler — CSS and JS are hand-written and enqueued
directly from `assets/`. PHP files are the theme itself; there is no separate `src/` vs `dist/`.

**The repo root *is* the theme directory.** `style.css`, `functions.php`, and every `template-*.php` file
sit at the top level because this repo gets deployed straight into
`wp-content/themes/emc-theme/` (see `docs/hostinger-auto-deploy.md`). Never assume a `wp-content/` or
`wp-config.php` layout — those belong to the WordPress install this theme is dropped into, not this repo.

Payment/Stripe processing is deliberately **not** in this theme — it lives in a separate "EMC Payments"
plugin. Code here only reads/exports data that plugin writes (donation logs, campaign totals) or renders
UI that the plugin's AJAX endpoints power. Look for `emc_render_payment_plugin_required()` in
`inc/helper-functions.php` — that's the guard shown when the plugin is missing.

## Commands

There is no build/lint/test tooling in this repo (no `package.json`, `composer.json`, phpunit config, or
CI). "Running" the theme means dropping/symlinking this directory into a WordPress install's
`wp-content/themes/` as `emc-theme` and activating it — there's no other local dev harness here. Verify
PHP changes with `php -l <file>` if you want a fast syntax check.

Deployment is via Hostinger's Git auto-deploy from the `main` branch directly into
`public_html/wp-content/themes/emc-theme` — see `docs/hostinger-auto-deploy.md` for the full runbook
before touching deploy/branch topology. There is no staging environment described in the repo.

## Architecture

### Bootstrapping — `functions.php` + `inc/`

`functions.php` handles theme setup (menus, image sizes, asset enqueueing) and then requires every
module in `inc/` via the `$emc_includes` array (~line 484). **When adding a new feature module, add it to
that array** — files in `inc/` are not auto-loaded any other way. The array's ordering/comments describe
what each file owns; skim it before hunting through individual files.

Each `inc/*.php` file owns one concern end-to-end: post type + admin UI + front-end helpers for that
feature all live together (e.g. `inc/badr-wall.php` has the CPT registration, admin screens, and the
public tile rendering in one file). When editing a feature, that single file is usually the whole story
plus whichever `template-*.php` / `template-parts/` file renders it.

Key modules to know about:
- `inc/custom-post-types.php` — all CPTs (`emc_event`, `emc_service`, `emc_team`, `emc_testimonial`,
  `emc_faq`, `emc_campaign`, `emc_vacancy`, `emc_volunteer_role`, `emc_portfolio`, `emc_pricing`,
  `emc_case_study`, `emc_gallery`) and their taxonomies/archive query overrides.
- `inc/acf-helpers.php` — `emc_acf()` / `emc_acf_image()` wrap ACF's `get_field()` with a default and a
  graceful fallback when ACF is deactivated. **Templates should call these, not `get_field()` directly.**
- `inc/site-content-editor.php` + `inc/customizer.php` + `inc/customizer-pages.php` — three overlapping
  systems for admin-editable content (a custom "Site Content" screen, the native Customizer, and
  Customizer panels that migrated old ACF page content). Check which system backs a given piece of copy
  before assuming it's hardcoded or ACF-driven — `emc_site_setting()` / `emc_option()` are the read-side
  helpers for the first two.
- `inc/admin-organizer.php` — regroups the CPTs above out of the default WP sidebar into a custom "EMC
  Website" admin workspace; don't be surprised the CPT menus aren't where core WP would normally put them.
- `inc/prayer-timetable-import.php` — parses an uploaded Masjidbox XLSX (hand-rolled zip/XML parsing, no
  library) into `assets/js/prayer-data.json`, which the front end fetches directly as a static JSON file.
- `inc/helper-functions.php` — `emc_ramadan_giving_schedule()` and related: the Ramadan recurring-giving
  window is computed server-side from a stored start date, deliberately non-editable, so the public JS and
  the payment plugin's server-side checks can never disagree about when the window is open.
- `inc/demo-data.php` + `inc/demo-import.php` + `inc/setup-wizard.php` — a one-time "Setup Wizard"
  (Appearance → EMC Setup) that programmatically creates all demo pages/menus/theme_mods on a fresh
  install.

### Timezone handling (recurring source of bugs)

Prayer times, Hijri dates, and the Ramadan giving window all need the *WordPress site timezone*, not the
visitor's browser timezone. The established pattern (see `assets/js/prayer-times.js` /
`prayer-topbar.js`) is: PHP passes `wp_timezone_string()` into JS via `wp_localize_script()` as
`emcPrayer.timezone` / `emcData`, and the JS uses `Intl.DateTimeFormat(..., { timeZone })` to compute
"today" instead of `new Date().getDate()`. A `setInterval` in `prayer-times.js` reloads the page at
local-site midnight to keep the date badge and timetable in sync. Follow this pattern for any new
date-sensitive front-end code — don't reach for the browser's local timezone.

### Templates and rendering

- Page routing mostly follows WP's normal template hierarchy (`single-emc_*.php`, `archive-emc_*.php`,
  `taxonomy-*.php`, `page-templates/*.php`), plus one manual override: the `template_include` filter near
  the top of `functions.php` force-assigns `template-campaign.php` and `page-badr-wall-tiles.php` to pages
  with matching slugs, bypassing the admin's "Page Attributes" template picker for those two.
- `template-parts/sections/*.php` are the front-page building blocks (hero, counters, services-preview,
  testimonials, etc.); `template-parts/components/*.php` are smaller reusable pieces (content cards,
  prayer widgets). `front-page.php` composes the sections.
- CSS/JS are enqueued per-context rather than as one bundle: `emc_enqueue_assets()` loads the sitewide
  core files, `emc_enqueue_page_assets()` (page templates) and `emc_enqueue_cpt_assets()` (CPT
  singles/archives) conditionally enqueue a matching `assets/css/<name>.css` / `assets/js/<name>.js` pair
  by page/post-type slug. When adding a page-specific stylesheet or script, follow that naming convention
  and register it in the relevant enqueue function rather than loading it sitewide.
- Elementor is an optional integration, not a requirement: `inc/elementor-compat.php` and
  `inc/elementor-widgets.php` only do anything when Elementor is active, and register theme
  locations/custom widgets (donate button, prayer times, stats counter) for it.

### Forms and data capture

Contact, volunteer, event-registration, Gift Aid, and newsletter submissions each have their own module
(`inc/contact-submissions.php`, `inc/volunteers.php`, `inc/volunteer-signups.php`,
`inc/event-registrations.php`, `inc/gift-aid.php`, `inc/newsletter.php`) that registers a private CPT (or
option) to store entries, wires an AJAX handler, sends notification emails, and adds an admin list screen.
`inc/form-notifications.php` centralizes the recipient/delivery settings shared across those forms, and
`inc/form-response-exports.php` provides CSV export for all of them — check that file when adding a new
form so its data is exportable the same way as the others.
