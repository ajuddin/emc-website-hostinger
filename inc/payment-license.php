<?php
/**
 * Payment module licensing.
 *
 * Validates this site against a self-hosted EMC license server and prevents
 * new Stripe payments when the license is missing or expired.
 *
 * @package emc-theme
 */

defined( 'ABSPATH' ) || exit;

define( 'EMC_PAYMENT_LICENSE_CACHE_SECONDS', 12 * HOUR_IN_SECONDS );
define( 'EMC_PAYMENT_LICENSE_GRACE_SECONDS',  3 * DAY_IN_SECONDS );

/**
 * Return the configured license server URL.
 */
function emc_payment_license_server_url() {
    $url = defined( 'EMC_PAYMENT_LICENSE_SERVER' )
        ? EMC_PAYMENT_LICENSE_SERVER
        : get_option( 'emc_payment_license_server_url', '' );

    return untrailingslashit( esc_url_raw( trim( (string) $url ) ) );
}

/**
 * Return the configured license key.
 */
function emc_payment_license_key() {
    $key = defined( 'EMC_PAYMENT_LICENSE_KEY' )
        ? EMC_PAYMENT_LICENSE_KEY
        : get_option( 'emc_payment_license_key', '' );

    return strtoupper( preg_replace( '/[^A-Z0-9-]/i', '', trim( (string) $key ) ) );
}

/**
 * Generate a stable, non-secret identifier for this WordPress installation.
 */
function emc_payment_license_instance_id() {
    $instance_id = get_option( 'emc_payment_license_instance_id', '' );
    if ( ! $instance_id ) {
        $instance_id = wp_generate_uuid4();
        update_option( 'emc_payment_license_instance_id', $instance_id, false );
    }

    return $instance_id;
}

/**
 * Default license state.
 */
function emc_payment_license_default_status() {
    return array(
        'state'           => 'missing',
        'message'         => __( 'A payment module license is required.', 'emc-theme' ),
        'expires_at'      => '',
        'last_checked_at' => '',
        'last_success_at' => '',
        'plan'            => '',
        'license_type'    => '',
    );
}

/**
 * Save a normalized status response.
 */
function emc_payment_license_store_status( $status ) {
    $status = wp_parse_args( is_array( $status ) ? $status : array(), emc_payment_license_default_status() );
    update_option( 'emc_payment_license_status', $status, false );
    set_transient( 'emc_payment_license_status_cache', $status, EMC_PAYMENT_LICENSE_CACHE_SECONDS );

    return $status;
}

/**
 * Make a signed-by-key request to the license server.
 */
