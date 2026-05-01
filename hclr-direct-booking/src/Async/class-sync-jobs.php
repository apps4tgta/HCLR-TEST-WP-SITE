<?php
/**
 * Background Sync Jobs
 *
 * @package HCLR\DirectBooking\Async
 */

namespace HCLR\DirectBooking\Async;

use HCLR\DirectBooking\Admin\Settings;
use HCLR\DirectBooking\API\OwnerRez_Client;
use HCLR\DirectBooking\Database\DB_Manager;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Sync_Jobs
 *
 * Manages scheduled background synchronisation of OwnerRez data.
 */
class Sync_Jobs {

    /**
     * Settings instance.
     *
     * @var Settings
     */
    private Settings $settings;

    /**
     * OwnerRez API client.
     *
     * @var OwnerRez_Client
     */
    private OwnerRez_Client $client;

    /**
     * Database manager.
     *
     * @var DB_Manager
     */
    private DB_Manager $db;

    /**
     * Constructor.
     *
     * @param Settings $settings Settings instance.
     */
    public function __construct( Settings $settings ) {
        $this->settings = $settings;
        $this->client   = new OwnerRez_Client( $settings );
        $this->db       = new DB_Manager();
    }

    /**
     * Register hooks and schedule the cron event.
     *
     * @return void
     */
    public function init(): void {
        add_action( 'hclr_hourly_sync', array( $this, 'sync_all_properties' ) );

        if ( ! wp_next_scheduled( 'hclr_hourly_sync' ) ) {
            wp_schedule_event( time(), 'hourly', 'hclr_hourly_sync' );
        }
    }

    /**
     * Sync all properties and their availability.
     *
     * @return void
     */
    public function sync_all_properties(): void {
        if ( ! $this->settings->has_credentials() ) {
            return;
        }

        $result = $this->client->get_properties();

        if ( is_wp_error( $result ) ) {
            error_log( '[HCLR Sync] Failed to fetch properties: ' . $result->get_error_message() );
            $this->save_sync_status( array(
                'success' => false,
                'error'   => $result->get_error_message(),
                'time'    => current_time( 'mysql' ),
            ) );
            return;
        }

        $properties = $result['items'] ?? $result['data'] ?? array();

        if ( empty( $properties ) ) {
            $this->save_sync_status( array(
                'success'    => true,
                'properties' => 0,
                'time'       => current_time( 'mysql' ),
            ) );
            return;
        }

        // Cache all properties.
        $cached = array();
        foreach ( $properties as $property ) {
            $or_id = absint( $property['id'] ?? 0 );
            if ( ! $or_id ) {
                continue;
            }
            $this->db->upsert_property_cache( $or_id, $property );
            $cached[] = array(
                'id'        => $or_id,
                'name'      => sanitize_text_field( $property['name'] ?? '' ),
                'bedrooms'  => absint( $property['bedrooms'] ?? 0 ),
                'bathrooms' => absint( $property['bathrooms'] ?? 0 ),
                'sleeps'    => absint( $property['sleeps'] ?? 0 ),
            );

            // Sync availability for next 90 days.
            $this->sync_property_availability( $or_id );
        }

        update_option( 'hclr_properties_cache', $cached );

        $this->save_sync_status( array(
            'success'    => true,
            'properties' => count( $cached ),
            'time'       => current_time( 'mysql' ),
        ) );

        update_option( 'hclr_last_sync', current_time( 'mysql' ) );
    }

    /**
     * Sync availability for a single property for the next 90 days.
     *
     * @param int $property_id OwnerRez property ID.
     * @return void
     */
    public function sync_property_availability( int $property_id ): void {
        $start = date( 'Y-m-d' );
        $end   = date( 'Y-m-d', strtotime( '+90 days' ) );

        $result = $this->client->get_availability( $property_id, $start, $end );

        if ( is_wp_error( $result ) ) {
            error_log( "[HCLR Sync] Availability sync failed for property {$property_id}: " . $result->get_error_message() );
            return;
        }

        // OwnerRez returns availability as array of date objects.
        $dates = $result['items'] ?? $result['data'] ?? array();

        foreach ( $dates as $day ) {
            $date         = sanitize_text_field( $day['date'] ?? '' );
            $is_available = (bool) ( $day['available'] ?? $day['isAvailable'] ?? false );
            $nightly_rate = floatval( $day['nightly_rate'] ?? $day['nightlyRate'] ?? 0 );

            if ( ! $date ) {
                continue;
            }

            $this->db->upsert_availability( $property_id, $date, $is_available, $nightly_rate );
        }
    }

    /**
     * Save sync status to options.
     *
     * @param array $status Status data.
     * @return void
     */
    private function save_sync_status( array $status ): void {
        update_option( 'hclr_sync_status', $status );
    }
}
