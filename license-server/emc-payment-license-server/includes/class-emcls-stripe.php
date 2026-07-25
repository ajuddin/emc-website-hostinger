<?php

defined( 'ABSPATH' ) || exit;

class EMCLS_Stripe {
    public static function init() {
        add_shortcode( 'emc_license_checkout', array( __CLASS__, 'checkout_shortcode' ) );
        add_action( 'admin_post_emcls_create_checkout', array( __CLASS__, 'create_checkout' ) );
        add_action( 'admin_post_nopriv_emcls_create_checkout', array( __CLASS__, 'create_checkout' ) );
        add_action( 'rest_api_init', array( __CLASS__, 'register_webhook' ) );
    }

    public static function settings() {
        $settings = wp_parse_args(
            get_option( 'emcls_settings', array() ),
            array(
                'stripe_secret_key'     => '',
                'stripe_price_id'       => '',
                'stripe_monthly_price_id'  => '',
                'stripe_yearly_price_id'   => '',
                'stripe_lifetime_price_id' => '',
                'stripe_webhook_secret' => '',
                'default_days'       => 365,
                'plan_name'          => 'EMC Payment Module',
            )
        );

        // Backward compatibility: the original single recurring price becomes monthly.
        if ( empty( $settings['stripe_monthly_price_id'] ) && ! empty( $settings['stripe_price_id'] ) ) {
            $settings['stripe_monthly_price_id'] = $settings['stripe_price_id'];
        }

        return $settings;
    }

    public static function plans( $settings = null ) {
        $settings = is_array( $settings ) ? $settings : self::settings();
        $base     = sanitize_text_field( $settings['plan_name'] );

        return array(
            'monthly' => array(
                'label'    => sprintf( __( '%s — Monthly', 'emc-license-server' ), $base ),
                'short'    => __( 'Monthly', 'emc-license-server' ),
                'mode'     => 'subscription',
                'price_id' => sanitize_text_field( $settings['stripe_monthly_price_id'] ),
            ),
            'yearly' => array(
                'label'    => sprintf( __( '%s — Yearly', 'emc-license-server' ), $base ),
                'short'    => __( 'Yearly', 'emc-license-server' ),
                'mode'     => 'subscription',
                'price_id' => sanitize_text_field( $settings['stripe_yearly_price_id'] ),
            ),
            'lifetime' => array(
                'label'    => sprintf( __( '%s — One-time / Lifetime', 'emc-license-server' ), $base ),
                'short'    => __( 'One-time / Lifetime', 'emc-license-server' ),
                'mode'     => 'payment',
                'price_id' => sanitize_text_field( $settings['stripe_lifetime_price_id'] ),
            ),
        );
    }

