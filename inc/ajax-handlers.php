<?php
/**
 * EMC Theme — inc/ajax-handlers.php
 * Server-side AJAX handlers for contact form and newsletter.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
   Contact Form Handler
   ========================================================================== */
function emc_handle_contact_form() {
    check_ajax_referer( 'emc_nonce', 'nonce' );

    $name    = sanitize_text_field( $_POST['name']    ?? '' );
    $email   = sanitize_email(      $_POST['email']   ?? '' );
    $subject = sanitize_text_field( $_POST['subject'] ?? '' );
    $message = sanitize_textarea_field( $_POST['message'] ?? '' );

    if ( ! $name || ! is_email( $email ) || ! $message ) {
        wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'emc-theme' ) ) );
    }

    $to      = get_theme_mod( 'emc_admin_email', get_option( 'admin_email' ) );
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . esc_html( $name ) . ' <' . $email . '>',
        'Reply-To: ' . $email,
    );

    $body  = '<h2>' . esc_html( $subject ) . '</h2>';
    $body .= '<p><strong>Name:</strong> ' . esc_html( $name ) . '</p>';
    $body .= '<p><strong>Email:</strong> ' . esc_html( $email ) . '</p>';
    $body .= '<p><strong>Message:</strong><br>' . nl2br( esc_html( $message ) ) . '</p>';

    $sent = wp_mail( $to, 'EMC Website Enquiry: ' . $subject, $body, $headers );

    if ( $sent ) {
        wp_send_json_success( array( 'message' => __( 'Message sent successfully. We will be in touch shortly.', 'emc-theme' ) ) );
    } else {
        wp_send_json_error( array( 'message' => __( 'Sorry, your message could not be sent. Please email us directly.', 'emc-theme' ) ) );
    }
}
add_action( 'wp_ajax_emc_contact',             'emc_handle_contact_form' );
add_action( 'wp_ajax_nopriv_emc_contact',      'emc_handle_contact_form' );
// Phase 10 JS uses 'emc_contact_form' — alias to same handler
add_action( 'wp_ajax_emc_contact_form',        'emc_handle_contact_form' );
add_action( 'wp_ajax_nopriv_emc_contact_form', 'emc_handle_contact_form' );


/* ==========================================================================
   Newsletter Subscribe Handler
   ========================================================================== */
function emc_handle_newsletter() {
    check_ajax_referer( 'emc_nonce', 'nonce' );

    $email = sanitize_email( $_POST['email'] ?? '' );

    if ( ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'emc-theme' ) ) );
    }

    /**
     * TODO (Phase 2+): integrate with Mailchimp or equivalent.
     * For now, email the admin and log the subscriber.
     */
    $to      = get_option( 'admin_email' );
    $subject = 'New Newsletter Subscriber — EMC Website';
    $body    = '<p>A new visitor has subscribed to the newsletter: <strong>' . esc_html( $email ) . '</strong></p>';
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    wp_mail( $to, $subject, $body, $headers );

    wp_send_json_success( array( 'message' => __( "You're subscribed! Jazakallahu Khayran.", 'emc-theme' ) ) );
}
add_action( 'wp_ajax_emc_newsletter',             'emc_handle_newsletter' );
add_action( 'wp_ajax_nopriv_emc_newsletter',      'emc_handle_newsletter' );
// Phase 10 JS uses 'emc_newsletter_subscribe' — alias to same handler
add_action( 'wp_ajax_emc_newsletter_subscribe',        'emc_handle_newsletter' );
add_action( 'wp_ajax_nopriv_emc_newsletter_subscribe', 'emc_handle_newsletter' );


/* ==========================================================================
   Gift Aid Declaration Handler
   ========================================================================== */
function emc_handle_gift_aid() {
    check_ajax_referer( 'emc_nonce', 'nonce' );

    $first    = sanitize_text_field( $_POST['first']    ?? '' );
    $last     = sanitize_text_field( $_POST['last']     ?? '' );
    $email    = sanitize_email(      $_POST['email']    ?? '' );
    $address  = sanitize_text_field( $_POST['address']  ?? '' );
    $postcode = sanitize_text_field( $_POST['postcode'] ?? '' );

    if ( ! $first || ! $last || ! is_email( $email ) || ! $address || ! $postcode ) {
        wp_send_json_error( array( 'message' => __( 'Please complete all required fields.', 'emc-theme' ) ) );
    }

    $to      = get_theme_mod( 'emc_admin_email', get_option( 'admin_email' ) );
    $subject = 'New Gift Aid Declaration — ' . $first . ' ' . $last;
    $body    = '<h2>New Gift Aid Declaration</h2>'
             . '<p><strong>Name:</strong> ' . esc_html( $first . ' ' . $last ) . '</p>'
             . '<p><strong>Email:</strong> ' . esc_html( $email ) . '</p>'
             . '<p><strong>Address:</strong> ' . esc_html( $address ) . ', ' . esc_html( $postcode ) . '</p>'
             . '<p><strong>Date Submitted:</strong> ' . current_time( 'Y-m-d H:i:s' ) . '</p>';
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    $sent = wp_mail( $to, $subject, $body, $headers );

    if ( $sent ) {
        wp_send_json_success( array( 'message' => __( 'Declaration received. Jazakallahu Khayran!', 'emc-theme' ) ) );
    } else {
        wp_send_json_error( array( 'message' => __( 'Submission failed. Please email us directly.', 'emc-theme' ) ) );
    }
}
add_action( 'wp_ajax_emc_gift_aid',        'emc_handle_gift_aid' );
add_action( 'wp_ajax_nopriv_emc_gift_aid', 'emc_handle_gift_aid' );

