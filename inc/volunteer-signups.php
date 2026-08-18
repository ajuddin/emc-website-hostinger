<?php
/**
 * Separate volunteer application workflow.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

function emc_register_volunteer_signup_type() {
    register_post_type( 'emc_volunteer_signup', array(
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
add_action( 'init', 'emc_register_volunteer_signup_type' );

/** Create the dedicated volunteer page without changing the job application page. */
function emc_ensure_volunteer_signup_page() {
    $page = get_page_by_path( 'volunteer', OBJECT, 'page' );
    if ( $page ) {
        if ( 'page-volunteer-registration.php' !== get_post_meta( $page->ID, '_wp_page_template', true ) || __( 'Volunteer With Us', 'emc-theme' ) !== $page->post_title ) {
            wp_update_post( array(
                'ID'         => $page->ID,
                'post_title' => __( 'Volunteer With Us', 'emc-theme' ),
                'meta_input' => array( '_wp_page_template' => 'page-volunteer-registration.php' ),
            ) );
        }
        return;
    }
    wp_insert_post( array(
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_title'   => __( 'Volunteer With Us', 'emc-theme' ),
        'post_name'    => 'volunteer',
        'post_content' => '<!-- Managed by the Volunteer Application page template. -->',
        'meta_input'   => array( '_wp_page_template' => 'page-volunteer-registration.php' ),
    ) );
}
add_action( 'init', 'emc_ensure_volunteer_signup_page', 21 );

/** Move legacy volunteer records out of the job application store once. */
function emc_migrate_legacy_volunteer_signups() {
    if ( get_option( 'emc_volunteer_signup_migration_complete' ) ) {
        return;
    }
    $ids = get_posts( array(
        'post_type' => 'emc_volunteer', 'post_status' => 'private', 'posts_per_page' => -1,
        'fields' => 'ids', 'no_found_rows' => true,
    ) );
    $fields = array( 'first_name', 'last_name', 'email', 'phone', 'postcode', 'over_18', 'interests', 'interest_other', 'availability', 'availability_details', 'skills', 'motivation', 'checks_consent', 'privacy_consent', 'submitted_at', 'status', 'email_status' );
    foreach ( $ids as $id ) {
        if ( get_post_meta( $id, '_emc_volunteer_position', true ) || ! get_post_meta( $id, '_emc_volunteer_interests', true ) ) {
            continue;
        }
        wp_update_post( array( 'ID' => $id, 'post_type' => 'emc_volunteer_signup' ) );
        foreach ( $fields as $field ) {
            update_post_meta( $id, '_emc_volunteer_signup_' . $field, get_post_meta( $id, '_emc_volunteer_' . $field, true ) );
        }
    }
    update_option( 'emc_volunteer_signup_migration_complete', '1', false );
}
add_action( 'init', 'emc_migrate_legacy_volunteer_signups', 30 );

function emc_get_volunteer_url( $role = '' ) {
    $page = get_page_by_path( 'volunteer', OBJECT, 'page' );
    $url  = $page ? get_permalink( $page ) : home_url( '/volunteer/' );
    if ( $role ) {
        $url = add_query_arg( 'role', sanitize_text_field( $role ), $url );
    }
    return $url . '#volunteer-registration';
}

