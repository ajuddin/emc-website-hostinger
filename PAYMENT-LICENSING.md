# EMC Payment Module Licensing

## What is included

This repository now contains two connected parts:

1. **Theme license client** — built into the EMC theme in `inc/payment-license.php`.
2. **License server plugin** — in `license-server/emc-payment-license-server/`.

The solution is self-hosted and does not require a paid WordPress licensing plugin. A separate WordPress installation acts as the license server.

When a license is missing, revoked, unreachable beyond the grace period, or expired:

- the Donate, Campaign and Ramadan donation templates show **Payment License Required**;
- Stripe.js and the donation JavaScript are not loaded by those templates;
- one-off PaymentIntent creation is rejected server-side;
- recurring Stripe subscription creation is rejected server-side;
- payment/subscription recording AJAX calls are rejected server-side;
- Stripe keys remain stored but cannot be used by the public payment forms.

Stripe webhooks intentionally remain available. This preserves records for payments and subscriptions that already existed before a license expired.

## Costs

The licensing code itself is free and self-hosted.

Possible external costs still remain:

- hosting and a domain/subdomain for the license-server WordPress site;
- Stripe's normal transaction fees when selling licenses;
- email delivery service if the WordPress server cannot reliably send mail.

## Install the license server

Use a separate WordPress installation, ideally on a subdomain such as:

`https://licenses.yourdomain.com`

Do not install the license server on a customer's licensed site.

1. Zip the `emc-payment-license-server` directory, or use the ready ZIP generated in `license-server/`.
2. In the license-server WordPress admin, open **Plugins → Add New → Upload Plugin**.
3. Install and activate **EMC Payment License Server**.
4. Open **EMC Licenses** to create manual licenses.
5. Open **EMC Licenses → Settings** to configure optional Stripe license sales.

The plugin creates its own license and activation database tables during activation.

## Start for free with manual licenses

The least expensive starting option requires no license checkout:

1. Open **EMC Licenses** on the license-server site.
2. Enter the customer's name and email.
3. Select Monthly, Yearly or One-time/Lifetime and set the allowed number of sites.
4. Select **Email the license key** if WordPress email is working.
5. Select **Create License**.

The generated key is shown once in the admin notice and remains visible to license-server administrators in the license list.

You can extend, revoke, restore, resend or reset the activated sites for a license from the same screen.

## Sell Monthly, Yearly and Lifetime licenses with Stripe

No paid WordPress extension is required.

1. In your Stripe Dashboard, create a product for the EMC Payment Module.
2. Create three prices as needed:
   - Monthly — recurring monthly price.
   - Yearly — recurring yearly price.
   - One-time/Lifetime — one-time price, not recurring.
3. Copy each `price_...` ID.
4. On the license server, open **EMC Licenses → Settings**.
5. Enter the license-selling Stripe account's secret key.
6. Enter the Monthly, Yearly and One-time/Lifetime Price IDs. Plans without a Price ID stay hidden from checkout.
7. Enter the fallback subscription duration. Normally Stripe's current subscription period controls the monthly/yearly expiry.
8. Create a WordPress page such as **Buy License** and add:

   `[emc_license_checkout]`

9. In Stripe, add this webhook endpoint:

   `https://licenses.yourdomain.com/wp-json/emc-license/v1/stripe-webhook`

10. Subscribe it to:

    - `checkout.session.completed`
    - `checkout.session.async_payment_succeeded`
    - `invoice.paid`
    - `invoice.payment_succeeded`
    - `customer.subscription.updated`
    - `customer.subscription.deleted`

11. Copy the Stripe `whsec_...` signing secret into **EMC Licenses → Settings**.

After Stripe confirms checkout, the plugin issues the license and emails it to the customer:

- Monthly renewals extend the license to the next monthly subscription period.
- Yearly renewals extend it to the next yearly subscription period.
- One-time/Lifetime payment creates a license with no expiry.
- A cancelled/deleted monthly or yearly subscription expires its license.

Use Stripe test-mode keys and test Prices for all enabled plans before enabling live mode.

## Activate a customer site

On the WordPress site running the EMC theme:

1. Open **Settings → EMC Payment License**.
2. Enter the license-server home URL, without the REST path.
3. Enter the customer's license key.
4. Select **Save License Settings**.
5. Select **Activate on This Site**.

The current state, plan, expiry and last check are shown on that page.

The values can instead be placed in `wp-config.php`:

```php
define( 'EMC_PAYMENT_LICENSE_SERVER', 'https://licenses.yourdomain.com' );
define( 'EMC_PAYMENT_LICENSE_KEY', 'EMC-XXXX-XXXX-XXXX-XXXX' );
```

## Validation behavior

- Successful validation is cached for 12 hours.
- WordPress cron checks twice daily.
- The expiry returned by the server is also enforced locally.
- One-time/Lifetime licenses return no expiry and remain active unless an administrator revokes them.
- A previously valid, unexpired license gets a maximum three-day grace period during a temporary license-server outage.
- The grace period never extends past the actual license expiry.
- One license allows one site by default; the license server can issue a higher activation limit.
- A site must be deactivated before the same one-site license can move to a different installation.

## Important operational behavior

An expired theme license blocks **new** donations and new recurring donation setup. It does not automatically cancel recurring donations already active in the charity's Stripe account. Existing Stripe subscriptions must be cancelled or managed from Stripe if that is commercially required.

Back up the license-server database. License records, expiry dates, Stripe subscription references and site activations live there.

Use HTTPS for both the customer site and the license server.

WordPress `wp_mail()` must be working for automatic key delivery. Use a transactional SMTP provider if normal server email is unreliable.

## Security limitations

This is an enforceable remote license check for normal WordPress installations, but no distributed PHP licensing system is impossible to remove. A customer with full access to the theme source can modify PHP code. Stronger commercial protection would require moving essential payment processing into a hosted service you control or using signed/encrypted commercial licensing infrastructure.

The current implementation still protects the normal user interface and payment endpoints, validates activations remotely, enforces expiry locally, rate-limits public license requests, verifies Stripe webhook signatures and prevents duplicate webhook processing.

## Deployment checks

Before production:

- install the plugin on a staging license server;
- create a short manual test license and activate a staging EMC theme site;
- confirm Donate, Campaign and Ramadan pages work while active;
- expire/revoke the test license and clear the theme site's license transient or select **Check Now**;
- confirm the pages show **Payment License Required**;
- confirm direct AJAX requests return HTTP 403 with `license_required`;
- complete Monthly, Yearly and One-time/Lifetime Stripe test checkouts;
- confirm one license is created and emailed;
- replay the same webhook and confirm it does not create a duplicate;
- test renewal and cancellation webhook events;
- verify WordPress cron and outgoing email.
