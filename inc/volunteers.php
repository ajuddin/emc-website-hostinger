<?php
/**
 * Volunteer application storage, submission handling, and admin records.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

function emc_register_volunteer_application_type() {
    register_post_type( 'emc_volunteer', array(
        'labels' => array(
            'name'          => __( 'Volunteer Applications', 'emc-theme' ),
            'singular_name' => __( 'Volunteer Application', 'emc-theme' ),
        ),
        'public'              => false,
        'show_ui'             => false,
        'show_in_rest'        => false,
        'exclude_from_search' => true,
        'supports'            => array( 'title' ),
    ) );
}
add_action( 'init', 'emc_register_volunteer_application_type' );

function emc_ensure_volunteer_page() {
    if ( get_page_by_path( 'volunteer', OBJECT, 'page' ) ) {
        return;
    }

    wp_insert_post( array(
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_title'   => __( 'Volunteer With Us', 'emc-theme' ),
        'post_name'    => 'volunteer',
        'post_content' => '<!-- Managed by the Volunteer Registration page template. -->',
        'meta_input'   => array(
            '_wp_page_template' => 'page-volunteer.php',
        ),
    ) );
}
add_action( 'init', 'emc_ensure_volunteer_page', 20 );

/**
 * Return the public volunteer registration form URL.
 *
 * @return string
 */
function emc_get_volunteer_url() {
    $page = get_page_by_path( 'volunteer', OBJECT, 'page' );
    $url  = $page ? get_permalink( $page ) : home_url( '/volunteer/' );

    return $url . '#volunteer-registration';
}

/**
 * Store a volunteer application.
 *
 * @param array $data Sanitized application fields.
 * @return int|WP_Error
 */
