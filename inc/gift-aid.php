<?php
/**
 * Gift Aid declaration storage and WordPress administration.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register a private post type used only as durable declaration storage.
 */
function emc_register_gift_aid_declaration_type() {
    register_post_type( 'emc_gift_aid', array(
        'labels' => array(
            'name'          => __( 'Gift Aid Declarations', 'emc-theme' ),
            'singular_name' => __( 'Gift Aid Declaration', 'emc-theme' ),
        ),
        'public'              => false,
        'show_ui'             => false,
        'show_in_rest'        => false,
        'exclude_from_search' => true,
        'supports'            => array( 'title' ),
    ) );
}
add_action( 'init', 'emc_register_gift_aid_declaration_type' );

/**
 * Find the public Gift Aid page whether it is nested below Donate or stored
 * at the site root.
 *
 * @return WP_Post|null
 */
function emc_get_gift_aid_page() {
    $page = get_page_by_path( 'donate/gift-aid', OBJECT, 'page' );

    if ( ! $page ) {
        $pages = get_posts( array(
            'post_type'      => 'page',
            'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
            'name'           => 'gift-aid',
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
 * Return the canonical Gift Aid page URL.
 *
 * @return string
 */
function emc_get_gift_aid_url() {
    $page = emc_get_gift_aid_page();
    return $page ? get_permalink( $page ) : home_url( '/donate/gift-aid/' );
}

/**
 * Ensure the public Gift Aid page exists on installations created before
 * this page template was added.
 */
function emc_ensure_gift_aid_page() {
    if ( emc_get_gift_aid_page() ) {
        return;
    }

    $donate_page = get_page_by_path( 'donate', OBJECT, 'page' );
    wp_insert_post( array(
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_title'   => __( 'Gift Aid Declaration', 'emc-theme' ),
        'post_name'    => 'gift-aid',
        'post_content' => '<!-- Managed by the Gift Aid page template. -->',
        'post_parent'  => $donate_page ? $donate_page->ID : 0,
        'meta_input'   => array(
            '_wp_page_template' => 'page-gift-aid.php',
        ),
    ) );
}
add_action( 'init', 'emc_ensure_gift_aid_page', 20 );

/**
 * Store a declaration and return its post ID or a WP_Error.
 *
 * @param array $data Sanitized declaration data.
 * @return int|WP_Error
 */
function emc_store_gift_aid_declaration( $data ) {
    $name = trim( ( $data['prefix'] ?? '' ) . ' ' . ( $data['first_name'] ?? '' ) . ' ' . ( $data['last_name'] ?? '' ) );

    $post_id = wp_insert_post( array(
        'post_type'   => 'emc_gift_aid',
        'post_status' => 'private',
        'post_title'  => sprintf(
            '%s — %s',
            $name,
            current_time( 'Y-m-d H:i:s' )
        ),
    ), true );

    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }

    $allowed_fields = array(
        'prefix',
        'first_name',
        'last_name',
        'address_line_1',
        'town_city',
        'postcode',
        'country',
        'phone',
        'email',
        'declaration_scope',
        'declaration_text',
        'non_uk_donor',
    );

    foreach ( $allowed_fields as $field ) {
        update_post_meta( $post_id, '_emc_gift_aid_' . $field, $data[ $field ] ?? '' );
    }

    update_post_meta( $post_id, '_emc_gift_aid_accuracy_confirmed', ! empty( $data['accuracy_confirmed'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_emc_gift_aid_taxpayer_confirmed', ! empty( $data['taxpayer_confirmed'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_emc_gift_aid_submitted_at', current_time( 'mysql' ) );

    return $post_id;
}

/**
 * Validate, store, and acknowledge a public Gift Aid declaration.
 */
function emc_handle_gift_aid_declaration() {
    check_ajax_referer( 'emc_nonce', 'nonce' );

    if ( ! empty( $_POST['website'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Unable to submit this declaration.', 'emc-theme' ) ), 400 );
    }

    $allowed_prefixes = array( '', 'Mr', 'Mrs', 'Miss', 'Ms', 'Dr' );
    $prefix           = sanitize_text_field( wp_unslash( $_POST['prefix'] ?? '' ) );
    $prefix           = in_array( $prefix, $allowed_prefixes, true ) ? $prefix : '';
    $first_name       = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
    $last_name        = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
    $address_line_1   = sanitize_text_field( wp_unslash( $_POST['address_line_1'] ?? '' ) );
    $town_city        = sanitize_text_field( wp_unslash( $_POST['town_city'] ?? '' ) );
    $postcode         = emc_normalize_postcode( wp_unslash( $_POST['postcode'] ?? '' ) );
    $country          = sanitize_text_field( wp_unslash( $_POST['country'] ?? 'United Kingdom' ) );
    $country_other    = sanitize_text_field( wp_unslash( $_POST['country_other'] ?? '' ) );
    if ( 'Other' === $country ) {
        $country = $country_other;
    }
    $phone            = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
    $email            = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $accuracy         = '1' === (string) ( $_POST['accuracy_confirmed'] ?? '' );
    $taxpayer         = '1' === (string) ( $_POST['taxpayer_confirmed'] ?? '' );

    $countries_without_postcode = array( 'Isle of Man', 'Jersey', 'Guernsey' );
    $postcode_required          = ! in_array( $country, $countries_without_postcode, true );

    if ( ! $first_name || ! $last_name || ! $address_line_1 || ! $town_city || ( $postcode_required && ! $postcode ) || ! $country || ! $phone || ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => __( 'Please complete all required contact and home address fields.', 'emc-theme' ) ), 400 );
    }

    if ( 'United Kingdom' === $country && ! emc_is_valid_postcode( $postcode ) ) {
        wp_send_json_error( array( 'message' => __( 'Please enter a valid UK postcode.', 'emc-theme' ) ), 400 );
    }

    if ( ! $accuracy || ! $taxpayer ) {
        wp_send_json_error( array( 'message' => __( 'Both confirmation boxes must be selected to make a Gift Aid declaration.', 'emc-theme' ) ), 400 );
    }

    $rate_key = 'emc_gift_aid_' . md5( strtolower( $email ) );
    if ( get_transient( $rate_key ) ) {
        wp_send_json_error( array( 'message' => __( 'A declaration was just submitted with this email. Please wait before trying again.', 'emc-theme' ) ), 429 );
    }

    $declaration_text = 'I want to Gift Aid my donations to Essex Muslim Centre made in the past 4 years and any donations I make in the future. I am a UK taxpayer and understand that if I pay less Income Tax and/or Capital Gains Tax than the amount of Gift Aid claimed on all my donations in that tax year, it is my responsibility to pay any difference.';
    $data = array(
        'prefix'             => $prefix,
        'first_name'         => $first_name,
        'last_name'          => $last_name,
        'address_line_1'     => $address_line_1,
        'town_city'          => $town_city,
        'postcode'           => $postcode,
        'country'            => $country,
        'phone'              => $phone,
        'email'              => $email,
        'accuracy_confirmed' => true,
        'taxpayer_confirmed' => true,
        'declaration_scope'  => 'past_4_years_and_future',
        'declaration_text'   => $declaration_text,
        'non_uk_donor'       => 'United Kingdom' !== $country ? '1' : '0',
    );

    $declaration_id = emc_store_gift_aid_declaration( $data );
    if ( is_wp_error( $declaration_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Your declaration could not be saved. Please try again.', 'emc-theme' ) ), 500 );
    }

    set_transient( $rate_key, 1, MINUTE_IN_SECONDS );

    $full_name = trim( $prefix . ' ' . $first_name . ' ' . $last_name );
    $to        = sanitize_email( get_option( 'emc_gift_aid_notification_email', 'info@essexmuslimcentre.org' ) );
    $subject   = 'New Gift Aid Declaration — ' . $full_name;
    $body      = '<h2>New Gift Aid Declaration</h2>'
               . '<p><strong>Declaration ID:</strong> #' . absint( $declaration_id ) . '</p>'
               . '<p><strong>Name:</strong> ' . esc_html( $full_name ) . '</p>'
               . '<p><strong>Email:</strong> ' . esc_html( $email ) . '</p>'
               . '<p><strong>Phone:</strong> ' . esc_html( $phone ) . '</p>'
               . '<p><strong>Home address:</strong> ' . esc_html( $address_line_1 . ', ' . $town_city . ', ' . $postcode . ', ' . $country ) . '</p>'
               . '<p><strong>Scope:</strong> Past 4 years and future donations</p>'
               . '<p><strong>Date Submitted:</strong> ' . esc_html( current_time( 'Y-m-d H:i:s' ) ) . '</p>';
    $headers   = array(
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . $full_name . ' <' . $email . '>',
    );
    wp_mail( $to ?: 'info@essexmuslimcentre.org', $subject, $body, $headers );

    $confirmation  = sprintf( __( "Dear %s,\n\nWe have received your Gift Aid declaration for Essex Muslim Centre.", 'emc-theme' ), $full_name );
    $confirmation .= "\n\n" . $declaration_text;
    $confirmation .= "\n\n" . __( 'Please tell us if you want to cancel this declaration, change your name or home address, or no longer pay sufficient UK tax.', 'emc-theme' );
    wp_mail( $email, __( 'Your Essex Muslim Centre Gift Aid declaration', 'emc-theme' ), $confirmation );

    wp_send_json_success( array(
        'message' => __( 'Thank you. Your Gift Aid declaration has been securely recorded.', 'emc-theme' ),
    ) );
}

remove_action( 'wp_ajax_emc_gift_aid', 'emc_handle_gift_aid' );
remove_action( 'wp_ajax_nopriv_emc_gift_aid', 'emc_handle_gift_aid' );
add_action( 'wp_ajax_emc_gift_aid', 'emc_handle_gift_aid_declaration' );
add_action( 'wp_ajax_nopriv_emc_gift_aid', 'emc_handle_gift_aid_declaration' );

/**
 * Gift Aid notification settings.
 */
function emc_register_gift_aid_settings() {
    register_setting( 'emc_gift_aid_settings', 'emc_gift_aid_notification_email', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default'           => 'info@essexmuslimcentre.org',
    ) );
}
add_action( 'admin_init', 'emc_register_gift_aid_settings' );

/**
 * Add a protected admin screen for declaration records.
 */
function emc_gift_aid_admin_menu() {
    add_menu_page(
        __( 'Gift Aid Declarations', 'emc-theme' ),
        __( 'Gift Aid', 'emc-theme' ),
        'manage_options',
        'emc-gift-aid-declarations',
        'emc_gift_aid_admin_page',
        'dashicons-heart',
        26
    );
}
add_action( 'admin_menu', 'emc_gift_aid_admin_menu' );

/**
 * Render Gift Aid declaration submissions in the admin area.
 */
function emc_gift_aid_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $paged = max( 1, absint( $_GET['paged'] ?? 1 ) );
    $query = new WP_Query( array(
        'post_type'      => 'emc_gift_aid',
        'post_status'    => 'private',
        'posts_per_page' => 50,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    $notification_email = sanitize_email( get_option( 'emc_gift_aid_notification_email', 'info@essexmuslimcentre.org' ) );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Gift Aid Declarations', 'emc-theme' ); ?></h1>
        <p><?php esc_html_e( 'Secure declarations submitted through the Gift Aid page. Access is restricted to administrators.', 'emc-theme' ); ?></p>
        <p><strong><?php esc_html_e( 'Record retention:', 'emc-theme' ); ?></strong> <?php esc_html_e( 'HMRC requires declaration records to be kept for six years after the most recent donation on which Gift Aid was claimed.', 'emc-theme' ); ?></p>

        <form action="options.php" method="post" style="margin:20px 0;padding:16px;background:#fff;border:1px solid #dcdcde;max-width:720px;">
            <?php settings_fields( 'emc_gift_aid_settings' ); ?>
            <label for="emc_gift_aid_notification_email"><strong><?php esc_html_e( 'New declaration notification email', 'emc-theme' ); ?></strong></label><br>
            <input type="email" class="regular-text" id="emc_gift_aid_notification_email" name="emc_gift_aid_notification_email" value="<?php echo esc_attr( $notification_email ); ?>" required>
            <?php submit_button( __( 'Save Email', 'emc-theme' ), 'secondary', 'submit', false ); ?>
        </form>

        <?php if ( ! $query->have_posts() ) : ?>
            <div class="notice notice-info inline"><p><?php esc_html_e( 'No Gift Aid declarations have been submitted yet.', 'emc-theme' ); ?></p></div>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Submitted', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Donor', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Home Address', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Contact', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Declaration', 'emc-theme' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ( $query->have_posts() ) : $query->the_post();
                        $declaration_id = get_the_ID();
                        $prefix         = get_post_meta( $declaration_id, '_emc_gift_aid_prefix', true );
                        $first_name     = get_post_meta( $declaration_id, '_emc_gift_aid_first_name', true );
                        $last_name      = get_post_meta( $declaration_id, '_emc_gift_aid_last_name', true );
                        $address        = get_post_meta( $declaration_id, '_emc_gift_aid_address_line_1', true );
                        $town_city      = get_post_meta( $declaration_id, '_emc_gift_aid_town_city', true );
                        $postcode       = get_post_meta( $declaration_id, '_emc_gift_aid_postcode', true );
                        $country        = get_post_meta( $declaration_id, '_emc_gift_aid_country', true );
                        $non_uk_donor   = '1' === get_post_meta( $declaration_id, '_emc_gift_aid_non_uk_donor', true );
                        $phone          = get_post_meta( $declaration_id, '_emc_gift_aid_phone', true );
                        $email          = get_post_meta( $declaration_id, '_emc_gift_aid_email', true );
                        $submitted      = get_post_meta( $declaration_id, '_emc_gift_aid_submitted_at', true );
                        $declaration    = get_post_meta( $declaration_id, '_emc_gift_aid_declaration_text', true );
                    ?>
                    <tr>
                        <td>
                            <?php echo esc_html( $submitted ?: get_the_date( 'Y-m-d H:i:s' ) ); ?><br>
                            <small>#<?php echo esc_html( $declaration_id ); ?></small>
                        </td>
                        <td><strong><?php echo esc_html( trim( $prefix . ' ' . $first_name . ' ' . $last_name ) ); ?></strong></td>
                        <td>
                            <?php echo esc_html( $address ); ?><br>
                            <?php echo esc_html( $town_city ); ?><br>
                            <?php echo esc_html( $postcode ); ?><br>
                            <?php echo esc_html( $country ); ?>
                            <?php if ( $non_uk_donor ) : ?><br><strong><?php esc_html_e( 'Non-UK donor', 'emc-theme' ); ?></strong><?php endif; ?>
                        </td>
                        <td>
                            <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a><br>
                            <?php echo esc_html( $phone ); ?>
                        </td>
                        <td>
                            <strong><?php esc_html_e( 'Past 4 years and future donations', 'emc-theme' ); ?></strong><br>
                            <small><?php esc_html_e( 'Accuracy and UK taxpayer declarations confirmed.', 'emc-theme' ); ?></small>
                            <?php if ( $declaration ) : ?>
                            <details style="margin-top:6px;">
                                <summary><?php esc_html_e( 'View declaration wording', 'emc-theme' ); ?></summary>
                                <p><?php echo esc_html( $declaration ); ?></p>
                            </details>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; wp_reset_postdata(); ?>
                </tbody>
            </table>

            <?php
            $pagination = paginate_links( array(
                'base'      => add_query_arg( 'paged', '%#%' ),
                'format'    => '',
                'current'   => $paged,
                'total'     => max( 1, $query->max_num_pages ),
                'prev_text' => __( '&laquo; Previous', 'emc-theme' ),
                'next_text' => __( 'Next &raquo;', 'emc-theme' ),
            ) );
            if ( $pagination ) {
                echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $pagination ) . '</div></div>';
            }
            ?>
        <?php endif; ?>
    </div>
    <?php
}
