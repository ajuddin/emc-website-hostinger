<?php

defined( 'ABSPATH' ) || exit;

class EMCLS_Repository {
    public static function licenses_table() {
        global $wpdb;
        return $wpdb->prefix . 'emc_payment_licenses';
    }

    public static function activations_table() {
        global $wpdb;
        return $wpdb->prefix . 'emc_payment_license_activations';
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset    = $wpdb->get_charset_collate();
        $licenses   = self::licenses_table();
        $activations = self::activations_table();

        dbDelta(
            "CREATE TABLE {$licenses} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                license_key varchar(64) NOT NULL,
                email varchar(190) NOT NULL DEFAULT '',
                customer_name varchar(190) NOT NULL DEFAULT '',
                status varchar(24) NOT NULL DEFAULT 'active',
                plan varchar(100) NOT NULL DEFAULT 'Payment Module',
                license_type varchar(24) NOT NULL DEFAULT 'monthly',
                expires_at datetime DEFAULT NULL,
                max_activations smallint(5) unsigned NOT NULL DEFAULT 1,
                stripe_customer_id varchar(100) NOT NULL DEFAULT '',
                stripe_subscription_id varchar(100) NOT NULL DEFAULT '',
                stripe_payment_id varchar(100) NOT NULL DEFAULT '',
                stripe_checkout_session_id varchar(100) NOT NULL DEFAULT '',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY license_key (license_key),
                KEY stripe_subscription_id (stripe_subscription_id),
                KEY stripe_checkout_session_id (stripe_checkout_session_id),
                KEY status_expires (status,expires_at)
            ) {$charset};"
        );

