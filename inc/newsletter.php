<?php
/**
 * Newsletter storage, Mailchimp synchronization, and administrator screens.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register private newsletter submissions for durable local records.
 */
function emc_register_newsletter_submission_type() {
    register_post_type( 'emc_newsletter', array(
        'labels' => array(
            'name'          => __( 'Newsletter Subscribers', 'emc-theme' ),
            'singular_name' => __( 'Newsletter Subscriber', 'emc-theme' ),
        ),
        'public'              => false,
        'show_ui'             => false,
        'show_in_rest'        => false,
        'exclude_from_search' => true,
        'supports'            => array( 'title' ),
    ) );
}
add_action( 'init', 'emc_register_newsletter_submission_type' );

/**
 * Mailchimp configuration. Constants may be used in wp-config.php.
 */
function emc_mailchimp_api_key() {
    if ( defined( 'EMC_MAILCHIMP_API_KEY' ) && EMC_MAILCHIMP_API_KEY ) {
        return trim( EMC_MAILCHIMP_API_KEY );
    }

    return trim( (string) get_option( 'emc_mailchimp_api_key', '' ) );
}

function emc_mailchimp_audience_id() {
    if ( defined( 'EMC_MAILCHIMP_AUDIENCE_ID' ) && EMC_MAILCHIMP_AUDIENCE_ID ) {
        return trim( EMC_MAILCHIMP_AUDIENCE_ID );
    }

    return trim( (string) get_option( 'emc_mailchimp_audience_id', '' ) );
}

function emc_mailchimp_server_prefix() {
    if ( defined( 'EMC_MAILCHIMP_SERVER_PREFIX' ) && EMC_MAILCHIMP_SERVER_PREFIX ) {
        return strtolower( trim( EMC_MAILCHIMP_SERVER_PREFIX ) );
    }

    $prefix = strtolower( trim( (string) get_option( 'emc_mailchimp_server_prefix', '' ) ) );
    if ( $prefix ) {
        return $prefix;
    }

    $parts     = explode( '-', emc_mailchimp_api_key() );
    $candidate = strtolower( (string) end( $parts ) );

    return preg_match( '/^[a-z]{2,4}\d+$/', $candidate ) ? $candidate : '';
}

function emc_mailchimp_is_configured() {
    return (bool) ( emc_mailchimp_api_key() && emc_mailchimp_server_prefix() && emc_mailchimp_audience_id() );
}

/**
 * Perform an authenticated Mailchimp Marketing API request.
 *
 * @return array|WP_Error
 */
function emc_mailchimp_request( $method, $path, $body = null ) {
    if ( ! emc_mailchimp_is_configured() ) {
        return new WP_Error( 'mailchimp_not_configured', __( 'Mailchimp is not configured.', 'emc-theme' ) );
    }

    $url  = 'https://' . emc_mailchimp_server_prefix() . '.api.mailchimp.com/3.0/' . ltrim( $path, '/' );
    $args = array(
        'method'      => strtoupper( $method ),
        'timeout'     => 20,
        'redirection' => 2,
        'headers'     => array(
            'Authorization' => 'Basic ' . base64_encode( 'emc:' . emc_mailchimp_api_key() ),
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ),
        'user-agent'  => 'Essex-Muslim-Centre-WordPress/' . EMC_VERSION,
    );

    if ( null !== $body ) {
        $args['body'] = wp_json_encode( $body );
    }

    $response = wp_remote_request( esc_url_raw( $url ), $args );
    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code( $response );
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    $data = is_array( $data ) ? $data : array();

    if ( $code < 200 || $code >= 300 ) {
        $message = sanitize_text_field( $data['detail'] ?? $data['title'] ?? __( 'Mailchimp rejected the request.', 'emc-theme' ) );
        return new WP_Error( 'mailchimp_api_error', $message, array( 'status' => $code ) );
    }

    return $data;
}

/**
 * Add or update an email in the configured Mailchimp audience.
 *
 * @return array|WP_Error
 */
function emc_mailchimp_subscribe( $email ) {
    $email       = strtolower( sanitize_email( $email ) );
    $double_opt  = '1' === (string) get_option( 'emc_mailchimp_double_optin', '1' );
    $member_hash = md5( $email );
    $status      = $double_opt ? 'pending' : 'subscribed';

    return emc_mailchimp_request(
        'PUT',
        'lists/' . rawurlencode( emc_mailchimp_audience_id() ) . '/members/' . $member_hash,
        array(
            'email_address' => $email,
            'status_if_new' => $status,
            'status'        => $status,
        )
    );
}

/**
 * Create a local newsletter submission.
 *
 * @return int|WP_Error
 */
