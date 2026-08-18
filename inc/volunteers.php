<?php
/**
 * Job application storage, submission handling, and admin records.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/** Return administrator-defined questions shown after the standard job fields. */
function emc_job_application_fields() {
    $fields = get_option( 'emc_job_application_fields', array() );
    return is_array( $fields ) ? $fields : array();
}

/** Sanitize the job application field-builder configuration. */
function emc_sanitize_job_application_fields( $submitted ) {
    $allowed_types = array( 'text', 'email', 'tel', 'number', 'date', 'textarea', 'select', 'checkbox' );
    $fields        = array();
    $used_keys     = array();

    foreach ( array_slice( is_array( $submitted ) ? $submitted : array(), 0, 30 ) as $index => $field ) {
        $label = sanitize_text_field( $field['label'] ?? '' );
        $type  = sanitize_key( $field['type'] ?? 'text' );
        $key   = sanitize_key( $field['key'] ?? '' );
        if ( ! $label || ! in_array( $type, $allowed_types, true ) ) {
            continue;
        }
        if ( ! $key || isset( $used_keys[ $key ] ) ) {
            $key = 'field_' . substr( md5( $index . '|' . $label . '|' . microtime() ), 0, 12 );
        }
        $used_keys[ $key ] = true;
        $options = array();
        if ( 'select' === $type ) {
            foreach ( array_slice( preg_split( '/\r\n|\r|\n/', (string) ( $field['options'] ?? '' ) ), 0, 50 ) as $option ) {
                $option = sanitize_text_field( $option );
                if ( '' !== $option ) {
                    $options[] = $option;
                }
            }
        }
        $fields[] = array(
            'key'      => $key,
            'label'    => $label,
            'type'     => $type,
            'required' => ! empty( $field['required'] ),
            'options'  => array_values( array_unique( $options ) ),
        );
    }
    return $fields;
}