function emc_store_volunteer_signup( $data ) {
    $full_name = trim( $data['first_name'] . ' ' . $data['last_name'] );
    $post_id   = wp_insert_post( array(
        'post_type'   => 'emc_volunteer_signup',
        'post_status' => 'private',
        'post_title'  => sprintf( '%s — %s', $full_name, current_time( 'Y-m-d H:i:s' ) ),
    ), true );
    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }
    foreach ( array( 'first_name', 'last_name', 'email', 'phone', 'postcode', 'over_18', 'role', 'interests', 'interest_other', 'availability', 'availability_details', 'skills', 'motivation' ) as $field ) {
        update_post_meta( $post_id, '_emc_volunteer_signup_' . $field, $data[ $field ] ?? '' );
    }
    update_post_meta( $post_id, '_emc_volunteer_signup_checks_consent', ! empty( $data['checks_consent'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_emc_volunteer_signup_privacy_consent', ! empty( $data['privacy_consent'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_emc_volunteer_signup_submitted_at', current_time( 'mysql' ) );
    update_post_meta( $post_id, '_emc_volunteer_signup_status', 'new' );
    return $post_id;
}

function emc_handle_volunteer_signup() {
    check_ajax_referer( 'emc_nonce', 'nonce' );
    if ( ! empty( $_POST['website'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Unable to submit this application.', 'emc-theme' ) ), 400 );
    }

    $allowed_interests = array( 'events', 'education', 'welfare', 'fundraising', 'admin', 'facilities', 'media', 'other' );
    $allowed_times     = array( 'weekday_day', 'weekday_evening', 'saturday', 'sunday', 'occasional' );
    $first_name        = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
    $last_name         = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
    $email             = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $phone             = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
    $postcode          = emc_normalize_postcode( wp_unslash( $_POST['postcode'] ?? '' ) );
    $over_18           = sanitize_key( wp_unslash( $_POST['over_18'] ?? '' ) );
    $role              = sanitize_text_field( wp_unslash( $_POST['role'] ?? '' ) );
    $interests         = array_values( array_intersect( $allowed_interests, array_map( 'sanitize_key', (array) ( $_POST['interests'] ?? array() ) ) ) );
    $availability      = array_values( array_intersect( $allowed_times, array_map( 'sanitize_key', (array) ( $_POST['availability'] ?? array() ) ) ) );
    $interest_other    = sanitize_text_field( wp_unslash( $_POST['interest_other'] ?? '' ) );
    $availability_info = sanitize_textarea_field( wp_unslash( $_POST['availability_details'] ?? '' ) );
    $skills            = sanitize_textarea_field( wp_unslash( $_POST['skills'] ?? '' ) );
    $motivation        = sanitize_textarea_field( wp_unslash( $_POST['motivation'] ?? '' ) );
    $checks_consent    = '1' === (string) ( $_POST['checks_consent'] ?? '' );
    $privacy_consent   = '1' === (string) ( $_POST['privacy_consent'] ?? '' );

    if ( ! $first_name || ! $last_name || ! is_email( $email ) || ! $phone || ! emc_is_valid_postcode( $postcode ) || ! in_array( $over_18, array( 'yes', 'no' ), true ) || ! $role ) {
        wp_send_json_error( array( 'message' => __( 'Please complete all required personal details and enter a valid UK postcode.', 'emc-theme' ) ), 400 );
    }
    if ( ! $interests || ! $availability ) {
        wp_send_json_error( array( 'message' => __( 'Please select at least one volunteering interest and one availability option.', 'emc-theme' ) ), 400 );
    }
    if ( in_array( 'other', $interests, true ) && ! $interest_other ) {
        wp_send_json_error( array( 'message' => __( 'Please describe your other volunteering interest.', 'emc-theme' ) ), 400 );
    }
    if ( ! $motivation || ! $checks_consent || ! $privacy_consent ) {
        wp_send_json_error( array( 'message' => __( 'Please explain why you would like to volunteer and accept both confirmations.', 'emc-theme' ) ), 400 );
    }

    $rate_key = 'emc_volunteer_signup_' . md5( strtolower( $email ) );
    if ( get_transient( $rate_key ) ) {
        wp_send_json_error( array( 'message' => __( 'An application was just submitted with this email. Please wait before trying again.', 'emc-theme' ) ), 429 );
    }

    $data = array(
        'first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'phone' => $phone,
        'postcode' => $postcode, 'over_18' => $over_18, 'role' => $role, 'interests' => implode( ', ', $interests ),
        'interest_other' => $interest_other, 'availability' => implode( ', ', $availability ),
        'availability_details' => $availability_info, 'skills' => $skills, 'motivation' => $motivation,
        'checks_consent' => true, 'privacy_consent' => true,
    );
    $application_id = emc_store_volunteer_signup( $data );
    if ( is_wp_error( $application_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Your application could not be saved. Please try again.', 'emc-theme' ) ), 500 );
    }
    set_transient( $rate_key, 1, MINUTE_IN_SECONDS );

    $full_name = trim( $first_name . ' ' . $last_name );
    $body = emc_form_notification_html( __( 'New Volunteer Application', 'emc-theme' ), array(
        __( 'Application ID', 'emc-theme' ) => '#' . absint( $application_id ), __( 'Name', 'emc-theme' ) => $full_name,
        __( 'Email', 'emc-theme' ) => $email, __( 'Phone', 'emc-theme' ) => $phone, __( 'Postcode', 'emc-theme' ) => $postcode,
        __( 'Over 18', 'emc-theme' ) => $over_18, __( 'Volunteer role', 'emc-theme' ) => $role, __( 'Interests', 'emc-theme' ) => $interests,
        __( 'Other interest', 'emc-theme' ) => $interest_other, __( 'Availability', 'emc-theme' ) => $availability,
        __( 'Availability details', 'emc-theme' ) => $availability_info, __( 'Skills and experience', 'emc-theme' ) => $skills,
        __( 'Reason for volunteering', 'emc-theme' ) => $motivation, __( 'Submitted at', 'emc-theme' ) => current_time( 'mysql' ),
    ) );
    $sent = emc_send_form_notification( 'volunteer_signup', 'New Volunteer Application — ' . $full_name, $body, array( 'Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . $full_name . ' <' . $email . '>' ) );
    update_post_meta( $application_id, '_emc_volunteer_signup_email_status', $sent ? 'sent' : 'failed' );
    wp_mail( $email, __( 'Your Essex Muslim Centre volunteer application', 'emc-theme' ), sprintf( __( "Dear %s,\n\nThank you for applying to volunteer with Essex Muslim Centre. Our team will review your application and contact you when a suitable opportunity is available.", 'emc-theme' ), $full_name ) );
    wp_send_json_success( array( 'message' => __( 'Thank you. Your volunteer application has been received.', 'emc-theme' ) ) );
}
add_action( 'wp_ajax_emc_volunteer_signup', 'emc_handle_volunteer_signup' );
add_action( 'wp_ajax_nopriv_emc_volunteer_signup', 'emc_handle_volunteer_signup' );

function emc_volunteer_signup_admin_menu() {
    add_submenu_page( null, __( 'Volunteer Applications', 'emc-theme' ), __( 'Volunteer Applications', 'emc-theme' ), 'manage_options', 'emc-volunteer-applications', 'emc_volunteer_signup_admin_page' );
}
add_action( 'admin_menu', 'emc_volunteer_signup_admin_menu' );

function emc_volunteer_signup_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $query = new WP_Query( array( 'post_type' => 'emc_volunteer_signup', 'post_status' => 'private', 'posts_per_page' => 100, 'orderby' => 'date', 'order' => 'DESC' ) );
    ?>
    <div class="wrap"><h1><?php esc_html_e( 'Volunteer Applications', 'emc-theme' ); ?></h1><p><?php esc_html_e( 'Applications submitted through the separate Volunteer With Us page.', 'emc-theme' ); ?></p>
    <?php if ( ! $query->have_posts() ) : ?><div class="notice notice-info inline"><p><?php esc_html_e( 'No volunteer applications have been submitted yet.', 'emc-theme' ); ?></p></div><?php else : ?>
    <table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Submitted', 'emc-theme' ); ?></th><th><?php esc_html_e( 'Applicant', 'emc-theme' ); ?></th><th><?php esc_html_e( 'Contact', 'emc-theme' ); ?></th><th><?php esc_html_e( 'Volunteer Role', 'emc-theme' ); ?></th><th><?php esc_html_e( 'Interests', 'emc-theme' ); ?></th><th><?php esc_html_e( 'Availability', 'emc-theme' ); ?></th><th><?php esc_html_e( 'Details', 'emc-theme' ); ?></th></tr></thead><tbody>
    <?php while ( $query->have_posts() ) : $query->the_post(); $id = get_the_ID(); ?>
    <tr><td><?php echo esc_html( get_post_meta( $id, '_emc_volunteer_signup_submitted_at', true ) ); ?><br><small>#<?php echo absint( $id ); ?></small></td>
    <td><strong><?php echo esc_html( trim( get_post_meta( $id, '_emc_volunteer_signup_first_name', true ) . ' ' . get_post_meta( $id, '_emc_volunteer_signup_last_name', true ) ) ); ?></strong></td>
    <td><a href="mailto:<?php echo esc_attr( get_post_meta( $id, '_emc_volunteer_signup_email', true ) ); ?>"><?php echo esc_html( get_post_meta( $id, '_emc_volunteer_signup_email', true ) ); ?></a><br><?php echo esc_html( get_post_meta( $id, '_emc_volunteer_signup_phone', true ) ); ?></td>
    <td><?php echo esc_html( get_post_meta( $id, '_emc_volunteer_signup_role', true ) ); ?></td><td><?php echo esc_html( get_post_meta( $id, '_emc_volunteer_signup_interests', true ) ); ?></td><td><?php echo esc_html( get_post_meta( $id, '_emc_volunteer_signup_availability', true ) ); ?></td>
    <td><details><summary><?php esc_html_e( 'View answers', 'emc-theme' ); ?></summary><p><strong><?php esc_html_e( 'Skills and experience:', 'emc-theme' ); ?></strong><br><?php echo nl2br( esc_html( get_post_meta( $id, '_emc_volunteer_signup_skills', true ) ) ); ?></p><p><strong><?php esc_html_e( 'Reason for volunteering:', 'emc-theme' ); ?></strong><br><?php echo nl2br( esc_html( get_post_meta( $id, '_emc_volunteer_signup_motivation', true ) ) ); ?></p></details></td></tr>
    <?php endwhile; wp_reset_postdata(); ?></tbody></table><?php endif; ?></div>
    <?php
}
