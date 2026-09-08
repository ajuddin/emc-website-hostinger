<?php
/**
 * Membership levels, applications, monthly Stripe subscriptions, and administration.
 *
 * Membership fees are recurring monthly charges. They are created through the same
 * licensed EMC Payments Stripe connection used by donations and paid event
 * registrations, using only emc_stripe_request() so no plugin internals are relied on.
 *
 * The flow is deliberately SetupIntent-first:
 *   1. Create (or reuse) a Stripe Customer for the applicant.
 *   2. Create a SetupIntent and confirm the card in the browser. This is where any
 *      3-D Secure challenge happens, and it stores a reusable mandate.
 *   3. Verify the SetupIntent server-side, attach the payment method as the
 *      customer's default, then create the monthly Subscription.
 *
 * Confirming the card before the subscription exists means a failed or abandoned
 * card step can never leave a half-created subscription behind.
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

/* ==========================================================================
   Membership levels
   ========================================================================== */

/**
 * The three membership levels.
 *
 * Keys are stable identifiers stored against every membership record and sent to
 * Stripe as metadata, so they must not be renamed once the page is live. Names,
 * monthly amounts and descriptions are Customizer-editable; the set of levels and
 * their dome colours are fixed by the page design.
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

        $levels[ $key ] = array(
            'key'    => $key,
            'order'  => $default['order'],
            'colour' => $default['colour'],
            'name'   => sanitize_text_field( emc_acf( 'mem_level_' . $key . '_name', $default['name'] ) ),
            'amount' => $amount,
            'pence'  => (int) round( $amount * 100 ),
            'desc'   => sanitize_textarea_field( emc_acf( 'mem_level_' . $key . '_desc', $default['desc'] ) ),
        );
    }

    return $levels;
}

/**
 * Return one membership level by key, or null when the key is unknown.
 *
 * A level with an amount below Stripe's 50p minimum cannot be charged monthly and
 * is treated as unavailable rather than silently taking nothing.
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
   Stripe products and prices
   ========================================================================== */

/**
 * Return a reusable monthly Stripe Price for one membership level.
 *
 * Prices are created once per level and amount, then cached, so changing a level's
 * fee creates a new Price while existing members stay on the price they signed up
 * to. Stripe Prices are immutable, which is what makes this safe.
 *
 * @param array $level Membership level.
 * @return string|WP_Error Stripe Price ID.
 */
function emc_membership_stripe_price_id( $level ) {
    $cache_key = $level['key'] . '_' . $level['pence'];
    $cache     = get_option( 'emc_membership_stripe_prices', array() );
    $cache     = is_array( $cache ) ? $cache : array();

    if ( ! empty( $cache[ $cache_key ] ) ) {
        return $cache[ $cache_key ];
    }

    $price = emc_stripe_request( 'POST', 'prices', array(
        'currency'                => 'gbp',
        'unit_amount'             => $level['pence'],
        'recurring[interval]'     => 'month',
        'product_data[name]'      => sprintf( 'EMC Membership — %s', $level['name'] ),
        'metadata[source]'        => 'EMC Membership',
        'metadata[level_key]'     => $level['key'],
    ) );

    if ( is_wp_error( $price ) ) {
        return $price;
    }
    if ( empty( $price['id'] ) ) {
        return new WP_Error( 'emc_membership_price', __( 'Stripe could not create the membership price.', 'emc-theme' ) );
    }

    $cache[ $cache_key ] = sanitize_text_field( $price['id'] );
    update_option( 'emc_membership_stripe_prices', $cache, false );

    return $cache[ $cache_key ];
}

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
        'city', 'postcode', 'level_key', 'level_name', 'notes',
    );
}

/**
 * Store a confirmed membership and notify everyone who needs to know.
 *
 * @param array $pending      Validated applicant data.
 * @param array $subscription Stripe subscription details.
 * @return array Front-end response payload.
 */
