<?php
/**
 * Daily sync of actual Stripe-collected regular giving totals.
 *
 * emc_donations_log (written by the EMC Payments plugin) only records a
 * subscription's first charge, not later renewals, so a page-load
 * calculation can only estimate the true total from elapsed billing
 * periods (see emc_membership_regular_giving_raised() in inc/membership.php).
 *
 * This asks Stripe directly, once a day, for the real paid-invoice total
 * per subscription and stores it for the page to read. No API calls happen
 * on a visitor's page load.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

define( 'EMC_REGULAR_GIVING_SYNC_HOOK', 'emc_regular_giving_stripe_sync' );

/**
 * Schedule the daily sync, and run it once immediately if it never has.
 *
 * The immediate run means a newly deployed site shows a real figure right
 * away instead of only the estimate for up to a day.
 */
function emc_schedule_regular_giving_sync() {
    if ( ! wp_next_scheduled( EMC_REGULAR_GIVING_SYNC_HOOK ) ) {
        wp_schedule_event( time(), 'daily', EMC_REGULAR_GIVING_SYNC_HOOK );
    }
    if ( ! get_option( 'emc_regular_giving_stripe_synced_at' ) && ! wp_next_scheduled( 'emc_regular_giving_stripe_sync_now' ) ) {
        wp_schedule_single_event( time() + 30, 'emc_regular_giving_stripe_sync_now' );
    }
}
add_action( 'init', 'emc_schedule_regular_giving_sync' );
add_action( 'emc_regular_giving_stripe_sync_now', 'emc_run_regular_giving_stripe_sync' );
add_action( EMC_REGULAR_GIVING_SYNC_HOOK, 'emc_run_regular_giving_stripe_sync' );

/** Stop the sync if this theme is ever switched away from. */
function emc_unschedule_regular_giving_sync() {
    $timestamp = wp_next_scheduled( EMC_REGULAR_GIVING_SYNC_HOOK );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, EMC_REGULAR_GIVING_SYNC_HOOK );
    }
    $single = wp_next_scheduled( 'emc_regular_giving_stripe_sync_now' );
    if ( $single ) {
        wp_unschedule_event( $single, 'emc_regular_giving_stripe_sync_now' );
    }
}
add_action( 'switch_theme', 'emc_unschedule_regular_giving_sync' );

/**
 * Total paid, from Stripe directly, for one subscription since a cutoff.
 *
 * @param string $subscription_id Stripe subscription ID.
 * @param int    $since_ts        Unix timestamp; invoices created before this are excluded.
 * @return float|WP_Error Amount in pounds, or WP_Error on an API failure.
 */
function emc_stripe_subscription_paid_total( $subscription_id, $since_ts ) {
    if ( ! function_exists( 'emc_stripe_secret_key' ) || ! emc_stripe_secret_key() ) {
        return new WP_Error( 'stripe_unavailable', __( 'Stripe is not configured.', 'emc-theme' ) );
    }

    $total          = 0;
    $starting_after = '';

    do {
        $query = array(
            'subscription' => $subscription_id,
            'status'       => 'paid',
            'limit'        => 100,
            'created'      => array( 'gte' => $since_ts ),
        );
        if ( $starting_after ) {
            $query['starting_after'] = $starting_after;
        }

        $response = wp_remote_get( 'https://api.stripe.com/v1/invoices?' . http_build_query( $query ), array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( emc_stripe_secret_key() . ':' ),
            ),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( 200 !== $code || ! is_array( $body ) ) {
            return new WP_Error( 'stripe_error', $body['error']['message'] ?? __( 'Stripe request failed.', 'emc-theme' ) );
        }

        foreach ( $body['data'] ?? array() as $invoice ) {
            $total += (int) ( $invoice['amount_paid'] ?? 0 );
        }

        $starting_after = ! empty( $body['has_more'] ) && ! empty( $body['data'] )
            ? end( $body['data'] )['id']
            : '';
    } while ( $starting_after );

    return $total / 100;
}

/**
 * Pull real paid totals from Stripe for every regular giver and store them.
 *
 * Per-membership figures are stored individually so a membership whose
 * Stripe request fails this run keeps its last known total instead of
 * dropping out of the grand total.
 */
function emc_run_regular_giving_stripe_sync() {
    $since    = (string) get_theme_mod( 'mem_progress_tracked_since', '2026-10-01' );
    $since_ts = strtotime( $since . ' 00:00:00' ) ?: strtotime( '2026-10-01 00:00:00' );

    $ids = get_posts( array(
        'post_type'      => 'emc_membership',
        'post_status'    => 'private',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );

    $had_error = false;

    foreach ( $ids as $id ) {
        if ( 'pending' === get_post_meta( $id, '_emc_membership_status', true ) ) {
            continue;
        }

        $email = get_post_meta( $id, '_emc_membership_email', true );
        if ( function_exists( 'emc_is_test_donation_record' ) && emc_is_test_donation_record( array( 'email' => $email ) ) ) {
            continue;
        }

        $subscription_id = get_post_meta( $id, '_emc_membership_subscription_id', true );
        if ( ! $subscription_id ) {
            continue;
        }

        $paid = emc_stripe_subscription_paid_total( $subscription_id, $since_ts );
        if ( is_wp_error( $paid ) ) {
            $had_error = true;
            continue;
        }

        update_post_meta( $id, '_emc_membership_stripe_paid_total', $paid );
    }

    $grand_total = 0.0;
    foreach ( $ids as $id ) {
        if ( 'pending' === get_post_meta( $id, '_emc_membership_status', true ) ) {
            continue;
        }
        $grand_total += (float) get_post_meta( $id, '_emc_membership_stripe_paid_total', true );
    }

    update_option( 'emc_regular_giving_stripe_total', $grand_total, false );
    update_option( 'emc_regular_giving_stripe_synced_at', time(), false );
    update_option( 'emc_regular_giving_stripe_sync_had_error', $had_error ? 1 : 0, false );
}

/**
 * Funds raised via regular giving, for display on the public page.
 *
 * Uses the real Stripe-synced total once the daily sync has run at least
 * once; falls back to the elapsed-periods estimate before that (e.g. right
 * after this feature is first deployed).
 *
 * @param string $since Y-m-d cutoff; must match mem_progress_tracked_since.
 * @return float
 */
function emc_regular_giving_raised( $since ) {
    if ( (int) get_option( 'emc_regular_giving_stripe_synced_at', 0 ) > 0 ) {
        return (float) get_option( 'emc_regular_giving_stripe_total', 0 );
    }
    return emc_membership_regular_giving_raised( $since );
}