/* ==========================================================================
   Stripe Configuration Helpers
   Keys are stored in WordPress options (set via WP Admin > Settings > EMC Stripe).
   For production, override in wp-config.php:
       define( 'EMC_STRIPE_SECRET_KEY', 'sk_live_...' );
       define( 'EMC_STRIPE_PUB_KEY',    'pk_live_...' );
   ========================================================================== */
function emc_stripe_secret_key() {
    if ( defined( 'EMC_STRIPE_SECRET_KEY' ) && EMC_STRIPE_SECRET_KEY ) {
        return trim( EMC_STRIPE_SECRET_KEY );
    }
    return trim( get_option( 'emc_stripe_secret_key', '' ) );
}
function emc_stripe_pub_key() {
    if ( defined( 'EMC_STRIPE_PUB_KEY' ) && EMC_STRIPE_PUB_KEY ) {
        return trim( EMC_STRIPE_PUB_KEY );
    }
    return trim( get_option( 'emc_stripe_pub_key', '' ) );
}
function emc_stripe_webhook_secret() {
    if ( defined( 'EMC_STRIPE_WEBHOOK_SECRET' ) && EMC_STRIPE_WEBHOOK_SECRET ) {
        return trim( EMC_STRIPE_WEBHOOK_SECRET );
    }
    return trim( get_option( 'emc_stripe_webhook_secret', '' ) );
}

function emc_stripe_request( $method, $endpoint, $body = array() ) {
    $args = array(
        'headers' => array(
            'Authorization' => 'Bearer ' . emc_stripe_secret_key(),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ),
        'timeout' => 20,
    );

    if ( 'POST' === $method ) {
        $args['body'] = $body;
        $response = wp_remote_post( 'https://api.stripe.com/v1/' . ltrim( $endpoint, '/' ), $args );
    } else {
        $response = wp_remote_get( 'https://api.stripe.com/v1/' . ltrim( $endpoint, '/' ), $args );
    }

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( ! is_array( $data ) ) {
        return new WP_Error( 'emc_stripe_invalid_response', 'Invalid payment gateway response.' );
    }
    if ( ! empty( $data['error'] ) ) {
        return new WP_Error( 'emc_stripe_error', $data['error']['message'] ?? 'A payment error occurred.' );
    }

    return $data;
}

function emc_stripe_frequency_to_recurring( $frequency ) {
    switch ( $frequency ) {
        case 'daily':
            return array( 'interval' => 'day', 'interval_count' => 1, 'label' => 'Daily' );
        case 'weekly':
            return array( 'interval' => 'week', 'interval_count' => 1, 'label' => 'Weekly' );
        case 'quarterly':
            return array( 'interval' => 'month', 'interval_count' => 3, 'label' => 'Quarterly' );
        case 'annually':
            return array( 'interval' => 'year', 'interval_count' => 1, 'label' => 'Annually' );
        case 'monthly':
        default:
            return array( 'interval' => 'month', 'interval_count' => 1, 'label' => 'Monthly' );
    }
}

function emc_stripe_should_start_subscription_now_for_testing( $frequency ) {
    $secret_key = emc_stripe_secret_key();

    return 0 === strpos( $secret_key, 'sk_test_' )
        && in_array( $frequency, array( 'daily', 'monthly' ), true );
}

/* ==========================================================================
   Stripe - Create PaymentIntent
   ========================================================================== */
