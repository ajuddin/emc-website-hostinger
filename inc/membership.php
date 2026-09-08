<?php
/**
 * Membership levels, applications, and administration.
 *
 * Money is handled entirely by the licensed EMC Payments plugin. This module does
 * not talk to Stripe directly. The public page collects the applicant's details,
 * stores a pending membership record, and then hands the amount over to the
 * plugin's payment bridge — window.emcOpenStripeModal() — with tab "regular" and
 * a monthly frequency, exactly as the Donate page's regular-giving option does.
 *
 * The plugin creates the Stripe subscription and appends a record to its own
 * emc_subscriptions_log option. This module watches that option and completes the
 * matching pending membership, so memberships and the centre's giving schedules
 * never disagree about what was set up.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/** Prefix used on the Stripe "fund" label so membership payments are identifiable. */
const EMC_MEMBERSHIP_FUND_PREFIX = 'Membership';

/**
 * Register a private post type used only as durable membership storage.
 */
function emc_register_membership_type() {
    register_post_type( 'emc_membership', array(
        'labels' => array(
            'name'          => __( 'Memberships', 'emc-theme' ),
            'singular_name' => __( 'Membership', 'emc-theme' ),
        ),
        'public'              => false,
        'show_ui'             => false,
        'show_in_rest'        => false,
        'exclude_from_search' => true,
        'supports'            => array( 'title' ),
    ) );
}
add_action( 'init', 'emc_register_membership_type' );

/* ==========================================================================
   Membership levels
   ========================================================================== */

/**
 * The three membership levels.
 *
 * Keys are stable identifiers stored on every record and encoded into the Stripe
 * fund label, so they must not be renamed once the page is live. Names, monthly
 * amounts and descriptions are Customizer-editable; the set of levels and their
 * dome colours are fixed by the page design.
 *
 * @return array<string,array>
 */
function emc_membership_level_defaults() {
    return array(
        'supporters' => array(
            'order'  => 1,
            'name'   => __( 'Supporters', 'emc-theme' ),
            'amount' => 10,
            'colour' => '#5a9e8e',
            'desc'   => __( 'For those who want to be part of keeping EMC open, welcoming and alive. Supporters help sustain the everyday essentials — the prayers that continue quietly, the doors that remain open, and the care that is always available. A simple but powerful way to share in the reward of maintaining the House of Allah.', 'emc-theme' ),
        ),
        'companions' => array(
            'order'  => 2,
            'name'   => __( 'Companions', 'emc-theme' ),
            'amount' => 30,
            'colour' => '#2d7a6e',
            'desc'   => __( 'For those who feel closely connected to EMC and want to walk alongside its work. Companions provide the stability that allows EMC to plan, deliver and serve with confidence. Through regular support, they help ensure that learning, pastoral care and community services are there not just when needed most, but all year round.', 'emc-theme' ),
        ),
        'custodians' => array(
            'order'  => 3,
            'name'   => __( 'Custodians', 'emc-theme' ),
            'amount' => 100,
            'colour' => '#1a3c2a',
            'desc'   => __( 'For those who wish to carry a deeper responsibility for the future of EMC. Custodians help safeguard the long-term strength of the centre, ensuring it remains resilient in all circumstances. Their heartfelt support protects not only what exists today, but what EMC will offer to future generations.', 'emc-theme' ),
        ),
    );
}

/**
 * Membership levels with the administrator's saved names, amounts and copy applied.
 *
 * @return array<string,array>
 */
function emc_membership_levels() {
    $levels = array();

    foreach ( emc_membership_level_defaults() as $key => $default ) {
        $amount = round( (float) get_theme_mod( 'mem_level_' . $key . '_amount', $default['amount'] ), 2 );
        $amount = $amount > 0 ? min( 10000, $amount ) : 0;
        $name   = sanitize_text_field( emc_acf( 'mem_level_' . $key . '_name', $default['name'] ) );

        $levels[ $key ] = array(
            'key'    => $key,
            'order'  => $default['order'],
            'colour' => $default['colour'],
            'name'   => $name,
            'amount' => $amount,
            'pence'  => (int) round( $amount * 100 ),
            'desc'   => sanitize_textarea_field( emc_acf( 'mem_level_' . $key . '_desc', $default['desc'] ) ),
            'fund'   => EMC_MEMBERSHIP_FUND_PREFIX . ' - ' . $name,
        );
    }

    return $levels;
}

