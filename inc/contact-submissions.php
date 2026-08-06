<?php
/**
 * Contact form storage, email notifications, and administrator records.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register private contact messages.
 */
function emc_register_contact_submission_type() {
    register_post_type( 'emc_contact_entry', array(
        'labels' => array(
            'name'          => __( 'Contact Messages', 'emc-theme' ),
            'singular_name' => __( 'Contact Message', 'emc-theme' ),
        ),
        'public'              => false,
        'show_ui'             => false,
        'show_in_rest'        => false,
        'exclude_from_search' => true,
        'supports'            => array( 'title' ),
    ) );
}
add_action( 'init', 'emc_register_contact_submission_type' );

/**
 * Public labels for the supported enquiry types.
 */
function emc_contact_subjects() {
    return array(
        'general'   => __( 'General Enquiry', 'emc-theme' ),
        'services'  => __( 'Services & Programmes', 'emc-theme' ),
        'donations' => __( 'Donations & Finance', 'emc-theme' ),
        'reversion' => __( 'Reversion to Islam', 'emc-theme' ),
        'feedback'  => __( 'Feedback / Complaint', 'emc-theme' ),
    );
}

/**
 * Store one sanitized contact message.
 *
 * @return int|WP_Error
 */
function emc_store_contact_submission( $data ) {
    $full_name = trim( $data['first_name'] . ' ' . $data['last_name'] );
    $post_id   = wp_insert_post( array(
        'post_type'   => 'emc_contact_entry',
        'post_status' => 'private',
        'post_title'  => sprintf( '%s — %s', $full_name, current_time( 'Y-m-d H:i:s' ) ),
    ), true );

    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }

    foreach ( array( 'first_name', 'last_name', 'email', 'subject', 'subject_label', 'message' ) as $field ) {
        update_post_meta( $post_id, '_emc_contact_' . $field, $data[ $field ] );
    }

    update_post_meta( $post_id, '_emc_contact_submitted_at', current_time( 'mysql' ) );
    update_post_meta( $post_id, '_emc_contact_status', 'new' );
    update_post_meta( $post_id, '_emc_contact_email_status', 'pending' );

    return $post_id;
}

/**
 * Handle the public contact form.
 */
