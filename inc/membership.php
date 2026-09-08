<?php
/**
 * Membership categories, applications, Stripe fee collection, and administration.
 *
 * Fees are taken through the same licensed EMC Payments Stripe connection used by
 * donations and paid event registrations. No card data ever reaches this theme.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

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

/**
 * Shipped membership categories.
 *
 * These are the Customizer defaults and the values used until an administrator
 * saves their own. Both the Customizer controls and the public page read them
 * from here so the two can never drift apart.
 *
 * @return array<int,array>
 */
function emc_membership_tier_defaults() {
    return array(
        1 => array(
            'enabled'  => 1,
            'name'     => __( 'Standard Membership', 'emc-theme' ),
            'price'    => 50,
            'period'   => __( 'per year', 'emc-theme' ),
            'badge'    => '',
            'desc'     => __( 'For adults aged 18 and over.', 'emc-theme' ),
            'features' => __( "Full voting rights at the AGM\nMember updates and meeting notices\nSupports the daily running of the centre", 'emc-theme' ),
        ),
        2 => array(
            'enabled'  => 1,
            'name'     => __( 'Family Membership', 'emc-theme' ),
            'price'    => 80,
            'period'   => __( 'per year', 'emc-theme' ),
            'badge'    => __( 'Most Popular', 'emc-theme' ),
            'desc'     => __( 'Two adults at the same address.', 'emc-theme' ),
            'features' => __( "Two adult votes at the AGM\nMember updates and meeting notices\nBest value for households", 'emc-theme' ),
        ),
        3 => array(
            'enabled'  => 1,
            'name'     => __( 'Student & Senior', 'emc-theme' ),
            'price'    => 25,
            'period'   => __( 'per year', 'emc-theme' ),
            'badge'    => '',
            'desc'     => __( 'Students in full-time education and members aged 65 and over.', 'emc-theme' ),
            'features' => __( "Full voting rights at the AGM\nMember updates and meeting notices\nReduced rate", 'emc-theme' ),
        ),
        4 => array(
            'enabled'  => 0,
            'name'     => __( 'Lifetime Membership', 'emc-theme' ),
            'price'    => 500,
            'period'   => __( 'one-off', 'emc-theme' ),
            'badge'    => '',
            'desc'     => __( 'A single payment with no annual renewal.', 'emc-theme' ),
            'features' => __( "Permanent voting rights at the AGM\nRecognition as a lifetime member\nNo annual renewal required", 'emc-theme' ),
        ),
    );
}

/**
 * Return every enabled membership category with its validated fee.
 *
 * @return array<string,array>
 */
function emc_membership_tiers() {
    $tiers = array();

    foreach ( emc_membership_tier_defaults() as $i => $default ) {
        if ( ! absint( get_theme_mod( 'mem_tier_' . $i . '_enabled', $default['enabled'] ) ) ) {
            continue;
        }

        $name = sanitize_text_field( emc_acf( 'mem_tier_' . $i . '_name', $default['name'] ) );
        if ( '' === $name ) {
            continue;
        }

        $price    = round( (float) get_theme_mod( 'mem_tier_' . $i . '_price', $default['price'] ), 2 );
        $price    = $price > 0 ? min( 10000, $price ) : 0;
        $features = preg_split( '/\r\n|\r|\n/', (string) emc_acf( 'mem_tier_' . $i . '_features', $default['features'] ) );
        $features = array_values( array_filter( array_map( 'trim', $features ), 'strlen' ) );

        $tiers[ 'tier_' . $i ] = array(
            'key'      => 'tier_' . $i,
            'name'     => $name,
            'price'    => $price,
            'pence'    => (int) round( $price * 100 ),
            'paid'     => $price >= 0.50,
            'period'   => sanitize_text_field( emc_acf( 'mem_tier_' . $i . '_period', $default['period'] ) ),
            'badge'    => sanitize_text_field( emc_acf( 'mem_tier_' . $i . '_badge', $default['badge'] ) ),
            'desc'     => sanitize_text_field( emc_acf( 'mem_tier_' . $i . '_desc', $default['desc'] ) ),
            'features' => $features,
        );
    }

    return $tiers;
}