function emc_stripe_create_intent() {
    check_ajax_referer( 'emc_donate_nonce', 'nonce' );
    if ( ! emc_stripe_secret_key() || ! emc_stripe_pub_key() ) {
        wp_send_json_error( array( 'message' => 'Stripe API keys are not configured. Please add them in WP Admin > Settings > EMC Stripe.' ) );
    }
    $amount = absint( $_POST['amount'] ?? 0 );
    $fund   = sanitize_text_field( $_POST['fund'] ?? 'General Fund' );
    $tab    = sanitize_text_field( $_POST['tab']  ?? 'one-off' );
    $name   = sanitize_text_field( $_POST['name'] ?? '' );
    $email  = sanitize_email( $_POST['email'] ?? '' );
    $address = sanitize_textarea_field( $_POST['address'] ?? '' );
    if ( $amount < 50 ) {
        wp_send_json_error( array( 'message' => 'Minimum donation is £0.50.' ) );
    }
    if ( ! $name || ! is_email( $email ) || ! $address ) {
        wp_send_json_error( array( 'message' => 'Please provide your name, email address and postal address.' ) );
    }

    $response = wp_remote_post( 'https://api.stripe.com/v1/payment_intents', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . emc_stripe_secret_key(),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ),
        'body' => array(
            'amount'                             => $amount,
            'currency'                           => 'gbp',
            'automatic_payment_methods[enabled]' => 'true',
            'description'                        => 'Donation to Essex Muslim Centre - ' . $fund,
            'metadata[fund]'                     => $fund,
            'metadata[tab]'                      => $tab,
            'metadata[source]'                   => 'EMC Website',
            'metadata[donor_name]'               => $name,
            'metadata[donor_email]'              => $email,
            'metadata[donor_address]'            => $address,
            'receipt_email'                       => $email,
        ),
        'timeout' => 20,
    ) );
    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => 'Payment gateway unavailable. Please try again.' ) );
    }
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( ! empty( $body['error'] ) ) {
        wp_send_json_error( array( 'message' => $body['error']['message'] ?? 'A payment error occurred.' ) );
    }
    wp_send_json_success( array( 'client_secret' => $body['client_secret'], 'pi_id' => $body['id'] ) );
}
add_action( 'wp_ajax_emc_stripe_create_intent',        'emc_stripe_create_intent' );
add_action( 'wp_ajax_nopriv_emc_stripe_create_intent', 'emc_stripe_create_intent' );

/* ==========================================================================
   Stripe - Create Recurring Subscription
   ========================================================================== */