/** Render a configured public job application field. */
function emc_render_job_application_field( $field ) {
    $key      = sanitize_key( $field['key'] ?? '' );
    $type     = sanitize_key( $field['type'] ?? 'text' );
    $required = ! empty( $field['required'] );
    if ( ! $key ) {
        return;
    }
    $id = 'job-extra-' . $key;
    ?>
    <div class="volunteer-field<?php echo 'checkbox' === $type ? ' volunteer-field--checkbox' : ''; ?>">
        <?php if ( 'checkbox' === $type ) : ?>
            <label for="<?php echo esc_attr( $id ); ?>"><input id="<?php echo esc_attr( $id ); ?>" type="checkbox" name="extra_fields[<?php echo esc_attr( $key ); ?>]" value="1" <?php echo $required ? 'required' : ''; ?>> <?php echo esc_html( $field['label'] ); ?><?php echo $required ? ' *' : ''; ?></label>
        <?php else : ?>
            <label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?><?php echo $required ? ' *' : ''; ?></label>
            <?php if ( 'textarea' === $type ) : ?>
                <textarea id="<?php echo esc_attr( $id ); ?>" name="extra_fields[<?php echo esc_attr( $key ); ?>]" rows="4" <?php echo $required ? 'required' : ''; ?>></textarea>
            <?php elseif ( 'select' === $type ) : ?>
                <select id="<?php echo esc_attr( $id ); ?>" name="extra_fields[<?php echo esc_attr( $key ); ?>]" <?php echo $required ? 'required' : ''; ?>><option value=""><?php esc_html_e( 'Select an option', 'emc-theme' ); ?></option><?php foreach ( $field['options'] ?? array() as $option ) : ?><option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option><?php endforeach; ?></select>
            <?php else : ?>
                <input id="<?php echo esc_attr( $id ); ?>" type="<?php echo esc_attr( in_array( $type, array( 'email', 'tel', 'number', 'date' ), true ) ? $type : 'text' ); ?>" name="extra_fields[<?php echo esc_attr( $key ); ?>]" <?php echo $required ? 'required' : ''; ?>>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

/** Validate and label the answers to configured additional questions. */
function emc_validate_job_application_fields( $submitted ) {
    $submitted = is_array( $submitted ) ? wp_unslash( $submitted ) : array();
    $answers   = array();
    foreach ( emc_job_application_fields() as $field ) {
        $key   = sanitize_key( $field['key'] ?? '' );
        $type  = sanitize_key( $field['type'] ?? 'text' );
        $raw   = $submitted[ $key ] ?? '';
        $value = 'textarea' === $type ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
        if ( 'checkbox' === $type ) {
            $value = '1' === (string) $raw ? 'Yes' : 'No';
        } elseif ( 'email' === $type && $value && ! is_email( $value ) ) {
            return new WP_Error( 'invalid_extra_email', sprintf( __( 'Please enter a valid email address for “%s”.', 'emc-theme' ), $field['label'] ) );
        } elseif ( 'select' === $type && $value && ! in_array( $value, (array) ( $field['options'] ?? array() ), true ) ) {
            return new WP_Error( 'invalid_extra_option', __( 'One of the selected answers is invalid.', 'emc-theme' ) );
        }
        if ( ! empty( $field['required'] ) && ( '' === $value || ( 'checkbox' === $type && 'Yes' !== $value ) ) ) {
            return new WP_Error( 'required_extra_field', sprintf( __( 'Please complete “%s”.', 'emc-theme' ), $field['label'] ) );
        }
        $answers[ $key ] = array( 'label' => $field['label'], 'type' => $type, 'value' => $value );
    }
    return $answers;
}

function emc_register_volunteer_application_type() {
    register_post_type( 'emc_volunteer', array(
        'labels' => array(
            'name'          => __( 'Job Applications', 'emc-theme' ),
            'singular_name' => __( 'Job Application', 'emc-theme' ),
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
    if ( get_page_by_path( 'job-application', OBJECT, 'page' ) ) {
        return;
    }

    wp_insert_post( array(
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_title'   => __( 'Job Application', 'emc-theme' ),
        'post_name'    => 'job-application',
        'post_content' => '<!-- Managed by the Job Application page template. -->',
        'meta_input'   => array(
            '_wp_page_template' => 'page-volunteer.php',
        ),
    ) );
}
add_action( 'init', 'emc_ensure_volunteer_page', 20 );

/**
 * Return the public job application form URL (legacy function name retained).
 *
 * @return string
 */
function emc_get_job_application_url( $position = '' ) {
    $page = get_page_by_path( 'job-application', OBJECT, 'page' );
    $url  = $page ? get_permalink( $page ) : home_url( '/job-application/' );

    if ( $position ) {
        $url = add_query_arg( 'position', sanitize_text_field( $position ), $url );
    }
    return $url . '#volunteer-registration';
}

/**
 * Store a job application.
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
        'position',
        'availability_details',
        'skills',
        'motivation',
    );

    foreach ( $fields as $field ) {
        update_post_meta( $post_id, '_emc_volunteer_' . $field, $data[ $field ] ?? '' );
    }

    update_post_meta( $post_id, '_emc_volunteer_checks_consent', ! empty( $data['checks_consent'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_emc_volunteer_privacy_consent', ! empty( $data['privacy_consent'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_emc_volunteer_extra_fields', $data['extra_fields'] ?? array() );
    update_post_meta( $post_id, '_emc_volunteer_submitted_at', current_time( 'mysql' ) );
    update_post_meta( $post_id, '_emc_volunteer_status', 'new' );

    return $post_id;
}

function emc_handle_volunteer_application() {
    check_ajax_referer( 'emc_nonce', 'nonce' );

    if ( ! empty( $_POST['website'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Unable to submit this application.', 'emc-theme' ) ), 400 );
    }

    $first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
    $last_name  = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
    $email      = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $phone      = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
    $postcode   = emc_normalize_postcode( wp_unslash( $_POST['postcode'] ?? '' ) );
    $over_18    = sanitize_text_field( wp_unslash( $_POST['over_18'] ?? '' ) );
    $position            = sanitize_text_field( wp_unslash( $_POST['position'] ?? '' ) );
    $availability_detail = sanitize_textarea_field( wp_unslash( $_POST['availability_details'] ?? '' ) );
    $skills              = sanitize_textarea_field( wp_unslash( $_POST['skills'] ?? '' ) );
    $motivation          = sanitize_textarea_field( wp_unslash( $_POST['motivation'] ?? '' ) );
    $checks_consent      = '1' === (string) ( $_POST['checks_consent'] ?? '' );
    $privacy_consent     = '1' === (string) ( $_POST['privacy_consent'] ?? '' );
    $extra_fields        = emc_validate_job_application_fields( $_POST['extra_fields'] ?? array() );

    if ( ! $first_name || ! $last_name || ! is_email( $email ) || ! $phone || ! emc_is_valid_postcode( $postcode ) || ! in_array( $over_18, array( 'yes', 'no' ), true ) || ! $position ) {
        wp_send_json_error( array( 'message' => __( 'Please complete all required personal details and enter a valid UK postcode.', 'emc-theme' ) ), 400 );
    }

    if ( is_wp_error( $extra_fields ) ) {
        wp_send_json_error( array( 'message' => $extra_fields->get_error_message() ), 400 );
    }

    if ( ! $motivation || ! $checks_consent || ! $privacy_consent ) {
        wp_send_json_error( array( 'message' => __( 'Please provide a supporting statement and accept both confirmation statements.', 'emc-theme' ) ), 400 );
    }

    $cv       = $_FILES['cv'] ?? array();
    $cv_error = absint( $cv['error'] ?? UPLOAD_ERR_NO_FILE );
    $cv_name  = sanitize_file_name( $cv['name'] ?? '' );
    $cv_ext   = strtolower( pathinfo( $cv_name, PATHINFO_EXTENSION ) );
    if ( UPLOAD_ERR_OK !== $cv_error || ! in_array( $cv_ext, array( 'pdf', 'doc', 'docx' ), true ) || empty( $cv['size'] ) || (int) $cv['size'] > 5 * MB_IN_BYTES ) {
        wp_send_json_error( array( 'message' => __( 'Please upload your CV as a PDF, DOC or DOCX file no larger than 5 MB.', 'emc-theme' ) ), 400 );
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
        'position'             => $position,
        'availability_details' => $availability_detail,
        'skills'               => $skills,
        'motivation'           => $motivation,
        'checks_consent'       => true,
        'privacy_consent'      => true,
        'extra_fields'         => $extra_fields,
    );

    $application_id = emc_store_volunteer_application( $data );
    if ( is_wp_error( $application_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Your application could not be saved. Please try again.', 'emc-theme' ) ), 500 );
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $cv_attachment_id = media_handle_upload( 'cv', $application_id, array(), array(
        'test_form' => false,
        'mimes'     => array(
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ),
    ) );
    if ( is_wp_error( $cv_attachment_id ) ) {
        wp_delete_post( $application_id, true );
        wp_send_json_error( array( 'message' => __( 'Your CV could not be uploaded. Please check the file and try again.', 'emc-theme' ) ), 400 );
    }
    update_post_meta( $application_id, '_emc_volunteer_cv_attachment_id', absint( $cv_attachment_id ) );
    update_post_meta( $application_id, '_emc_volunteer_cv_filename', $cv_name );

    set_transient( $rate_key, 1, MINUTE_IN_SECONDS );

    $full_name = trim( $first_name . ' ' . $last_name );
    $responses = array(
        __( 'Application ID', 'emc-theme' )         => '#' . absint( $application_id ),
        __( 'First name', 'emc-theme' )             => $first_name,
        __( 'Last name', 'emc-theme' )              => $last_name,
        __( 'Email', 'emc-theme' )                  => $email,
        __( 'Phone', 'emc-theme' )                  => $phone,
        __( 'Postcode', 'emc-theme' )               => $postcode,
        __( 'Over 18', 'emc-theme' )                => $over_18,
        __( 'Position applied for', 'emc-theme' )   => $position,
        __( 'Notice period / availability', 'emc-theme' ) => $availability_detail,
        __( 'CV', 'emc-theme' )                     => wp_get_attachment_url( $cv_attachment_id ),
        __( 'Skills and experience', 'emc-theme' )  => $skills,
        __( 'Supporting statement', 'emc-theme' )   => $motivation,
        __( 'Checks consent', 'emc-theme' )         => $checks_consent ? __( 'Yes', 'emc-theme' ) : __( 'No', 'emc-theme' ),
        __( 'Privacy consent', 'emc-theme' )        => $privacy_consent ? __( 'Yes', 'emc-theme' ) : __( 'No', 'emc-theme' ),
        __( 'Submitted at', 'emc-theme' )           => current_time( 'mysql' ),
    );
    foreach ( $extra_fields as $answer ) {
        $responses[ $answer['label'] ] = $answer['value'];
    }
    $subject = 'New Job Application — ' . $full_name;
    $body    = emc_form_notification_html( __( 'New Job Application', 'emc-theme' ), $responses );
    $headers   = array(
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . $full_name . ' <' . $email . '>',
    );
    $notification_sent = emc_send_form_notification( 'volunteer', $subject, $body, $headers );
    update_post_meta( $application_id, '_emc_volunteer_email_status', $notification_sent ? 'sent' : 'failed' );

    $confirmation  = sprintf( __( "Dear %s,\n\nThank you for applying to work with Essex Muslim Centre.", 'emc-theme' ), $full_name );
    $confirmation .= "\n\n" . __( 'Our team will review your application and contact you if you are shortlisted.', 'emc-theme' );
    wp_mail( $email, __( 'Your Essex Muslim Centre job application', 'emc-theme' ), $confirmation );

    wp_send_json_success( array(
        'message' => __( 'Thank you. Your job application has been received.', 'emc-theme' ),
    ) );
}
add_action( 'wp_ajax_emc_job_application', 'emc_handle_volunteer_application' );
add_action( 'wp_ajax_nopriv_emc_job_application', 'emc_handle_volunteer_application' );

function emc_register_volunteer_settings() {
    register_setting( 'emc_volunteer_settings', 'emc_volunteer_notification_email', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default'           => 'info@essexmuslimcentre.org',
    ) );
    register_setting( 'emc_volunteer_settings', 'emc_job_application_fields', array(
        'type'              => 'array',
        'sanitize_callback' => 'emc_sanitize_job_application_fields',
        'default'           => array(),
    ) );
}
add_action( 'admin_init', 'emc_register_volunteer_settings' );

function emc_volunteer_admin_menu() {
    add_submenu_page(
        null,
        __( 'Job Applications', 'emc-theme' ),
        __( 'Job Applications', 'emc-theme' ),
        'manage_options',
        'emc-job-applications',
        'emc_volunteer_admin_page'
    );
}
add_action( 'admin_menu', 'emc_volunteer_admin_menu' );

/** Render the administrator field builder used by the job application form. */
function emc_job_application_field_builder() {
    $types = array(
        'text' => 'Text', 'email' => 'Email', 'tel' => 'Telephone', 'number' => 'Number',
        'date' => 'Date', 'textarea' => 'Long text', 'select' => 'Dropdown', 'checkbox' => 'Checkbox',
    );
    ?>
    <h2><?php esc_html_e( 'Additional application fields', 'emc-theme' ); ?></h2>
    <p><?php esc_html_e( 'Add any extra questions that should appear on the public job application form. Dropdown choices go one per line.', 'emc-theme' ); ?></p>
    <table class="widefat striped" id="emc-job-field-builder" style="max-width:1000px">
        <thead><tr><th><?php esc_html_e( 'Field name', 'emc-theme' ); ?></th><th style="width:150px"><?php esc_html_e( 'Type', 'emc-theme' ); ?></th><th><?php esc_html_e( 'Dropdown options', 'emc-theme' ); ?></th><th style="width:75px;text-align:center"><?php esc_html_e( 'Required', 'emc-theme' ); ?></th><th style="width:70px"></th></tr></thead>
        <tbody>
        <?php foreach ( emc_job_application_fields() as $index => $field ) : ?>
            <tr>
                <td><input type="hidden" data-field="key" name="emc_job_application_fields[<?php echo esc_attr( $index ); ?>][key]" value="<?php echo esc_attr( $field['key'] ); ?>"><input type="text" class="widefat" data-field="label" name="emc_job_application_fields[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $field['label'] ); ?>" required></td>
                <td><select class="widefat" data-field="type" name="emc_job_application_fields[<?php echo esc_attr( $index ); ?>][type]"><?php foreach ( $types as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $field['type'], $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td>
                <td><textarea class="widefat" data-field="options" name="emc_job_application_fields[<?php echo esc_attr( $index ); ?>][options]" rows="2"><?php echo esc_textarea( implode( "\n", (array) ( $field['options'] ?? array() ) ) ); ?></textarea></td>
                <td style="text-align:center"><input type="checkbox" data-field="required" name="emc_job_application_fields[<?php echo esc_attr( $index ); ?>][required]" value="1" <?php checked( ! empty( $field['required'] ) ); ?>></td>
                <td><button type="button" class="button-link-delete emc-remove-job-field"><?php esc_html_e( 'Remove', 'emc-theme' ); ?></button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p><button type="button" class="button" id="emc-add-job-field"><?php esc_html_e( 'Add field', 'emc-theme' ); ?></button></p>
    <script>
    (() => {
        const table = document.querySelector('#emc-job-field-builder tbody');
        const add = document.getElementById('emc-add-job-field');
        if (!table || !add) return;
        const reindex = () => table.querySelectorAll('tr').forEach((row, index) => row.querySelectorAll('[data-field]').forEach(input => input.name = `emc_job_application_fields[${index}][${input.dataset.field}]`));
        const bind = row => row.querySelector('.emc-remove-job-field')?.addEventListener('click', () => { row.remove(); reindex(); });
        table.querySelectorAll('tr').forEach(bind);
        add.addEventListener('click', () => {
            const row = document.createElement('tr');
            row.innerHTML = `<td><input type="hidden" data-field="key" value="field_${Date.now()}"><input type="text" class="widefat" data-field="label" required></td><td><select class="widefat" data-field="type"><option value="text">Text</option><option value="email">Email</option><option value="tel">Telephone</option><option value="number">Number</option><option value="date">Date</option><option value="textarea">Long text</option><option value="select">Dropdown</option><option value="checkbox">Checkbox</option></select></td><td><textarea class="widefat" data-field="options" rows="2" placeholder="One dropdown option per line"></textarea></td><td style="text-align:center"><input type="checkbox" data-field="required" value="1"></td><td><button type="button" class="button-link-delete emc-remove-job-field">Remove</button></td>`;
            table.appendChild(row); bind(row); reindex(); row.querySelector('[data-field="label"]').focus();
        });
    })();
    </script>
    <?php
}

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
        <h1><?php esc_html_e( 'Job Applications', 'emc-theme' ); ?></h1>
        <p><?php esc_html_e( 'Applications submitted through the Job Application page. Access is restricted to administrators.', 'emc-theme' ); ?></p>

        <form action="options.php" method="post" style="margin:20px 0;padding:16px;background:#fff;border:1px solid #dcdcde;max-width:1100px;">
            <?php settings_fields( 'emc_volunteer_settings' ); ?>
            <label for="emc_volunteer_notification_email"><strong><?php esc_html_e( 'New application notification email', 'emc-theme' ); ?></strong></label><br>
            <input type="email" class="regular-text" id="emc_volunteer_notification_email" name="emc_volunteer_notification_email" value="<?php echo esc_attr( $notification_email ); ?>" required>
            <hr style="margin:20px 0">
            <?php emc_job_application_field_builder(); ?>
            <?php submit_button( __( 'Save Application Form Settings', 'emc-theme' ), 'primary', 'submit', false ); ?>
        </form>

        <?php if ( ! $query->have_posts() ) : ?>
            <div class="notice notice-info inline"><p><?php esc_html_e( 'No job applications have been submitted yet.', 'emc-theme' ); ?></p></div>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Submitted', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Applicant', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Contact', 'emc-theme' ); ?></th>
                        <th><?php esc_html_e( 'Position', 'emc-theme' ); ?></th>
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
                        $position       = get_post_meta( $application_id, '_emc_volunteer_position', true );
                        $legacy_interests = get_post_meta( $application_id, '_emc_volunteer_interests', true );
                        $availability_details = get_post_meta( $application_id, '_emc_volunteer_availability_details', true );
                        $skills         = get_post_meta( $application_id, '_emc_volunteer_skills', true );
                        $motivation     = get_post_meta( $application_id, '_emc_volunteer_motivation', true );
                        $over_18        = get_post_meta( $application_id, '_emc_volunteer_over_18', true );
                        $submitted      = get_post_meta( $application_id, '_emc_volunteer_submitted_at', true );
                        $cv_id          = absint( get_post_meta( $application_id, '_emc_volunteer_cv_attachment_id', true ) );
                        $extra_fields   = get_post_meta( $application_id, '_emc_volunteer_extra_fields', true );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $submitted ?: get_the_date( 'Y-m-d H:i:s' ) ); ?><br><small>#<?php echo esc_html( $application_id ); ?></small></td>
                        <td><strong><?php echo esc_html( trim( $first_name . ' ' . $last_name ) ); ?></strong><br><small><?php echo esc_html( 'yes' === $over_18 ? '18 or over' : 'Under 18' ); ?></small></td>
                        <td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a><br><?php echo esc_html( $phone ); ?><br><?php echo esc_html( $postcode ); ?></td>
                        <td><?php echo esc_html( $position ?: $legacy_interests ); ?></td>
                        <td><?php echo esc_html( $availability_details ); ?></td>
                        <td>
                            <details>
                                <summary><?php esc_html_e( 'View answers', 'emc-theme' ); ?></summary>
                                <?php if ( $cv_id && wp_get_attachment_url( $cv_id ) ) : ?><p><strong><?php esc_html_e( 'CV:', 'emc-theme' ); ?></strong> <a href="<?php echo esc_url( wp_get_attachment_url( $cv_id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View / download CV', 'emc-theme' ); ?></a></p><?php endif; ?>
                                <p><strong><?php esc_html_e( 'Skills and experience:', 'emc-theme' ); ?></strong><br><?php echo nl2br( esc_html( $skills ) ); ?></p>
                                <p><strong><?php esc_html_e( 'Supporting statement:', 'emc-theme' ); ?></strong><br><?php echo nl2br( esc_html( $motivation ) ); ?></p>
                                <?php foreach ( is_array( $extra_fields ) ? $extra_fields : array() as $answer ) : ?><p><strong><?php echo esc_html( $answer['label'] ?? '' ); ?>:</strong><br><?php echo nl2br( esc_html( $answer['value'] ?? '' ) ); ?></p><?php endforeach; ?>
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