function emc_store_membership( $pending, $subscription ) {
    $subscription_id = sanitize_text_field( $subscription['subscription_id'] ?? '' );

    // Never store the same Stripe subscription twice.
    if ( $subscription_id ) {
        $existing = get_posts( array(
            'post_type'      => 'emc_membership',
            'post_status'    => 'private',
            'meta_key'       => '_emc_membership_subscription_id',
            'meta_value'     => $subscription_id,
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ) );
        if ( $existing ) {
            return array( 'message' => __( 'Thank you. Your membership has already been set up.', 'emc-theme' ) );
        }
    }

    $full_name = trim( $pending['first_name'] . ' ' . $pending['last_name'] );
    $post_id   = wp_insert_post( array(
        'post_type'   => 'emc_membership',
        'post_status' => 'private',
        'post_title'  => sprintf( '%s — %s', $full_name ?: __( 'Member', 'emc-theme' ), current_time( 'Y-m-d H:i:s' ) ),
    ), true );

    if ( is_wp_error( $post_id ) ) {
        return array( 'message' => __( 'Your membership was set up with Stripe but could not be saved on the website. Please contact the centre.', 'emc-theme' ) );
    }

    foreach ( emc_membership_record_fields() as $field ) {
        update_post_meta( $post_id, '_emc_membership_' . $field, $pending[ $field ] ?? '' );
    }

    $amount_pence = absint( $subscription['amount_pence'] ?? 0 );

    update_post_meta( $post_id, '_emc_membership_gift_aid', ! empty( $pending['gift_aid'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_emc_membership_consent', ! empty( $pending['consent'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_emc_membership_submitted_at', current_time( 'mysql' ) );
    update_post_meta( $post_id, '_emc_membership_start_date', current_time( 'Y-m-d' ) );
    update_post_meta( $post_id, '_emc_membership_frequency', 'monthly' );
    update_post_meta( $post_id, '_emc_membership_amount', number_format( $amount_pence / 100, 2, '.', '' ) );
    update_post_meta( $post_id, '_emc_membership_subscription_id', $subscription_id );
    update_post_meta( $post_id, '_emc_membership_customer_id', sanitize_text_field( $subscription['customer_id'] ?? '' ) );
    update_post_meta( $post_id, '_emc_membership_status', sanitize_text_field( $subscription['status'] ?? 'active' ) );

    emc_notify_membership_application( $pending, $subscription_id, $amount_pence );
    $confirmed = emc_send_membership_confirmation( $pending, $subscription_id, $amount_pence );

    return array(
        'message' => $confirmed
            ? __( 'Thank you. Your monthly membership is set up and a confirmation has been sent to your email address.', 'emc-theme' )
            : __( 'Thank you. Your monthly membership is now set up.', 'emc-theme' ),
    );
}

/**
 * Email the administrators about a new membership.
 *
 * @param array  $pending         Applicant data.
 * @param string $subscription_id Stripe subscription reference.
 * @param int    $amount_pence    Monthly amount.
 */
function emc_notify_membership_application( $pending, $subscription_id, $amount_pence ) {
    if ( ! function_exists( 'emc_send_form_notification' ) ) {
        return;
    }

    $address = trim( implode( ', ', array_filter( array( $pending['address_1'], $pending['address_2'], $pending['city'], $pending['postcode'] ) ) ) );

    $lines = array(
        sprintf( __( 'Name: %s', 'emc-theme' ), trim( $pending['first_name'] . ' ' . $pending['last_name'] ) ),
        sprintf( __( 'Email: %s', 'emc-theme' ), $pending['email'] ),
        sprintf( __( 'Phone: %s', 'emc-theme' ), $pending['phone'] ?: '—' ),
        sprintf( __( 'Level: %s', 'emc-theme' ), $pending['level_name'] ),
        sprintf( __( 'Monthly amount: £%s', 'emc-theme' ), number_format( $amount_pence / 100, 2 ) ),
        sprintf( __( 'Address: %s', 'emc-theme' ), $address ?: '—' ),
        sprintf( __( 'Gift Aid: %s', 'emc-theme' ), ! empty( $pending['gift_aid'] ) ? __( 'Yes', 'emc-theme' ) : __( 'No', 'emc-theme' ) ),
        sprintf( __( 'Stripe subscription: %s', 'emc-theme' ), $subscription_id ),
    );

    if ( ! empty( $pending['notes'] ) ) {
        $lines[] = sprintf( __( 'Notes: %s', 'emc-theme' ), $pending['notes'] );
    }

    emc_send_form_notification(
        'membership',
        sprintf( __( 'New membership: %s', 'emc-theme' ), $pending['level_name'] ),
        implode( "\n", $lines )
    );
}

/**
 * Email the new member their confirmation.
 *
 * @param array  $pending         Applicant data.
 * @param string $subscription_id Stripe subscription reference.
 * @param int    $amount_pence    Monthly amount.
 * @return bool Whether the confirmation was sent.
 */
function emc_send_membership_confirmation( $pending, $subscription_id, $amount_pence ) {
    if ( ! is_email( $pending['email'] ?? '' ) ) {
        return false;
    }

    $body  = sprintf( __( "Assalamu Alaikum %s,\n\nJazak Allahu Khairan for becoming a member of Essex Muslim Centre.", 'emc-theme' ), $pending['first_name'] ?: __( 'friend', 'emc-theme' ) );
    $body .= "\n\n" . sprintf( __( 'Membership level: %s', 'emc-theme' ), $pending['level_name'] );
    $body .= "\n" . sprintf( __( 'Monthly amount: £%s', 'emc-theme' ), number_format( $amount_pence / 100, 2 ) );
    $body .= "\n" . sprintf( __( 'First payment: %s', 'emc-theme' ), date_i18n( get_option( 'date_format' ) ) );
    $body .= "\n" . sprintf( __( 'Stripe reference: %s', 'emc-theme' ), $subscription_id );
    $body .= "\n\n" . __( 'Your membership renews automatically each month. To change the amount or cancel at any time, simply reply to this email and we will take care of it.', 'emc-theme' );

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
 * Step one: validate the application, create the Stripe Customer, and hand back a
 * SetupIntent so the browser can collect and authenticate the card.
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

    $level = $validated['level'];
    unset( $validated['level'] );

    if ( ! emc_membership_stripe_is_available() ) {
        wp_send_json_error( array( 'message' => __( 'Monthly membership payments are temporarily unavailable. Please contact the centre to join.', 'emc-theme' ) ), 503 );
    }

    $rate_key = 'emc_mem_join_' . md5( $level['key'] . '|' . strtolower( $validated['email'] ) );
    if ( get_transient( $rate_key ) ) {
        wp_send_json_error( array( 'message' => __( 'An application was just submitted with these details. Please wait a moment before trying again.', 'emc-theme' ) ), 429 );
    }

    $address = trim( implode( ', ', array_filter( array( $validated['address_1'], $validated['address_2'] ) ) ) );

    $customer = emc_stripe_request( 'POST', 'customers', array(
        'name'                   => trim( $validated['first_name'] . ' ' . $validated['last_name'] ),
        'email'                  => $validated['email'],
        'phone'                  => $validated['phone'],
        'address[line1]'         => $validated['address_1'],
        'address[line2]'         => $validated['address_2'],
        'address[city]'          => $validated['city'],
        'address[postal_code]'   => $validated['postcode'],
        'address[country]'       => 'GB',
        'description'            => sprintf( 'EMC member — %s', $level['name'] ),
        'metadata[source]'       => 'EMC Membership',
        'metadata[level_key]'    => $level['key'],
        'metadata[gift_aid]'     => ! empty( $validated['gift_aid'] ) ? 'yes' : 'no',
        'metadata[full_address]' => $address,
    ) );

    if ( is_wp_error( $customer ) || empty( $customer['id'] ) ) {
        $message = is_wp_error( $customer ) ? $customer->get_error_message() : __( 'Stripe could not create your membership record.', 'emc-theme' );
        wp_send_json_error( array( 'message' => $message ), 502 );
    }

    $setup = emc_stripe_request( 'POST', 'setup_intents', array(
        'customer'                     => $customer['id'],
        'usage'                        => 'off_session',
        'payment_method_types[]'       => 'card',
        'metadata[source]'             => 'EMC Membership',
        'metadata[level_key]'          => $level['key'],
    ) );

    if ( is_wp_error( $setup ) || empty( $setup['client_secret'] ) || empty( $setup['id'] ) ) {
        $message = is_wp_error( $setup ) ? $setup->get_error_message() : __( 'Stripe could not prepare the card setup.', 'emc-theme' );
        wp_send_json_error( array( 'message' => $message ), 502 );
    }

    $token               = wp_generate_uuid4();
    $pending             = $validated;
    $pending['rate_key'] = $rate_key;
    $pending['token']    = $token;
    $pending['level']    = $level;
    $pending['customer'] = sanitize_text_field( $customer['id'] );
    $pending['setup']    = sanitize_text_field( $setup['id'] );

    if ( ! set_transient( 'emc_mem_pending_' . $token, $pending, 30 * MINUTE_IN_SECONDS ) ) {
        wp_send_json_error( array( 'message' => __( 'The membership session could not be saved. No payment has been taken.', 'emc-theme' ) ), 500 );
    }

    wp_send_json_success( array(
        'clientSecret' => $setup['client_secret'],
        'token'        => $token,
        'amount'       => '£' . number_format( $level['pence'] / 100, 2 ),
    ) );
}
add_action( 'wp_ajax_emc_membership_join', 'emc_ajax_membership_join' );
add_action( 'wp_ajax_nopriv_emc_membership_join', 'emc_ajax_membership_join' );

/**
 * Step two: verify the confirmed card, then create the monthly subscription.
 */
function emc_ajax_membership_confirm() {
    check_ajax_referer( 'emc_membership', 'nonce' );

    $token    = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
    $setup_id = sanitize_text_field( wp_unslash( $_POST['setup_intent'] ?? '' ) );

    if ( ! $token || ! $setup_id || ! emc_membership_stripe_is_available() ) {
        wp_send_json_error( array( 'message' => __( 'The membership could not be verified.', 'emc-theme' ) ), 400 );
    }

    $pending = get_transient( 'emc_mem_pending_' . $token );
    if ( ! is_array( $pending ) || $setup_id !== ( $pending['setup'] ?? '' ) ) {
        wp_send_json_error( array( 'message' => __( 'This membership session has expired. Please start again, or contact the centre if a payment was taken.', 'emc-theme' ) ), 410 );
    }

    // The card must be confirmed and saved against the customer we created.
    $setup = emc_stripe_request( 'GET', 'setup_intents/' . rawurlencode( $setup_id ) );
    if ( is_wp_error( $setup ) ) {
        wp_send_json_error( array( 'message' => $setup->get_error_message() ), 502 );
    }

    $payment_method = is_array( $setup ) ? (string) ( $setup['payment_method'] ?? '' ) : '';
    $valid          = is_array( $setup )
        && 'succeeded' === ( $setup['status'] ?? '' )
        && $payment_method
        && ( $pending['customer'] ?? '' ) === (string) ( $setup['customer'] ?? '' );

    if ( ! $valid ) {
        wp_send_json_error( array( 'message' => __( 'Stripe has not confirmed your card. Please try again or contact the centre.', 'emc-theme' ) ), 409 );
    }

    $level = is_array( $pending['level'] ?? null ) ? $pending['level'] : null;
    if ( ! $level ) {
        wp_send_json_error( array( 'message' => __( 'This membership session is no longer valid. Please start again.', 'emc-theme' ) ), 410 );
    }

    $price_id = emc_membership_stripe_price_id( $level );
    if ( is_wp_error( $price_id ) ) {
        wp_send_json_error( array( 'message' => $price_id->get_error_message() ), 502 );
    }

    // Make the confirmed card the default for future invoices.
    $customer_update = emc_stripe_request( 'POST', 'customers/' . rawurlencode( $pending['customer'] ), array(
        'invoice_settings[default_payment_method]' => $payment_method,
    ) );
    if ( is_wp_error( $customer_update ) ) {
        wp_send_json_error( array( 'message' => $customer_update->get_error_message() ), 502 );
    }

    /*
     * error_if_incomplete makes Stripe reject the whole subscription if the first
     * charge does not go through, rather than leaving an unpaid subscription that
     * looks active on the website but is not collecting anything.
     */
    $subscription = emc_stripe_request( 'POST', 'subscriptions', array(
        'customer'                  => $pending['customer'],
        'items[0][price]'           => $price_id,
        'default_payment_method'    => $payment_method,
        'payment_behavior'          => 'error_if_incomplete',
        'metadata[source]'          => 'EMC Membership',
        'metadata[level_key]'       => $level['key'],
        'metadata[level_name]'      => $level['name'],
        'metadata[membership_token]'=> $token,
        'metadata[gift_aid]'        => ! empty( $pending['gift_aid'] ) ? 'yes' : 'no',
    ) );

    if ( is_wp_error( $subscription ) ) {
        wp_send_json_error( array( 'message' => $subscription->get_error_message() ), 502 );
    }
    if ( empty( $subscription['id'] ) || ! in_array( $subscription['status'] ?? '', array( 'active', 'trialing' ), true ) ) {
        wp_send_json_error( array( 'message' => __( 'Your card was saved but the monthly membership could not be started. Please contact the centre before trying again.', 'emc-theme' ) ), 409 );
    }

    set_transient( $pending['rate_key'], 1, MINUTE_IN_SECONDS );

    $result = emc_store_membership( $pending, array(
        'subscription_id' => $subscription['id'],
        'customer_id'     => $pending['customer'],
        'amount_pence'    => $level['pence'],
        'status'          => $subscription['status'],
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
 * Monthly income currently committed through memberships.
 *
 * @return array{count:int,monthly:float}
 */
function emc_membership_totals() {
    $ids     = get_posts( array(
        'post_type'      => 'emc_membership',
        'post_status'    => 'private',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );
    $monthly = 0.0;

    foreach ( $ids as $id ) {
        if ( in_array( get_post_meta( $id, '_emc_membership_status', true ), array( 'active', 'trialing' ), true ) ) {
            $monthly += (float) get_post_meta( $id, '_emc_membership_amount', true );
        }
    }

    return array( 'count' => count( $ids ), 'monthly' => $monthly );
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
                /* translators: 1: number of membership records, 2: committed monthly total. */
                esc_html__( '%1$d membership records, £%2$s committed each month.', 'emc-theme' ),
                absint( $totals['count'] ),
                esc_html( number_format( $totals['monthly'], 2 ) )
            );
            ?>
            <a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=emc_pg_membership' ) ); ?>"><?php esc_html_e( 'Edit membership levels', 'emc-theme' ); ?></a>
        </p>
        <p class="description"><?php esc_html_e( 'Memberships are monthly Stripe subscriptions. Cancel or change one in the Stripe dashboard using the subscription reference below.', 'emc-theme' ); ?></p>

        <?php if ( ! $query->have_posts() ) : ?>
            <div class="notice notice-info inline"><p><?php esc_html_e( 'No memberships have been taken out yet.', 'emc-theme' ); ?></p></div>
        <?php else : ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Joined', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Member', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Contact', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Level', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Monthly', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Subscription', 'emc-theme' ); ?></th>
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
                        <td><?php echo esc_html( get_post_meta( $id, '_emc_membership_level_name', true ) ); ?></td>
                        <td><strong><?php echo esc_html( '£' . get_post_meta( $id, '_emc_membership_amount', true ) ); ?></strong></td>
                        <td>
                            <code><?php echo esc_html( get_post_meta( $id, '_emc_membership_subscription_id', true ) ); ?></code><br>
                            <small><?php echo esc_html( get_post_meta( $id, '_emc_membership_status', true ) ); ?></small>
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
