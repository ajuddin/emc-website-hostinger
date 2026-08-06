# EMC Theme Hardcoded Content Audit

Date: 25 July 2026
Scope: 130 PHP, JavaScript, JSON, and CSS files
Audit type: Read-only; no theme files were changed as part of the audit.

The theme is partly dynamic, but several important values remain hardcoded. The most urgent findings are the Ramadan/payment configuration, homepage videos, annual prayer data, and admin fields that the templates ignore.

## 1. Donation and payment information

The following bank information can only be changed in code:

- NatWest Payit URL
- Account name: Essex Muslim Centre
- Account number: `31512852`
- Sort code: `56-00-18`
- BIC: `NWBKGB2L`
- IBAN: `GB38NWBK56001831512852`
- Postal address

Locations:

- `template-donate.php:37`
- The NatWest link is duplicated in `template-campaign.php:46`.

These values should be moved into a single WordPress settings page so the Donate and Campaign templates use the same source.

Other fixed donation values:

- One-off buttons: £5, £10, £25, £50, £100 — `template-donate.php:82`
- Regular buttons: £5, £10, £20, £50 — `template-donate.php:154`
- Regular frequencies and fund choices — `template-donate.php:162`
- Minimum donation: £0.50 — `plugins/emc-payments/assets/js/donate.js:504` and `plugins/emc-payments/includes/stripe-gateway.php:140`
- Currency: GBP — `plugins/emc-payments/includes/stripe-gateway.php:155`
- Zakat Nisab: £452.06
- Zakat rate: 2.5% — `plugins/emc-payments/assets/js/donate.js:161`

The admin has quick-amount fields, but the templates ignore them:

- ACF donation fields — `inc/acf-fields.php:312`
- Hardcoded buttons — `template-donate.php:82`

The Donor Portal URL is collected from admin but never output as a link:

- `template-donate.php:197`

Fund labels are editable, but their internal payment values remain fixed as `General Fund`, `Education`, and `Zakat`. Renaming a visible label will not rename the value saved with Stripe.

## 2. Ramadan, Fidya and Fitrana

The dedicated Ramadan page is mostly controlled by code rather than admin:

- Daily amounts: £1, £2, £3, £5, £10
- Giving periods: 30, 10 and 5 days
- Fitrana rate: £7
- Fidya rate: £5
- Maximum missed fasts: 30

Locations:

- `template-ramadan.php:109`
- `template-ramadan.php:212`

The Fitrana and Fidya fallback rates are repeated in inline JavaScript:

- `template-ramadan.php:319`

More seriously:

- Ramadan start date defaults to `2027-02-08`.
- There is no registered admin field for `ramadan_start_date`.
- A temporary testing override replaces the date with today on every request.

Location:

- `template-ramadan.php:38`

This means even a manually stored date would currently be ignored.

The admin fields for Ramadan amounts, headings, descriptions, buttons and giving-period labels are stale and unused by the dedicated Ramadan template:

- `inc/acf-fields.php:336`

## 3. Gift Aid duplicate legacy handler

Two different functions are registered for the same `emc_gift_aid` AJAX action:

- Old email-only handler — `inc/ajax-handlers.php:14`
- New admin-record handler — `inc/gift-aid.php:139`

Because the old file is loaded first at `functions.php:476`, it can respond and terminate before the new handler stores the declaration. This can prevent Gift Aid submissions from appearing in the admin area.

The following are also hardcoded:

- Gift Aid declaration wording — `page-gift-aid.php:132`
- Duplicate stored declaration wording — `inc/gift-aid.php:184`
- Prefix and country choices — `page-gift-aid.php:49`
- Eligibility link to GOV.UK — `page-gift-aid.php:142`
- Confirmation email subject and text — `inc/gift-aid.php:210`

The notification recipient is admin-configurable; only its default is fixed.

## 4. Badr Wall and campaign

These values are fixed in code:

- Founder level: £10,000
- Founder total tiles: 100
- Co-Founder level: £5,000
- Co-Founder total tiles: 213

Location:

- `inc/helper-functions.php:31`

Only the “taken” counts are editable in the Customizer:

- `inc/customizer.php:653`

Most campaign body content is also hardcoded, including:

- Wall of Honour wording
- Membership instructions
- Payment option descriptions
- “Why This Matters” content
- Capacity for 500+
- Gift Aid claims
- “100% goes to the building fund” statement

Locations:

- `template-campaign.php:135`
- `template-campaign.php:289`

The homepage campaign’s “Why This Matters” list is separately hardcoded:

- `template-parts/sections/campaign.php:104`

## 5. Homepage media

Although the main Media page correctly reads videos from the Media Videos admin area, the homepage does not.

It contains three fixed YouTube IDs, titles and 2026 dates:

- `template-parts/sections/media-preview.php:16`

The YouTube channel URL is also fixed:

- `template-parts/sections/media-preview.php:105`

If there are no blog posts, the homepage displays three fixed news stories and dates:

- `template-parts/sections/media-preview.php:158`

The main Media page has additional hardcoded fallback content:

- Fixed gallery categories and bundled gallery images — `template-media.php:138`
- Three fixed news stories from April/May 2026
- Fallback “Read More” links pointing to `#`

Location:

- `template-media.php:272`

## 6. Prayer timetable and header times

The complete prayer timetable is a bundled static 2026 dataset:

- `assets/js/prayer-data.json`
- An older unused duplicate exists at `assets/data/prayer-times.json`.

The theme has no admin upload/import mechanism for the annual timetable. For 2027, the JSON file must currently be replaced in the theme.

Other fixed prayer values:

