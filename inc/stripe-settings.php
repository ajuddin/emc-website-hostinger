<?php
/**
 * EMC Theme — inc/stripe-settings.php
 * WordPress admin settings page for Stripe API keys.
 * Keys are stored encrypted in wp_options and NEVER committed to version control.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------
   Keys are configured in ONE of two ways (priority order):
   1. Constants in wp-config.php  ← recommended (gitignored, secure)
        define( 'EMC_STRIPE_PUB_KEY',    'pk_test_...' );
        define( 'EMC_STRIPE_SECRET_KEY', 'sk_test_...' );
   2. WP Admin > Settings > EMC Stripe  (stored in wp_options)
   The functions emc_stripe_pub_key() and emc_stripe_secret_key()
   in ajax-handlers.php read from constants first, then wp_options.
------------------------------------------------------------------ */

/* ------------------------------------------------------------------
   Register the settings page under Settings > EMC Stripe
------------------------------------------------------------------ */
add_action( 'admin_menu', function () {
    add_options_page(
        'EMC Stripe Settings',
        'EMC Stripe',
        'manage_options',
        'emc-stripe-settings',
        'emc_stripe_settings_page'
    );

    add_options_page(
        'EMC Donations',
        'EMC Donations',
        'manage_options',
        'emc-donations',
        'emc_donations_page'
    );
} );

