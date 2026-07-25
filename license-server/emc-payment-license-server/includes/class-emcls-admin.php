<?php

defined( 'ABSPATH' ) || exit;

class EMCLS_Admin {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'settings' ) );
        add_action( 'admin_post_emcls_create_license', array( __CLASS__, 'create_license' ) );
        add_action( 'admin_post_emcls_license_action', array( __CLASS__, 'license_action' ) );
    }

    public static function menu() {
        add_menu_page(
            __( 'EMC Licenses', 'emc-license-server' ),
            __( 'EMC Licenses', 'emc-license-server' ),
            'manage_options',
            'emcls-licenses',
            array( __CLASS__, 'licenses_page' ),
            'dashicons-lock',
            58
        );
        add_submenu_page(
            'emcls-licenses',
            __( 'License Settings', 'emc-license-server' ),
            __( 'Settings', 'emc-license-server' ),
            'manage_options',
            'emcls-settings',
            array( __CLASS__, 'settings_page' )
        );
    }

    public static function settings() {
        register_setting(
            'emcls_settings_group',
            'emcls_settings',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
            )
        );
    }

    public static function sanitize_settings( $value ) {
        $value = is_array( $value ) ? $value : array();
        return array(
            'stripe_secret_key'     => sanitize_text_field( $value['stripe_secret_key'] ?? '' ),
            'stripe_price_id'       => '',
            'stripe_monthly_price_id'  => sanitize_text_field( $value['stripe_monthly_price_id'] ?? '' ),
            'stripe_yearly_price_id'   => sanitize_text_field( $value['stripe_yearly_price_id'] ?? '' ),
            'stripe_lifetime_price_id' => sanitize_text_field( $value['stripe_lifetime_price_id'] ?? '' ),
            'stripe_webhook_secret' => sanitize_text_field( $value['stripe_webhook_secret'] ?? '' ),
            'default_days'          => max( 1, absint( $value['default_days'] ?? 365 ) ),
            'plan_name'             => sanitize_text_field( $value['plan_name'] ?? 'EMC Payment Module' ),
        );
    }

    public static function create_license() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Not allowed.', 'emc-license-server' ) );
        }
        check_admin_referer( 'emcls_create_license' );
        $license_type = sanitize_key( $_POST['license_type'] ?? 'monthly' );
        if ( ! in_array( $license_type, array( 'monthly', 'yearly', 'lifetime' ), true ) ) {
            $license_type = 'monthly';
        }
        $settings = EMCLS_Stripe::settings();
        $plans    = EMCLS_Stripe::plans( $settings );
        if ( 'lifetime' === $license_type ) {
            $expires_at = null;
        } elseif ( 'yearly' === $license_type ) {
            $expires_at = gmdate( 'Y-m-d H:i:s', strtotime( '+1 year', time() ) );
        } else {
            $expires_at = gmdate( 'Y-m-d H:i:s', strtotime( '+1 month', time() ) );
        }
        $license = EMCLS_Repository::create(
            array(
                'customer_name'   => sanitize_text_field( $_POST['customer_name'] ?? '' ),
                'email'           => sanitize_email( $_POST['email'] ?? '' ),
                'plan'            => $plans[ $license_type ]['label'],
                'license_type'    => $license_type,
                'expires_at'      => $expires_at,
                'max_activations' => max( 1, absint( $_POST['max_activations'] ?? 1 ) ),
            )
        );
        if ( ! is_wp_error( $license ) ) {
            set_transient( 'emcls_created_' . get_current_user_id(), $license['license_key'], MINUTE_IN_SECONDS );
            if ( ! empty( $_POST['email_license'] ) ) {
                EMCLS_Stripe::send_license_email( $license );
            }
        }
        wp_safe_redirect( admin_url( 'admin.php?page=emcls-licenses' ) );
        exit;
    }

    public static function license_action() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Not allowed.', 'emc-license-server' ) );
        }
        check_admin_referer( 'emcls_license_action' );
        $id      = absint( $_POST['license_id'] ?? 0 );
        $action  = sanitize_key( $_POST['license_action'] ?? '' );
        $license = EMCLS_Repository::find_by_id( $id );
        if ( $license ) {
            if ( 'revoke' === $action ) {
                EMCLS_Repository::update( $id, array( 'status' => 'revoked' ) );
            } elseif ( 'restore' === $action ) {
                EMCLS_Repository::update( $id, array( 'status' => 'active' ) );
            } elseif ( 'extend' === $action ) {
                $license_type = sanitize_key( $license['license_type'] ?? 'monthly' );
                $base         = max( time(), strtotime( ( $license['expires_at'] ?: 'now' ) . ' UTC' ) );
                if ( 'lifetime' === $license_type ) {
                    $new_expiry = null;
                } elseif ( 'yearly' === $license_type ) {
                    $new_expiry = gmdate( 'Y-m-d H:i:s', strtotime( '+1 year', $base ) );
                } else {
                    $new_expiry = gmdate( 'Y-m-d H:i:s', strtotime( '+1 month', $base ) );
                }
                EMCLS_Repository::update(
                    $id,
                    array(
                        'status'     => 'active',
                        'expires_at' => $new_expiry,
                    )
                );
            } elseif ( 'email' === $action ) {
                EMCLS_Stripe::send_license_email( $license );
            } elseif ( 'reset_sites' === $action ) {
                EMCLS_Repository::clear_activations( $id );
            }
        }
        wp_safe_redirect( admin_url( 'admin.php?page=emcls-licenses' ) );
        exit;
    }

    public static function licenses_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $created_key = get_transient( 'emcls_created_' . get_current_user_id() );
        if ( $created_key ) {
            delete_transient( 'emcls_created_' . get_current_user_id() );
        }
        $licenses = EMCLS_Repository::list_all();
        $settings = EMCLS_Stripe::settings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'EMC Payment Licenses', 'emc-license-server' ); ?></h1>
            <?php if ( $created_key ) : ?>
                <div class="notice notice-success"><p><?php esc_html_e( 'License created. Copy this key now:', 'emc-license-server' ); ?> <code><?php echo esc_html( $created_key ); ?></code></p></div>
            <?php endif; ?>

            <h2><?php esc_html_e( 'Create a License', 'emc-license-server' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="emcls_create_license">
                <?php wp_nonce_field( 'emcls_create_license' ); ?>
                <table class="form-table" role="presentation">
                    <tr><th><label for="emcls_customer_name"><?php esc_html_e( 'Customer name', 'emc-license-server' ); ?></label></th><td><input class="regular-text" id="emcls_customer_name" name="customer_name" type="text"></td></tr>
                    <tr><th><label for="emcls_email"><?php esc_html_e( 'Email', 'emc-license-server' ); ?></label></th><td><input class="regular-text" id="emcls_email" name="email" type="email"></td></tr>
                    <tr>
                        <th><label for="emcls_license_type"><?php esc_html_e( 'License type', 'emc-license-server' ); ?></label></th>
                        <td>
                            <select id="emcls_license_type" name="license_type">
                                <option value="monthly"><?php esc_html_e( 'Monthly — expires after one month', 'emc-license-server' ); ?></option>
                                <option value="yearly"><?php esc_html_e( 'Yearly — expires after one year', 'emc-license-server' ); ?></option>
                                <option value="lifetime"><?php esc_html_e( 'One-time / Lifetime — no expiry', 'emc-license-server' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr><th><label for="emcls_max"><?php esc_html_e( 'Site activations', 'emc-license-server' ); ?></label></th><td><input id="emcls_max" name="max_activations" type="number" min="1" value="1"></td></tr>
                    <tr><th><?php esc_html_e( 'Delivery', 'emc-license-server' ); ?></th><td><label><input name="email_license" type="checkbox" value="1"> <?php esc_html_e( 'Email the license key to the customer', 'emc-license-server' ); ?></label></td></tr>
                </table>
                <?php submit_button( __( 'Create License', 'emc-license-server' ) ); ?>
            </form>

            <h2><?php esc_html_e( 'Issued Licenses', 'emc-license-server' ); ?></h2>
            <div style="overflow:auto;">
                <table class="widefat striped">
                    <thead><tr>
                        <th><?php esc_html_e( 'Key', 'emc-license-server' ); ?></th>
                        <th><?php esc_html_e( 'Customer', 'emc-license-server' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'emc-license-server' ); ?></th>
                        <th><?php esc_html_e( 'Expires (UTC)', 'emc-license-server' ); ?></th>
                        <th><?php esc_html_e( 'Sites', 'emc-license-server' ); ?></th>
                        <th><?php esc_html_e( 'Billing reference', 'emc-license-server' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'emc-license-server' ); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php if ( ! $licenses ) : ?>
                        <tr><td colspan="7"><?php esc_html_e( 'No licenses yet.', 'emc-license-server' ); ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ( $licenses as $license ) : ?>
                        <?php $license_state = EMCLS_Repository::state( $license ); ?>
                        <tr>
                            <td><code><?php echo esc_html( $license['license_key'] ); ?></code><br><small><?php echo esc_html( $license['plan'] ); ?> (<?php echo esc_html( ucfirst( $license['license_type'] ?? 'monthly' ) ); ?>)</small></td>
                            <td><?php echo esc_html( $license['customer_name'] ?: '—' ); ?><br><a href="mailto:<?php echo esc_attr( $license['email'] ); ?>"><?php echo esc_html( $license['email'] ); ?></a></td>
                            <td><strong><?php echo esc_html( $license_state ); ?></strong></td>
                            <td><?php echo esc_html( $license['expires_at'] ?: __( 'Never', 'emc-license-server' ) ); ?></td>
                            <td><?php echo esc_html( absint( $license['activation_count'] ) . ' / ' . absint( $license['max_activations'] ) ); ?></td>
                            <td><code><?php echo esc_html( $license['stripe_subscription_id'] ?: ( $license['stripe_payment_id'] ?: '—' ) ); ?></code></td>
                            <td>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:4px;flex-wrap:wrap;">
                                    <input type="hidden" name="action" value="emcls_license_action">
                                    <input type="hidden" name="license_id" value="<?php echo esc_attr( $license['id'] ); ?>">
                                    <?php wp_nonce_field( 'emcls_license_action' ); ?>
                                    <button class="button" name="license_action" value="extend"><?php esc_html_e( 'Extend', 'emc-license-server' ); ?></button>
                                    <button class="button" name="license_action" value="email"><?php esc_html_e( 'Email', 'emc-license-server' ); ?></button>
                                    <?php if ( absint( $license['activation_count'] ) > 0 ) : ?>
                                        <button class="button" name="license_action" value="reset_sites" onclick="return confirm('<?php echo esc_js( __( 'Release every activated site for this license?', 'emc-license-server' ) ); ?>');"><?php esc_html_e( 'Reset sites', 'emc-license-server' ); ?></button>
                                    <?php endif; ?>
                                    <?php if ( 'active' === $license_state ) : ?>
                                        <button class="button" name="license_action" value="revoke"><?php esc_html_e( 'Revoke', 'emc-license-server' ); ?></button>
                                    <?php else : ?>
                                        <button class="button" name="license_action" value="restore"><?php esc_html_e( 'Restore', 'emc-license-server' ); ?></button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public static function settings_page() {
        $settings = EMCLS_Stripe::settings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'License Server Settings', 'emc-license-server' ); ?></h1>
            <p><?php esc_html_e( 'The license API itself is free and self-hosted. Stripe charges its normal transaction fees when you sell subscriptions or lifetime licenses.', 'emc-license-server' ); ?></p>
            <form method="post" action="options.php">
                <?php settings_fields( 'emcls_settings_group' ); ?>
                <table class="form-table" role="presentation">
                    <tr><th><label for="emcls_plan_name"><?php esc_html_e( 'Plan name', 'emc-license-server' ); ?></label></th><td><input class="regular-text" id="emcls_plan_name" name="emcls_settings[plan_name]" value="<?php echo esc_attr( $settings['plan_name'] ); ?>"></td></tr>
                    <tr><th><label for="emcls_default_days"><?php esc_html_e( 'Fallback subscription days', 'emc-license-server' ); ?></label></th><td><input id="emcls_default_days" name="emcls_settings[default_days]" type="number" min="1" value="<?php echo esc_attr( $settings['default_days'] ); ?>"><p class="description"><?php esc_html_e( 'Used only if Stripe does not return a subscription period end.', 'emc-license-server' ); ?></p></td></tr>
                    <tr><th><label for="emcls_stripe_secret"><?php esc_html_e( 'Stripe secret key', 'emc-license-server' ); ?></label></th><td><input class="regular-text" id="emcls_stripe_secret" name="emcls_settings[stripe_secret_key]" type="password" value="<?php echo esc_attr( $settings['stripe_secret_key'] ); ?>" autocomplete="off" placeholder="sk_live_..."></td></tr>
                    <tr><th><label for="emcls_monthly_price"><?php esc_html_e( 'Monthly recurring Price ID', 'emc-license-server' ); ?></label></th><td><input class="regular-text" id="emcls_monthly_price" name="emcls_settings[stripe_monthly_price_id]" value="<?php echo esc_attr( $settings['stripe_monthly_price_id'] ); ?>" placeholder="price_..."></td></tr>
                    <tr><th><label for="emcls_yearly_price"><?php esc_html_e( 'Yearly recurring Price ID', 'emc-license-server' ); ?></label></th><td><input class="regular-text" id="emcls_yearly_price" name="emcls_settings[stripe_yearly_price_id]" value="<?php echo esc_attr( $settings['stripe_yearly_price_id'] ); ?>" placeholder="price_..."></td></tr>
                    <tr><th><label for="emcls_lifetime_price"><?php esc_html_e( 'One-time / Lifetime Price ID', 'emc-license-server' ); ?></label></th><td><input class="regular-text" id="emcls_lifetime_price" name="emcls_settings[stripe_lifetime_price_id]" value="<?php echo esc_attr( $settings['stripe_lifetime_price_id'] ); ?>" placeholder="price_..."><p class="description"><?php esc_html_e( 'Monthly and yearly prices must be recurring. The lifetime price must be a one-time Stripe price.', 'emc-license-server' ); ?></p></td></tr>
                    <tr><th><label for="emcls_webhook_secret"><?php esc_html_e( 'Stripe webhook secret', 'emc-license-server' ); ?></label></th><td><input class="regular-text" id="emcls_webhook_secret" name="emcls_settings[stripe_webhook_secret]" type="password" value="<?php echo esc_attr( $settings['stripe_webhook_secret'] ); ?>" autocomplete="off" placeholder="whsec_..."><p class="description"><?php esc_html_e( 'Webhook URL:', 'emc-license-server' ); ?> <code><?php echo esc_html( rest_url( 'emc-license/v1/stripe-webhook' ) ); ?></code></p></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2><?php esc_html_e( 'Checkout Page', 'emc-license-server' ); ?></h2>
            <p><?php esc_html_e( 'Add this shortcode to any public WordPress page:', 'emc-license-server' ); ?> <code>[emc_license_checkout]</code></p>
            <p><?php esc_html_e( 'Subscribe the webhook to checkout.session.completed, checkout.session.async_payment_succeeded, invoice.paid, invoice.payment_succeeded, customer.subscription.updated and customer.subscription.deleted.', 'emc-license-server' ); ?></p>
        </div>
        <?php
    }
}