function emc_stripe_create_subscription() {
    check_ajax_referer( 'emc_donate_nonce', 'nonce' );
    if ( ! emc_stripe_secret_key() || ! emc_stripe_pub_key() ) {
        wp_send_json_error( array( 'message' => 'Stripe API keys are not configured. Please add them in WP Admin > Settings > EMC Stripe.' ) );
    }

    $amount     = absint( $_POST['amount'] ?? 0 );
    $fund       = sanitize_text_field( $_POST['fund'] ?? 'General Fund' );
    $frequency  = sanitize_key( $_POST['frequency'] ?? 'monthly' );
    $start_date = sanitize_text_field( $_POST['start_date'] ?? '' );
    $occurrences = absint( $_POST['occurrences'] ?? 0 );
    $name       = sanitize_text_field( $_POST['name'] ?? '' );
    $email      = sanitize_email( $_POST['email'] ?? '' );
    $address    = sanitize_textarea_field( $_POST['address'] ?? '' );
    $gift_aid   = filter_var( $_POST['gift_aid'] ?? false, FILTER_VALIDATE_BOOLEAN );
    $message    = sanitize_textarea_field( $_POST['message'] ?? '' );

    if ( $amount < 50 ) {
        wp_send_json_error( array( 'message' => 'Minimum regular donation is £0.50.' ) );
    }

    if ( ! $name || ! is_email( $email ) || ! $address ) {
        wp_send_json_error( array( 'message' => 'Please provide your name, email address and postal address.' ) );
    }

    $recurring = emc_stripe_frequency_to_recurring( $frequency );
    $now_timestamp        = current_time( 'timestamp' );
    $requested_start_date = $start_date;
    $starts_immediately   = emc_stripe_should_start_subscription_now_for_testing( $frequency );

    if ( $starts_immediately ) {
        $start_date = '';
    }

    $customer_body = array(
        'name'             => $name ?: 'EMC donor',
        'metadata[source]' => 'EMC Website',
    );
    if ( is_email( $email ) ) {
        $customer_body['email'] = $email;
    }
    if ( $address ) {
        $customer_body['address[line1]'] = $address;
    }

    $customer = emc_stripe_request( 'POST', 'customers', $customer_body );
    if ( is_wp_error( $customer ) ) {
        wp_send_json_error( array( 'message' => $customer->get_error_message() ) );
    }

    $price = emc_stripe_request( 'POST', 'prices', array(
        'unit_amount'               => $amount,
        'currency'                  => 'gbp',
        'recurring[interval]'       => $recurring['interval'],
        'recurring[interval_count]' => $recurring['interval_count'],
        'product_data[name]'        => 'EMC ' . $recurring['label'] . ' Donation - ' . $fund,
    ) );
    if ( is_wp_error( $price ) ) {
        wp_send_json_error( array( 'message' => $price->get_error_message() ) );
    }

    $subscription_body = array(
        'customer'                                     => $customer['id'],
        'items[0][price]'                              => $price['id'],
        'payment_behavior'                             => 'default_incomplete',
        'payment_settings[save_default_payment_method]' => 'on_subscription',
        'metadata[fund]'                               => $fund,
        'metadata[frequency]'                          => $frequency,
        'metadata[occurrences]'                        => $occurrences,
        'metadata[requested_start_date]'                => $requested_start_date,
        'metadata[test_start_now]'                      => $starts_immediately ? '1' : '0',
        'metadata[source]'                             => 'EMC Website',
        'metadata[donor_name]'                         => $name,
        'metadata[donor_email]'                        => $email,
        'metadata[donor_address]'                      => $address,
        'metadata[gift_aid]'                           => $gift_aid ? '1' : '0',
        'metadata[message]'                            => $message,
        'expand[]'                                     => 'latest_invoice.payment_intent',
    );

    $start_timestamp = $start_date ? strtotime( $start_date . ' 00:00:00' ) : false;
    if ( $start_timestamp && $start_timestamp > current_time( 'timestamp' ) + DAY_IN_SECONDS ) {
        $subscription_body['trial_end'] = $start_timestamp;
        $subscription_body['expand[]']  = 'pending_setup_intent';
    }

    if ( 'daily' === $frequency && $occurrences > 0 ) {
        $billing_start = ( $start_timestamp && $start_timestamp > $now_timestamp ) ? $start_timestamp : $now_timestamp;
        $subscription_body['cancel_at'] = $billing_start + ( $occurrences * DAY_IN_SECONDS );
    }

    $subscription = emc_stripe_request( 'POST', 'subscriptions', $subscription_body );
    if ( is_wp_error( $subscription ) ) {
        wp_send_json_error( array( 'message' => $subscription->get_error_message() ) );
    }

    $client_secret = '';
    $confirm_mode  = 'payment';
    $intent_id     = '';

    if ( ! empty( $subscription['pending_setup_intent']['client_secret'] ) ) {
        $client_secret = $subscription['pending_setup_intent']['client_secret'];
        $intent_id     = $subscription['pending_setup_intent']['id'];
        $confirm_mode  = 'setup';
    } elseif ( ! empty( $subscription['latest_invoice']['payment_intent']['client_secret'] ) ) {
        $client_secret = $subscription['latest_invoice']['payment_intent']['client_secret'];
        $intent_id     = $subscription['latest_invoice']['payment_intent']['id'];
    }

    if ( ! $client_secret ) {
        wp_send_json_error( array( 'message' => 'Could not prepare the subscription payment.' ) );
    }

    wp_send_json_success( array(
        'client_secret'   => $client_secret,
        'pi_id'           => $intent_id,
        'subscription_id' => $subscription['id'],
        'customer_id'     => $customer['id'],
        'confirm_mode'    => $confirm_mode,
        'start_date'      => $start_date ?: date_i18n( 'Y-m-d', $now_timestamp ),
        'start_now'       => $starts_immediately,
    ) );
}
add_action( 'wp_ajax_emc_stripe_create_subscription',        'emc_stripe_create_subscription' );
add_action( 'wp_ajax_nopriv_emc_stripe_create_subscription', 'emc_stripe_create_subscription' );

/* ==========================================================================
   Stripe - Record Successful Donation
   ========================================================================== */