    private static function stripe_request( $method, $endpoint, $body = array() ) {
        $settings = self::settings();
        if ( empty( $settings['stripe_secret_key'] ) ) {
            return new WP_Error( 'emcls_stripe_missing', __( 'Stripe is not configured.', 'emc-license-server' ) );
        }

        $url  = 'https://api.stripe.com/v1/' . ltrim( $endpoint, '/' );
        $args = array(
            'timeout' => 25,
            'headers' => array(
                'Authorization' => 'Bearer ' . trim( $settings['stripe_secret_key'] ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
        );

        if ( 'POST' === strtoupper( $method ) ) {
            $args['body'] = $body;
            $response = wp_remote_post( $url, $args );
        } else {
            $response = wp_remote_get( $url, $args );
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $data ) ) {
            return new WP_Error( 'emcls_stripe_response', __( 'Stripe returned an invalid response.', 'emc-license-server' ) );
        }
        if ( wp_remote_retrieve_response_code( $response ) >= 400 || ! empty( $data['error'] ) ) {
            return new WP_Error( 'emcls_stripe_error', sanitize_text_field( $data['error']['message'] ?? __( 'Stripe rejected the request.', 'emc-license-server' ) ) );
        }
        return $data;
    }

    public static function checkout_shortcode() {
        $settings = self::settings();
        $plans     = self::plans( $settings );
        $available_plans = array_filter(
            $plans,
            function ( $plan ) {
                return ! empty( $plan['price_id'] );
            }
        );
        $success  = isset( $_GET['emc_license_checkout'] ) && 'success' === sanitize_key( $_GET['emc_license_checkout'] );
        $error    = isset( $_GET['emc_license_error'] ) ? sanitize_text_field( wp_unslash( $_GET['emc_license_error'] ) ) : '';

        ob_start();
        ?>
        <div class="emcls-checkout" style="max-width:620px;margin:32px auto;padding:32px;border:1px solid #dce7e5;border-radius:16px;background:#fff;">
            <?php if ( $success ) : ?>
                <h2><?php esc_html_e( 'Thank you', 'emc-license-server' ); ?></h2>
                <p><?php esc_html_e( 'Your payment is being confirmed. Your license key will be emailed after Stripe confirms it.', 'emc-license-server' ); ?></p>
            <?php else : ?>
                <h2><?php echo esc_html( $settings['plan_name'] ); ?></h2>
                <p><?php esc_html_e( 'Choose a monthly, yearly or one-time lifetime license for the EMC donation and Stripe module.', 'emc-license-server' ); ?></p>
                <?php if ( $error ) : ?>
                    <p style="color:#b32d2e;"><?php echo esc_html( $error ); ?></p>
                <?php endif; ?>
                <?php if ( empty( $settings['stripe_secret_key'] ) || empty( $available_plans ) ) : ?>
                    <?php if ( current_user_can( 'manage_options' ) ) : ?>
                        <p><?php esc_html_e( 'Configure the Stripe secret key and at least one plan Price ID in EMC Licenses → Settings.', 'emc-license-server' ); ?></p>
                    <?php else : ?>
                        <p><?php esc_html_e( 'License checkout is temporarily unavailable.', 'emc-license-server' ); ?></p>
                    <?php endif; ?>
                <?php else : ?>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="emcls_create_checkout">
                        <?php wp_nonce_field( 'emcls_create_checkout' ); ?>
                        <fieldset style="border:0;padding:0;margin:0 0 20px;">
                            <legend><strong><?php esc_html_e( 'License plan', 'emc-license-server' ); ?></strong></legend>
                            <div style="display:grid;gap:10px;margin-top:10px;">
                                <?php $first_plan = true; ?>
                                <?php foreach ( $available_plans as $plan_key => $plan ) : ?>
                                    <label style="display:flex;align-items:center;gap:10px;padding:14px;border:1px solid #dce7e5;border-radius:10px;">
                                        <input required type="radio" name="license_plan" value="<?php echo esc_attr( $plan_key ); ?>" <?php checked( $first_plan ); ?>>
                                        <strong><?php echo esc_html( $plan['short'] ); ?></strong>
                                    </label>
                                    <?php $first_plan = false; ?>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                        <p>
                            <label for="emcls_name"><strong><?php esc_html_e( 'Name', 'emc-license-server' ); ?></strong></label><br>
                            <input required style="width:100%;" type="text" id="emcls_name" name="customer_name" autocomplete="name">
                        </p>
                        <p>
                            <label for="emcls_email"><strong><?php esc_html_e( 'Email', 'emc-license-server' ); ?></strong></label><br>
                            <input required style="width:100%;" type="email" id="emcls_email" name="customer_email" autocomplete="email">
                        </p>
                        <p style="display:none !important;" aria-hidden="true">
                            <label>Company <input type="text" name="company_website" tabindex="-1" autocomplete="off"></label>
                        </p>
                        <button type="submit"><?php esc_html_e( 'Continue to Secure Checkout', 'emc-license-server' ); ?></button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function checkout_return_url() {
        $referer = wp_get_referer();
        return wp_validate_redirect( $referer, home_url( '/' ) );
    }

    private static function checkout_error( $message ) {
        wp_safe_redirect(
            add_query_arg(
                'emc_license_error',
                sanitize_text_field( $message ),
                self::checkout_return_url()
            )
        );
        exit;
    }

    public static function create_checkout() {
        check_admin_referer( 'emcls_create_checkout' );
        if ( ! empty( $_POST['company_website'] ) ) {
            self::checkout_error( __( 'Invalid submission.', 'emc-license-server' ) );
        }

        $email = sanitize_email( $_POST['customer_email'] ?? '' );
        $name  = sanitize_text_field( $_POST['customer_name'] ?? '' );
        $license_plan = sanitize_key( $_POST['license_plan'] ?? '' );
        if ( ! is_email( $email ) || ! $name ) {
            self::checkout_error( __( 'Enter a valid name and email address.', 'emc-license-server' ) );
        }

        $ip       = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
        $rate_key = 'emcls_checkout_' . md5( $ip );
        if ( get_transient( $rate_key ) ) {
            self::checkout_error( __( 'Please wait before trying again.', 'emc-license-server' ) );
        }
        set_transient( $rate_key, 1, 20 );

        $settings   = self::settings();
        $plans      = self::plans( $settings );
        $return_url = self::checkout_return_url();
        if ( empty( $plans[ $license_plan ]['price_id'] ) ) {
            self::checkout_error( __( 'License checkout is not configured.', 'emc-license-server' ) );
        }
        $selected_plan = $plans[ $license_plan ];

        $success_url = add_query_arg( 'emc_license_checkout', 'success', $return_url );
        $success_url .= ( false === strpos( $success_url, '?' ) ? '?' : '&' ) . 'session_id={CHECKOUT_SESSION_ID}';

        $checkout_body = array(
                'mode'                                      => $selected_plan['mode'],
                'payment_method_types[0]'                   => 'card',
                'line_items[0][price]'                      => $selected_plan['price_id'],
                'line_items[0][quantity]'                   => 1,
                'customer_email'                            => $email,
                'client_reference_id'                       => wp_generate_uuid4(),
                'metadata[emcls_customer_name]'             => $name,
                'metadata[emcls_customer_email]'            => $email,
                'metadata[emcls_license_type]'               => $license_plan,
                'success_url' => $success_url,
                'cancel_url' => add_query_arg( 'emc_license_checkout', 'cancelled', $return_url ),
        );

        if ( 'subscription' === $selected_plan['mode'] ) {
            $checkout_body['subscription_data[metadata][emcls_customer_name]']  = $name;
            $checkout_body['subscription_data[metadata][emcls_customer_email]'] = $email;
            $checkout_body['subscription_data[metadata][emcls_license_type]']   = $license_plan;
        } else {
            $checkout_body['payment_intent_data[metadata][emcls_customer_name]']  = $name;
            $checkout_body['payment_intent_data[metadata][emcls_customer_email]'] = $email;
            $checkout_body['payment_intent_data[metadata][emcls_license_type]']   = $license_plan;
        }

        $session = self::stripe_request(
            'POST',
            'checkout/sessions',
            $checkout_body
        );

        if ( is_wp_error( $session ) || empty( $session['url'] ) ) {
            self::checkout_error( is_wp_error( $session ) ? $session->get_error_message() : __( 'Could not start checkout.', 'emc-license-server' ) );
        }

        wp_redirect( esc_url_raw( $session['url'] ) );
        exit;
    }

    public static function register_webhook() {
        register_rest_route(
            'emc-license/v1',
            '/stripe-webhook',
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'webhook' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    private static function verify_signature( $payload, $header ) {
        $settings = self::settings();
        $secret   = trim( $settings['stripe_webhook_secret'] );
        if ( ! $secret || ! $header ) {
            return false;
        }

        $timestamp = 0;
        $signatures = array();
        foreach ( explode( ',', $header ) as $part ) {
            $pair = array_map( 'trim', explode( '=', $part, 2 ) );
            if ( 2 !== count( $pair ) ) {
                continue;
            }
            if ( 't' === $pair[0] ) {
                $timestamp = absint( $pair[1] );
            } elseif ( 'v1' === $pair[0] ) {
                $signatures[] = $pair[1];
            }
        }
        if ( ! $timestamp || abs( time() - $timestamp ) > 300 ) {
            return false;
        }
        $expected = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );
        foreach ( $signatures as $signature ) {
            if ( hash_equals( $expected, $signature ) ) {
                return true;
            }
        }
        return false;
    }

    private static function subscription_id_from_invoice( $invoice ) {
        if ( ! empty( $invoice['subscription'] ) ) {
            return sanitize_text_field( $invoice['subscription'] );
        }
        return sanitize_text_field( $invoice['parent']['subscription_details']['subscription'] ?? '' );
    }

    private static function subscription_period_end( $subscription, $license_type = 'monthly' ) {
        $end = absint( $subscription['current_period_end'] ?? 0 );
        if ( ! $end ) {
            $end = absint( $subscription['items']['data'][0]['current_period_end'] ?? 0 );
        }
        if ( ! $end ) {
            $settings = self::settings();
            $fallback_days = 'yearly' === $license_type ? 366 : ( 'monthly' === $license_type ? 31 : max( 1, absint( $settings['default_days'] ) ) );
            $end = time() + ( $fallback_days * DAY_IN_SECONDS );
        }
        return $end;
    }

    private static function license_status_from_subscription( $subscription ) {
        $stripe_status = sanitize_key( $subscription['status'] ?? '' );
        if ( in_array( $stripe_status, array( 'active', 'trialing', 'past_due' ), true ) ) {
            return 'active';
        }
        return 'expired';
    }

    private static function sync_subscription( $subscription, $session = array() ) {
        $subscription_id = sanitize_text_field( $subscription['id'] ?? '' );
        if ( ! $subscription_id ) {
            return new WP_Error( 'emcls_subscription_missing', __( 'Missing Stripe subscription ID.', 'emc-license-server' ) );
        }

        $metadata = is_array( $subscription['metadata'] ?? null ) ? $subscription['metadata'] : array();
        $license_type = sanitize_key( $metadata['emcls_license_type'] ?? ( $session['metadata']['emcls_license_type'] ?? 'monthly' ) );
        if ( ! in_array( $license_type, array( 'monthly', 'yearly' ), true ) ) {
            $license_type = 'monthly';
        }
        $email    = sanitize_email( $metadata['emcls_customer_email'] ?? ( $session['customer_details']['email'] ?? ( $session['customer_email'] ?? '' ) ) );
        $name     = sanitize_text_field( $metadata['emcls_customer_name'] ?? ( $session['metadata']['emcls_customer_name'] ?? ( $session['customer_details']['name'] ?? '' ) ) );
        $customer = sanitize_text_field( $subscription['customer'] ?? ( $session['customer'] ?? '' ) );
        $status   = self::license_status_from_subscription( $subscription );
        $expires  = gmdate( 'Y-m-d H:i:s', self::subscription_period_end( $subscription, $license_type ) );
        $settings = self::settings();
        $plans    = self::plans( $settings );
        $license  = EMCLS_Repository::find_by_subscription( $subscription_id );
        $created  = false;

        if ( ! $license ) {
            if ( ! $email ) {
                return new WP_Error( 'emcls_email_missing', __( 'Could not issue a license because the customer email is missing.', 'emc-license-server' ) );
            }
            $license = EMCLS_Repository::create(
                array(
                    'email'                  => $email,
                    'customer_name'          => $name,
                    'status'                 => $status,
                    'plan'                   => $plans[ $license_type ]['label'],
                    'license_type'           => $license_type,
                    'expires_at'             => $expires,
                    'max_activations'        => 1,
                    'stripe_customer_id'     => $customer,
                    'stripe_subscription_id' => $subscription_id,
                )
            );
            $created = ! is_wp_error( $license );
        } else {
            $next_status = 'revoked' === $license['status'] ? 'revoked' : $status;
            EMCLS_Repository::update(
                $license['id'],
                array(
                    'email'              => $email ?: $license['email'],
                    'customer_name'      => $name ?: $license['customer_name'],
                    'status'             => $next_status,
                    'plan'               => $plans[ $license_type ]['label'],
                    'license_type'       => $license_type,
                    'expires_at'         => $expires,
                    'stripe_customer_id' => $customer,
                )
            );
            $license = EMCLS_Repository::find_by_id( $license['id'] );
        }

        if ( is_wp_error( $license ) ) {
            return $license;
        }
        if ( $created ) {
            self::send_license_email( $license );
        }
        return $license;
    }

    private static function sync_lifetime_payment( $session ) {
        $session_id = sanitize_text_field( $session['id'] ?? '' );
        if ( ! $session_id || 'paid' !== ( $session['payment_status'] ?? '' ) ) {
            return new WP_Error( 'emcls_lifetime_unpaid', __( 'The one-time license payment is not confirmed.', 'emc-license-server' ) );
        }

        $existing = EMCLS_Repository::find_by_checkout_session( $session_id );
        if ( $existing ) {
            return $existing;
        }

        $metadata = is_array( $session['metadata'] ?? null ) ? $session['metadata'] : array();
        $email    = sanitize_email( $metadata['emcls_customer_email'] ?? ( $session['customer_details']['email'] ?? ( $session['customer_email'] ?? '' ) ) );
        $name     = sanitize_text_field( $metadata['emcls_customer_name'] ?? ( $session['customer_details']['name'] ?? '' ) );
        if ( ! $email ) {
            return new WP_Error( 'emcls_email_missing', __( 'Could not issue a license because the customer email is missing.', 'emc-license-server' ) );
        }

        $settings = self::settings();
        $plans    = self::plans( $settings );
        $license  = EMCLS_Repository::create(
            array(
                'email'                      => $email,
                'customer_name'              => $name,
                'status'                     => 'active',
                'plan'                       => $plans['lifetime']['label'],
                'license_type'               => 'lifetime',
                'expires_at'                 => null,
                'max_activations'            => 1,
                'stripe_customer_id'         => sanitize_text_field( $session['customer'] ?? '' ),
                'stripe_payment_id'          => sanitize_text_field( $session['payment_intent'] ?? '' ),
                'stripe_checkout_session_id' => $session_id,
            )
        );
        if ( ! is_wp_error( $license ) ) {
            self::send_license_email( $license );
        }
        return $license;
    }

    public static function send_license_email( $license ) {
        if ( empty( $license['email'] ) ) {
            return false;
        }
        $subject = __( 'Your EMC Payment Module license', 'emc-license-server' );
        $expiry  = $license['expires_at'] ? $license['expires_at'] . ' UTC' : __( 'Never (lifetime license)', 'emc-license-server' );
        $body    = sprintf(
            "Hello %s,\n\nYour EMC Payment Module license is ready.\n\nLicense type: %s\nLicense key: %s\nExpires: %s\nActivation limit: %d site(s)\n\nInstall the EMC theme, then open Settings > EMC Payment License and enter this license server URL:\n%s\n\nKeep this key private.",
            $license['customer_name'] ?: 'there',
            ucfirst( $license['license_type'] ?? 'monthly' ),
            $license['license_key'],
            $expiry,
            absint( $license['max_activations'] ),
            home_url( '/' )
        );
        return wp_mail( $license['email'], $subject, $body );
    }

    public static function webhook( WP_REST_Request $request ) {
        $payload = $request->get_body();
        if ( ! self::verify_signature( $payload, $request->get_header( 'stripe-signature' ) ) ) {
            return new WP_REST_Response( array( 'error' => 'Invalid signature.' ), 400 );
        }
        $event = json_decode( $payload, true );
        if ( ! is_array( $event ) || empty( $event['id'] ) || empty( $event['type'] ) ) {
            return new WP_REST_Response( array( 'error' => 'Invalid event.' ), 400 );
        }

        $event_key = 'emcls_evt_' . md5( $event['id'] );
        if ( get_transient( $event_key ) ) {
            return new WP_REST_Response( array( 'received' => true, 'duplicate' => true ), 200 );
        }

        $object = $event['data']['object'] ?? array();
        $result = true;

        if ( 'checkout.session.completed' === $event['type'] && 'subscription' === ( $object['mode'] ?? '' ) ) {
            $subscription_id = sanitize_text_field( $object['subscription'] ?? '' );
            $subscription = self::stripe_request( 'GET', 'subscriptions/' . rawurlencode( $subscription_id ) );
            $result = is_wp_error( $subscription ) ? $subscription : self::sync_subscription( $subscription, $object );
        } elseif (
            in_array( $event['type'], array( 'checkout.session.completed', 'checkout.session.async_payment_succeeded' ), true )
            && 'payment' === ( $object['mode'] ?? '' )
            && 'lifetime' === ( $object['metadata']['emcls_license_type'] ?? '' )
        ) {
            $result = 'paid' === ( $object['payment_status'] ?? '' )
                ? self::sync_lifetime_payment( $object )
                : true;
        } elseif ( 'invoice.paid' === $event['type'] || 'invoice.payment_succeeded' === $event['type'] ) {
            $subscription_id = self::subscription_id_from_invoice( $object );
            if ( $subscription_id ) {
                $subscription = self::stripe_request( 'GET', 'subscriptions/' . rawurlencode( $subscription_id ) );
                $result = is_wp_error( $subscription ) ? $subscription : self::sync_subscription( $subscription );
            }
        } elseif ( in_array( $event['type'], array( 'customer.subscription.updated', 'customer.subscription.deleted' ), true ) ) {
            if ( 'customer.subscription.deleted' === $event['type'] ) {
                $object['status'] = 'canceled';
            }
            $result = self::sync_subscription( $object );
        }

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array( 'error' => $result->get_error_message() ), 500 );
        }

        set_transient( $event_key, 1, 7 * DAY_IN_SECONDS );
        return new WP_REST_Response( array( 'received' => true ), 200 );
    }
}