function emc_payment_license_remote_request( $action ) {
    $server = emc_payment_license_server_url();
    $key    = emc_payment_license_key();

    if ( ! $server || ! $key ) {
        return new WP_Error( 'emc_license_missing', __( 'Enter a license server URL and license key first.', 'emc-theme' ) );
    }

    $response = wp_remote_post(
        $server . '/wp-json/emc-license/v1/' . sanitize_key( $action ),
        array(
            'timeout' => 12,
            'headers' => array( 'Accept' => 'application/json' ),
            'body'    => array(
                'license_key' => $key,
                'site_url'    => home_url( '/' ),
                'instance_id' => emc_payment_license_instance_id(),
                'product'     => 'emc-payment-module',
                'version'     => defined( 'EMC_VERSION' ) ? EMC_VERSION : '',
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code( $response );
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( ! is_array( $data ) ) {
        return new WP_Error( 'emc_license_bad_response', __( 'The license server returned an invalid response.', 'emc-theme' ) );
    }

    if ( $code < 200 || $code >= 300 ) {
        return new WP_Error(
            'emc_license_rejected',
            sanitize_text_field( $data['message'] ?? __( 'The license server rejected this license.', 'emc-theme' ) ),
            array( 'status' => $code, 'response' => $data )
        );
    }

    return $data;
}

/**
 * Convert the license server response into the local status format.
 */
function emc_payment_license_status_from_response( $response ) {
    $valid = ! empty( $response['valid'] );
    $state = sanitize_key( $response['status'] ?? ( $valid ? 'active' : 'invalid' ) );
    if ( ! $valid && 'active' === $state ) {
        $state = 'invalid';
    }

    return array(
        'state'           => $valid ? 'active' : $state,
        'message'         => sanitize_text_field( $response['message'] ?? ( $valid ? __( 'License active.', 'emc-theme' ) : __( 'License required.', 'emc-theme' ) ) ),
        'expires_at'      => sanitize_text_field( $response['expires_at'] ?? '' ),
        'last_checked_at' => current_time( 'mysql', true ),
        'last_success_at' => $valid ? current_time( 'mysql', true ) : '',
        'plan'            => sanitize_text_field( $response['plan'] ?? '' ),
        'license_type'    => sanitize_key( $response['license_type'] ?? '' ),
    );
}

/**
 * Validate now, with a short outage grace period for a previously valid license.
 */
function emc_payment_license_refresh_status( $action = 'validate' ) {
    if ( ! emc_payment_license_server_url() || ! emc_payment_license_key() ) {
        return emc_payment_license_store_status( emc_payment_license_default_status() );
    }

    $previous = wp_parse_args(
        get_option( 'emc_payment_license_status', array() ),
        emc_payment_license_default_status()
    );
    $response = emc_payment_license_remote_request( $action );

    if ( ! is_wp_error( $response ) ) {
        return emc_payment_license_store_status( emc_payment_license_status_from_response( $response ) );
    }

    $error_data = $response->get_error_data();
    if ( is_array( $error_data ) && ! empty( $error_data['response'] ) ) {
        return emc_payment_license_store_status(
            emc_payment_license_status_from_response( $error_data['response'] )
        );
    }

    $last_success = ! empty( $previous['last_success_at'] ) ? strtotime( $previous['last_success_at'] . ' UTC' ) : 0;
    $expires      = ! empty( $previous['expires_at'] ) ? strtotime( $previous['expires_at'] . ' UTC' ) : 0;
    $now          = time();

    if (
        'active' === $previous['state']
        && $last_success
        && ( $last_success + EMC_PAYMENT_LICENSE_GRACE_SECONDS ) > $now
        && ( ! $expires || $expires > $now )
    ) {
        $previous['message']         = __( 'License active (temporary validation grace period).', 'emc-theme' );
        $previous['last_checked_at'] = current_time( 'mysql', true );
        return emc_payment_license_store_status( $previous );
    }

    return emc_payment_license_store_status(
        array(
            'state'           => 'unreachable',
            'message'         => __( 'The payment license could not be validated. Please contact the site administrator.', 'emc-theme' ),
            'last_checked_at' => current_time( 'mysql', true ),
        )
    );
}

/**
 * Get the cached license status and enforce the expiry locally.
 */
function emc_payment_license_status( $force = false ) {
    if ( $force ) {
        $status = emc_payment_license_refresh_status();
    } else {
        $status = get_transient( 'emc_payment_license_status_cache' );
        if ( false === $status ) {
            $status = emc_payment_license_refresh_status();
        }
    }

    $status  = wp_parse_args( is_array( $status ) ? $status : array(), emc_payment_license_default_status() );
    $expires = ! empty( $status['expires_at'] ) ? strtotime( $status['expires_at'] . ' UTC' ) : 0;

    if ( $expires && $expires <= time() ) {
        $status['state']   = 'expired';
        $status['message'] = __( 'The payment module license has expired.', 'emc-theme' );
    }

    return $status;
}

/**
 * Whether new donations and Stripe payment creation are licensed.
 */
function emc_payment_license_is_active() {
    $status = emc_payment_license_status();
    $active = 'active' === $status['state'];

    return (bool) apply_filters( 'emc_payment_license_is_active', $active, $status );
}

/**
 * Stop a payment AJAX request when the module is unlicensed.
 */
function emc_payment_license_guard_ajax() {
    if ( emc_payment_license_is_active() ) {
        return;
    }

    $status = emc_payment_license_status();
    wp_send_json_error(
        array(
            'code'    => 'license_required',
            'message' => $status['message'] ?: __( 'Payment module license required.', 'emc-theme' ),
        ),
        403
    );
}

/**
 * Render the public licensed-feature notice.
 */
function emc_payment_license_render_required() {
    $status = emc_payment_license_status();
    ?>
    <main class="emc-license-required-wrap">
        <section class="emc-license-required" aria-labelledby="emc-license-required-title">
            <span class="emc-license-lock" aria-hidden="true">&#128274;</span>
            <h1 id="emc-license-required-title"><?php esc_html_e( 'Payment License Required', 'emc-theme' ); ?></h1>
            <p><?php echo esc_html( $status['message'] ); ?></p>
            <?php if ( current_user_can( 'manage_options' ) ) : ?>
                <a class="btn btn-primary" href="<?php echo esc_url( admin_url( 'options-general.php?page=emc-payment-license' ) ); ?>">
                    <?php esc_html_e( 'Manage License', 'emc-theme' ); ?>
                </a>
            <?php endif; ?>
        </section>
    </main>
    <?php
}

/**
 * Load the locked-page styling only on payment templates.
 */
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'emc-payment-license',
        EMC_ASSETS . '/css/payment-license.css',
        array( 'emc-style' ),
        EMC_VERSION
    );
} );