function emc_store_newsletter_submission( $email ) {
    $post_id = wp_insert_post( array(
        'post_type'   => 'emc_newsletter',
        'post_status' => 'private',
        'post_title'  => sprintf( '%s — %s', $email, current_time( 'Y-m-d H:i:s' ) ),
    ), true );

    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }

    update_post_meta( $post_id, '_emc_newsletter_email', $email );
    update_post_meta( $post_id, '_emc_newsletter_consent', '1' );
    update_post_meta( $post_id, '_emc_newsletter_submitted_at', current_time( 'mysql' ) );
    update_post_meta( $post_id, '_emc_newsletter_status', 'received' );

    return $post_id;
}

/**
 * Synchronize a stored submission and retain the result for administrators.
 *
 * @return array|WP_Error
 */
function emc_sync_newsletter_submission( $post_id ) {
    $email = sanitize_email( get_post_meta( $post_id, '_emc_newsletter_email', true ) );
    if ( ! is_email( $email ) ) {
        return new WP_Error( 'invalid_submission', __( 'The stored email address is invalid.', 'emc-theme' ) );
    }

    if ( ! emc_mailchimp_is_configured() ) {
        update_post_meta( $post_id, '_emc_newsletter_status', 'awaiting_configuration' );
        update_post_meta( $post_id, '_emc_newsletter_error', __( 'Mailchimp settings are incomplete.', 'emc-theme' ) );
        return new WP_Error( 'mailchimp_not_configured', __( 'Mailchimp settings are incomplete.', 'emc-theme' ) );
    }

    $result = emc_mailchimp_subscribe( $email );
    if ( is_wp_error( $result ) ) {
        update_post_meta( $post_id, '_emc_newsletter_status', 'sync_failed' );
        update_post_meta( $post_id, '_emc_newsletter_error', $result->get_error_message() );
        return $result;
    }

    $status = sanitize_key( $result['status'] ?? 'subscribed' );
    update_post_meta( $post_id, '_emc_newsletter_status', $status );
    update_post_meta( $post_id, '_emc_newsletter_mailchimp_id', sanitize_text_field( $result['id'] ?? '' ) );
    update_post_meta( $post_id, '_emc_newsletter_synced_at', current_time( 'mysql' ) );
    delete_post_meta( $post_id, '_emc_newsletter_error' );

    return $result;
}

/**
 * Public newsletter AJAX handler.
 */
