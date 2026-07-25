=== EMC Payment License Server ===
Contributors: emc
Tags: licensing, stripe, subscriptions
Requires at least: 6.2
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later

Self-hosted monthly, yearly and one-time lifetime license management for the EMC payment module.

== Installation ==

1. Install this plugin on a separate WordPress license-server site.
2. Activate it.
3. Open EMC Licenses to issue monthly, yearly or lifetime licenses.
4. Optionally configure Stripe under EMC Licenses > Settings.
5. Put [emc_license_checkout] on a public page to sell licenses.

See PAYMENT-LICENSING.md in the EMC theme repository for full setup and deployment checks.

== License API ==

The theme uses:

* POST /wp-json/emc-license/v1/activate
* POST /wp-json/emc-license/v1/validate
* POST /wp-json/emc-license/v1/deactivate

Stripe sends signed events to:

* POST /wp-json/emc-license/v1/stripe-webhook
