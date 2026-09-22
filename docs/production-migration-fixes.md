# Production migration fixes

Steps that must run against the **production** database and server. The code
changes are already in the theme; these cover what deploying files cannot do.

Run everything on `essexmuslimcentre.org`. Take a database backup first —
hPanel → Databases → Backups, or `wp db export pre-fix.sql`.

---

## 1. Single CPT URLs returning 404

Symptom: `/events/quranic-arabic-language-course/` 404s while `/events/` works.

The staging site resolves every CPT route correctly with identical code, so the
theme's `register_post_type()` calls are not at fault. Two things can produce
this on production alone.

### 1a. Stale rewrite rules (most likely)

`emc_maybe_refresh_content_rewrites()` flushed once and recorded a marker in
`wp_options`. A migrated database **carries that marker with it**, so the
destination site believes it has already flushed and never rebuilds its rules.

This is now fixed in code: the marker includes the site host, so production
will flush exactly once on the first request after deploy. To force it
immediately rather than waiting:

```bash
wp rewrite flush --hard
```

Then confirm the rules exist:

```bash
wp rewrite list --format=table | grep -E 'events|service'
```

You should see a rule mapping `events/([^/]+)/?$` to
`index.php?emc_event=$matches[1]`. If that line is absent, continue to 1b.

### 1b. A Page whose slug collides with the CPT base

`emc_event` registers with `'rewrite' => array( 'slug' => 'events' )`. If
production also has a **Page** with the slug `events`, WordPress matches the
page rule first, looks for a child page named after the event, finds none, and
404s — which is exactly the reported pattern of "listing works, singles don't".

Check:

```bash
wp post list --post_type=page --name=events --fields=ID,post_title,post_name
```

Empty result means no collision; skip ahead. If it returns a row, the page and
the CPT archive are fighting over `/events/`. Decide which owns that URL:

- **CPT archive owns it** — trash or rename the page, then `wp rewrite flush --hard`.
  `archive-emc_event.php` already renders the listing, and single event URLs
  start working with no redirects.
- **Page owns it** — the CPT base has to move, which changes every single-event
  URL and would need redirects. Not recommended given the no-redirects constraint.

Repeat both checks for `service` if service singles also 404:

```bash
wp post list --post_type=page --name=service --fields=ID,post_title,post_name
```

### 1c. Leftovers from the old host

```bash
wp option get home
wp option get siteurl
wp search-replace 'old-domain.tld' 'essexmuslimcentre.org' --dry-run --all-tables
```

Drop `--dry-run` once the reported counts look right.

---

## 2. Contact email: `admin@` → `info@`

The theme's hardcoded defaults are updated, and
`emc_migrate_contact_email()` rewrites the saved Customizer value on first load
— but only where it is still the old address, so a deliberately different
value is left alone.

Anything stored in **page or post content** is outside the theme's reach and
needs a search-replace:

```bash
wp search-replace 'admin@essexmuslimcentre.org' 'info@essexmuslimcentre.org' \
  --all-tables --precise --dry-run
```

Review the table-by-table counts, then run it for real without `--dry-run`.

`--precise` is important: it makes WP-CLI unserialize PHP arrays rather than
string-replace inside them, which is what keeps Customizer settings and widget
options from being corrupted by the length prefixes in serialized data.

Verify:

```bash
wp option get theme_mods_emc-theme | grep -i email
wp post list --post_type=page --s='admin@essexmuslimcentre.org' --fields=ID,post_title
```

Then clear any page cache and check the footer.

---

## 3. Friday Prayer showing the wrong day

This was wrong data, not wrong code. `date_i18n( 'D', … )` was faithfully
rendering the stored date, and that date (`2026-06-20`) genuinely falls on a
Saturday.

Events now carry a **Recurring Day** field in Event Details.
`emc_migrate_recurring_event_days()` sets Friday Prayer to Friday on first
load, and the front end resolves recurring events to their next occurrence, so
the day never drifts again.

Confirm after deploying:

```bash
wp post meta get $(wp post list --post_type=emc_event --name=friday-prayer-jumuah --field=ID) _emc_event_day
```

Expected output: `friday`.

For any other weekly event, set the day in the editor: **Events → edit →
Event Details → Recurring Day**. Leave Start Date blank for a purely weekly
event; if both are set, the recurring day wins.

---

## Order of operations

1. Deploy the theme (merge to `main`, let auto-deploy run).
2. `wp rewrite flush --hard`.
3. Check 1b for a colliding `events` page; resolve if present.
4. Run the email search-replace.
5. Spot-check a single event URL, the footer email, and `/events/`.
