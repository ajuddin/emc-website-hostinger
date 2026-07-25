<?php
/**
 * Plugin Name: EMC Payment License Server
 * Description: Self-hosted license management and optional Stripe subscription sales for the EMC payment module.
 * Version: 1.1.0
 * Author: EMC
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'EMCLS_VERSION', '1.1.0' );
define( 'EMCLS_FILE', __FILE__ );
define( 'EMCLS_DIR', plugin_dir_path( __FILE__ ) );

require_once EMCLS_DIR . 'includes/class-emcls-repository.php';
require_once EMCLS_DIR . 'includes/class-emcls-api.php';
require_once EMCLS_DIR . 'includes/class-emcls-stripe.php';
require_once EMCLS_DIR . 'includes/class-emcls-admin.php';

register_activation_hook( __FILE__, array( 'EMCLS_Repository', 'install' ) );

add_action( 'plugins_loaded', function () {
    EMCLS_Repository::maybe_upgrade();
    EMCLS_API::init();
    EMCLS_Stripe::init();
    EMCLS_Admin::init();
} );
