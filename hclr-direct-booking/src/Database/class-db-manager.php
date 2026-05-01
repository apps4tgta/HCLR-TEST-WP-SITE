<?php
/**
 * Database Manager
 *
 * @package HCLR\DirectBooking\Database
 */

namespace HCLR\DirectBooking\Database;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class DB_Manager
 *
 * Handles creation and management of custom database tables.
 * All queries use prepared statements for security.
 */
class DB_Manager {

    /**
     * Create all custom database tables on plugin activation.
     *
     * @return void
     */
    public static function create_tables(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Bookings table.
        $bookings_table = $wpdb->prefix . 'hclr_bookings';
        dbDelta( "CREATE TABLE IF NOT EXISTS `{$bookings_table}` (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            property_id BIGINT UNSIGNED NOT NULL,
            or_booking_id BIGINT UNSIGNED DEFAULT 0,
            check_in DATE NOT NULL,
            check_out DATE NOT NULL,
            guests TINYINT UNSIGNED NOT NULL DEFAULT 1,
            guest_name VARCHAR(200) NOT NULL DEFAULT '',
            guest_email VARCHAR(200) NOT NULL DEFAULT '',
            guest_phone VARCHAR(50) NOT NULL DEFAULT '',
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            special_requests TEXT NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY property_id (property_id),
            KEY status (status),
            KEY check_in (check_in)
        ) {$charset_collate};" );

        // Properties cache table.
        $properties_table = $wpdb->prefix . 'hclr_properties_cache';
        dbDelta( "CREATE TABLE IF NOT EXISTS `{$properties_table}` (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            or_property_id BIGINT UNSIGNED NOT NULL,
            property_data LONGTEXT NOT NULL DEFAULT '',
            last_synced DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY or_property_id (or_property_id)
        ) {$charset_collate};" );

        // Availability cache table.
        $availability_table = $wpdb->prefix . 'hclr_availability_cache';
        dbDelta( "CREATE TABLE IF NOT EXISTS `{$availability_table}` (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            property_id BIGINT UNSIGNED NOT NULL,
            date DATE NOT NULL,
            is_available TINYINT(1) NOT NULL DEFAULT 1,
            nightly_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            last_synced DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY property_date (property_id, date),
            KEY property_id (property_id)
        ) {$charset_collate};" );
    }

    /**
     * Retrieve a booking by ID.
     *
     * @param int $id Booking ID.
     * @return object|null
     */
    public function get_booking( int $id ): ?object {
        global $wpdb;
        $table = $wpdb->prefix . 'hclr_bookings';

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM `{$table}` WHERE id = %d",
            $id
        ) );
    }

    /**
     * Save a booking (insert or update).
     *
     * @param array $data Booking data.
     * @return int Inserted/updated row ID.
     */
    public function save_booking( array $data ): int {
        global $wpdb;
        $table = $wpdb->prefix . 'hclr_bookings';

        $row = array(
            'property_id'     => absint( $data['property_id'] ),
            'or_booking_id'   => absint( $data['or_booking_id'] ?? 0 ),
            'check_in'        => sanitize_text_field( $data['check_in'] ),
            'check_out'       => sanitize_text_field( $data['check_out'] ),
            'guests'          => absint( $data['guests'] ?? 1 ),
            'guest_name'      => sanitize_text_field( $data['guest_name'] ?? '' ),
            'guest_email'     => sanitize_email( $data['guest_email'] ?? '' ),
            'guest_phone'     => sanitize_text_field( $data['guest_phone'] ?? '' ),
            'total_amount'    => floatval( $data['total_amount'] ?? 0 ),
            'status'          => sanitize_text_field( $data['status'] ?? 'pending' ),
            'special_requests' => sanitize_textarea_field( $data['special_requests'] ?? '' ),
        );

        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( $table, $row, array( 'id' => absint( $data['id'] ) ) );
            return absint( $data['id'] );
        }

        $wpdb->insert( $table, $row );
        return (int) $wpdb->insert_id;
    }

    /**
     * Update booking status.
     *
     * @param int    $id     Booking ID.
     * @param string $status New status.
     * @return bool
     */
    public function update_booking_status( int $id, string $status ): bool {
        global $wpdb;
        $table  = $wpdb->prefix . 'hclr_bookings';
        $result = $wpdb->update(
            $table,
            array( 'status' => sanitize_text_field( $status ) ),
            array( 'id' => $id ),
            array( '%s' ),
            array( '%d' )
        );
        return false !== $result;
    }

    /**
     * Get all bookings for a property.
     *
     * @param int $property_id Property ID.
     * @return array
     */
    public function get_bookings_by_property( int $property_id ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'hclr_bookings';

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM `{$table}` WHERE property_id = %d ORDER BY check_in ASC",
            $property_id
        ) ) ?? array();
    }

    /**
     * Upsert a cached property record.
     *
     * @param int   $or_id    OwnerRez property ID.
     * @param array $property Property data array.
     * @return void
     */
    public function upsert_property_cache( int $or_id, array $property ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'hclr_properties_cache';

        $wpdb->replace( $table, array(
            'or_property_id' => $or_id,
            'property_data'  => wp_json_encode( $property ),
            'last_synced'    => current_time( 'mysql' ),
        ) );
    }

    /**
     * Upsert an availability record for a given date.
     *
     * @param int    $property_id  Property ID.
     * @param string $date         Date (Y-m-d).
     * @param bool   $is_available Availability flag.
     * @param float  $nightly_rate Nightly rate.
     * @return void
     */
    public function upsert_availability( int $property_id, string $date, bool $is_available, float $nightly_rate ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'hclr_availability_cache';

        $wpdb->replace( $table, array(
            'property_id'  => $property_id,
            'date'         => $date,
            'is_available' => $is_available ? 1 : 0,
            'nightly_rate' => round( $nightly_rate, 2 ),
            'last_synced'  => current_time( 'mysql' ),
        ) );
    }
}