function emc_handle_newsletter_signup() {
    check_ajax_referer( 'emc_newsletter_signup', 'nonce' );

    if ( ! empty( $_POST['website'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Unable to process this signup.', 'emc-theme' ) ), 400 );
    }

    $email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $consent = '1' === (string) ( $_POST['consent'] ?? '' );

    if ( ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'emc-theme' ) ), 400 );
    }

    if ( ! $consent ) {
        wp_send_json_error( array( 'message' => __( 'Please confirm that you agree to receive newsletter emails.', 'emc-theme' ) ), 400 );
    }

    $rate_key = 'emc_newsletter_' . md5( strtolower( $email ) );
    if ( get_transient( $rate_key ) ) {
        wp_send_json_error( array( 'message' => __( 'This email was just submitted. Please wait before trying again.', 'emc-theme' ) ), 429 );
    }

    $submission_id = emc_store_newsletter_submission( $email );
    if ( is_wp_error( $submission_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Your signup could not be saved. Please try again.', 'emc-theme' ) ), 500 );
    }

    set_transient( $rate_key, 1, MINUTE_IN_SECONDS );
    $result = emc_sync_newsletter_submission( $submission_id );

    $mailchimp_status = is_wp_error( $result )
        ? __( 'Stored locally — Mailchimp sync requires attention', 'emc-theme' )
        : sanitize_text_field( $result['status'] ?? __( 'Synchronized', 'emc-theme' ) );
    $body = emc_form_notification_html( __( 'New Newsletter Signup', 'emc-theme' ), array(
        __( 'Submission ID', 'emc-theme' )    => '#' . absint( $submission_id ),
        __( 'Email', 'emc-theme' )            => $email,
        __( 'Marketing consent', 'emc-theme' ) => $consent ? __( 'Yes', 'emc-theme' ) : __( 'No', 'emc-theme' ),
        __( 'Mailchimp status', 'emc-theme' ) => $mailchimp_status,
        __( 'Mailchimp message', 'emc-theme' ) => is_wp_error( $result ) ? $result->get_error_message() : '',
        __( 'Submitted at', 'emc-theme' )     => current_time( 'mysql' ),
    ) );
    $notification_sent = emc_send_form_notification(
        'newsletter',
        sprintf( __( 'New newsletter signup: %s', 'emc-theme' ), $email ),
        $body,
        array( 'Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . $email )
    );
    update_post_meta( $submission_id, '_emc_newsletter_email_status', $notification_sent ? 'sent' : 'failed' );

    if ( is_wp_error( $result ) ) {
        wp_send_json_success( array(
            'message' => __( 'Thank you. Your signup has been recorded and an administrator will complete the subscription.', 'emc-theme' ),
            'status'  => 'recorded',
        ) );
    }

    $status  = sanitize_key( $result['status'] ?? '' );
    $message = 'pending' === $status
        ? __( 'Please check your inbox and confirm your newsletter subscription.', 'emc-theme' )
        : __( "You're subscribed! Jazakallahu Khayran.", 'emc-theme' );

    wp_send_json_success( array(
        'message' => $message,
        'status'  => $status,
    ) );
}
add_action( 'wp_ajax_emc_newsletter_subscribe', 'emc_handle_newsletter_signup' );
add_action( 'wp_ajax_nopriv_emc_newsletter_subscribe', 'emc_handle_newsletter_signup' );
add_action( 'wp_ajax_emc_newsletter', 'emc_handle_newsletter_signup' );
add_action( 'wp_ajax_nopriv_emc_newsletter', 'emc_handle_newsletter_signup' );

/**
 * Mailchimp settings.
 */
function emc_sanitize_mailchimp_api_key( $value ) {
    $value = trim( sanitize_text_field( $value ) );
    return $value ?: (string) get_option( 'emc_mailchimp_api_key', '' );
}

function emc_sanitize_mailchimp_prefix( $value ) {
    $value = strtolower( sanitize_key( $value ) );
    return preg_match( '/^[a-z]{2,4}\d+$/', $value ) ? $value : '';
}

function emc_register_newsletter_settings() {
    register_setting( 'emc_mailchimp_settings', 'emc_mailchimp_api_key', array(
        'type'              => 'string',
        'sanitize_callback' => 'emc_sanitize_mailchimp_api_key',
        'default'           => '',
    ) );
    register_setting( 'emc_mailchimp_settings', 'emc_mailchimp_server_prefix', array(
        'type'              => 'string',
        'sanitize_callback' => 'emc_sanitize_mailchimp_prefix',
        'default'           => '',
    ) );
    register_setting( 'emc_mailchimp_settings', 'emc_mailchimp_audience_id', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ) );
    register_setting( 'emc_mailchimp_settings', 'emc_mailchimp_double_optin', array(
        'type'              => 'string',
        'sanitize_callback' => function ( $value ) {
            return '1' === (string) $value ? '1' : '0';
        },
        'default'           => '1',
    ) );
}
add_action( 'admin_init', 'emc_register_newsletter_settings' );

/**
 * Newsletter administrator navigation.
 */
function emc_newsletter_admin_menu() {
    add_submenu_page(
        null,
        __( 'Newsletter Subscribers', 'emc-theme' ),
        __( 'Subscribers', 'emc-theme' ),
        'manage_options',
        'emc-newsletter',
        'emc_newsletter_submissions_page'
    );
    add_submenu_page(
        null,
        __( 'Mailchimp Settings', 'emc-theme' ),
        __( 'Mailchimp Settings', 'emc-theme' ),
        'manage_options',
        'emc-newsletter-settings',
        'emc_newsletter_settings_page'
    );
}
add_action( 'admin_menu', 'emc_newsletter_admin_menu' );

function emc_newsletter_status_label( $status ) {
    $labels = array(
        'subscribed'            => __( 'Subscribed', 'emc-theme' ),
        'pending'               => __( 'Confirmation pending', 'emc-theme' ),
        'unsubscribed'          => __( 'Unsubscribed', 'emc-theme' ),
        'cleaned'               => __( 'Cleaned', 'emc-theme' ),
        'sync_failed'           => __( 'Sync failed', 'emc-theme' ),
        'awaiting_configuration'=> __( 'Awaiting configuration', 'emc-theme' ),
        'received'              => __( 'Received', 'emc-theme' ),
    );

    return $labels[ $status ] ?? ucfirst( str_replace( '_', ' ', $status ) );
}

/**
 * Stored subscriber listing.
 */
