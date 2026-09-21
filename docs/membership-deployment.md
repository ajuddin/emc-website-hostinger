# Membership Page — Hostinger Deployment Guide

Branch: `feat/membership-page`

## The short version

**Everything is theme code.** There is no plugin to install, no plugin file to edit,
no database import, and no Custom HTML block to paste into a page.

| What | Count | Effort |
|---|---|---|
| New theme files to upload | 4 | Straight copy |
| Existing theme files to patch | 5 | 7 small insertions |
| Manual steps in WordPress admin | 4 | About 5 minutes |

Theme root on Hostinger:

```
/public_html/wp-content/themes/emc-theme/
```

Everything below is relative to that folder.

---

## Before you start

1. **Back up the theme folder.** In hPanel → Files → File Manager, right-click
   `emc-theme` → Compress, and download the archive. Or use the Hostinger backup tool.
2. **Confirm the live theme matches `main`.** If anyone has hand-edited theme files on
   Hostinger without committing them back, uploading whole files will silently wipe
   those edits. If you are not certain, follow Part B by hand-editing rather than
   overwriting the five shared files.
3. **Check the EMC Payments plugin is active and licensed.** Plugins → it should be
   `emc-payments-1`, active. Memberships depend on it entirely.

---

## Part A — Upload the four new files

These files do not exist on the live site, so there is nothing to overwrite. Upload
them exactly as they are in the repository.

| Upload to | From the repo |
|---|---|
| `template-membership.php` | `template-membership.php` |
| `inc/membership.php` | `inc/membership.php` |
| `assets/css/membership.css` | `assets/css/membership.css` |
| `assets/js/membership.js` | `assets/js/membership.js` |

**What each one does**

- `template-membership.php` — the page itself. Registers "Membership" in the page
  Template dropdown. Contains the Qur'anic banner, the three dome cards, the benefits
  matrix, the allocation donut, the progress bar and the application form.
- `inc/membership.php` — all the server logic: the `emc_membership` record type, the
  three levels, form validation, the handover to the payments plugin, the listener
  that completes memberships from the plugin's subscription log, and the admin screen.
- `assets/css/membership.css` — the dark green and gold styling. Scoped to
  `.emc-membership`, so it cannot affect any other page.
- `assets/js/membership.js` — validates the form, posts it, then opens the payments
  plugin's card modal.

**How to upload:** hPanel → File Manager → navigate to the theme folder → Upload.
Create the `inc`, `assets/css` and `assets/js` paths if prompted — they already exist.

---

## Part B — Patch the five existing files

Seven insertions in total. Nothing is deleted or rewritten, so each edit is additive
and safe to do by hand in the File Manager's text editor.

### B1. `functions.php` — four insertions

**1 of 4 — register the template slug.** Find:

```php
        } elseif ( is_page_template( 'page-volunteer-registration.php' ) ) {
            $slug = 'volunteer';
        } else {
```

Insert the new branch before `} else {`:

```php
        } elseif ( is_page_template( 'page-volunteer-registration.php' ) ) {
            $slug = 'volunteer';
        } elseif ( is_page_template( 'template-membership.php' ) ) {
            $slug = 'membership';
        } else {
```

**2 of 4 — add the CSS and JS to the asset map.** Find:

```php
            'job-application' => array( 'css' => 'volunteer.css', 'js' => 'volunteer.js' ),
        );
```

Add one line:

```php
            'job-application' => array( 'css' => 'volunteer.css', 'js' => 'volunteer.js' ),
            'membership'      => array( 'css' => 'membership.css', 'js' => 'membership.js' ),
        );
```

**3 of 4 — pass the AJAX URL and nonce to the page script.** Find the events block:

```php
            if ( 'events' === $slug ) {
                wp_localize_script( $handle, 'emcEventsConfig', array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'emc_event_registration' ),
                ) );
            }
```

Add a matching block straight after its closing `}`:

```php
            if ( 'membership' === $slug ) {
                wp_localize_script( $handle, 'emcMembershipConfig', array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'emc_membership' ),
                ) );
            }
```

**4 of 4 — load the module.** In the `$emc_includes` array, find:

```php
    '/inc/event-registrations.php', // Event forms, email notifications, submissions, and settings
```

Add one line beneath it:

```php
    '/inc/membership.php',          // Membership levels, applications, and admin records
```

> This is the only edit that is strictly required for the page to work. Without it,
> `inc/membership.php` is never loaded and the page will fatal on `emc_membership_levels()`.

### B2. `inc/customizer-pages.php` — one insertion (the largest)

This adds the **Page Content → Membership Page** Customizer section.

Find the very end of the `emc_register_page_content_sections()` function:

```php
    emc_bulk_text_settings( $wp_customize, $donate, 'emc_pg_donate' );
}
add_action( 'customize_register', 'emc_register_page_content_sections' );
```

Paste the whole `MEMBERSHIP PAGE` block from the repository version between
`emc_bulk_text_settings( ... 'emc_pg_donate' );` and the closing `}`, then paste the
`emc_sanitize_membership_amount()` helper after
`add_action( 'customize_register', ... );`.

Because this block is ~150 lines, **overwriting the whole file is easier and less
error-prone** — provided nobody has hand-edited page copy directly on Hostinger.
Saved Customizer values live in the database, not in this file, so replacing the file
does not lose any wording an administrator has already saved.