function emc_stripe_record_donation() {
    check_ajax_referer( 'emc_donate_nonce', 'nonce' );
    $pi_id    = sanitize_text_field( $_POST['pi_id']   ?? '' );
    $amount   = absint( $_POST['amount']               ?? 0 );
    $fund     = sanitize_text_field( $_POST['fund']    ?? 'General Fund' );
    $name     = sanitize_text_field( $_POST['name']    ?? '' );
    $email    = sanitize_email(      $_POST['email']   ?? '' );
    $address  = sanitize_textarea_field( $_POST['address'] ?? '' );
    $gift_aid = filter_var( $_POST['gift_aid'] ?? false, FILTER_VALIDATE_BOOLEAN );
    $message  = sanitize_textarea_field( $_POST['message'] ?? '' );
    if ( empty( $pi_id ) ) {
        wp_send_json_error( array( 'message' => 'Invalid payment reference.' ) );
    }
    if ( ! $name || ! is_email( $email ) || ! $address ) {
        wp_send_json_error( array( 'message' => 'Please provide your name, email address and postal address.' ) );
    }
    // Verify with Stripe before recording
    $check   = wp_remote_get( 'https://api.stripe.com/v1/payment_intents/' . urlencode( $pi_id ),
        array( 'headers' => array( 'Authorization' => 'Bearer ' . emc_stripe_secret_key() ), 'timeout' => 15 ) );
    $pi_data = json_decode( wp_remote_retrieve_body( $check ), true );
    if ( empty( $pi_data['status'] ) || $pi_data['status'] !== 'succeeded' ) {
        wp_send_json_error( array( 'message' => 'Payment not confirmed. Contact us if funds were charged.' ) );
    }
    $amount_gbp = number_format( $amount / 100, 2 );
    // Log
    $log   = get_option( 'emc_donations_log', array() );
    $log[] = array( 'pi_id' => $pi_id, 'amount' => $amount_gbp, 'fund' => $fund,
                    'name' => $name ?: 'Anonymous', 'email' => $email, 'address' => $address, 'gift_aid' => $gift_aid,
                    'message' => $message, 'date' => current_time( 'Y-m-d H:i:s' ) );
    update_option( 'emc_donations_log', array_slice( $log, -1000 ) );
    // Admin email
    $tbody = '<h2>New Donation 🎉</h2><table style="font-family:sans-serif;font-size:14px;">'
           . '<tr><td><strong>Amount</strong></td><td>£' . esc_html($amount_gbp) . '</td></tr>'
           . '<tr><td><strong>Fund</strong></td><td>'   . esc_html($fund)        . '</td></tr>'
           . '<tr><td><strong>Donor</strong></td><td>'  . esc_html($name ?: 'Anonymous') . '</td></tr>'
           . '<tr><td><strong>Email</strong></td><td>'  . esc_html($email ?: 'N/A') . '</td></tr>'
           . '<tr><td><strong>Address</strong></td><td>' . nl2br( esc_html($address ?: 'N/A') ) . '</td></tr>'
           . '<tr><td><strong>Gift Aid</strong></td><td>' . ($gift_aid ? 'Yes' : 'No') . '</td></tr>'
           . '<tr><td><strong>Message</strong></td><td>' . esc_html($message ?: '—') . '</td></tr>'
           . '<tr><td><strong>Stripe PI</strong></td><td><code>' . esc_html($pi_id) . '</code></td></tr>'
           . '<tr><td><strong>Date</strong></td><td>'   . current_time('j F Y, H:i') . '</td></tr></table>';
    wp_mail( get_option('admin_email'), 'New Donation - £' . $amount_gbp . ' - ' . $fund,
             $tbody, array('Content-Type: text/html; charset=UTF-8') );
    // Donor receipt
    if ( is_email( $email ) ) {
        $donor_body = '<div style="font-family:sans-serif;max-width:520px;">'
            . '<h2 style="color:#0c7a6d;">JazakAllahu Khayran, ' . esc_html($name ?: 'dear donor') . '!</h2>'
            . '<p>Your donation of <strong>£' . esc_html($amount_gbp) . '</strong> to the <strong>'
            . esc_html($fund) . '</strong> has been received.</p>'
            . '<p style="font-size:12px;color:#999;">Stripe Ref: <code>' . esc_html($pi_id) . '</code></p>'
            . '<p>May Allah reward you abundantly.</p>'
            . '<hr><p style="font-size:11px;color:#aaa;">Essex Muslim Centre · Charity No. '
            . esc_html(get_option('emc_charity_number','1209815')) . '</p></div>';
        wp_mail( $email, 'Thank you for your donation - Essex Muslim Centre',
                 $donor_body, array('Content-Type: text/html; charset=UTF-8') );
    }
    wp_send_json_success( array( 'message' => 'Donation recorded. Jazakallahu Khayran!' ) );
}
add_action( 'wp_ajax_emc_stripe_record',        'emc_stripe_record_donation' );
add_action( 'wp_ajax_nopriv_emc_stripe_record', 'emc_stripe_record_donation' );

/* ==========================================================================
   Stripe - Record Confirmed Subscription Setup
   ========================================================================== */
function emc_stripe_subscription_log_has_ref( $subscription_id ) {
    if ( ! $subscription_id ) {
        return false;
    }

    $log = get_option( 'emc_subscriptions_log', array() );
    if ( ! is_array( $log ) ) {
        return false;
    }

    foreach ( $log as $entry ) {
        if ( ! empty( $entry['subscription_id'] ) && $entry['subscription_id'] === $subscription_id ) {
            return true;
        }
    }

    return false;
}