function emc_newsletter_submissions_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $paged = max( 1, absint( $_GET['paged'] ?? 1 ) );
    $query = new WP_Query( array(
        'post_type'      => 'emc_newsletter',
        'post_status'    => 'private',
        'posts_per_page' => 50,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Newsletter Subscribers', 'emc-theme' ); ?></h1>
        <p>
            <?php esc_html_e( 'Every website newsletter signup is stored here, including its Mailchimp synchronization status.', 'emc-theme' ); ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=emc-newsletter-settings' ) ); ?>"><?php esc_html_e( 'Mailchimp settings', 'emc-theme' ); ?></a>
        </p>

        <?php if ( isset( $_GET['sync'] ) ) : ?>
            <div class="notice <?php echo 'success' === sanitize_key( $_GET['sync'] ) ? 'notice-success' : 'notice-error'; ?> is-dismissible">
                <p><?php echo 'success' === sanitize_key( $_GET['sync'] ) ? esc_html__( 'The subscriber was synchronized with Mailchimp.', 'emc-theme' ) : esc_html__( 'Mailchimp synchronization failed. Check the status details and settings.', 'emc-theme' ); ?></p>
            </div>
        <?php endif; ?>

        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Submitted', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Email', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Consent', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Mailchimp Status', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Last Sync', 'emc-theme' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'emc-theme' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $query->have_posts() ) : ?>
                    <?php while ( $query->have_posts() ) : $query->the_post();
                        $submission_id = get_the_ID();
                        $email         = get_post_meta( $submission_id, '_emc_newsletter_email', true );
                        $status        = get_post_meta( $submission_id, '_emc_newsletter_status', true );
                        $error         = get_post_meta( $submission_id, '_emc_newsletter_error', true );
                        $submitted     = get_post_meta( $submission_id, '_emc_newsletter_submitted_at', true );
                        $synced        = get_post_meta( $submission_id, '_emc_newsletter_synced_at', true );
                        $retry_url     = wp_nonce_url(
                            admin_url( 'admin-post.php?action=emc_newsletter_retry&submission_id=' . $submission_id ),
                            'emc_newsletter_retry_' . $submission_id
                        );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $submitted ?: get_the_date( 'Y-m-d H:i:s' ) ); ?><br><small>#<?php echo esc_html( $submission_id ); ?></small></td>
                        <td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td>
                        <td><?php esc_html_e( 'Confirmed', 'emc-theme' ); ?></td>
                        <td>
                            <strong><?php echo esc_html( emc_newsletter_status_label( $status ) ); ?></strong>
                            <?php if ( $error ) : ?><br><small style="color:#b32d2e;"><?php echo esc_html( $error ); ?></small><?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $synced ?: '—' ); ?></td>
                        <td><a class="button button-small" href="<?php echo esc_url( $retry_url ); ?>"><?php esc_html_e( 'Sync to Mailchimp', 'emc-theme' ); ?></a></td>
                    </tr>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php else : ?>
                    <tr><td colspan="6"><?php esc_html_e( 'No newsletter signups have been submitted yet.', 'emc-theme' ); ?></td></tr>
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

/**
 * Mailchimp configuration screen.
 */
