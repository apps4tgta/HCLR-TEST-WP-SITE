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
     * OwnerRez API v2 base URL (properties, bookings, quotes).
     *
     * @var string
     */
    const API_BASE = 'https://api.ownerrez.com/v2';

    /**
     * OwnerRez API v1 base URL (property images — not available in v2).
     *
     * @var string
     */
    const API_BASE_V1 = 'https://api.ownerrez.com/v1';

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
        // Images live on the v1 API — the /properties/{id}/images endpoint is not available in v2.
        $raw = $this->request( "/properties/{$property_id}/images", 'GET', array(), true, self::API_BASE_V1 );

        if ( is_wp_error( $raw ) ) {
            return array();
        }

        // v1 returns a plain JSON array (no wrapper key).
        $items  = is_array( $raw ) && isset( $raw[0] ) ? $raw : array();
        $photos = array();

        foreach ( $items as $i => $photo ) {
            // v1 fields: largeUrl, originalUrl, croppedUrl (no caption/position in this endpoint).
            $url = $photo['largeUrl'] ?? $photo['originalUrl'] ?? $photo['croppedUrl'] ?? '';
            if ( ! $url ) {
                continue;
            }
            $photos[] = array(
                'url'        => esc_url_raw( $url ),
                'caption'    => sanitize_text_field( $photo['caption'] ?? $photo['title'] ?? '' ),
                'sort_order' => $i, // preserve API order
            );
        }

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
        // OwnerRez v2 has no dedicated calendar endpoint.
        // Availability is derived by fetching all bookings and marking their date ranges as blocked.
        // Use since_utc far enough back to capture any long-running blocks set before $start.
        $since_utc = gmdate( 'Y-m-d\T00:00:00\Z', strtotime( $start . ' -3 years' ) );

        $raw = $this->request( '/bookings?' . http_build_query( array(
            'property_ids' => $property_id,
            'since_utc'    => $since_utc,
            'limit'        => 500,
        ) ), 'GET', array(), false ); // not cached — always fetch fresh for accuracy

        if ( is_wp_error( $raw ) ) {
            return $raw;
        }

        $bookings = $raw['items'] ?? array();

        // Initialize every date in the requested range as available.
        $normalized = array();
        $current    = new \DateTime( $start );
        $end_dt     = new \DateTime( $end );

        while ( $current <= $end_dt ) {
            $normalized[ $current->format( 'Y-m-d' ) ] = array(
                'available' => true,
                'rate'      => 0,
                'min_stay'  => 1,
            );
            $current->modify( '+1 day' );
        }

        // Mark each booking's nights as unavailable (arrival inclusive, departure exclusive).
        foreach ( $bookings as $booking ) {
            $arrival   = $booking['arrival']   ?? '';
            $departure = $booking['departure'] ?? '';
            if ( ! $arrival || ! $departure ) {
                continue;
            }

            $day     = new \DateTime( $arrival );
            $dep_dt  = new \DateTime( $departure );

            while ( $day < $dep_dt ) {
                $date_str = $day->format( 'Y-m-d' );
                if ( isset( $normalized[ $date_str ] ) ) {
                    $normalized[ $date_str ]['available'] = false;
                }
                $day->modify( '+1 day' );
            }
        }

        return $normalized;
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
            'property_id' => $property_id,
            'arrival'     => $check_in,
            'departure'   => $check_out,
            'adults'      => $guests,
        );
        return $this->request( '/quotes', 'POST', $body, false );
    }

    /**
     * Create a guest record in OwnerRez (v1 API).
     *
     * @param string $first_name First name.
     * @param string $last_name  Last name.
     * @param string $email      Email address.
     * @param string $phone      Phone number (optional).
     * @return array|\WP_Error
     */
    public function create_guest( string $first_name, string $last_name, string $email, string $phone = '' ): array|\WP_Error {
        $body = array(
            'FirstName' => $first_name,
            'LastName'  => $last_name,
            'Email'     => $email,
        );
        if ( $phone ) {
            $body['Phone'] = $phone;
        }
        return $this->request( '/guests', 'POST', $body, false, self::API_BASE_V1 );
    }

    /**
     * Create a v1 quote and return the hosted paymentForm URL.
     *
     * OwnerRez's payment page (orez.io) collects card details — we redirect the
     * guest there after creating the quote.
     *
     * @param int    $guest_id    OwnerRez guest ID.
     * @param int    $property_id OwnerRez property ID.
     * @param string $arrival     Arrival date (Y-m-d).
     * @param string $departure   Departure date (Y-m-d).
     * @param int    $adults      Number of adults.
     * @return array|\WP_Error
     */
    public function create_v1_quote( int $guest_id, int $property_id, string $arrival, string $departure, int $adults = 1 ): array|\WP_Error {
        $body = array(
            'GuestId'    => $guest_id,
            'PropertyId' => $property_id,
            'Arrival'    => $arrival,
            'Departure'  => $departure,
            'Adults'     => $adults,
            'Children'   => 0,
            'Pets'       => 0,
        );
        return $this->request( '/quotes', 'POST', $body, false, self::API_BASE_V1 );
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
    private function request( string $endpoint, string $method = 'GET', array $body = array(), bool $cacheable = true, string $api_base = '' ): array|\WP_Error {
        if ( ! $this->settings->has_credentials() ) {
            return new \WP_Error( 'no_credentials', __( 'OwnerRez API credentials not configured.', 'hclr-direct-booking' ) );
        }

        $base      = $api_base ?: self::API_BASE;
        $cache_key = 'hclr_' . md5( $base . $endpoint );

        // Check cache for GET requests.
        if ( 'GET' === $method && $cacheable ) {
            $cached = get_transient( $cache_key );
            if ( false !== $cached ) {
                return $cached;
            }
        }

        $auth    = base64_encode( $this->settings->get_or_email() . ':' . $this->settings->get_or_token() );
        $url     = $base . $endpoint;
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
