<?php
/**
 * Plugin Name: HW Invoice PDF
 * Description: Generate professional PDF invoices for WooCommerce orders with a fast engine and modern design dashboard.
 * Version: 1.0.0
 * Author: Hayu Widyas Handmade
 * Text Domain: hw-invoice-pdf
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'HWIP_PLUGIN_FILE', __FILE__ );
define( 'HWIP_PLUGIN_DIR', plugin_dir_path( HWIP_PLUGIN_FILE ) );
define( 'HWIP_PLUGIN_URL', plugin_dir_url( HWIP_PLUGIN_FILE ) );
require_once HWIP_PLUGIN_DIR . 'includes/helpers.php';
require_once HWIP_PLUGIN_DIR . 'includes/class-hwip-pdf-generator.php';
require_once HWIP_PLUGIN_DIR . 'includes/class-hwip-invoice.php';
require_once HWIP_PLUGIN_DIR . 'includes/class-hwip-cron.php';
require_once HWIP_PLUGIN_DIR . 'includes/class-hwip-admin.php';
require_once HWIP_PLUGIN_DIR . 'includes/class-hwip-plugin.php';

/**
 * Initialize plugin instance.
 *
 * @return HWIP_Plugin
 */
function hwip_plugin() {
    return HWIP_Plugin::instance();
}

hwip_plugin();

register_activation_hook( __FILE__, array( 'HWIP_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'HWIP_Plugin', 'deactivate' ) );
