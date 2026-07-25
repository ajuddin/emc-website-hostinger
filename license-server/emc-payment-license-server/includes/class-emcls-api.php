<?php

defined( 'ABSPATH' ) || exit;

class EMCLS_API {
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
    }

    public static function routes() {
        foreach ( array( 'activate', 'validate', 'deactivate' ) as $action ) {
            register_rest_route(
                'emc-license/v1',
                '/' . $action,
                array(
                    'methods'             => 'POST',
                    'callback'            => array( __CLASS__, $action ),
                    'permission_callback' => '__return_true',
                )
            );
        }
    }

    private static function rate_limited( WP_REST_Request $request ) {
        $ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
        $key = 'emcls_rl_' . md5( $ip . '|' . $request->get_route() );
        $hits = absint( get_transient( $key ) );
        set_transient( $key, $hits + 1, MINUTE_IN_SECONDS );
        return $hits >= 60;
    }

    private static function input( WP_REST_Request $request ) {
        return array(
            'license_key' => strtoupper( preg_replace( '/[^A-Z0-9-]/i', '', (string) $request->get_param( 'license_key' ) ) ),
            'site_url'    => esc_url_raw( $request->get_param( 'site_url' ) ),
            'instance_id' => sanitize_text_field( $request->get_param( 'instance_id' ) ),
            'product'     => sanitize_key( $request->get_param( 'product' ) ),
        );
    }

    private static function response( $license, $valid, $status, $message, $http = 200 ) {
        return new WP_REST_Response(
            array(
                'valid'      => (bool) $valid,
                'status'     => sanitize_key( $status ),
                'message'    => sanitize_text_field( $message ),
                'expires_at' => ! empty( $license['expires_at'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $license['expires_at'] . ' UTC' ) ) : '',
                'plan'       => ! empty( $license['plan'] ) ? sanitize_text_field( $license['plan'] ) : '',
                'license_type' => ! empty( $license['license_type'] ) ? sanitize_key( $license['license_type'] ) : '',
            ),
            $http
        );
    }

    private static function find_valid_license( WP_REST_Request $request ) {
        if ( self::rate_limited( $request ) ) {
            return new WP_Error( 'emcls_rate_limit', __( 'Too many license requests. Try again shortly.', 'emc-license-server' ), array( 'status' => 429 ) );
        }
        $input = self::input( $request );
        if ( 'emc-payment-module' !== $input['product'] ) {
            return new WP_Error( 'emcls_product', __( 'Invalid product.', 'emc-license-server' ), array( 'status' => 400 ) );
        }
        if ( ! $input['license_key'] || ! $input['site_url'] || ! $input['instance_id'] ) {
            return new WP_Error( 'emcls_required', __( 'License key, site URL and instance ID are required.', 'emc-license-server' ), array( 'status' => 400 ) );
        }
        $license = EMCLS_Repository::find_by_key( $input['license_key'] );
        if ( ! $license ) {
            return new WP_Error( 'emcls_invalid', __( 'License key not found.', 'emc-license-server' ), array( 'status' => 404 ) );
        }
        return array( $license, $input );
    }

    public static function activate( WP_REST_Request $request ) {
        $found = self::find_valid_license( $request );
        if ( is_wp_error( $found ) ) {
            return $found;
        }
        list( $license, $input ) = $found;
        $state = EMCLS_Repository::state( $license );
        if ( 'active' !== $state ) {
            return self::response( $license, false, $state, __( 'This license is not active.', 'emc-license-server' ), 403 );
        }
        $activated = EMCLS_Repository::activate( $license, $input['instance_id'], $input['site_url'] );
        if ( is_wp_error( $activated ) ) {
            return self::response( $license, false, 'activation_limit', $activated->get_error_message(), 409 );
        }
        return self::response( $license, true, 'active', __( 'License activated.', 'emc-license-server' ) );
    }

    public static function validate( WP_REST_Request $request ) {
        $found = self::find_valid_license( $request );
        if ( is_wp_error( $found ) ) {
            return $found;
        }
        list( $license, $input ) = $found;
        $state = EMCLS_Repository::state( $license );
        if ( 'active' !== $state ) {
            return self::response( $license, false, $state, __( 'This license is not active.', 'emc-license-server' ), 403 );
        }
        if ( ! EMCLS_Repository::validate_activation( $license, $input['instance_id'], $input['site_url'] ) ) {
            return self::response( $license, false, 'not_activated', __( 'This site has not activated the license.', 'emc-license-server' ), 403 );
        }
        return self::response( $license, true, 'active', __( 'License active.', 'emc-license-server' ) );
    }

    public static function deactivate( WP_REST_Request $request ) {
        $found = self::find_valid_license( $request );
        if ( is_wp_error( $found ) ) {
            return $found;
        }
        list( $license, $input ) = $found;
        EMCLS_Repository::deactivate( $license, $input['instance_id'] );
        return self::response( $license, false, 'deactivated', __( 'License deactivated on this site.', 'emc-license-server' ) );
    }
}
