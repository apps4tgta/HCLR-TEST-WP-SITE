<?php
/**
 * HCLR Direct Booking - Uninstall Routine
 *
 * Runs when the plugin is deleted from WordPress admin.
 *
 * @package HCLR\DirectBooking
 */

// Only run when uninstalling via WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Delete all plugin options.
$options_to_delete = array(
    'hclr_or_email',
    'hclr_or_token',
    'hclr_cache_duration',
    'hclr_booking_redirect_url',
    'hclr_min_stay_override',
    'hclr_max_stay_override',
    'hclr_db_version',
    'hclr_last_sync',
    'hclr_sync_status',
);

foreach ( $options_to_delete as $option ) {
    delete_option( $option );
}

// Drop custom tables.
$tables = array(
    $wpdb->prefix . 'hclr_bookings',
    $wpdb->prefix . 'hclr_properties_cache',
    $wpdb->prefix . 'hclr_availability_cache',
);

foreach ( $tables as $table ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
}

// Delete all transients with hclr_ prefix.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hclr_%' OR option_name LIKE '_transient_timeout_hclr_%'"
);

// Clear scheduled hooks.
wp_clear_scheduled_hook( 'hclr_hourly_sync' );