        dbDelta(
            "CREATE TABLE {$activations} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                license_id bigint(20) unsigned NOT NULL,
                instance_id varchar(64) NOT NULL,
                site_url varchar(255) NOT NULL,
                created_at datetime NOT NULL,
                last_seen_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY license_instance (license_id,instance_id),
                KEY license_id (license_id)
            ) {$charset};"
        );

        update_option( 'emcls_db_version', EMCLS_VERSION, false );
    }

    public static function maybe_upgrade() {
        if ( EMCLS_VERSION !== get_option( 'emcls_db_version' ) ) {
            self::install();
        }
    }

    public static function generate_key() {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $parts    = array();
        for ( $group = 0; $group < 4; $group++ ) {
            $part = '';
            for ( $i = 0; $i < 4; $i++ ) {
                $part .= $alphabet[ random_int( 0, strlen( $alphabet ) - 1 ) ];
            }
            $parts[] = $part;
        }
        return 'EMC-' . implode( '-', $parts );
    }

    public static function create( $data = array() ) {
        global $wpdb;

        $defaults = array(
            'license_key'           => self::generate_key(),
            'email'                 => '',
            'customer_name'         => '',
            'status'                => 'active',
            'plan'                  => 'Payment Module',
            'license_type'          => 'monthly',
            'expires_at'            => gmdate( 'Y-m-d H:i:s', time() + ( 365 * DAY_IN_SECONDS ) ),
            'max_activations'       => 1,
            'stripe_customer_id'    => '',
            'stripe_subscription_id'=> '',
            'stripe_payment_id'     => '',
            'stripe_checkout_session_id' => '',
        );
        $data = wp_parse_args( $data, $defaults );
        $now  = current_time( 'mysql', true );

        $inserted = $wpdb->insert(
            self::licenses_table(),
            array(
                'license_key'            => strtoupper( sanitize_text_field( $data['license_key'] ) ),
                'email'                  => sanitize_email( $data['email'] ),
                'customer_name'          => sanitize_text_field( $data['customer_name'] ),
                'status'                 => sanitize_key( $data['status'] ),
                'plan'                   => sanitize_text_field( $data['plan'] ),
                'license_type'           => sanitize_key( $data['license_type'] ),
                'expires_at'             => $data['expires_at'] ? gmdate( 'Y-m-d H:i:s', strtotime( $data['expires_at'] . ' UTC' ) ) : null,
                'max_activations'        => max( 1, absint( $data['max_activations'] ) ),
                'stripe_customer_id'     => sanitize_text_field( $data['stripe_customer_id'] ),
                'stripe_subscription_id' => sanitize_text_field( $data['stripe_subscription_id'] ),
                'stripe_payment_id'      => sanitize_text_field( $data['stripe_payment_id'] ),
                'stripe_checkout_session_id' => sanitize_text_field( $data['stripe_checkout_session_id'] ),
                'created_at'             => $now,
                'updated_at'             => $now,
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        return $inserted ? self::find_by_id( $wpdb->insert_id ) : new WP_Error( 'emcls_insert_failed', $wpdb->last_error ?: __( 'Could not create the license.', 'emc-license-server' ) );
    }

    public static function find_by_id( $id ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( 'SELECT * FROM ' . self::licenses_table() . ' WHERE id = %d', absint( $id ) ),
            ARRAY_A
        );
    }

    public static function find_by_key( $key ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . self::licenses_table() . ' WHERE license_key = %s',
                strtoupper( preg_replace( '/[^A-Z0-9-]/i', '', (string) $key ) )
            ),
            ARRAY_A
        );
    }

    public static function find_by_subscription( $subscription_id ) {
        global $wpdb;
        if ( ! $subscription_id ) {
            return null;
        }
        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . self::licenses_table() . ' WHERE stripe_subscription_id = %s ORDER BY id DESC LIMIT 1',
                sanitize_text_field( $subscription_id )
            ),
            ARRAY_A
        );
    }

    public static function find_by_checkout_session( $session_id ) {
        global $wpdb;
        if ( ! $session_id ) {
            return null;
        }
        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . self::licenses_table() . ' WHERE stripe_checkout_session_id = %s ORDER BY id DESC LIMIT 1',
                sanitize_text_field( $session_id )
            ),
            ARRAY_A
        );
    }

    public static function list_all( $limit = 200 ) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                'SELECT l.*, (SELECT COUNT(*) FROM ' . self::activations_table() . ' a WHERE a.license_id = l.id) activation_count
                FROM ' . self::licenses_table() . ' l ORDER BY l.id DESC LIMIT %d',
                absint( $limit )
            ),
            ARRAY_A
        );
    }

    public static function update( $id, $data ) {
        global $wpdb;
        $allowed = array(
            'email', 'customer_name', 'status', 'plan', 'license_type', 'expires_at',
            'max_activations', 'stripe_customer_id', 'stripe_subscription_id',
            'stripe_payment_id', 'stripe_checkout_session_id',
        );
        $clean = array();
        foreach ( $allowed as $field ) {
            if ( array_key_exists( $field, $data ) ) {
                $clean[ $field ] = $data[ $field ];
            }
        }
        if ( isset( $clean['email'] ) ) {
            $clean['email'] = sanitize_email( $clean['email'] );
        }
        if ( isset( $clean['customer_name'] ) ) {
            $clean['customer_name'] = sanitize_text_field( $clean['customer_name'] );
        }
        if ( isset( $clean['status'] ) ) {
            $clean['status'] = sanitize_key( $clean['status'] );
        }
        if ( isset( $clean['plan'] ) ) {
            $clean['plan'] = sanitize_text_field( $clean['plan'] );
        }
        if ( isset( $clean['license_type'] ) ) {
            $clean['license_type'] = sanitize_key( $clean['license_type'] );
        }
        if ( isset( $clean['max_activations'] ) ) {
            $clean['max_activations'] = max( 1, absint( $clean['max_activations'] ) );
        }
        if ( isset( $clean['expires_at'] ) && $clean['expires_at'] ) {
            $clean['expires_at'] = gmdate( 'Y-m-d H:i:s', strtotime( $clean['expires_at'] . ' UTC' ) );
        }
        if ( isset( $clean['stripe_customer_id'] ) ) {
            $clean['stripe_customer_id'] = sanitize_text_field( $clean['stripe_customer_id'] );
        }
        if ( isset( $clean['stripe_subscription_id'] ) ) {
            $clean['stripe_subscription_id'] = sanitize_text_field( $clean['stripe_subscription_id'] );
        }
        if ( isset( $clean['stripe_payment_id'] ) ) {
            $clean['stripe_payment_id'] = sanitize_text_field( $clean['stripe_payment_id'] );
        }
        if ( isset( $clean['stripe_checkout_session_id'] ) ) {
            $clean['stripe_checkout_session_id'] = sanitize_text_field( $clean['stripe_checkout_session_id'] );
        }
        $clean['updated_at'] = current_time( 'mysql', true );

        return false !== $wpdb->update( self::licenses_table(), $clean, array( 'id' => absint( $id ) ) );
    }

    public static function normalize_site_url( $url ) {
        $parts = wp_parse_url( esc_url_raw( $url ) );
        if ( empty( $parts['host'] ) ) {
            return '';
        }
        $scheme = ! empty( $parts['scheme'] ) ? strtolower( $parts['scheme'] ) : 'https';
        $host   = strtolower( $parts['host'] );
        $port   = ! empty( $parts['port'] ) ? ':' . absint( $parts['port'] ) : '';
        $path   = ! empty( $parts['path'] ) ? '/' . trim( $parts['path'], '/' ) : '';
        return untrailingslashit( $scheme . '://' . $host . $port . $path );
    }

    public static function state( $license ) {
        if ( ! $license ) {
            return 'invalid';
        }
        if ( 'active' !== $license['status'] ) {
            return sanitize_key( $license['status'] );
        }
        if ( ! empty( $license['expires_at'] ) && strtotime( $license['expires_at'] . ' UTC' ) <= time() ) {
            self::update( $license['id'], array( 'status' => 'expired' ) );
            return 'expired';
        }
        return 'active';
    }

    public static function activation_count( $license_id ) {
        global $wpdb;
        return absint(
            $wpdb->get_var(
                $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::activations_table() . ' WHERE license_id = %d', absint( $license_id ) )
            )
        );
    }

    public static function find_activation( $license_id, $instance_id ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . self::activations_table() . ' WHERE license_id = %d AND instance_id = %s',
                absint( $license_id ),
                sanitize_text_field( $instance_id )
            ),
            ARRAY_A
        );
    }

    public static function activate( $license, $instance_id, $site_url ) {
        global $wpdb;
        $instance_id = sanitize_text_field( $instance_id );
        $site_url    = self::normalize_site_url( $site_url );
        if ( ! $instance_id || ! $site_url ) {
            return new WP_Error( 'emcls_bad_site', __( 'A valid site URL and instance ID are required.', 'emc-license-server' ) );
        }

        $existing = self::find_activation( $license['id'], $instance_id );
        if ( $existing ) {
            $wpdb->update(
                self::activations_table(),
                array( 'site_url' => $site_url, 'last_seen_at' => current_time( 'mysql', true ) ),
                array( 'id' => $existing['id'] )
            );
            return true;
        }

        if ( self::activation_count( $license['id'] ) >= absint( $license['max_activations'] ) ) {
            return new WP_Error( 'emcls_limit', __( 'This license has reached its activation limit.', 'emc-license-server' ) );
        }

        return (bool) $wpdb->insert(
            self::activations_table(),
            array(
                'license_id'  => absint( $license['id'] ),
                'instance_id' => $instance_id,
                'site_url'    => $site_url,
                'created_at'  => current_time( 'mysql', true ),
                'last_seen_at'=> current_time( 'mysql', true ),
            ),
            array( '%d', '%s', '%s', '%s', '%s' )
        );
    }

    public static function validate_activation( $license, $instance_id, $site_url ) {
        global $wpdb;
        $activation = self::find_activation( $license['id'], $instance_id );
        if ( ! $activation || self::normalize_site_url( $site_url ) !== $activation['site_url'] ) {
            return false;
        }
        $wpdb->update(
            self::activations_table(),
            array( 'last_seen_at' => current_time( 'mysql', true ) ),
            array( 'id' => $activation['id'] )
        );
        return true;
    }

    public static function deactivate( $license, $instance_id ) {
        global $wpdb;
        return false !== $wpdb->delete(
            self::activations_table(),
            array(
                'license_id'  => absint( $license['id'] ),
                'instance_id' => sanitize_text_field( $instance_id ),
            ),
            array( '%d', '%s' )
        );
    }

    public static function clear_activations( $license_id ) {
        global $wpdb;
        return false !== $wpdb->delete(
            self::activations_table(),
            array( 'license_id' => absint( $license_id ) ),
            array( '%d' )
        );
    }
}