/**
 * Return one membership level by key, or null when it cannot be charged.
 *
 * Stripe will not take a recurring charge below 50p, so an amount under that is
 * treated as unavailable rather than silently collecting nothing.
 *
 * @param string $key Level key.
 * @return array|null
 */
function emc_membership_level( $key ) {
    $levels = emc_membership_levels();
    $level  = $levels[ $key ] ?? null;

    return $level && $level['pence'] >= 50 ? $level : null;
}

/**
 * Whether the EMC Payments plugin is installed, licensed, and able to take money.
 *
 * @return bool
 */
function emc_membership_payments_available() {
    if ( ! function_exists( 'emc_payments_is_available' ) || ! emc_payments_is_available() ) {
        return false;
    }

    return ! function_exists( 'emc_payment_license_is_active' ) || emc_payment_license_is_active();
}

/* ==========================================================================
   The public page
   ========================================================================== */

/**
 * The public Membership page, whether or not it uses the default slug.
 *
 * @return WP_Post|null
 */
function emc_get_membership_page() {
    $page = get_page_by_path( 'membership', OBJECT, 'page' );

    if ( ! $page ) {
        $pages = get_posts( array(
            'post_type'      => 'page',
            'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
            'meta_key'       => '_wp_page_template',
            'meta_value'     => 'template-membership.php',
            'posts_per_page' => 1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ) );
        $page = $pages ? reset( $pages ) : null;
    }

    return $page;
}

/**
 * Canonical Membership page URL.
 *
 * @return string
 */
function emc_get_membership_url() {
    $page = emc_get_membership_page();
    return $page ? get_permalink( $page ) : home_url( '/membership/' );
}

/**
 * Assign the Membership template to an existing Membership page once.
 *
 * Sites created before this template existed already have a Membership page
 * rendering through page.php. This attaches the template for them, and never
 * touches a page where an administrator has already chosen a template.
 */
function emc_assign_membership_page_template() {
    $version = '2026-09-08';
    if ( $version === get_option( 'emc_membership_template_version' ) ) {
        return;
    }
    update_option( 'emc_membership_template_version', $version, false );

    $page = get_page_by_path( 'membership', OBJECT, 'page' );
    if ( ! $page ) {
        return;
    }

    $current = (string) get_post_meta( $page->ID, '_wp_page_template', true );
    if ( '' === $current || 'default' === $current ) {
        update_post_meta( $page->ID, '_wp_page_template', 'template-membership.php' );
    }
}
add_action( 'after_setup_theme', 'emc_assign_membership_page_template', 20 );

/* ==========================================================================
   Application records
   ========================================================================== */

/**
 * Field keys stored against each membership record.
 *
 * @return string[]
 */
function emc_membership_record_fields() {
    return array(
        'first_name', 'last_name', 'email', 'phone', 'address_1', 'address_2',
        'city', 'postcode', 'level_key', 'level_name', 'notes',
    );
}

/**
 * Create the membership record for an application, before payment is attempted.
 *
 * The record starts as "pending" and is completed once the payment plugin reports
 * the subscription. Pending records are deliberately kept even if the applicant
 * abandons the card step, so the centre can follow up.
 *
 * @param array $data Validated applicant data.
 * @return int|WP_Error Post ID.
 */