function emc_store_volunteer_application( $data ) {
    $full_name = trim( ( $data['first_name'] ?? '' ) . ' ' . ( $data['last_name'] ?? '' ) );
    $post_id   = wp_insert_post( array(
        'post_type'   => 'emc_volunteer',
        'post_status' => 'private',
        'post_title'  => sprintf( '%s — %s', $full_name, current_time( 'Y-m-d H:i:s' ) ),
    ), true );

    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }

    $fields = array(
        'first_name',
        'last_name',
        'email',
        'phone',
        'postcode',
        'over_18',
        'interests',
        'interest_other',
        'availability',
        'availability_details',
        'skills',
        'motivation',
    );

    foreach ( $fields as $field ) {
        update_post_meta( $post_id, '_emc_volunteer_' . $field, $data[ $field ] ?? '' );
    }

    update_post_meta( $post_id, '_emc_volunteer_checks_consent', ! empty( $data['checks_consent'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_emc_volunteer_privacy_consent', ! empty( $data['privacy_consent'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_emc_volunteer_submitted_at', current_time( 'mysql' ) );
    update_post_meta( $post_id, '_emc_volunteer_status', 'new' );

    return $post_id;
}

function emc_handle_volunteer_application() {
    check_ajax_referer( 'emc_nonce', 'nonce' );

    if ( ! empty( $_POST['website'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Unable to submit this application.', 'emc-theme' ) ), 400 );
    }

    $allowed_interests = array( 'events', 'education', 'welfare', 'fundraising', 'admin', 'facilities', 'media', 'other' );
    $allowed_times     = array( 'weekday_day', 'weekday_evening', 'saturday', 'sunday', 'occasional' );

    $first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
    $last_name  = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
    $email      = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $phone      = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
    $postcode   = emc_normalize_postcode( wp_unslash( $_POST['postcode'] ?? '' ) );
    $over_18    = sanitize_text_field( wp_unslash( $_POST['over_18'] ?? '' ) );
    $interests  = array_values( array_intersect(
        $allowed_interests,
        array_map( 'sanitize_key', (array) ( $_POST['interests'] ?? array() ) )
    ) );
    $availability = array_values( array_intersect(
        $allowed_times,
        array_map( 'sanitize_key', (array) ( $_POST['availability'] ?? array() ) )
    ) );
    $interest_other      = sanitize_text_field( wp_unslash( $_POST['interest_other'] ?? '' ) );
    $availability_detail = sanitize_textarea_field( wp_unslash( $_POST['availability_details'] ?? '' ) );
    $skills              = sanitize_textarea_field( wp_unslash( $_POST['skills'] ?? '' ) );
    $motivation          = sanitize_textarea_field( wp_unslash( $_POST['motivation'] ?? '' ) );
    $checks_consent      = '1' === (string) ( $_POST['checks_consent'] ?? '' );
    $privacy_consent     = '1' === (string) ( $_POST['privacy_consent'] ?? '' );

    if ( ! $first_name || ! $last_name || ! is_email( $email ) || ! $phone || ! emc_is_valid_postcode( $postcode ) || ! in_array( $over_18, array( 'yes', 'no' ), true ) ) {
        wp_send_json_error( array( 'message' => __( 'Please complete all required personal details and enter a valid UK postcode.', 'emc-theme' ) ), 400 );
    }

    if ( empty( $interests ) || empty( $availability ) ) {
        wp_send_json_error( array( 'message' => __( 'Please select at least one volunteering interest and one availability option.', 'emc-theme' ) ), 400 );
    }

    if ( in_array( 'other', $interests, true ) && ! $interest_other ) {
        wp_send_json_error( array( 'message' => __( 'Please describe your other volunteering interest.', 'emc-theme' ) ), 400 );
    }

    if ( ! $motivation || ! $checks_consent || ! $privacy_consent ) {
        wp_send_json_error( array( 'message' => __( 'Please provide your reason for volunteering and accept both confirmation statements.', 'emc-theme' ) ), 400 );
    }

    $rate_key = 'emc_volunteer_' . md5( strtolower( $email ) );
    if ( get_transient( $rate_key ) ) {
        wp_send_json_error( array( 'message' => __( 'An application was just submitted with this email. Please wait before trying again.', 'emc-theme' ) ), 429 );
    }

    $data = array(
        'first_name'           => $first_name,
        'last_name'            => $last_name,
        'email'                => $email,
        'phone'                => $phone,
        'postcode'             => $postcode,
        'over_18'              => $over_18,
        'interests'            => implode( ', ', $interests ),
        'interest_other'       => $interest_other,
        'availability'         => implode( ', ', $availability ),
        'availability_details' => $availability_detail,
        'skills'               => $skills,
        'motivation'           => $motivation,
        'checks_consent'       => true,
        'privacy_consent'      => true,
    );

    $application_id = emc_store_volunteer_application( $data );
    if ( is_wp_error( $application_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Your application could not be saved. Please try again.', 'emc-theme' ) ), 500 );
    }

    set_transient( $rate_key, 1, MINUTE_IN_SECONDS );

    $full_name = trim( $first_name . ' ' . $last_name );
    $to        = sanitize_email( get_option( 'emc_volunteer_notification_email', 'info@essexmuslimcentre.org' ) );
    $subject   = 'New Volunteer Application — ' . $full_name;
    $body      = '<h2>New Volunteer Application</h2>'
               . '<p><strong>Application ID:</strong> #' . absint( $application_id ) . '</p>'
               . '<p><strong>Name:</strong> ' . esc_html( $full_name ) . '</p>'
               . '<p><strong>Email:</strong> ' . esc_html( $email ) . '</p>'
               . '<p><strong>Phone:</strong> ' . esc_html( $phone ) . '</p>'
               . '<p><strong>Postcode:</strong> ' . esc_html( $postcode ) . '</p>'
               . '<p><strong>Interests:</strong> ' . esc_html( implode( ', ', $interests ) ) . '</p>'
               . '<p><strong>Availability:</strong> ' . esc_html( implode( ', ', $availability ) ) . '</p>'
               . '<p><strong>Motivation:</strong><br>' . nl2br( esc_html( $motivation ) ) . '</p>';
    $headers   = array(
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . $full_name . ' <' . $email . '>',
    );
    wp_mail( $to ?: 'info@essexmuslimcentre.org', $subject, $body, $headers );

    $confirmation  = sprintf( __( "Dear %s,\n\nThank you for applying to volunteer with Essex Muslim Centre.", 'emc-theme' ), $full_name );
    $confirmation .= "\n\n" . __( 'Our team will review your application and contact you if a suitable opportunity is available.', 'emc-theme' );
    wp_mail( $email, __( 'Your Essex Muslim Centre volunteer application', 'emc-theme' ), $confirmation );

    wp_send_json_success( array(
        'message' => __( 'Thank you. Your volunteer application has been received.', 'emc-theme' ),
    ) );
}
add_action( 'wp_ajax_emc_volunteer_application', 'emc_handle_volunteer_application' );
add_action( 'wp_ajax_nopriv_emc_volunteer_application', 'emc_handle_volunteer_application' );

function emc_register_volunteer_settings() {
    register_setting( 'emc_volunteer_settings', 'emc_volunteer_notification_email', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default'           => 'info@essexmuslimcentre.org',
    ) );
}
add_action( 'admin_init', 'emc_register_volunteer_settings' );

function emc_volunteer_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=emc_vacancy',
        __( 'Volunteer Applications', 'emc-theme' ),
        __( 'Volunteer Applications', 'emc-theme' ),
        'manage_options',
        'emc-volunteer-applications',
        'emc_volunteer_admin_page'
    );
}
add_action( 'admin_menu', 'emc_volunteer_admin_menu' );

function emc_volunteer_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $paged = max( 1, absint( $_GET['paged'] ?? 1 ) );
    $query = new WP_Query( array(
        'post_type'      => 'emc_volunteer',
        'post_status'    => 'private',
        'posts_per_page' => 50,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );
    $notification_email = sanitize_email( get_option( 'emc_volunteer_notification_email', 'info@essexmuslimcentre.org' ) );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Volunteer Applications', 'emc-theme' ); ?></h1>
        <p><?php esc_html_e( 'Applications submitted through the Volunteer With Us page. Access is restricted to administrators.', 'emc-theme' ); ?></p>

        <form action="options.php" method="post" style="margin:20px 0;padding:16px;background:#fff;border:1px solid #dcdcde;max-width:720px;">
            <?php settings_fields( 'emc_volunteer_settings' ); ?>
            <label for="emc_volunteer_notification_email"><strong><?php esc_html_e( 'New application notification email', 'emc-theme' ); ?></strong></label><br>
            <input type="email" class="regular-text" id="emc_volunteer_notification_email" name="emc_volunteer_notification_email" value="<?php echo esc_attr( $notification_email ); ?>" required>
            <?php submit_button( __( 'Save Email', 'emc-theme' ), 'secondary', 'submit', false ); ?>
        </form>

        <?php if ( ! $query->have_posts() ) : ?>
            <div class="notice notice-info inline"><p><?php esc_html_e( 'No volunteer applications have been submitted yet.', 'emc-theme' ); ?></p></div>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Submitted', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Applicant', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Contact', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Interests', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Availability', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Application Details', 'emc-theme' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ( $query->have_posts() ) : $query->the_post();
                        $application_id = get_the_ID();
                        $first_name     = get_post_meta( $application_id, '_emc_volunteer_first_name', true );
                        $last_name      = get_post_meta( $application_id, '_emc_volunteer_last_name', true );
                        $email          = get_post_meta( $application_id, '_emc_volunteer_email', true );
                        $phone          = get_post_meta( $application_id, '_emc_volunteer_phone', true );
                        $postcode       = get_post_meta( $application_id, '_emc_volunteer_postcode', true );
                        $interests      = get_post_meta( $application_id, '_emc_volunteer_interests', true );
                        $other          = get_post_meta( $application_id, '_emc_volunteer_interest_other', true );
                        $availability   = get_post_meta( $application_id, '_emc_volunteer_availability', true );
                        $availability_details = get_post_meta( $application_id, '_emc_volunteer_availability_details', true );
                        $skills         = get_post_meta( $application_id, '_emc_volunteer_skills', true );
                        $motivation     = get_post_meta( $application_id, '_emc_volunteer_motivation', true );
                        $over_18        = get_post_meta( $application_id, '_emc_volunteer_over_18', true );
                        $submitted      = get_post_meta( $application_id, '_emc_volunteer_submitted_at', true );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $submitted ?: get_the_date( 'Y-m-d H:i:s' ) ); ?><br><small>#<?php echo esc_html( $application_id ); ?></small></td>
                        <td><strong><?php echo esc_html( trim( $first_name . ' ' . $last_name ) ); ?></strong><br><small><?php echo esc_html( 'yes' === $over_18 ? '18 or over' : 'Under 18' ); ?></small></td>
                        <td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a><br><?php echo esc_html( $phone ); ?><br><?php echo esc_html( $postcode ); ?></td>
                        <td><?php echo esc_html( $interests ); ?><?php if ( $other ) : ?><br><?php echo esc_html( $other ); ?><?php endif; ?></td>
                        <td><?php echo esc_html( $availability ); ?><?php if ( $availability_details ) : ?><br><small><?php echo esc_html( $availability_details ); ?></small><?php endif; ?></td>
                        <td>
                            <details>
                                <summary><?php esc_html_e( 'View answers', 'emc-theme' ); ?></summary>
                                <p><strong><?php esc_html_e( 'Skills and experience:', 'emc-theme' ); ?></strong><br><?php echo nl2br( esc_html( $skills ) ); ?></p>
                                <p><strong><?php esc_html_e( 'Why they want to volunteer:', 'emc-theme' ); ?></strong><br><?php echo nl2br( esc_html( $motivation ) ); ?></p>
                            </details>
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
