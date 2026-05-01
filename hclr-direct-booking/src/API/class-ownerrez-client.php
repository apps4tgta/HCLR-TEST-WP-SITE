<?php
/**
 * OwnerRez API V2 Client
 *
 * @package HCLR\DirectBooking\API
 */

namespace HCLR\DirectBooking\API;

use HCLR\DirectBooking\Admin\Settings;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class OwnerRez_Client
 *
 * Handles all HTTP communication with the OwnerRez V2 REST API.
 * Uses Basic Auth (email:token), WP HTTP API, and transient caching.
 */
class OwnerRez_Client {

    /**
     * OwnerRez API base URL.
     *
     * @var string
     */
    const API_BASE = 'https://api.ownerrez.com/v2';

    /**
     * Settings instance.
     *
     * @var Settings
     */
    private Settings $settings;

    /**
     * Constructor.
     *
     * @param Settings $settings Settings instance.
     */
    public function __construct( Settings $settings ) {
        $this->settings = $settings;
    }

    /**
     * Get all properties for the account.
     *
     * @return array|\WP_Error
     */
    public function get_properties(): array|\WP_Error {
        return $this->request( '/properties' );
    }

    /**
     * Get a single property by ID.
     *
     * @param int $property_id OwnerRez property ID.
     * @return array|\WP_Error
     */
    public function get_property( int $property_id ): array|\WP_Error {
        return $this->request( "/properties/{$property_id}" );
    }

    /**
     * Get photos for a property.
     *
     * Returns an array of photo objects: [ { url, caption, sort_order }, ... ]
     *
     * @param int $property_id OwnerRez property ID.
     * @return array|\WP_Error
     */
    public function get_property_photos( int $property_id ): array|\WP_Error {
        $raw = $this->request( "/properties/{$property_id}/photos" );

        if ( is_wp_error( $raw ) ) {
            // Try alternate endpoint.
            $raw = $this->request( '/propertyphotos?' . http_build_query( array( 'propertyid' => $property_id ) ) );
        }

        if ( is_wp_error( $raw ) ) {
            return array();
        }

        // Normalize to a simple array of { url, caption, sort_order }.
        $items = $raw['items'] ?? $raw['data'] ?? $raw ?? array();
        $photos = array();
        foreach ( (array) $items as $photo ) {
            $url = $photo['url'] ?? $photo['photoUrl'] ?? $photo['src'] ?? '';
            if ( ! $url ) continue;
            $photos[] = array(
                'url'        => esc_url_raw( $url ),
                'caption'    => sanitize_text_field( $photo['caption'] ?? $photo['title'] ?? '' ),
                'sort_order' => absint( $photo['sortOrder'] ?? $photo['sort_order'] ?? 0 ),
            );
        }

        // Sort by sort_order.
        usort( $photos, fn( $a, $b ) => $a['sort_order'] <=> $b['sort_order'] );

        return $photos;
    }

    /**
     * Get full property data: details + photos combined.
     *
     * @param int $property_id OwnerRez property ID.
     * @return array|\WP_Error
     */
    public function get_property_full( int $property_id ): array|\WP_Error {
        $property = $this->get_property( $property_id );

        if ( is_wp_error( $property ) ) {
            return $property;
        }

        $photos = $this->get_property_photos( $property_id );
        $property['photos'] = is_array( $photos ) ? $photos : array();

        return $property;
    }

    /**
     * Get calendar data for a property over a date range.
     *
     * Calls the OwnerRez V2 calendar endpoint and normalizes response to
     * a date-keyed array: { "YYYY-MM-DD": { available, rate, min_stay } }.
     *
     * @param int    $property_id OwnerRez property ID.
     * @param string $start       Start date (Y-m-d).
     * @param string $end         End date (Y-m-d).
     * @return array|\WP_Error  Normalized date-keyed availability array.
     */
    public function get_availability( int $property_id, string $start, string $end ): array|\WP_Error {
        $endpoint = "/properties/{$property_id}/calendar";
        $query    = http_build_query( array(
            'startDate' => $start,
            'endDate'   => $end,
        ) );
        $raw = $this->request( "{$endpoint}?{$query}" );

        if ( is_wp_error( $raw ) ) {
            return $raw;
        }

        return $this->normalize_calendar_response( $raw );
    }

    /**
     * Get calendar data for a specific month.
     *
     * @param int $property_id OwnerRez property ID.
     * @param int $year        4-digit year.
     * @param int $month       1-12.
     * @return array|\WP_Error Normalized date-keyed availability array.
     */
    public function get_calendar_month( int $property_id, int $year, int $month ): array|\WP_Error {
        $start = sprintf( '%04d-%02d-01', $year, $month );
        $end   = date( 'Y-m-t', strtotime( $start ) );
        return $this->get_availability( $property_id, $start, $end );
    }