function emc_stripe_record_subscription_setup() {
    check_ajax_referer( 'emc_donate_nonce', 'nonce' );

    $subscription_id = sanitize_text_field( $_POST['subscription_id'] ?? '' );
    $setup_ref       = sanitize_text_field( $_POST['setup_ref'] ?? '' );
    $amount          = absint( $_POST['amount'] ?? 0 );
    $fund            = sanitize_text_field( $_POST['fund'] ?? 'Recurring Donation' );
    $frequency       = sanitize_key( $_POST['frequency'] ?? 'monthly' );
    $start_date      = sanitize_text_field( $_POST['start_date'] ?? '' );
    $occurrences     = absint( $_POST['occurrences'] ?? 0 );
    $name            = sanitize_text_field( $_POST['name'] ?? '' );
    $email           = sanitize_email( $_POST['email'] ?? '' );
    $address         = sanitize_textarea_field( $_POST['address'] ?? '' );
    $gift_aid        = filter_var( $_POST['gift_aid'] ?? false, FILTER_VALIDATE_BOOLEAN );
    $message         = sanitize_textarea_field( $_POST['message'] ?? '' );

    if ( ! $subscription_id ) {
        wp_send_json_error( array( 'message' => 'Missing subscription reference.' ) );
    }
    if ( ! $name || ! is_email( $email ) || ! $address ) {
        wp_send_json_error( array( 'message' => 'Please provide your name, email address and postal address.' ) );
    }

    if ( emc_stripe_subscription_log_has_ref( $subscription_id ) ) {
        wp_send_json_success( array( 'message' => 'Subscription already recorded.' ) );
    }

    $subscription = emc_stripe_request( 'GET', 'subscriptions/' . rawurlencode( $subscription_id ) );
    if ( is_wp_error( $subscription ) ) {
        wp_send_json_error( array( 'message' => $subscription->get_error_message() ) );
    }

    $status = sanitize_text_field( $subscription['status'] ?? '' );
    if ( in_array( $status, array( 'incomplete', 'incomplete_expired', 'canceled' ), true ) ) {
        wp_send_json_error( array( 'message' => 'Subscription has not been confirmed by Stripe.' ) );
    }

    $metadata = array();
    if ( ! empty( $subscription['metadata'] ) && is_array( $subscription['metadata'] ) ) {
        $metadata = $subscription['metadata'];
    }

    $item_amount = absint( $subscription['items']['data'][0]['price']['unit_amount'] ?? 0 );
    if ( $item_amount > 0 ) {
        $amount = $item_amount;
    }

    if ( ! $start_date && ! empty( $subscription['trial_end'] ) ) {
        $start_date = date_i18n( 'Y-m-d', absint( $subscription['trial_end'] ) );
    }

    $log   = get_option( 'emc_subscriptions_log', array() );
    $log[] = array(
        'subscription_id' => $subscription_id,
        'setup_ref'       => $setup_ref,
        'amount'          => number_format( $amount / 100, 2 ),
        'fund'            => sanitize_text_field( $metadata['fund'] ?? $fund ),
        'frequency'       => sanitize_key( $metadata['frequency'] ?? $frequency ),
        'start_date'      => $start_date,
        'occurrences'     => absint( $metadata['occurrences'] ?? $occurrences ),
        'name'            => sanitize_text_field( $metadata['donor_name'] ?? ( $name ?: 'Anonymous' ) ),
        'email'           => sanitize_email( $metadata['donor_email'] ?? $email ),
        'address'         => sanitize_textarea_field( $metadata['donor_address'] ?? $address ),
        'gift_aid'        => ! empty( $metadata['gift_aid'] ) ? '1' === (string) $metadata['gift_aid'] : $gift_aid,
        'message'         => sanitize_textarea_field( $metadata['message'] ?? $message ),
        'status'          => $status,
        'date'            => current_time( 'Y-m-d H:i:s' ),
    );

    update_option( 'emc_subscriptions_log', array_slice( $log, -1000 ) );
    wp_send_json_success( array( 'message' => 'Subscription setup recorded.' ) );
}
add_action( 'wp_ajax_emc_stripe_record_subscription_setup',        'emc_stripe_record_subscription_setup' );
add_action( 'wp_ajax_nopriv_emc_stripe_record_subscription_setup', 'emc_stripe_record_subscription_setup' );

/* ==========================================================================
   Stripe Webhook - Record Recurring Subscription Invoices
   ========================================================================== */