add_action( 'admin_init', function () {
    register_setting( 'emc_stripe_settings', 'emc_stripe_pub_key',    array( 'sanitize_callback' => 'sanitize_text_field' ) );
    register_setting( 'emc_stripe_settings', 'emc_stripe_secret_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
    register_setting( 'emc_stripe_settings', 'emc_stripe_webhook_secret', array( 'sanitize_callback' => 'sanitize_text_field' ) );
} );

function emc_stripe_settings_page() {
    $pub    = esc_attr( get_option( 'emc_stripe_pub_key',    '' ) );
    $secret = get_option( 'emc_stripe_secret_key', '' );
    $webhook_secret = get_option( 'emc_stripe_webhook_secret', '' );
    $masked = $secret ? substr( $secret, 0, 8 ) . str_repeat( '*', max( 0, strlen( $secret ) - 12 ) ) . substr( $secret, -4 ) : '';
    $webhook_masked = $webhook_secret ? substr( $webhook_secret, 0, 8 ) . str_repeat( '*', max( 0, strlen( $webhook_secret ) - 12 ) ) . substr( $webhook_secret, -4 ) : '';
    $webhook_url = rest_url( 'emc/v1/stripe-webhook' );
    ?>
    <div class="wrap">
        <h1><span class="dashicons dashicons-shield-alt" style="color:#2aaca0;font-size:28px;margin-right:8px;"></span> EMC Stripe Settings</h1>
        <p style="color:#666;max-width:600px;">
            Enter your Stripe API keys below. These are stored securely in the WordPress database and are never committed to version control.
            For production, you can also define them in <code>wp-config.php</code>:<br>
            <code>define( 'EMC_STRIPE_SECRET_KEY', 'sk_live_...' );</code><br>
            <code>define( 'EMC_STRIPE_PUB_KEY', 'pk_live_...' );</code>
        </p>
        <?php if ( settings_errors( 'emc_stripe_settings' ) ) settings_errors( 'emc_stripe_settings' ); ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'emc_stripe_settings' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="emc_stripe_pub_key">Publishable Key</label></th>
                    <td>
                        <input type="text" id="emc_stripe_pub_key" name="emc_stripe_pub_key"
                               value="<?php echo $pub; ?>" class="regular-text" placeholder="pk_test_... or pk_live_...">
                        <p class="description">Safe to share — used in the browser JavaScript.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="emc_stripe_secret_key">Secret Key</label></th>
                    <td>
                        <input type="password" id="emc_stripe_secret_key" name="emc_stripe_secret_key"
                               value="<?php echo esc_attr( $secret ); ?>"
                               class="regular-text" placeholder="sk_test_... or sk_live_...">
                        <?php if ( $masked ) : ?>
                        <p class="description">Current key: <code><?php echo esc_html( $masked ); ?></code></p>
                        <?php endif; ?>
                        <p class="description" style="color:#d63638;">Never share this key publicly.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="emc_stripe_webhook_secret">Webhook Signing Secret</label></th>
                    <td>
                        <input type="password" id="emc_stripe_webhook_secret" name="emc_stripe_webhook_secret"
                               value="<?php echo esc_attr( $webhook_secret ); ?>"
                               class="regular-text" placeholder="whsec_...">
                        <?php if ( $webhook_masked ) : ?>
                        <p class="description">Current webhook secret: <code><?php echo esc_html( $webhook_masked ); ?></code></p>
                        <?php endif; ?>
                        <p class="description">Stripe webhook URL: <code><?php echo esc_html( $webhook_url ); ?></code></p>
                        <p class="description">In Stripe, send <code>invoice.payment_succeeded</code> and <code>customer.subscription.updated</code> so recurring donations and subscription statuses stay in sync.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Save Stripe Keys' ); ?>
        </form>

        <?php if ( emc_stripe_pub_key() && emc_stripe_secret_key() && emc_payment_license_is_active() ) : ?>
        <div class="notice notice-success" style="max-width:600px;">
            <p>Stripe keys are configured and the payment module license is active.</p>
        </div>
        <?php elseif ( ! emc_payment_license_is_active() ) : ?>
        <div class="notice notice-warning" style="max-width:600px;">
            <p><strong>License required.</strong> Stripe keys are retained, but new payment requests remain locked until the payment module license is active.</p>
        </div>
        <?php else : ?>
        <div class="notice notice-warning" style="max-width:600px;">
            <p>Stripe keys are not yet set. The donate button will not process payments until both keys are entered.</p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

function emc_donations_page() {
    $donations = get_option( 'emc_donations_log', array() );
    if ( ! is_array( $donations ) ) {
        $donations = array();
    }
    $donations = array_reverse( $donations );

    $subscriptions = get_option( 'emc_subscriptions_log', array() );
    if ( ! is_array( $subscriptions ) ) {
        $subscriptions = array();
    }
    $subscriptions = array_reverse( $subscriptions );
    ?>
    <div class="wrap">
        <h1>EMC Donations</h1>
        <p>Scheduled subscriptions are shown when setup is confirmed. Payments are shown after Stripe confirms money received.</p>

        <h2>Scheduled Subscriptions</h2>
        <?php if ( empty( $subscriptions ) ) : ?>
            <div class="notice notice-info">
                <p>No subscription setups have been recorded in WordPress yet.</p>
            </div>
        <?php else : ?>
            <table class="widefat striped" style="margin-bottom:24px;">
                <thead>
                    <tr>
                        <th>Setup Date</th>
                        <th>Amount</th>
                        <th>Frequency</th>
                        <th>Start Date</th>
                        <th>Runs</th>
                        <th>Fund</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Address Line 1</th>
                        <th>Postcode</th>
                        <th>Status</th>
                        <th>Stripe Subscription</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $subscriptions as $subscription ) : ?>
                        <tr>
                            <td><?php echo esc_html( $subscription['date'] ?? '' ); ?></td>
                            <td>&pound;<?php echo esc_html( $subscription['amount'] ?? '0.00' ); ?></td>
                            <td><?php echo esc_html( ucfirst( $subscription['frequency'] ?? '' ) ); ?></td>
                            <td><?php echo esc_html( $subscription['start_date'] ?? '' ); ?></td>
                            <td><?php echo esc_html( ! empty( $subscription['occurrences'] ) ? $subscription['occurrences'] : 'Ongoing' ); ?></td>
                            <td><?php echo esc_html( $subscription['fund'] ?? '' ); ?></td>
                            <td><?php echo esc_html( $subscription['name'] ?? 'Anonymous' ); ?></td>
                            <td>
                                <?php if ( ! empty( $subscription['email'] ) ) : ?>
                                    <a href="mailto:<?php echo esc_attr( $subscription['email'] ); ?>"><?php echo esc_html( $subscription['email'] ); ?></a>
                                <?php else : ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( $subscription['address'] ?? '' ); ?></td>
                            <td><?php echo esc_html( $subscription['postcode'] ?? '' ); ?></td>
                            <td><?php echo esc_html( ucfirst( $subscription['status'] ?? '' ) ); ?></td>
                            <td><code><?php echo esc_html( $subscription['subscription_id'] ?? '' ); ?></code></td>
                            <td><?php echo esc_html( $subscription['message'] ?? '' ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2>Payments Received</h2>

        <?php if ( empty( $donations ) ) : ?>
            <div class="notice notice-info">
                <p>No donations have been recorded in WordPress yet.</p>
            </div>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Fund</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Address Line 1</th>
                        <th>Postcode</th>
                        <th>Gift Aid</th>
                        <th>Stripe Ref</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $donations as $donation ) : ?>
                        <tr>
                            <td><?php echo esc_html( $donation['date'] ?? '' ); ?></td>
                            <td><?php echo esc_html( '£' . ( $donation['amount'] ?? '0.00' ) ); ?></td>
                            <td><?php echo esc_html( $donation['fund'] ?? '' ); ?></td>
                            <td><?php echo esc_html( $donation['name'] ?? 'Anonymous' ); ?></td>
                            <td>
                                <?php if ( ! empty( $donation['email'] ) ) : ?>
                                    <a href="mailto:<?php echo esc_attr( $donation['email'] ); ?>"><?php echo esc_html( $donation['email'] ); ?></a>
                                <?php else : ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( $donation['address'] ?? '' ); ?></td>
                            <td><?php echo esc_html( $donation['postcode'] ?? '' ); ?></td>
                            <td><?php echo ! empty( $donation['gift_aid'] ) ? 'Yes' : 'No'; ?></td>
                            <td><code><?php echo esc_html( $donation['pi_id'] ?? '' ); ?></code></td>
                            <td><?php echo esc_html( $donation['message'] ?? '' ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

/* ------------------------------------------------------------------
   Admin notice if keys are missing (on donate-related pages)
------------------------------------------------------------------ */
add_action( 'admin_notices', function () {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( emc_stripe_secret_key() && emc_stripe_pub_key() ) return;
    $url = admin_url( 'options-general.php?page=emc-stripe-settings' );
    echo '<div class="notice notice-warning is-dismissible"><p>'
       . '🔑 <strong>EMC Stripe:</strong> Stripe API keys are not configured. '
       . '<a href="' . esc_url( $url ) . '">Add your keys here</a> to enable online donations.'
       . '</p></div>';
} );
