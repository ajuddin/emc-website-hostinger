<?php
/**
 * EMC Theme — theme-only AJAX handlers.
 *
 * Payment and Stripe handlers live in the separate EMC Payments plugin.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Issue a fresh nonce for one of the public form actions.
 *
 * Event, membership, gift aid, and volunteering pages are served from
 * LiteSpeed's full-page cache for logged-out visitors (see
 * emc_disable_logged_in_page_cache() in inc/performance.php), so a nonce
 * baked into the cached HTML at render time can outlive WordPress's
 * ~12-24 hour nonce lifetime before that cache entry is next purged. Each
 * form's JS calls this uncached admin-ajax endpoint right before submitting
 * so it always has a currently-valid nonce, regardless of page cache age.
 */
function emc_ajax_fresh_nonce() {
    $allowed_actions = array( 'emc_nonce', 'emc_membership', 'emc_event_registration' );
    $nonce_action    = isset( $_POST['nonce_action'] ) ? sanitize_key( wp_unslash( $_POST['nonce_action'] ) ) : '';

    if ( ! in_array( $nonce_action, $allowed_actions, true ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid request.', 'emc-theme' ) ), 400 );
    }

    wp_send_json_success( array( 'nonce' => wp_create_nonce( $nonce_action ) ) );
}
add_action( 'wp_ajax_emc_fresh_nonce', 'emc_ajax_fresh_nonce' );
add_action( 'wp_ajax_nopriv_emc_fresh_nonce', 'emc_ajax_fresh_nonce' );

/**
 * Legacy Gift Aid declaration email handler.
 *
 * The full Gift Aid records feature is implemented in inc/gift-aid.php; this
 * action remains for compatibility with forms using the original AJAX action.
 */
function emc_handle_gift_aid() {
    check_ajax_referer( 'emc_nonce', 'nonce' );

    $first    = sanitize_text_field( $_POST['first'] ?? '' );
    $last     = sanitize_text_field( $_POST['last'] ?? '' );
    $email    = sanitize_email( $_POST['email'] ?? '' );
    $address  = sanitize_text_field( $_POST['address'] ?? '' );
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
    $sent = wp_mail(
        $to,
        $subject,
        $body,
        array( 'Content-Type: text/html; charset=UTF-8' )
    );

    if ( $sent ) {
        wp_send_json_success( array( 'message' => __( 'Declaration received. Jazakallahu Khayran!', 'emc-theme' ) ) );
    }

    wp_send_json_error( array( 'message' => __( 'Submission failed. Please email us directly.', 'emc-theme' ) ) );
}
add_action( 'wp_ajax_emc_gift_aid', 'emc_handle_gift_aid' );
add_action( 'wp_ajax_nopriv_emc_gift_aid', 'emc_handle_gift_aid' );