function emc_newsletter_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $key_from_constant      = defined( 'EMC_MAILCHIMP_API_KEY' ) && EMC_MAILCHIMP_API_KEY;
    $prefix_from_constant   = defined( 'EMC_MAILCHIMP_SERVER_PREFIX' ) && EMC_MAILCHIMP_SERVER_PREFIX;
    $audience_from_constant = defined( 'EMC_MAILCHIMP_AUDIENCE_ID' ) && EMC_MAILCHIMP_AUDIENCE_ID;
    $test_notice            = get_transient( 'emc_mailchimp_test_' . get_current_user_id() );
    delete_transient( 'emc_mailchimp_test_' . get_current_user_id() );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Mailchimp Newsletter Settings', 'emc-theme' ); ?></h1>
        <p><?php esc_html_e( 'Connect the website signup form to a Mailchimp audience. The API key remains server-side and is never exposed to visitors.', 'emc-theme' ); ?></p>

        <?php if ( $test_notice ) : ?>
            <div class="notice <?php echo ! empty( $test_notice['success'] ) ? 'notice-success' : 'notice-error'; ?> inline"><p><?php echo esc_html( $test_notice['message'] ); ?></p></div>
        <?php endif; ?>

        <form action="options.php" method="post">
            <?php settings_fields( 'emc_mailchimp_settings' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="emc_mailchimp_api_key"><?php esc_html_e( 'Mailchimp API key', 'emc-theme' ); ?></label></th>
                    <td>
                        <?php if ( $key_from_constant ) : ?>
                            <code><?php esc_html_e( 'Configured in wp-config.php', 'emc-theme' ); ?></code>
                        <?php else : ?>
                            <input type="password" class="regular-text" id="emc_mailchimp_api_key" name="emc_mailchimp_api_key" value="" autocomplete="new-password" placeholder="<?php echo get_option( 'emc_mailchimp_api_key' ) ? esc_attr__( 'Saved — enter a value only to replace it', 'emc-theme' ) : ''; ?>">
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="emc_mailchimp_server_prefix"><?php esc_html_e( 'Server prefix', 'emc-theme' ); ?></label></th>
                    <td>
                        <?php if ( $prefix_from_constant ) : ?>
                            <code><?php echo esc_html( emc_mailchimp_server_prefix() ); ?></code>
                        <?php else : ?>
                            <input type="text" class="regular-text" id="emc_mailchimp_server_prefix" name="emc_mailchimp_server_prefix" value="<?php echo esc_attr( get_option( 'emc_mailchimp_server_prefix', '' ) ); ?>" placeholder="us21">
                            <p class="description"><?php esc_html_e( 'Usually the suffix at the end of the API key. Leave blank to detect it automatically.', 'emc-theme' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="emc_mailchimp_audience_id"><?php esc_html_e( 'Audience ID', 'emc-theme' ); ?></label></th>
                    <td>
                        <?php if ( $audience_from_constant ) : ?>
                            <code><?php echo esc_html( emc_mailchimp_audience_id() ); ?></code>
                        <?php else : ?>
                            <input type="text" class="regular-text" id="emc_mailchimp_audience_id" name="emc_mailchimp_audience_id" value="<?php echo esc_attr( get_option( 'emc_mailchimp_audience_id', '' ) ); ?>" required>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Confirmation', 'emc-theme' ); ?></th>
                    <td>
                        <input type="hidden" name="emc_mailchimp_double_optin" value="0">
                        <label><input type="checkbox" name="emc_mailchimp_double_optin" value="1" <?php checked( '1', get_option( 'emc_mailchimp_double_optin', '1' ) ); ?>> <?php esc_html_e( 'Require subscribers to confirm by email (recommended)', 'emc-theme' ); ?></label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Mailchimp Settings', 'emc-theme' ) ); ?>
        </form>

        <hr>
        <h2><?php esc_html_e( 'Connection Test', 'emc-theme' ); ?></h2>
        <p><?php esc_html_e( 'Save the settings first, then test access to the configured audience.', 'emc-theme' ); ?></p>
        <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
            <input type="hidden" name="action" value="emc_mailchimp_test">
            <?php wp_nonce_field( 'emc_mailchimp_test' ); ?>
            <?php submit_button( __( 'Test Mailchimp Connection', 'emc-theme' ), 'secondary', 'submit', false ); ?>
        </form>
    </div>
    <?php
}

/**
 * Admin connection test.
 */
function emc_handle_mailchimp_test() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to perform this action.', 'emc-theme' ) );
    }
    check_admin_referer( 'emc_mailchimp_test' );

    $result = emc_mailchimp_request(
        'GET',
        'lists/' . rawurlencode( emc_mailchimp_audience_id() ) . '?fields=id,name,stats.member_count'
    );
    $notice = is_wp_error( $result )
        ? array( 'success' => false, 'message' => $result->get_error_message() )
        : array(
            'success' => true,
            'message' => sprintf(
                __( 'Connected successfully to “%1$s” (%2$s contacts).', 'emc-theme' ),
                sanitize_text_field( $result['name'] ?? __( 'Mailchimp audience', 'emc-theme' ) ),
                number_format_i18n( absint( $result['stats']['member_count'] ?? 0 ) )
            ),
        );

    set_transient( 'emc_mailchimp_test_' . get_current_user_id(), $notice, MINUTE_IN_SECONDS );
    wp_safe_redirect( admin_url( 'admin.php?page=emc-newsletter-settings' ) );
    exit;
}
add_action( 'admin_post_emc_mailchimp_test', 'emc_handle_mailchimp_test' );

/**
 * Retry synchronization for a stored signup.
 */
function emc_handle_newsletter_retry() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to perform this action.', 'emc-theme' ) );
    }

    $submission_id = absint( $_GET['submission_id'] ?? 0 );
    check_admin_referer( 'emc_newsletter_retry_' . $submission_id );

    if ( 'emc_newsletter' !== get_post_type( $submission_id ) ) {
        wp_die( esc_html__( 'Invalid newsletter submission.', 'emc-theme' ) );
    }

    $result = emc_sync_newsletter_submission( $submission_id );
    wp_safe_redirect( admin_url( 'admin.php?page=emc-newsletter&sync=' . ( is_wp_error( $result ) ? 'failed' : 'success' ) ) );
    exit;
}
add_action( 'admin_post_emc_newsletter_retry', 'emc_handle_newsletter_retry' );
