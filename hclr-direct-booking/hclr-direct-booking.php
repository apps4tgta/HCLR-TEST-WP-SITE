<?php
/**
 * Plugin Name:       HCLR Direct Booking
 * Plugin URI:        https://hillcountrylakesrentals.com
 * Description:       OwnerRez API integration for direct vacation rental bookings at Hill Country Lakes Rentals. Provides availability calendars, price quotes, and booking forms.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Hill Country Lakes Rentals
 * Author URI:        https://hillcountrylakesrentals.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       hclr-direct-booking
 * Domain Path:       /languages
 *
 * @package HCLR\DirectBooking
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants.
define( 'HCLR_DB_VERSION', '1.0.0' );
define( 'HCLR_PLUGIN_VERSION', '1.0.2' );
define( 'HCLR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HCLR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HCLR_PLUGIN_FILE', __FILE__ );

/**
 * PSR-4 Autoloader for HCLR\DirectBooking namespace.
 */
spl_autoload_register( function ( string $class ) {
    $prefix = 'HCLR\\DirectBooking\\';
    $base_dir = HCLR_PLUGIN_DIR . 'src/';

    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

    // Convert PascalCase class name to WordPress file naming convention.
    $parts      = explode( '/', $file );
    $filename   = array_pop( $parts );
    $filename   = 'class-' . strtolower( str_replace( '_', '-', $filename ) );
    $parts[]    = $filename;
    $file       = implode( '/', $parts );

    if ( file_exists( $file ) ) {
        require $file;
    }
} );

/**
 * Activation hook.
 */
function hclr_activate_plugin(): void {
    require_once HCLR_PLUGIN_DIR . 'src/Database/class-db-manager.php';
    HCLR\DirectBooking\Database\DB_Manager::create_tables();
    flush_rewrite_rules();
    update_option( 'hclr_db_version', HCLR_DB_VERSION );
}
register_activation_hook( __FILE__, 'hclr_activate_plugin' );

/**
 * Deactivation hook.
 */
function hclr_deactivate_plugin(): void {
    wp_clear_scheduled_hook( 'hclr_hourly_sync' );
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'hclr_deactivate_plugin' );

/**
 * Bootstrap the plugin on plugins_loaded.
 */
function hclr_init_plugin(): void {
    // Load text domain.
    load_plugin_textdomain(
        'hclr-direct-booking',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );

    // Boot main plugin class.
    HCLR\DirectBooking\Plugin::get_instance();
}
add_action( 'plugins_loaded', 'hclr_init_plugin' );