    /**
     * Normalize OwnerRez calendar response to a consistent format.
     *
     * Handles both date-keyed object format and items-array format.
     *
     * @param array $raw Raw API response.
     * @return array Normalized: { "YYYY-MM-DD": { available, rate, min_stay } }
     */
    private function normalize_calendar_response( array $raw ): array {
        $normalized = array();

        // Format 1: { "data": { "calendar": { "YYYY-MM-DD": {...} } } }
        if ( isset( $raw['data']['calendar'] ) && is_array( $raw['data']['calendar'] ) ) {
            foreach ( $raw['data']['calendar'] as $date => $day ) {
                $normalized[ $date ] = array(
                    'available' => (bool) ( $day['available'] ?? true ),
                    'rate'      => floatval( $day['nightly_rate'] ?? $day['price'] ?? 0 ),
                    'min_stay'  => absint( $day['min_stay'] ?? $day['minStay'] ?? 1 ),
                );
            }
            return $normalized;
        }

        // Format 2: { "items": [ { "date": "...", "availability": "available", "price": 0 } ] }
        $items = $raw['items'] ?? $raw['data'] ?? array();
        if ( is_array( $items ) ) {
            foreach ( $items as $day ) {
                $date = substr( $day['date'] ?? '', 0, 10 );
                if ( ! $date ) {
                    continue;
                }
                $avail = $day['availability'] ?? ( isset( $day['available'] ) ? ( $day['available'] ? 'available' : 'blocked' ) : 'available' );
                $normalized[ $date ] = array(
                    'available' => ( 'available' === $avail || 'checkout' === $avail ),
                    'rate'      => floatval( $day['price'] ?? $day['nightly_rate'] ?? 0 ),
                    'min_stay'  => absint( $day['minstaynights'] ?? $day['min_stay'] ?? 1 ),
                );
            }
        }

        return $normalized;
    }

    /**
     * Get nightly rates for a property over a date range.
     *
     * @param int    $property_id OwnerRez property ID.
     * @param string $start       Start date (Y-m-d).
     * @param string $end         End date (Y-m-d).
     * @return array|\WP_Error
     */
    public function get_rates( int $property_id, string $start, string $end ): array|\WP_Error {
        $endpoint = "/properties/{$property_id}/rates";
        $query    = http_build_query( array(
            'startDate' => $start,
            'endDate'   => $end,
        ) );
        return $this->request( "{$endpoint}?{$query}" );
    }

    /**
     * Get a price quote for a date range.
     *
     * @param int    $property_id OwnerRez property ID.
     * @param string $check_in    Check-in date (Y-m-d).
     * @param string $check_out   Check-out date (Y-m-d).
     * @param int    $guests      Number of guests.
     * @return array|\WP_Error
     */
    public function get_quote( int $property_id, string $check_in, string $check_out, int $guests = 1 ): array|\WP_Error {
        $body = array(
            'propertyId' => $property_id,
            'checkIn'    => $check_in,
            'checkOut'   => $check_out,
            'adults'     => $guests,
        );
        return $this->request( '/quotes', 'POST', $body, false );
    }

    /**
     * Create a booking in OwnerRez.
     *
     * @param array $data Booking data array.
     * @return array|\WP_Error
     */
    public function create_booking( array $data ): array|\WP_Error {
        return $this->request( '/bookings', 'POST', $data, false );
    }

    /**
     * Get a single booking by ID.
     *
     * @param int $booking_id OwnerRez booking ID.
     * @return array|\WP_Error
     */
    public function get_booking( int $booking_id ): array|\WP_Error {
        return $this->request( "/bookings/{$booking_id}" );
    }

    /**
     * Core HTTP request method.
     *
     * @param string $endpoint  API endpoint path (with leading slash).
     * @param string $method    HTTP method (GET, POST, PUT, DELETE).
     * @param array  $body      Request body for POST/PUT.
     * @param bool   $cacheable Whether to cache GET responses.
     * @return array|\WP_Error
     */
    private function request( string $endpoint, string $method = 'GET', array $body = array(), bool $cacheable = true ): array|\WP_Error {
        if ( ! $this->settings->has_credentials() ) {
            return new \WP_Error( 'no_credentials', __( 'OwnerRez API credentials not configured.', 'hclr-direct-booking' ) );
        }

        // Check cache for GET requests.
        $cache_key = 'hclr_' . md5( $endpoint );
        if ( 'GET' === $method && $cacheable ) {
            $cached = get_transient( $cache_key );
            if ( false !== $cached ) {
                return $cached;
            }
        }

        $auth    = base64_encode( $this->settings->get_or_email() . ':' . $this->settings->get_or_token() );
        $url     = self::API_BASE . $endpoint;
        $attempts = 0;
        $max_attempts = 2;

        do {
            $attempts++;
            $args = array(
                'method'  => $method,
                'timeout' => 20,
                'headers' => array(
                    'Authorization' => 'Basic ' . $auth,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ),
            );

            if ( ! empty( $body ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
                $args['body'] = wp_json_encode( $body );
            }

            $response = wp_remote_request( $url, $args );

            if ( is_wp_error( $response ) ) {
                if ( $attempts < $max_attempts && str_contains( $response->get_error_code(), 'timeout' ) ) {
                    continue; // Retry on timeout.
                }
                error_log( '[HCLR] API request failed: ' . $response->get_error_message() );
                return $response;
            }

            break; // Request succeeded (no timeout).
        } while ( $attempts < $max_attempts );

        $http_code = wp_remote_retrieve_response_code( $response );
        $raw_body  = wp_remote_retrieve_body( $response );
        $decoded   = json_decode( $raw_body, true );

        if ( $http_code < 200 || $http_code >= 300 ) {
            $error_message = $decoded['message'] ?? "HTTP {$http_code}";
            error_log( "[HCLR] API error {$http_code} for {$endpoint}: {$error_message}" );
            return new \WP_Error( 'api_error', $error_message, array( 'status' => $http_code ) );
        }

        if ( null === $decoded ) {
            return new \WP_Error( 'invalid_response', __( 'Invalid JSON response from OwnerRez.', 'hclr-direct-booking' ) );
        }

        // Cache successful GET responses.
        if ( 'GET' === $method && $cacheable ) {
            set_transient( $cache_key, $decoded, $this->settings->get_cache_duration() );
        }

        return $decoded;
    }
}