/**
 * License settings screen.
 */
add_action( 'admin_menu', function () {
    add_options_page(
        __( 'EMC Payment License', 'emc-theme' ),
        __( 'EMC Payment License', 'emc-theme' ),
        'manage_options',
        'emc-payment-license',
        'emc_payment_license_settings_page'
    );
} );

add_action( 'admin_init', function () {
    register_setting(
        'emc_payment_license',
        'emc_payment_license_server_url',
        array(
            'type'              => 'string',
            'sanitize_callback' => function ( $value ) {
                delete_transient( 'emc_payment_license_status_cache' );
                return untrailingslashit( esc_url_raw( $value ) );
            },
        )
    );
    register_setting(
        'emc_payment_license',
        'emc_payment_license_key',
        array(
            'type'              => 'string',
            'sanitize_callback' => function ( $value ) {
                delete_transient( 'emc_payment_license_status_cache' );
                return strtoupper( preg_replace( '/[^A-Z0-9-]/i', '', trim( (string) $value ) ) );
            },
        )
    );
} );

add_action( 'admin_post_emc_payment_license_action', function () {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You are not allowed to manage this license.', 'emc-theme' ) );
    }

    check_admin_referer( 'emc_payment_license_action' );
    $license_action = sanitize_key( $_POST['license_action'] ?? 'validate' );
    if ( ! in_array( $license_action, array( 'activate', 'validate', 'deactivate' ), true ) ) {
        $license_action = 'validate';
    }

    if ( 'validate' === $license_action ) {
        $status = emc_payment_license_refresh_status();
    } else {
        $result = emc_payment_license_remote_request( $license_action );
        if ( is_wp_error( $result ) ) {
            $status = array(
                'state'           => 'error',
                'message'         => $result->get_error_message(),
                'last_checked_at' => current_time( 'mysql', true ),
            );
        } else {
            $status = emc_payment_license_status_from_response( $result );
            if ( 'deactivate' === $license_action ) {
                $status['state'] = 'deactivated';
            }
        }
        emc_payment_license_store_status( $status );
    }

    wp_safe_redirect(
        add_query_arg(
            'emc_license_updated',
            '1',
            admin_url( 'options-general.php?page=emc-payment-license' )
        )
    );
    exit;
} );