- Second Jumu’ah time: 14:15 — `header.php:113`
- First Jumu’ah fallback: 13:15 — `assets/js/prayer-topbar.js:119`
- Prayer PDF fallback filename and “Download 2026 PDF” label — `template-parts/sections/prayer-times.php:34`
- Copyright start year: 2025 — `inc/helper-functions.php:383`

The generated PDF on the full timetable page uses the bundled JSON dynamically, but the underlying year’s data is static.

## 7. Contact and vacancy information

The contact page contains fixed location information:

- Coordinates `51.745083, 0.507917`
- Google Maps short link
- Train, bus and taxi instructions

Locations:

- `template-contact.php:150`
- `template-contact.php:165`

The map embed itself can be overridden through admin, but the instructions and exact-map button cannot.

All vacancies display the same fixed location regardless of the vacancy:

- Archive: “Essex Muslim Centre, Chelmsford, Essex” — `archive-emc_vacancy.php:85`
- Single vacancy: “Chelmsford, Essex” — `single-emc_vacancy.php:82`

The “no vacancies” email uses a separate `emc_email` option with an `.org.uk` fallback, while the rest of the theme uses `emc_admin_email`:

- `archive-emc_vacancy.php:115`

## 8. Forms and email content

The following form structures are code-controlled and cannot be edited in admin:

- Gift Aid fields, countries and declaration
- Volunteer interests and availability choices
- Event registration fields
- Newsletter consent text
- Contact form field structure

Volunteer choices are duplicated in frontend and backend validation:

- `page-volunteer.php:75`
- `inc/volunteers.php:107`

Confirmation email subjects and message bodies for donations, Gift Aid, volunteering, events and contact messages are fixed in PHP. The recipient addresses are generally configurable, but the email wording is not.

## 9. Admin fields that currently have no effect

These controls are misleading because they exist in admin but are unused or partially unused:

- Donation quick amounts
- Ramadan amounts, periods, heading, description and button
- Building, Sadaqah and Lillah fund labels
- Donor Portal URL
- Old Services page tab fields
- Friday prayer service fields
- Youth, reversion and wellbeing service content
- Footer newsletter visibility, heading and description

The footer newsletter settings are read into variables but never rendered:

- `footer.php:16`

The old Services fields remain registered at `inc/acf-fields.php:103`, while the current Services page instead displays Service posts.

## 10. Automatic and demo content

The theme automatically creates eight fixed Service posts on first load:

- `functions.php:507`

It also contains an update routine that can rewrite the core Islamic Education service during a seed-version upgrade:

- `functions.php:672`

Six fixed 2026 Event posts are automatically created on first load:

- `functions.php:812`

The setup wizard also includes hardcoded:

- Page titles, slugs and hierarchy
- Menu layouts
- Privacy policy text
- Default contact and charity information
- Gallery items
- Relative-date demo events
- Theme colours and typography

Locations:

- `inc/demo-data.php:20`
- `inc/demo-data.php:367`

These are mostly seed-only, but once imported they become WordPress database content.

The current Gift Aid and Volunteer auto-page functions are guarded against an existing page, so the present version should not repeatedly create duplicates. They are still automatic code-created pages:

- `inc/gift-aid.php:67`
- `inc/volunteers.php:25`

## 11. Fixed fallback images and display limits

Bundled fallback images are used for:

- Header logo
- Homepage hero
- About hero and mission
- Events without featured images
- Media/news fallback cards

Examples:

- `header.php:169`
- `template-parts/sections/hero.php:21`
- `template-events.php:82`

Fixed query limits include:

- Events page: 12
- Services: 8
- Media gallery: 60
- Media news: 6
- Homepage news: 3
- Related content: usually 3
- Admin submission tables: 50
- Donation/subscription logs retained: 1,000

These are code settings rather than admin settings.

## 12. Presentation values

Most CSS spacing, individual component colours, breakpoints, card radii, animation delays and element heights are naturally hardcoded in the CSS.

The main palette, fonts, global radius, container width and section padding are admin-configurable through CSS variables:

- `inc/helper-functions.php:575`

One mismatch remains: the performance code always preloads the Outfit font even when the heading font is changed in the Customizer:

- `inc/performance.php:109`

## Already genuinely dynamic

These parts are correctly admin-driven:

- Primary and footer menus, provided menus are assigned under Appearance → Menus
- Blog posts, featured images and categories
- Blog sidebar under Appearance → Widgets
- Events and registrations
- Main Media Videos page
- Annual reports and Charity Commission link
- Trustee names, roles, biographies and photographs
- Contact submissions, volunteer applications and newsletter records
- Mailchimp credentials and audience
- Stripe API keys
- Header logo, site title, contact details and most homepage headings

The menu fallback lists are hardcoded only when no WordPress menu is assigned:

- `inc/helper-functions.php:212`
- `inc/helper-functions.php:319`

No real Stripe secret key or Mailchimp API key is committed in the theme; only placeholder examples are present.

## Recommended priority

1. Remove the duplicate Gift Aid AJAX handler.
2. Move bank details, payment URL, currency and donation limits into admin settings.
3. Make Ramadan date, Fidya, Fitrana, periods and amounts admin-configurable.
4. Connect homepage videos to the existing Media Videos admin records.
5. Add an annual prayer timetable upload/import setting.
6. Remove or connect unused ACF and Customizer controls.
7. Move Badr Wall totals, levels and campaign claims into admin settings.
8. Add vacancy-specific location fields.
9. Replace live fallback stories and events with empty-state messages.
10. Make form confirmation emails editable through admin settings.