/**
 * Categories a visitor can complete online right now.
 *
 * Paid categories disappear if the licensed Stripe connection is unavailable,
 * so the page can never present a fee it cannot take.
 *
 * @return array<string,array>
 */
function emc_membership_available_tiers() {
    $tiers = emc_membership_tiers();

    if ( emc_membership_stripe_is_available() ) {
        return $tiers;
    }

    return array_filter( $tiers, static function ( $tier ) {
        return ! $tier['paid'];
    } );
}

/**
 * Return one membership category by key, or null when it is not offered.
 *
 * @param string $key Category key, for example tier_2.
 * @return array|null
 */
function emc_membership_tier( $key ) {
    $tiers = emc_membership_tiers();
    return $tiers[ $key ] ?? null;
}

/**
 * Whether the licensed EMC Payments Stripe connection can take membership fees.
 *
 * @return bool
 */
function emc_membership_stripe_is_available() {
    if ( ! function_exists( 'emc_stripe_request' ) || ! function_exists( 'emc_stripe_pub_key' ) || ! function_exists( 'emc_stripe_secret_key' ) || ! emc_stripe_pub_key() || ! emc_stripe_secret_key() ) {
        return false;
    }

    return function_exists( 'emc_payment_license_is_active' ) && emc_payment_license_is_active();
}

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
   Application storage
   ========================================================================== */

/**
 * Field keys stored against each membership record.
 *
 * @return string[]
 */
function emc_membership_record_fields() {
    return array(
        'first_name', 'last_name', 'email', 'phone', 'address_1', 'address_2',
        'city', 'postcode', 'tier_key', 'tier_name', 'notes',
    );
}

/**
 * Store a completed membership application.
 *
 * @param array $pending Validated applicant data.
 * @param array $payment Optional Stripe payment details.
 * @return array Front-end response payload.
 */