function emc_payment_license_settings_page() {
    $status = emc_payment_license_status();
    $key    = emc_payment_license_key();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'EMC Payment License', 'emc-theme' ); ?></h1>
        <?php if ( isset( $_GET['emc_license_updated'] ) ) : ?>
            <div class="notice notice-info is-dismissible"><p><?php echo esc_html( $status['message'] ); ?></p></div>
        <?php endif; ?>
        <p><?php esc_html_e( 'A valid license enables the donation forms and new Stripe payment requests on this site.', 'emc-theme' ); ?></p>

        <table class="widefat striped" style="max-width:760px;margin:18px 0;">
            <tbody>
                <tr><th><?php esc_html_e( 'Status', 'emc-theme' ); ?></th><td><strong><?php echo esc_html( ucfirst( $status['state'] ) ); ?></strong> &mdash; <?php echo esc_html( $status['message'] ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Plan', 'emc-theme' ); ?></th><td><?php echo esc_html( $status['plan'] ?: '—' ); ?></td></tr>
                <tr><th><?php esc_html_e( 'License type', 'emc-theme' ); ?></th><td><?php echo esc_html( $status['license_type'] ? ucfirst( $status['license_type'] ) : '—' ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Expires', 'emc-theme' ); ?></th><td><?php echo esc_html( $status['expires_at'] ?: '—' ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Last checked', 'emc-theme' ); ?></th><td><?php echo esc_html( $status['last_checked_at'] ?: '—' ); ?></td></tr>
            </tbody>
        </table>

        <?php if ( ! defined( 'EMC_PAYMENT_LICENSE_SERVER' ) || ! defined( 'EMC_PAYMENT_LICENSE_KEY' ) ) : ?>
            <form method="post" action="options.php">
                <?php settings_fields( 'emc_payment_license' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="emc_payment_license_server_url"><?php esc_html_e( 'License server URL', 'emc-theme' ); ?></label></th>
                        <td>
                            <?php if ( defined( 'EMC_PAYMENT_LICENSE_SERVER' ) ) : ?>
                                <code><?php echo esc_html( EMC_PAYMENT_LICENSE_SERVER ); ?></code> <em><?php esc_html_e( '(set in wp-config.php)', 'emc-theme' ); ?></em>
                            <?php else : ?>
                                <input class="regular-text" type="url" id="emc_payment_license_server_url" name="emc_payment_license_server_url" value="<?php echo esc_attr( emc_payment_license_server_url() ); ?>" placeholder="https://licenses.example.com">
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="emc_payment_license_key"><?php esc_html_e( 'License key', 'emc-theme' ); ?></label></th>
                        <td>
                            <?php if ( defined( 'EMC_PAYMENT_LICENSE_KEY' ) ) : ?>
                                <code><?php echo esc_html( substr( $key, 0, 8 ) . '••••••••' . substr( $key, -4 ) ); ?></code> <em><?php esc_html_e( '(set in wp-config.php)', 'emc-theme' ); ?></em>
                            <?php else : ?>
                                <input class="regular-text" type="text" id="emc_payment_license_key" name="emc_payment_license_key" value="<?php echo esc_attr( $key ); ?>" autocomplete="off" placeholder="EMC-XXXX-XXXX-XXXX-XXXX">
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Save License Settings', 'emc-theme' ) ); ?>
            </form>
        <?php else : ?>
            <p><em><?php esc_html_e( 'The license server and key are defined in wp-config.php.', 'emc-theme' ); ?></em></p>
        <?php endif; ?>

        <?php if ( $key && emc_payment_license_server_url() ) : ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php wp_nonce_field( 'emc_payment_license_action' ); ?>
                <input type="hidden" name="action" value="emc_payment_license_action">
                <button class="button button-primary" name="license_action" value="activate"><?php esc_html_e( 'Activate on This Site', 'emc-theme' ); ?></button>
                <button class="button" name="license_action" value="validate"><?php esc_html_e( 'Check Now', 'emc-theme' ); ?></button>
                <button class="button" name="license_action" value="deactivate"><?php esc_html_e( 'Deactivate This Site', 'emc-theme' ); ?></button>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Keep administrators informed when payments are locked.
 */
add_action( 'admin_notices', function () {
    if ( ! current_user_can( 'manage_options' ) || emc_payment_license_is_active() ) {
        return;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( $screen && 'settings_page_emc-payment-license' === $screen->id ) {
        return;
    }

    $status = emc_payment_license_status();
    echo '<div class="notice notice-warning"><p><strong>'
        . esc_html__( 'EMC payments are locked:', 'emc-theme' )
        . '</strong> ' . esc_html( $status['message'] )
        . ' <a href="' . esc_url( admin_url( 'options-general.php?page=emc-payment-license' ) ) . '">'
        . esc_html__( 'Manage license', 'emc-theme' ) . '</a></p></div>';
} );

/**
 * Refresh active licenses twice daily without slowing every page view.
 */
add_action( 'init', function () {
    if ( ! wp_next_scheduled( 'emc_payment_license_cron_check' ) ) {
        wp_schedule_event( time() + HOUR_IN_SECONDS, 'twicedaily', 'emc_payment_license_cron_check' );
    }
} );
add_action( 'emc_payment_license_cron_check', function () {
    emc_payment_license_refresh_status();
} );

add_action( 'switch_theme', function () {
    wp_clear_scheduled_hook( 'emc_payment_license_cron_check' );
} );