### B3. `inc/form-notifications.php` — two one-line insertions

In `emc_form_notification_types()`, after the `event_registration` line:

```php
		'membership'         => __( 'Membership applications and fees', 'emc-theme' ),
```

In `emc_form_legacy_recipient()`, after its `event_registration` line:

```php
		'membership'         => get_option( 'admin_email' ),
```

Skip this file and memberships still work — you just will not get the admin
notification email, and "Membership applications and fees" will not appear on the
Form Notifications screen.

### B4. `inc/form-response-exports.php` — three insertions

In `emc_form_response_export_types()`, after the `gift-aid` line:

```php
		'memberships'          => array( __( 'Memberships', 'emc-theme' ), 'manage_options' ),
```

Add the whole `emc_membership_export_data()` function immediately before
`function emc_newsletter_export_data()` (copy it from the repo file).

In `emc_form_response_export_data()`, add `'memberships' => 'emc_membership_export_data',`
into the `$callbacks` array, between the `gift-aid` and `newsletter` entries.

Optional. Skipping it only removes the Memberships CSV export.

### B5. `inc/admin-organizer.php` — two one-line insertions

Adds the two shortcut cards to the EMC admin hub. Purely navigational — skip it and
everything still works, you just reach the screens by URL instead.

In the `'responses'` array, after the Gift Aid line:

```php
			array( 'Memberships', 'Members, levels, monthly amounts and Stripe subscription references.', 'dashicons-id', admin_url( 'admin.php?page=emc-memberships' ), 'manage_options' ),
```

In the `'settings'` array, after the Ramadan Giving Schedule line:

```php
			array( 'Membership Levels', 'Set the membership levels, monthly amounts and wording shown on the Membership page.', 'dashicons-id', admin_url( 'customize.php?autofocus[section]=emc_pg_membership' ), 'edit_theme_options' ),
```

---

## Part C — Manual steps in WordPress

### C1. Attach the template to your Membership page

Pages → Membership → in the sidebar under **Page Attributes**, set **Template** to
**Membership** → Update.

The theme tries to do this for you automatically on first load after deployment, but
only if the page slug is exactly `membership` **and** no template has been chosen yet.
Check it and set it manually if it has not happened.

### C2. Set the levels and wording

Appearance → Customize → **Page Content** → **Membership Page**.

The three levels ship as Supporters £10, Companions £30 and Custodians £100 per month,
with all the page copy already filled in. You only need to open this if you want to
change something.

Two fields to look at:

- **Funds raised to date (£)** and **Membership target (£)** drive the progress bar.
  Both default to 0, which shows the bar empty. Set the target to make the bar move.

> Changing a level's monthly amount creates a new price for future members. Anyone who
> has already joined stays on the amount they signed up to until you move them in Stripe.

### C3. Set who gets the notification email

EMC Website → Form Notifications → **Membership applications and fees** → enter the
recipients → Save. Defaults to the site admin email.

### C4. Check the menu

The screenshot you sent already shows Membership in the header menu, so this is
probably done. If not: Appearance → Menus → add the Membership page.

---

## Part D — Test before you announce it

Put Stripe in **test mode** first, in the EMC Payments settings.

1. Visit `/membership/`. Confirm the banner, three dome cards, benefits table,
   donut chart and progress bar all render, and the palette is green and gold.
2. Click **Join as Companions** on a dome card. The page should scroll to the form
   with Companions selected and "£30 / month" showing.
3. Fill the form and submit. The EMC Payments modal should open showing £30.00.
4. Pay with Stripe test card `4242 4242 4242 4242`, any future expiry, any CVC.
5. Confirm all four of these:
   - Stripe dashboard → Subscriptions shows a new **monthly** subscription.
   - EMC Website → **Memberships** shows the member with status `active` and the
     subscription reference — **not** "Payment not completed".
   - EMC Website → Donations & Payments shows the same schedule under fund
     `Membership - Companions`.
   - The member and the admin both received an email.
6. Cancel the test subscription in Stripe, delete the test record, switch Stripe back
   to live mode.

**If step 5 shows "Payment not completed"** the payment succeeded but the theme did
not match it back. The record is safe — the money is in Stripe either way. Check that
the fund label in the subscription log reads exactly `Membership - <Level name>`; the
match is on that string, so renaming a level in the Customizer between the application
and the payment would break it.

---

## Part E — If something goes wrong

Symptoms and causes, most likely first:

| Symptom | Cause |
|---|---|
| White screen across the whole site | A typo in `functions.php`. Restore your backup of that one file. |
| Page renders as a normal page, no styling | Template not set on the page (C1), or `template-membership.php` not uploaded. |
| "Undefined function emc_membership_levels" | The `$emc_includes` line (B1, edit 4 of 4) is missing. |
| Membership Page missing from the Customizer | `inc/customizer-pages.php` edit (B2) not applied. |
| "Card payments are not available on this page yet" | EMC Payments plugin inactive or unlicensed, so the modal bridge never loaded. |
| Page styled but the cards do nothing | `membership.js` not uploaded, or the asset map line (B1, edit 2 of 4) missing. |

**Full rollback:** delete the four new files from Part A and restore the five patched
files from your backup. Nothing else changes — no database tables are created, and no
existing options are modified. Any membership records already taken stay in the
database harmlessly, and the Stripe subscriptions are unaffected either way, so
rolling back the theme does **not** cancel anyone's payments.