function emc_stripe_verify_webhook_signature( $payload, $signature_header ) {
    $secret = emc_stripe_webhook_secret();
    if ( ! $secret || ! $signature_header ) {
        return false;
    }

    $timestamp = '';
    $signatures = array();
    foreach ( explode( ',', $signature_header ) as $part ) {
        $pieces = explode( '=', $part, 2 );
        if ( 2 !== count( $pieces ) ) {
            continue;
        }
        if ( 't' === $pieces[0] ) {
            $timestamp = $pieces[1];
        } elseif ( 'v1' === $pieces[0] ) {
            $signatures[] = $pieces[1];
        }
    }

    if ( ! $timestamp || empty( $signatures ) || abs( time() - (int) $timestamp ) > 300 ) {
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

function emc_stripe_donation_log_has_ref( $ref, $invoice_id = '' ) {
    if ( ! $ref && ! $invoice_id ) {
        return false;
    }

    $log = get_option( 'emc_donations_log', array() );
    if ( ! is_array( $log ) ) {
        return false;
    }

    foreach ( $log as $entry ) {
        if ( $ref && ! empty( $entry['pi_id'] ) && $entry['pi_id'] === $ref ) {
            return true;
        }
        if ( $invoice_id && ! empty( $entry['invoice_id'] ) && $entry['invoice_id'] === $invoice_id ) {
            return true;
        }
    }

    return false;
}

function emc_stripe_find_subscription_log_entry( $subscription_id ) {
    if ( ! $subscription_id ) {
        return array();
    }

    $log = get_option( 'emc_subscriptions_log', array() );
    if ( ! is_array( $log ) ) {
        return array();
    }

    foreach ( $log as $entry ) {
        if ( ! empty( $entry['subscription_id'] ) && $entry['subscription_id'] === $subscription_id ) {
            return is_array( $entry ) ? $entry : array();
        }
    }

    return array();
}

function emc_stripe_update_subscription_log_status( $subscription_id, $status = 'active' ) {
    if ( ! $subscription_id ) {
        return;
    }

    $log = get_option( 'emc_subscriptions_log', array() );
    if ( ! is_array( $log ) ) {
        return;
    }

    $changed = false;
    foreach ( $log as &$entry ) {
        if ( ! empty( $entry['subscription_id'] ) && $entry['subscription_id'] === $subscription_id ) {
            $entry['status'] = sanitize_text_field( $status );
            $changed = true;
            break;
        }
    }
    unset( $entry );

    if ( $changed ) {
        update_option( 'emc_subscriptions_log', $log );
    }
}

function emc_stripe_record_subscription_status_update( $subscription ) {
    if ( empty( $subscription['id'] ) || empty( $subscription['status'] ) ) {
        return;
    }

    emc_stripe_update_subscription_log_status(
        sanitize_text_field( $subscription['id'] ),
        sanitize_text_field( $subscription['status'] )
    );
}

function emc_stripe_record_invoice_payment( $invoice ) {
    if ( empty( $invoice['paid'] ) && ( $invoice['status'] ?? '' ) !== 'paid' ) {
        return;
    }

    $subscription_id = '';
    if ( ! empty( $invoice['subscription'] ) ) {
        $subscription_id = is_array( $invoice['subscription'] ) ? ( $invoice['subscription']['id'] ?? '' ) : $invoice['subscription'];
    } elseif ( ! empty( $invoice['parent']['subscription_details']['subscription'] ) ) {
        $subscription_id = $invoice['parent']['subscription_details']['subscription'];
    }

    if ( ! $subscription_id ) {
        return;
    }

    $payment_ref = '';
    if ( ! empty( $invoice['payment_intent'] ) ) {
        $payment_ref = is_array( $invoice['payment_intent'] ) ? ( $invoice['payment_intent']['id'] ?? '' ) : $invoice['payment_intent'];
    } elseif ( ! empty( $invoice['charge'] ) ) {
        $payment_ref = is_array( $invoice['charge'] ) ? ( $invoice['charge']['id'] ?? '' ) : $invoice['charge'];
    }
    $invoice_id = sanitize_text_field( $invoice['id'] ?? '' );
    if ( ! $payment_ref ) {
        $payment_ref = $invoice_id;
    }
    emc_stripe_update_subscription_log_status( $subscription_id, 'active' );
    if ( emc_stripe_donation_log_has_ref( $payment_ref, $invoice_id ) ) {
        return;
    }

    $local_subscription = emc_stripe_find_subscription_log_entry( $subscription_id );
    $subscription = emc_stripe_request( 'GET', 'subscriptions/' . rawurlencode( $subscription_id ) );
    $metadata = array();
    if ( ! empty( $local_subscription ) ) {
        $metadata = array(
            'fund'          => $local_subscription['fund'] ?? '',
            'frequency'     => $local_subscription['frequency'] ?? '',
            'occurrences'   => $local_subscription['occurrences'] ?? '',
            'donor_name'    => $local_subscription['name'] ?? '',
            'donor_email'   => $local_subscription['email'] ?? '',
            'donor_address' => $local_subscription['address'] ?? '',
            'gift_aid'      => ! empty( $local_subscription['gift_aid'] ) ? '1' : '0',
            'message'       => $local_subscription['message'] ?? '',
        );
    }
    if ( is_array( $subscription ) && ! empty( $subscription['metadata'] ) && is_array( $subscription['metadata'] ) ) {
        $metadata = array_merge( $metadata, $subscription['metadata'] );
    }
    if ( ! empty( $invoice['subscription_details']['metadata'] ) && is_array( $invoice['subscription_details']['metadata'] ) ) {
        $metadata = array_merge( $metadata, $invoice['subscription_details']['metadata'] );
    }
    if ( ! empty( $invoice['parent']['subscription_details']['metadata'] ) && is_array( $invoice['parent']['subscription_details']['metadata'] ) ) {
        $metadata = array_merge( $metadata, $invoice['parent']['subscription_details']['metadata'] );
    }

    $customer = array();
    $customer_id = is_array( $subscription ) ? ( $subscription['customer'] ?? '' ) : '';
    if ( $customer_id ) {
        $customer_response = emc_stripe_request( 'GET', 'customers/' . rawurlencode( $customer_id ) );
        if ( is_array( $customer_response ) ) {
            $customer = $customer_response;
        }
    }

    $amount = absint( $invoice['amount_paid'] ?? 0 );
    if ( $amount < 1 ) {
        return;
    }

    $amount_gbp = number_format( $amount / 100, 2 );
    $fund       = sanitize_text_field( $metadata['fund'] ?? 'Recurring Donation' );
    $name       = sanitize_text_field( $metadata['donor_name'] ?? ( $customer['name'] ?? 'Anonymous' ) );
    $email      = sanitize_email( $metadata['donor_email'] ?? ( $invoice['customer_email'] ?? ( $customer['email'] ?? '' ) ) );
    $customer_address = ( ! empty( $customer['address'] ) && is_array( $customer['address'] ) ) ? ( $customer['address']['line1'] ?? '' ) : '';
    $address    = sanitize_textarea_field( $metadata['donor_address'] ?? $customer_address );
    $gift_aid   = ! empty( $metadata['gift_aid'] ) && '1' === (string) $metadata['gift_aid'];
    $message    = sanitize_textarea_field( $metadata['message'] ?? 'Recurring subscription payment' );
    $paid_at    = absint( $invoice['status_transitions']['paid_at'] ?? ( $invoice['created'] ?? 0 ) );

    $log   = get_option( 'emc_donations_log', array() );
    $log[] = array(
        'pi_id'           => sanitize_text_field( $payment_ref ),
        'invoice_id'      => $invoice_id,
        'subscription_id' => sanitize_text_field( $subscription_id ),
        'amount'          => $amount_gbp,
        'fund'            => $fund,
        'name'            => $name ?: 'Anonymous',
        'email'           => $email,
        'address'         => $address,
        'gift_aid'        => $gift_aid,
        'message'         => $message ?: 'Recurring subscription payment',
        'date'            => $paid_at ? date_i18n( 'Y-m-d H:i:s', $paid_at ) : current_time( 'Y-m-d H:i:s' ),
    );
    update_option( 'emc_donations_log', array_slice( $log, -1000 ) );
}

function emc_stripe_webhook( WP_REST_Request $request ) {
    $payload = $request->get_body();
    $signature = $request->get_header( 'stripe-signature' );

    if ( ! emc_stripe_verify_webhook_signature( $payload, $signature ) ) {
        return new WP_REST_Response( array( 'error' => 'Invalid Stripe signature.' ), 400 );
    }

    $event = json_decode( $payload, true );
    if ( ! is_array( $event ) || empty( $event['type'] ) ) {
        return new WP_REST_Response( array( 'error' => 'Invalid Stripe payload.' ), 400 );
    }

    if ( 'invoice.payment_succeeded' === $event['type'] && ! empty( $event['data']['object'] ) ) {
        emc_stripe_record_invoice_payment( $event['data']['object'] );
    } elseif ( in_array( $event['type'], array( 'customer.subscription.updated', 'customer.subscription.deleted' ), true ) && ! empty( $event['data']['object'] ) ) {
        emc_stripe_record_subscription_status_update( $event['data']['object'] );
    }

    return new WP_REST_Response( array( 'received' => true ), 200 );
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'emc/v1', '/stripe-webhook', array(
        'methods'             => 'POST',
        'callback'            => 'emc_stripe_webhook',
        'permission_callback' => '__return_true',
    ) );
} );