function emc_handle_contact_submission() {
    check_ajax_referer( 'emc_contact_submission', 'nonce' );

    if ( ! empty( $_POST['website'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Unable to submit this message.', 'emc-theme' ) ), 400 );
    }

    $first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
    $last_name  = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
    $email      = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $subject    = sanitize_key( wp_unslash( $_POST['subject'] ?? '' ) );
    $message    = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
    $subjects   = emc_contact_subjects();

    if ( ! $first_name || ! $last_name || ! is_email( $email ) || ! isset( $subjects[ $subject ] ) || ! $message ) {
        wp_send_json_error( array( 'message' => __( 'Please complete all required fields.', 'emc-theme' ) ), 400 );
    }

    $rate_key = 'emc_contact_' . md5( strtolower( $email ) );
    if ( get_transient( $rate_key ) ) {
        wp_send_json_error( array( 'message' => __( 'A message was just submitted with this email. Please wait before trying again.', 'emc-theme' ) ), 429 );
    }

    $data = array(
        'first_name'    => $first_name,
        'last_name'     => $last_name,
        'email'         => $email,
        'subject'       => $subject,
        'subject_label' => $subjects[ $subject ],
        'message'       => $message,
    );

    $submission_id = emc_store_contact_submission( $data );
    if ( is_wp_error( $submission_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Your message could not be saved. Please try again.', 'emc-theme' ) ), 500 );
    }

    set_transient( $rate_key, 1, MINUTE_IN_SECONDS );

    $full_name = trim( $first_name . ' ' . $last_name );
    $body      = emc_form_notification_html( $subjects[ $subject ], array(
        __( 'Submission ID', 'emc-theme' ) => '#' . absint( $submission_id ),
        __( 'First name', 'emc-theme' )    => $first_name,
        __( 'Last name', 'emc-theme' )     => $last_name,
        __( 'Email', 'emc-theme' )         => $email,
        __( 'Subject', 'emc-theme' )       => $subjects[ $subject ],
        __( 'Message', 'emc-theme' )       => $message,
        __( 'Submitted at', 'emc-theme' )  => current_time( 'mysql' ),
    ) );
    $headers   = array(
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . $full_name . ' <' . $email . '>',
    );
    $sent      = emc_send_form_notification(
        'contact',
        'EMC Website Enquiry: ' . $subjects[ $subject ],
        $body,
        $headers
    );

    update_post_meta( $submission_id, '_emc_contact_email_status', $sent ? 'sent' : 'failed' );

    wp_send_json_success( array(
        'message' => __( 'Message received successfully. We will be in touch shortly.', 'emc-theme' ),
    ) );
}
add_action( 'wp_ajax_emc_contact', 'emc_handle_contact_submission' );
add_action( 'wp_ajax_nopriv_emc_contact', 'emc_handle_contact_submission' );
add_action( 'wp_ajax_emc_contact_form', 'emc_handle_contact_submission' );
add_action( 'wp_ajax_nopriv_emc_contact_form', 'emc_handle_contact_submission' );

/**
 * Add the Contact Messages administrator screen.
 */
function emc_contact_submissions_admin_menu() {
    add_submenu_page(
        null,
        __( 'Contact Messages', 'emc-theme' ),
        __( 'Contact Messages', 'emc-theme' ),
        'manage_options',
        'emc-contact-messages',
        'emc_contact_submissions_admin_page'
    );
}
add_action( 'admin_menu', 'emc_contact_submissions_admin_menu' );

/**
 * Contact message listing.
 */
function emc_contact_submissions_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $paged = max( 1, absint( $_GET['paged'] ?? 1 ) );
    $query = new WP_Query( array(
        'post_type'      => 'emc_contact_entry',
        'post_status'    => 'private',
        'posts_per_page' => 50,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Contact Messages', 'emc-theme' ); ?></h1>
        <p><?php esc_html_e( 'Messages submitted through the Contact Us form are stored here. Notification delivery is shown separately from the saved submission.', 'emc-theme' ); ?></p>

        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Submitted', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Sender', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Subject', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Message', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Email Notification', 'emc-theme' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $query->have_posts() ) : ?>
                    <?php while ( $query->have_posts() ) : $query->the_post();
                        $submission_id = get_the_ID();
                        $first_name    = get_post_meta( $submission_id, '_emc_contact_first_name', true );
                        $last_name     = get_post_meta( $submission_id, '_emc_contact_last_name', true );
                        $email         = get_post_meta( $submission_id, '_emc_contact_email', true );
                        $subject       = get_post_meta( $submission_id, '_emc_contact_subject_label', true );
                        $message       = get_post_meta( $submission_id, '_emc_contact_message', true );
                        $submitted     = get_post_meta( $submission_id, '_emc_contact_submitted_at', true );
                        $email_status  = get_post_meta( $submission_id, '_emc_contact_email_status', true );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $submitted ?: get_the_date( 'Y-m-d H:i:s' ) ); ?><br><small>#<?php echo esc_html( $submission_id ); ?></small></td>
                        <td><strong><?php echo esc_html( trim( $first_name . ' ' . $last_name ) ); ?></strong><br><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td>
                        <td><?php echo esc_html( $subject ); ?></td>
                        <td>
                            <details>
                                <summary><?php echo esc_html( wp_trim_words( $message, 12, '…' ) ); ?></summary>
                                <p><?php echo nl2br( esc_html( $message ) ); ?></p>
                            </details>
                        </td>
                        <td>
                            <?php if ( 'sent' === $email_status ) : ?>
                                <span style="color:#16723a;"><?php esc_html_e( 'Sent', 'emc-theme' ); ?></span>
                            <?php elseif ( 'failed' === $email_status ) : ?>
                                <span style="color:#b32d2e;"><?php esc_html_e( 'Failed — message still saved', 'emc-theme' ); ?></span>
                            <?php else : ?>
                                <?php esc_html_e( 'Pending', 'emc-theme' ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php else : ?>
                    <tr><td colspan="5"><?php esc_html_e( 'No contact messages have been submitted yet.', 'emc-theme' ); ?></td></tr>
                <?php endif; ?>
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
    </div>
    <?php
}