function emc_store_membership_application( $pending, $payment = array() ) {
    $payment_intent = sanitize_text_field( $payment['payment_intent'] ?? '' );

    // Never store the same Stripe payment twice.
    if ( $payment_intent ) {
        $existing = get_posts( array(
            'post_type'      => 'emc_membership',
            'post_status'    => 'private',
            'meta_key'       => '_emc_membership_payment_intent',
            'meta_value'     => $payment_intent,
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ) );
        if ( $existing ) {
            return array( 'message' => __( 'Thank you. Your payment and membership have already been confirmed.', 'emc-theme' ) );
        }
    }

    $full_name = trim( $pending['first_name'] . ' ' . $pending['last_name'] );
    $post_id   = wp_insert_post( array(
        'post_type'   => 'emc_membership',
        'post_status' => 'private',
        'post_title'  => sprintf( '%s — %s', $full_name ?: __( 'Member', 'emc-theme' ), current_time( 'Y-m-d H:i:s' ) ),
    ), true );

    if ( is_wp_error( $post_id ) ) {
        return array( 'message' => __( 'Your membership could not be saved. Please contact the centre.', 'emc-theme' ) );
    }

    foreach ( emc_membership_record_fields() as $field ) {
        update_post_meta( $post_id, '_emc_membership_' . $field, $pending[ $field ] ?? '' );
    }

    $amount_pence = absint( $payment['amount_pence'] ?? 0 );
    $starts       = current_time( 'Y-m-d' );

    update_post_meta( $post_id, '_emc_membership_gift_aid', ! empty( $pending['gift_aid'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_emc_membership_consent', ! empty( $pending['consent'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_emc_membership_submitted_at', current_time( 'mysql' ) );
    update_post_meta( $post_id, '_emc_membership_status', 'active' );
    update_post_meta( $post_id, '_emc_membership_start_date', $starts );
    update_post_meta( $post_id, '_emc_membership_expiry_date', gmdate( 'Y-m-d', strtotime( $starts . ' +1 year' ) ) );
    update_post_meta( $post_id, '_emc_membership_payment_status', $payment_intent ? 'paid' : 'unpaid' );
    update_post_meta( $post_id, '_emc_membership_payment_intent', $payment_intent );
    update_post_meta( $post_id, '_emc_membership_amount', number_format( $amount_pence / 100, 2, '.', '' ) );

    emc_notify_membership_application( $pending, $payment_intent, $amount_pence );
    $confirmed = emc_send_membership_confirmation( $pending, $payment_intent, $amount_pence );

    return array(
        'message' => $confirmed
            ? __( 'Thank you. Your membership is confirmed and a confirmation has been sent to your email address.', 'emc-theme' )
            : __( 'Thank you. Your membership has been confirmed.', 'emc-theme' ),
    );
}

/**
 * Email the administrators about a new membership.
 *
 * @param array  $pending        Applicant data.
 * @param string $payment_intent Stripe reference, if paid.
 * @param int    $amount_pence   Amount charged.
 */
function emc_notify_membership_application( $pending, $payment_intent, $amount_pence ) {
    if ( ! function_exists( 'emc_send_form_notification' ) ) {
        return;
    }

    $lines = array(
        sprintf( __( 'Name: %s', 'emc-theme' ), trim( $pending['first_name'] . ' ' . $pending['last_name'] ) ),
        sprintf( __( 'Email: %s', 'emc-theme' ), $pending['email'] ),
        sprintf( __( 'Phone: %s', 'emc-theme' ), $pending['phone'] ?: '—' ),
        sprintf( __( 'Category: %s', 'emc-theme' ), $pending['tier_name'] ),
        sprintf( __( 'Address: %s', 'emc-theme' ), trim( implode( ', ', array_filter( array( $pending['address_1'], $pending['address_2'], $pending['city'], $pending['postcode'] ) ) ) ) ?: '—' ),
        sprintf( __( 'Gift Aid: %s', 'emc-theme' ), ! empty( $pending['gift_aid'] ) ? __( 'Yes', 'emc-theme' ) : __( 'No', 'emc-theme' ) ),
        sprintf( __( 'Payment: %s', 'emc-theme' ), $payment_intent ? '£' . number_format( $amount_pence / 100, 2 ) . ' (' . $payment_intent . ')' : __( 'No fee taken', 'emc-theme' ) ),
    );

    if ( ! empty( $pending['notes'] ) ) {
        $lines[] = sprintf( __( 'Notes: %s', 'emc-theme' ), $pending['notes'] );
    }

    emc_send_form_notification(
        'membership',
        sprintf( __( 'New membership: %s', 'emc-theme' ), $pending['tier_name'] ),
        implode( "\n", $lines )
    );
}

/**
 * Email the new member their confirmation.
 *
 * @param array  $pending        Applicant data.
 * @param string $payment_intent Stripe reference, if paid.
 * @param int    $amount_pence   Amount charged.
 * @return bool Whether the confirmation was sent.
 */
function emc_send_membership_confirmation( $pending, $payment_intent, $amount_pence ) {
    if ( ! is_email( $pending['email'] ?? '' ) ) {
        return false;
    }

    $body  = sprintf( __( "Assalamu Alaikum %s,\n\nThank you for becoming a member of Essex Muslim Centre.", 'emc-theme' ), $pending['first_name'] ?: __( 'friend', 'emc-theme' ) );
    $body .= "\n\n" . sprintf( __( 'Membership category: %s', 'emc-theme' ), $pending['tier_name'] );
    $body .= "\n" . sprintf( __( 'Start date: %s', 'emc-theme' ), date_i18n( get_option( 'date_format' ) ) );

    if ( $payment_intent ) {
        $body .= "\n" . sprintf( __( 'Fee paid: £%s', 'emc-theme' ), number_format( $amount_pence / 100, 2 ) );
        $body .= "\n" . sprintf( __( 'Stripe reference: %s', 'emc-theme' ), $payment_intent );
    }

    $body .= "\n\n" . __( 'If any of these details are wrong, please reply to this email and we will correct our records.', 'emc-theme' );

    return wp_mail(
        $pending['email'],
        __( 'Your Essex Muslim Centre membership', 'emc-theme' ),
        $body
    );
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
    $tier_key = sanitize_key( wp_unslash( $source['tier'] ?? '' ) );
    $tier     = emc_membership_tier( $tier_key );

    if ( ! $tier ) {
        return new WP_Error( 'emc_membership_tier', __( 'Please choose an available membership category.', 'emc-theme' ) );
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
        'tier_key'   => $tier['key'],
        'tier_name'  => $tier['name'],
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

    $data['tier'] = $tier;

    return $data;
}

/**
 * Begin a membership application, preparing a Stripe payment when a fee applies.
 */
function emc_ajax_membership_join() {
    check_ajax_referer( 'emc_membership', 'nonce' );

    if ( ! empty( $_POST['website'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Unable to submit this application.', 'emc-theme' ) ), 400 );
    }

    $validated = emc_membership_validate_submission( $_POST );
    if ( is_wp_error( $validated ) ) {
        wp_send_json_error( array( 'message' => $validated->get_error_message() ), 400 );
    }

    $tier = $validated['tier'];
    unset( $validated['tier'] );

    $rate_key = 'emc_mem_join_' . md5( $tier['key'] . '|' . strtolower( $validated['email'] ) );
    if ( get_transient( $rate_key ) ) {
        wp_send_json_error( array( 'message' => __( 'An application was just submitted with these details. Please wait a moment before trying again.', 'emc-theme' ) ), 429 );
    }

    $pending             = $validated;
    $pending['rate_key'] = $rate_key;

    if ( ! $tier['paid'] ) {
        set_transient( $rate_key, 1, MINUTE_IN_SECONDS );
        wp_send_json_success( emc_store_membership_application( $pending ) );
    }

    if ( ! emc_membership_stripe_is_available() ) {
        wp_send_json_error( array( 'message' => __( 'Online payment is temporarily unavailable. Please contact the centre to join.', 'emc-theme' ) ), 503 );
    }

    $token       = wp_generate_uuid4();
    $intent_body = array(
        'amount'                             => $tier['pence'],
        'currency'                           => 'gbp',
        'automatic_payment_methods[enabled]' => 'true',
        'description'                        => sprintf( 'EMC membership: %s', $tier['name'] ),
        'metadata[source]'                   => 'EMC Membership',
        'metadata[membership_token]'         => $token,
        'metadata[tier_key]'                 => $tier['key'],
        'metadata[tier_name]'                => $tier['name'],
        'receipt_email'                      => $validated['email'],
    );

    $intent = emc_stripe_request( 'POST', 'payment_intents', $intent_body );

    if ( is_wp_error( $intent ) || empty( $intent['client_secret'] ) || empty( $intent['id'] ) ) {
        $message = is_wp_error( $intent ) ? $intent->get_error_message() : __( 'Stripe could not prepare this payment.', 'emc-theme' );
        wp_send_json_error( array( 'message' => $message ), 502 );
    }

    $pending['token']          = $token;
    $pending['payment_intent'] = sanitize_text_field( $intent['id'] );
    $pending['amount_pence']   = $tier['pence'];

    if ( ! set_transient( 'emc_mem_pending_' . $token, $pending, 30 * MINUTE_IN_SECONDS ) ) {
        wp_send_json_error( array( 'message' => __( 'The membership payment session could not be saved. No payment has been taken.', 'emc-theme' ) ), 500 );
    }

    wp_send_json_success( array(
        'requiresPayment' => true,
        'clientSecret'    => $intent['client_secret'],
        'paymentIntent'   => $intent['id'],
        'token'           => $token,
        'amount'          => '£' . number_format( $tier['pence'] / 100, 2 ),
    ) );
}
add_action( 'wp_ajax_emc_membership_join', 'emc_ajax_membership_join' );
add_action( 'wp_ajax_nopriv_emc_membership_join', 'emc_ajax_membership_join' );

/**
 * Verify the Stripe payment and store the paid membership.
 */
function emc_ajax_membership_confirm() {
    check_ajax_referer( 'emc_membership', 'nonce' );

    $token = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
    $pi_id = sanitize_text_field( wp_unslash( $_POST['payment_intent'] ?? '' ) );

    if ( ! $token || ! $pi_id || ! emc_membership_stripe_is_available() ) {
        wp_send_json_error( array( 'message' => __( 'The membership payment could not be verified.', 'emc-theme' ) ), 400 );
    }

    $pending = get_transient( 'emc_mem_pending_' . $token );
    if ( ! is_array( $pending ) || $pi_id !== ( $pending['payment_intent'] ?? '' ) ) {
        wp_send_json_error( array( 'message' => __( 'This membership payment session has expired. Please contact the centre if a payment was taken.', 'emc-theme' ) ), 410 );
    }

    $intent = emc_stripe_request( 'GET', 'payment_intents/' . rawurlencode( $pi_id ) );
    if ( is_wp_error( $intent ) ) {
        wp_send_json_error( array( 'message' => $intent->get_error_message() ), 502 );
    }

    $metadata = is_array( $intent ) && is_array( $intent['metadata'] ?? null ) ? $intent['metadata'] : array();
    $valid    = is_array( $intent )
        && 'succeeded' === ( $intent['status'] ?? '' )
        && 'gbp' === strtolower( $intent['currency'] ?? '' )
        && absint( $intent['amount_received'] ?? $intent['amount'] ?? 0 ) === absint( $pending['amount_pence'] ?? 0 )
        && $token === ( $metadata['membership_token'] ?? '' )
        && ( $pending['tier_key'] ?? '' ) === ( $metadata['tier_key'] ?? '' );

    if ( ! $valid ) {
        wp_send_json_error( array( 'message' => __( 'Stripe has not confirmed the expected payment. Please try again or contact the centre.', 'emc-theme' ) ), 409 );
    }

    set_transient( $pending['rate_key'], 1, MINUTE_IN_SECONDS );
    $result = emc_store_membership_application( $pending, array(
        'payment_intent' => $pi_id,
        'amount_pence'   => absint( $pending['amount_pence'] ),
    ) );
    delete_transient( 'emc_mem_pending_' . $token );

    wp_send_json_success( $result );
}
add_action( 'wp_ajax_emc_membership_confirm', 'emc_ajax_membership_confirm' );
add_action( 'wp_ajax_nopriv_emc_membership_confirm', 'emc_ajax_membership_confirm' );

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
 * List every stored membership.
 */
function emc_membership_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $query = new WP_Query( array(
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
            <?php esc_html_e( 'Applications submitted through the public Membership page, including the fee taken through Stripe.', 'emc-theme' ); ?>
            <a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=emc_pg_membership' ) ); ?>"><?php esc_html_e( 'Edit membership categories', 'emc-theme' ); ?></a>
        </p>

        <?php if ( ! $query->have_posts() ) : ?>
            <div class="notice notice-info inline"><p><?php esc_html_e( 'No memberships have been submitted yet.', 'emc-theme' ); ?></p></div>
        <?php else : ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Submitted', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Member', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Contact', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Category', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Period', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Payment', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Details', 'emc-theme' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                while ( $query->have_posts() ) :
                    $query->the_post();
                    $id      = get_the_ID();
                    $email   = get_post_meta( $id, '_emc_membership_email', true );
                    $address = array_filter( array(
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
                        <td><?php echo esc_html( get_post_meta( $id, '_emc_membership_tier_name', true ) ); ?></td>
                        <td>
                            <?php echo esc_html( get_post_meta( $id, '_emc_membership_start_date', true ) ); ?>
                            &rarr;
                            <?php echo esc_html( get_post_meta( $id, '_emc_membership_expiry_date', true ) ); ?>
                        </td>
                        <td>
                            <?php if ( 'paid' === get_post_meta( $id, '_emc_membership_payment_status', true ) ) : ?>
                                <strong><?php echo esc_html( '£' . get_post_meta( $id, '_emc_membership_amount', true ) ); ?></strong><br>
                                <code><?php echo esc_html( get_post_meta( $id, '_emc_membership_payment_intent', true ) ); ?></code>
                            <?php else : ?>
                                <?php esc_html_e( 'No fee taken', 'emc-theme' ); ?>
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
