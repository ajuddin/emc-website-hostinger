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