function emc_create_pending_membership( $data ) {
    $full_name = trim( $data['first_name'] . ' ' . $data['last_name'] );

    $post_id = wp_insert_post( array(
        'post_type'   => 'emc_membership',
        'post_status' => 'private',
        'post_title'  => sprintf( '%s — %s', $full_name ?: __( 'Member', 'emc-theme' ), current_time( 'Y-m-d H:i:s' ) ),
    ), true );

    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }

    foreach ( emc_membership_record_fields() as $field ) {
        update_post_meta( $post_id, '_emc_membership_' . $field, $data[ $field ] ?? '' );
    }

    update_post_meta( $post_id, '_emc_membership_gift_aid', ! empty( $data['gift_aid'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_emc_membership_consent', ! empty( $data['consent'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_emc_membership_submitted_at', current_time( 'mysql' ) );
    update_post_meta( $post_id, '_emc_membership_frequency', 'monthly' );
    update_post_meta( $post_id, '_emc_membership_amount', number_format( ( $data['amount_pence'] ?? 0 ) / 100, 2, '.', '' ) );
    update_post_meta( $post_id, '_emc_membership_status', 'pending' );

    return $post_id;
}

/**
 * Find the most recent pending membership matching a subscription record.
 *
 * @param string $email    Applicant email.
 * @param string $level_key Level key.
 * @return int Post ID, or 0 when there is no match.
 */
function emc_find_pending_membership( $email, $level_key ) {
    $matches = get_posts( array(
        'post_type'      => 'emc_membership',
        'post_status'    => 'private',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => array(
            array( 'key' => '_emc_membership_status', 'value' => 'pending' ),
            array( 'key' => '_emc_membership_email', 'value' => $email ),
            array( 'key' => '_emc_membership_level_key', 'value' => $level_key ),
        ),
    ) );

    return $matches ? (int) $matches[0] : 0;
}

/**
 * Match a membership level to the fund label written by the payments plugin.
 *
 * @param string $fund Fund label, for example "Membership - Companions".
 * @return array|null
 */
function emc_membership_level_from_fund( $fund ) {
    $fund = trim( (string) $fund );

    if ( 0 !== stripos( $fund, EMC_MEMBERSHIP_FUND_PREFIX ) ) {
        return null;
    }

    foreach ( emc_membership_levels() as $level ) {
        if ( 0 === strcasecmp( $fund, $level['fund'] ) ) {
            return $level;
        }
    }

    return null;
}

/**
 * Complete membership records from new EMC Payments subscription log entries.
 *
 * The payments plugin owns the Stripe subscription and appends to
 * emc_subscriptions_log. Watching that option keeps this module out of the
 * payment path entirely while still recording what was actually set up.
 *
 * @param string $option    Option name.
 * @param mixed  $old_value Previous option value.
 * @param mixed  $new_value New option value.
 */
function emc_capture_membership_subscriptions( $option, $old_value, $new_value ) {
    if ( 'emc_subscriptions_log' !== $option || ! is_array( $new_value ) ) {
        return;
    }

    $old_value = is_array( $old_value ) ? $old_value : array();
    $seen      = array();
    foreach ( $old_value as $record ) {
        if ( is_array( $record ) && ! empty( $record['subscription_id'] ) ) {
            $seen[] = $record['subscription_id'];
        }
    }

    foreach ( $new_value as $record ) {
        if ( ! is_array( $record ) || empty( $record['subscription_id'] ) ) {
            continue;
        }
        if ( in_array( $record['subscription_id'], $seen, true ) ) {
            continue;
        }

        $level = emc_membership_level_from_fund( $record['fund'] ?? '' );
        if ( ! $level ) {
            continue; // An ordinary giving schedule, not a membership.
        }

        emc_complete_membership_from_record( $record, $level );
    }
}
add_action( 'updated_option', 'emc_capture_membership_subscriptions', 20, 3 );

/**
 * Handle the very first subscription record on a fresh installation.
 *
 * @param string $option Option name.
 * @param mixed  $value  Option value.
 */
function emc_capture_first_membership_subscription( $option, $value ) {
    emc_capture_membership_subscriptions( $option, array(), $value );
}
add_action( 'added_option', 'emc_capture_first_membership_subscription', 20, 2 );

/**
 * Complete, or create, the membership record for one subscription log entry.
 *
 * @param array $record Subscription log record written by the payments plugin.
 * @param array $level  Matched membership level.
 */
function emc_complete_membership_from_record( $record, $level ) {
    $subscription_id = sanitize_text_field( $record['subscription_id'] );
    $email           = sanitize_email( $record['email'] ?? '' );

    // Never process the same subscription twice.
    $existing = get_posts( array(
        'post_type'      => 'emc_membership',
        'post_status'    => 'private',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_key'       => '_emc_membership_subscription_id',
        'meta_value'     => $subscription_id,
    ) );
    if ( $existing ) {
        return;
    }

    $post_id = $email ? emc_find_pending_membership( $email, $level['key'] ) : 0;

    /*
     * No pending record means the applicant reached the payment modal by another
     * route, or their session was lost. Create the record from the log entry so a
     * paying member is never missing from the Memberships screen.
     */
    if ( ! $post_id ) {
        $name  = trim( (string) ( $record['name'] ?? '' ) );
        $parts = preg_split( '/\s+/', $name, 2 );

        $post_id = emc_create_pending_membership( array(
            'first_name' => sanitize_text_field( $parts[0] ?? '' ),
            'last_name'  => sanitize_text_field( $parts[1] ?? '' ),
            'email'      => $email,
            'phone'      => '',
            'address_1'  => sanitize_text_field( $record['address'] ?? '' ),
            'address_2'  => '',
            'city'       => '',
            'postcode'   => sanitize_text_field( $record['postcode'] ?? '' ),
            'level_key'  => $level['key'],
            'level_name' => $level['name'],
            'notes'      => sanitize_textarea_field( $record['message'] ?? '' ),
            'gift_aid'   => ! empty( $record['gift_aid'] ) && '0' !== (string) $record['gift_aid'],
            'consent'    => true,
        ) );

        if ( is_wp_error( $post_id ) ) {
            return;
        }
    }

    $amount = isset( $record['amount'] ) ? number_format( (float) $record['amount'], 2, '.', '' ) : number_format( $level['pence'] / 100, 2, '.', '' );

    update_post_meta( $post_id, '_emc_membership_subscription_id', $subscription_id );
    update_post_meta( $post_id, '_emc_membership_amount', $amount );
    update_post_meta( $post_id, '_emc_membership_frequency', sanitize_text_field( $record['frequency'] ?? 'monthly' ) );
    update_post_meta( $post_id, '_emc_membership_start_date', sanitize_text_field( $record['start_date'] ?? current_time( 'Y-m-d' ) ) );
    update_post_meta( $post_id, '_emc_membership_status', sanitize_text_field( $record['status'] ?? 'active' ) );
    update_post_meta( $post_id, '_emc_membership_confirmed_at', current_time( 'mysql' ) );

    emc_notify_new_membership( $post_id, $level, $amount, $subscription_id );
}

/**
 * Email the administrators and the new member.
 *
 * @param int    $post_id         Membership record.
 * @param array  $level           Membership level.
 * @param string $amount          Monthly amount, formatted.
 * @param string $subscription_id Stripe subscription reference.
 */
function emc_notify_new_membership( $post_id, $level, $amount, $subscription_id ) {
    $first = get_post_meta( $post_id, '_emc_membership_first_name', true );
    $last  = get_post_meta( $post_id, '_emc_membership_last_name', true );
    $email = get_post_meta( $post_id, '_emc_membership_email', true );
    $phone = get_post_meta( $post_id, '_emc_membership_phone', true );
    $notes = get_post_meta( $post_id, '_emc_membership_notes', true );

    $address = trim( implode( ', ', array_filter( array(
        get_post_meta( $post_id, '_emc_membership_address_1', true ),
        get_post_meta( $post_id, '_emc_membership_address_2', true ),
        get_post_meta( $post_id, '_emc_membership_city', true ),
        get_post_meta( $post_id, '_emc_membership_postcode', true ),
    ) ) ) );

    if ( function_exists( 'emc_send_form_notification' ) ) {
        $lines = array(
            sprintf( __( 'Name: %s', 'emc-theme' ), trim( $first . ' ' . $last ) ),
            sprintf( __( 'Email: %s', 'emc-theme' ), $email ?: '—' ),
            sprintf( __( 'Phone: %s', 'emc-theme' ), $phone ?: '—' ),
            sprintf( __( 'Level: %s', 'emc-theme' ), $level['name'] ),
            sprintf( __( 'Monthly amount: £%s', 'emc-theme' ), $amount ),
            sprintf( __( 'Address: %s', 'emc-theme' ), $address ?: '—' ),
            sprintf( __( 'Gift Aid: %s', 'emc-theme' ), '1' === get_post_meta( $post_id, '_emc_membership_gift_aid', true ) ? __( 'Yes', 'emc-theme' ) : __( 'No', 'emc-theme' ) ),
            sprintf( __( 'Stripe subscription: %s', 'emc-theme' ), $subscription_id ),
        );
        if ( $notes ) {
            $lines[] = sprintf( __( 'Notes: %s', 'emc-theme' ), $notes );
        }

        emc_send_form_notification(
            'membership',
            sprintf( __( 'New membership: %s', 'emc-theme' ), $level['name'] ),
            implode( "\n", $lines )
        );
    }

    if ( ! is_email( $email ) ) {
        return;
    }

    $body  = sprintf( __( "Assalamu Alaikum %s,\n\nJazak Allahu Khairan for becoming a member of Essex Muslim Centre.", 'emc-theme' ), $first ?: __( 'friend', 'emc-theme' ) );
    $body .= "\n\n" . sprintf( __( 'Membership level: %s', 'emc-theme' ), $level['name'] );
    $body .= "\n" . sprintf( __( 'Monthly amount: £%s', 'emc-theme' ), $amount );
    $body .= "\n" . sprintf( __( 'Reference: %s', 'emc-theme' ), $subscription_id );
    $body .= "\n\n" . __( 'Your membership renews automatically each month. To change the amount or cancel at any time, simply reply to this email and we will take care of it.', 'emc-theme' );

    wp_mail( $email, __( 'Your Essex Muslim Centre membership', 'emc-theme' ), $body );
}

/* ==========================================================================
   Public submission
   ========================================================================== */

/**
 * Validate a submitted membership application.
 *
 * @param array $source Raw $_POST data.
 * @return array|WP_Error
 */
function emc_membership_validate_submission( $source ) {
    $level_key = sanitize_key( wp_unslash( $source['level'] ?? '' ) );
    $level     = emc_membership_level( $level_key );

    if ( ! $level ) {
        return new WP_Error( 'emc_membership_level', __( 'Please choose an available membership level.', 'emc-theme' ) );
    }

    $data = array(
        'first_name' => sanitize_text_field( wp_unslash( $source['first_name'] ?? '' ) ),
        'last_name'  => sanitize_text_field( wp_unslash( $source['last_name'] ?? '' ) ),
        'email'      => sanitize_email( wp_unslash( $source['email'] ?? '' ) ),
        'phone'      => sanitize_text_field( wp_unslash( $source['phone'] ?? '' ) ),
        'address_1'  => sanitize_text_field( wp_unslash( $source['address_1'] ?? '' ) ),
        'address_2'  => sanitize_text_field( wp_unslash( $source['address_2'] ?? '' ) ),
        'city'       => sanitize_text_field( wp_unslash( $source['city'] ?? '' ) ),
        'postcode'   => strtoupper( sanitize_text_field( wp_unslash( $source['postcode'] ?? '' ) ) ),
        'notes'      => sanitize_textarea_field( wp_unslash( $source['notes'] ?? '' ) ),
        'gift_aid'   => ! empty( $source['gift_aid'] ),
        'consent'    => ! empty( $source['consent'] ),
        'level_key'  => $level['key'],
        'level_name' => $level['name'],
    );

    if ( '' === $data['first_name'] || '' === $data['last_name'] ) {
        return new WP_Error( 'emc_membership_name', __( 'Please enter your first and last name.', 'emc-theme' ) );
    }
    if ( ! is_email( $data['email'] ) ) {
        return new WP_Error( 'emc_membership_email', __( 'Please enter a valid email address.', 'emc-theme' ) );
    }
    if ( '' === $data['address_1'] || '' === $data['postcode'] ) {
        return new WP_Error( 'emc_membership_address', __( 'Please enter your address and postcode.', 'emc-theme' ) );
    }
    if ( ! $data['consent'] ) {
        return new WP_Error( 'emc_membership_consent', __( 'Please confirm you agree to us holding these details.', 'emc-theme' ) );
    }

    $data['level'] = $level;

    return $data;
}

/**
 * Validate the application, record it as pending, and return what the browser
 * needs to open the EMC Payments modal.
 */
function emc_ajax_membership_join() {
    check_ajax_referer( 'emc_membership', 'nonce' );

    if ( ! empty( $_POST['website'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Unable to submit this application.', 'emc-theme' ) ), 400 );
    }

    if ( ! emc_membership_payments_available() ) {
        wp_send_json_error( array( 'message' => __( 'Monthly membership payments are temporarily unavailable. Please contact the centre to join.', 'emc-theme' ) ), 503 );
    }

    $validated = emc_membership_validate_submission( $_POST );
    if ( is_wp_error( $validated ) ) {
        wp_send_json_error( array( 'message' => $validated->get_error_message() ), 400 );
    }

    $level = $validated['level'];
    unset( $validated['level'] );

    $rate_key = 'emc_mem_join_' . md5( $level['key'] . '|' . strtolower( $validated['email'] ) );
    if ( get_transient( $rate_key ) ) {
        wp_send_json_error( array( 'message' => __( 'An application was just submitted with these details. Please complete the payment, or wait a moment before trying again.', 'emc-theme' ) ), 429 );
    }
    set_transient( $rate_key, 1, 2 * MINUTE_IN_SECONDS );

    $validated['amount_pence'] = $level['pence'];
    $post_id = emc_create_pending_membership( $validated );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Your application could not be saved. Please contact the centre.', 'emc-theme' ) ), 500 );
    }

    $notes = array( sprintf( 'Membership level: %s', $level['name'] ) );
    if ( $validated['phone'] ) {
        $notes[] = sprintf( 'Phone: %s', $validated['phone'] );
    }
    if ( $validated['city'] ) {
        $notes[] = sprintf( 'Town/city: %s', $validated['city'] );
    }
    if ( $validated['notes'] ) {
        $notes[] = sprintf( 'Notes: %s', $validated['notes'] );
    }

    // Everything below is passed straight to window.emcOpenStripeModal().
    wp_send_json_success( array(
        'amount'    => $level['pence'],
        'fund'      => $level['fund'],
        'tab'       => 'regular',
        'frequency' => 'monthly',
        'name'      => trim( $validated['first_name'] . ' ' . $validated['last_name'] ),
        'email'     => $validated['email'],
        'address'   => trim( implode( ', ', array_filter( array( $validated['address_1'], $validated['address_2'] ) ) ) ),
        'postcode'  => $validated['postcode'],
        'giftAid'   => (bool) $validated['gift_aid'],
        'message'   => implode( "\n", $notes ),
    ) );
}
add_action( 'wp_ajax_emc_membership_join', 'emc_ajax_membership_join' );
add_action( 'wp_ajax_nopriv_emc_membership_join', 'emc_ajax_membership_join' );

/* ==========================================================================
   Administration
   ========================================================================== */

/**
 * Add the hidden Memberships screen reached from the EMC admin hub.
 */
function emc_membership_admin_menu() {
    add_submenu_page(
        null,
        __( 'Memberships', 'emc-theme' ),
        __( 'Memberships', 'emc-theme' ),
        'manage_options',
        'emc-memberships',
        'emc_membership_admin_page'
    );
}
add_action( 'admin_menu', 'emc_membership_admin_menu' );

/**
 * Active member count and the monthly income they commit.
 *
 * @return array{active:int,pending:int,monthly:float}
 */
function emc_membership_totals() {
    $ids     = get_posts( array(
        'post_type'      => 'emc_membership',
        'post_status'    => 'private',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );
    $active  = 0;
    $pending = 0;
    $monthly = 0.0;

    foreach ( $ids as $id ) {
        $status = get_post_meta( $id, '_emc_membership_status', true );
        if ( 'pending' === $status ) {
            $pending++;
            continue;
        }
        if ( in_array( $status, array( 'active', 'trialing' ), true ) ) {
            $active++;
            $monthly += (float) get_post_meta( $id, '_emc_membership_amount', true );
        }
    }

    return array( 'active' => $active, 'pending' => $pending, 'monthly' => $monthly );
}

/**
 * List every stored membership.
 */
function emc_membership_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $totals = emc_membership_totals();
    $query  = new WP_Query( array(
        'post_type'      => 'emc_membership',
        'post_status'    => 'private',
        'posts_per_page' => 200,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Memberships', 'emc-theme' ); ?></h1>
        <p>
            <?php
            printf(
                /* translators: 1: active members, 2: committed monthly total, 3: applications awaiting payment. */
                esc_html__( '%1$d active members committing £%2$s each month. %3$d applications have not completed payment.', 'emc-theme' ),
                absint( $totals['active'] ),
                esc_html( number_format( $totals['monthly'], 2 ) ),
                absint( $totals['pending'] )
            );
            ?>
        </p>
        <p class="description">
            <?php esc_html_e( 'Memberships are monthly giving schedules created by the EMC Payments plugin. Change or cancel one in Stripe, or on the Donations & Payments screen, using the subscription reference below.', 'emc-theme' ); ?>
            <a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=emc_pg_membership' ) ); ?>"><?php esc_html_e( 'Edit membership levels', 'emc-theme' ); ?></a>
        </p>

        <?php if ( ! $query->have_posts() ) : ?>
            <div class="notice notice-info inline"><p><?php esc_html_e( 'No memberships have been taken out yet.', 'emc-theme' ); ?></p></div>
        <?php else : ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Applied', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Member', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Contact', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Level', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Monthly', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Details', 'emc-theme' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                while ( $query->have_posts() ) :
                    $query->the_post();
                    $id           = get_the_ID();
                    $email        = get_post_meta( $id, '_emc_membership_email', true );
                    $status       = get_post_meta( $id, '_emc_membership_status', true );
                    $subscription = get_post_meta( $id, '_emc_membership_subscription_id', true );
                    $address      = array_filter( array(
                        get_post_meta( $id, '_emc_membership_address_1', true ),
                        get_post_meta( $id, '_emc_membership_address_2', true ),
                        get_post_meta( $id, '_emc_membership_city', true ),
                        get_post_meta( $id, '_emc_membership_postcode', true ),
                    ) );
                    ?>
                    <tr>
                        <td><?php echo esc_html( get_post_meta( $id, '_emc_membership_submitted_at', true ) ); ?><br><small>#<?php echo absint( $id ); ?></small></td>
                        <td><strong><?php echo esc_html( trim( get_post_meta( $id, '_emc_membership_first_name', true ) . ' ' . get_post_meta( $id, '_emc_membership_last_name', true ) ) ); ?></strong></td>
                        <td>
                            <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a><br>
                            <?php echo esc_html( get_post_meta( $id, '_emc_membership_phone', true ) ); ?>
                        </td>
                        <td><?php echo esc_html( get_post_meta( $id, '_emc_membership_level_name', true ) ); ?></td>
                        <td><strong><?php echo esc_html( '£' . get_post_meta( $id, '_emc_membership_amount', true ) ); ?></strong></td>
                        <td>
                            <?php if ( 'pending' === $status ) : ?>
                                <em><?php esc_html_e( 'Payment not completed', 'emc-theme' ); ?></em>
                            <?php else : ?>
                                <?php echo esc_html( $status ); ?><br>
                                <code><?php echo esc_html( $subscription ); ?></code>
                            <?php endif; ?>
                        </td>
                        <td>
                            <details>
                                <summary><?php esc_html_e( 'View details', 'emc-theme' ); ?></summary>
                                <p><strong><?php esc_html_e( 'Address:', 'emc-theme' ); ?></strong><br><?php echo esc_html( implode( ', ', $address ) ?: '—' ); ?></p>
                                <p><strong><?php esc_html_e( 'Gift Aid:', 'emc-theme' ); ?></strong> <?php echo '1' === get_post_meta( $id, '_emc_membership_gift_aid', true ) ? esc_html__( 'Yes', 'emc-theme' ) : esc_html__( 'No', 'emc-theme' ); ?></p>
                                <p><strong><?php esc_html_e( 'Notes:', 'emc-theme' ); ?></strong><br><?php echo nl2br( esc_html( get_post_meta( $id, '_emc_membership_notes', true ) ?: '—' ) ); ?></p>
                            </details>
                        </td>
                    </tr>
                <?php endwhile; wp_reset_postdata(); ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
