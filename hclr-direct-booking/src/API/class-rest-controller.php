<?php
/**
 * REST API Controller
 *
 * @package HCLR\DirectBooking\API
 */

namespace HCLR\DirectBooking\API;

use HCLR\DirectBooking\Admin\Settings;
use HCLR\DirectBooking\Database\DB_Manager;
use HCLR\DirectBooking\Helpers\Helpers;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class REST_Controller
 *
 * Registers and handles all public REST API endpoints for HCLR Direct Booking.
 * Namespace: hclr/v1
 */
class REST_Controller extends \WP_REST_Controller {

    /**
     * REST namespace.
     *
     * @var string
     */
    protected $namespace = 'hclr/v1';

    /**
     * Settings instance.
     *
     * @var Settings
     */
    private Settings $settings;

    /**
     * OwnerRez client.
     *
     * @var OwnerRez_Client
     */
    private OwnerRez_Client $client;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->settings = \HCLR\DirectBooking\Plugin::get_instance()->settings;
        $this->client   = new OwnerRez_Client( $this->settings );
    }

    /**
     * Register all routes.
     *
     * @return void
     */
    public function register_routes(): void {
        // GET /hclr/v1/properties
        register_rest_route( $this->namespace, '/properties', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_properties' ),
                'permission_callback' => '__return_true',
            ),
        ) );

        // GET /hclr/v1/properties/{id}
        register_rest_route( $this->namespace, '/properties/(?P<id>\d+)', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_property' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'id' => array(
                        'type'     => 'integer',
                        'required' => true,
                        'minimum'  => 1,
                    ),
                ),
            ),
        ) );

        // GET /hclr/v1/properties/{id}/full — property details + photos combined
        register_rest_route( $this->namespace, '/properties/(?P<id>\d+)/full', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_property_full' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'id' => array( 'type' => 'integer', 'required' => true, 'minimum' => 1 ),
                ),
            ),
        ) );

        // GET /hclr/v1/properties/{id}/photos
        register_rest_route( $this->namespace, '/properties/(?P<id>\d+)/photos', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_property_photos' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'id' => array( 'type' => 'integer', 'required' => true, 'minimum' => 1 ),
                ),
            ),
        ) );

        // GET /hclr/v1/calendar  — month-view calendar data for the widget
        register_rest_route( $this->namespace, '/calendar', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_calendar' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'property_id' => array(
                        'type'     => 'integer',
                        'required' => true,
                        'minimum'  => 1,
                    ),
                    'year'  => array(
                        'type'    => 'integer',
                        'default' => (int) date( 'Y' ),
                    ),
                    'month' => array(
                        'type'    => 'integer',
                        'default' => (int) date( 'm' ),
                        'minimum' => 1,
                        'maximum' => 12,
                    ),
                ),
            ),
        ) );

        // GET /hclr/v1/availability — date-range check (check_in / check_out)
        register_rest_route( $this->namespace, '/availability', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_availability' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'property_id' => array(
                        'type'     => 'integer',
                        'required' => true,
                        'minimum'  => 1,
                    ),
                    'check_in' => array(
                        'type'     => 'string',
                        'required' => true,
                        'format'   => 'date',
                    ),
                    'check_out' => array(
                        'type'     => 'string',
                        'required' => true,
                        'format'   => 'date',
                    ),
                ),
            ),
        ) );

        // POST /hclr/v1/quote
        register_rest_route( $this->namespace, '/quote', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'get_quote' ),
                'permission_callback' => array( $this, 'check_rate_limit' ),
                'args'                => array(
                    'property_id' => array( 'type' => 'integer', 'required' => true, 'minimum' => 1 ),
                    'check_in'    => array( 'type' => 'string', 'required' => true, 'format' => 'date' ),
                    'check_out'   => array( 'type' => 'string', 'required' => true, 'format' => 'date' ),
                    'guests'      => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 ),
                ),
            ),
        ) );

        // POST /hclr/v1/booking
        register_rest_route( $this->namespace, '/booking', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_booking' ),
                'permission_callback' => array( $this, 'verify_booking_nonce' ),
                'args'                => array(
                    'property_id'     => array( 'type' => 'integer', 'required' => true ),
                    'check_in'        => array( 'type' => 'string', 'required' => true, 'format' => 'date' ),
                    'check_out'       => array( 'type' => 'string', 'required' => true, 'format' => 'date' ),
                    'guests'          => array( 'type' => 'integer', 'required' => true, 'minimum' => 1 ),
                    'first_name'      => array( 'type' => 'string', 'required' => true ),
                    'last_name'       => array( 'type' => 'string', 'required' => true ),
                    'email'           => array( 'type' => 'string', 'required' => true, 'format' => 'email' ),
                    'phone'           => array( 'type' => 'string', 'required' => false ),
                    'special_requests' => array( 'type' => 'string', 'required' => false ),
                    'nonce'           => array( 'type' => 'string', 'required' => true ),
                ),
            ),
        ) );

        // GET /hclr/v1/booking/{id}
        register_rest_route( $this->namespace, '/booking/(?P<id>\d+)', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_booking' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'id' => array( 'type' => 'integer', 'required' => true, 'minimum' => 1 ),
                ),
            ),
        ) );
    }

    /**
     * GET /hclr/v1/properties
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_properties( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $result = $this->client->get_properties();
        if ( is_wp_error( $result ) ) {
            return rest_ensure_response( new \WP_Error( 'api_error', $result->get_error_message(), array( 'status' => 502 ) ) );
        }
        return rest_ensure_response( $result );
    }

    /**
     * GET /hclr/v1/properties/{id}
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_property( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $id     = absint( $request->get_param( 'id' ) );
        $result = $this->client->get_property( $id );
        if ( is_wp_error( $result ) ) {
            return rest_ensure_response( new \WP_Error( 'api_error', $result->get_error_message(), array( 'status' => 502 ) ) );
        }
        return rest_ensure_response( $result );
    }

    /**
     * GET /hclr/v1/properties/{id}/full
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_property_full( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $id     = absint( $request->get_param( 'id' ) );
        $result = $this->client->get_property_full( $id );
        if ( is_wp_error( $result ) ) {
            return rest_ensure_response( new \WP_Error( 'api_error', $result->get_error_message(), array( 'status' => 502 ) ) );
        }
        return rest_ensure_response( $result );
    }

    /**
     * GET /hclr/v1/properties/{id}/photos
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_property_photos( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $id     = absint( $request->get_param( 'id' ) );
        $result = $this->client->get_property_photos( $id );
        return rest_ensure_response( is_array( $result ) ? $result : array() );
    }

    /**
     * GET /hclr/v1/calendar
     *
     * Returns normalized date-keyed availability data for a full month.
     * Response: { "YYYY-MM-DD": { available, rate, min_stay }, ... }
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_calendar( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $property_id = absint( $request->get_param( 'property_id' ) );
        $year        = absint( $request->get_param( 'year' ) ) ?: (int) date( 'Y' );
        $month       = absint( $request->get_param( 'month' ) ) ?: (int) date( 'm' );

        if ( $month < 1 || $month > 12 ) {
            return new \WP_Error( 'invalid_month', __( 'Month must be 1–12.', 'hclr-direct-booking' ), array( 'status' => 400 ) );
        }

        $result = $this->client->get_calendar_month( $property_id, $year, $month );

        if ( is_wp_error( $result ) ) {
            return rest_ensure_response( new \WP_Error( 'api_error', $result->get_error_message(), array( 'status' => 502 ) ) );
        }

        return rest_ensure_response( array(
            'property_id' => $property_id,
            'year'        => $year,
            'month'       => $month,
            'days'        => $result,
        ) );
    }

    /**
     * GET /hclr/v1/availability
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_availability( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $property_id = absint( $request->get_param( 'property_id' ) );
        $check_in    = sanitize_text_field( $request->get_param( 'check_in' ) );
        $check_out   = sanitize_text_field( $request->get_param( 'check_out' ) );

        $validation = Helpers::validate_dates( $check_in, $check_out );
        if ( is_wp_error( $validation ) ) {
            return rest_ensure_response( new \WP_Error( 'invalid_dates', $validation->get_error_message(), array( 'status' => 400 ) ) );
        }

        $result = $this->client->get_availability( $property_id, $check_in, $check_out );
        if ( is_wp_error( $result ) ) {
            return rest_ensure_response( new \WP_Error( 'api_error', $result->get_error_message(), array( 'status' => 502 ) ) );
        }
        return rest_ensure_response( $result );
    }

    /**
     * POST /hclr/v1/quote
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_quote( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $property_id = absint( $request->get_param( 'property_id' ) );
        $check_in    = sanitize_text_field( $request->get_param( 'check_in' ) );
        $check_out   = sanitize_text_field( $request->get_param( 'check_out' ) );
        $guests      = absint( $request->get_param( 'guests' ) );

        $validation = Helpers::validate_dates( $check_in, $check_out );
        if ( is_wp_error( $validation ) ) {
            return rest_ensure_response( new \WP_Error( 'invalid_dates', $validation->get_error_message(), array( 'status' => 400 ) ) );
        }

        $result = $this->client->get_quote( $property_id, $check_in, $check_out, $guests );
        if ( is_wp_error( $result ) ) {
            return rest_ensure_response( new \WP_Error( 'api_error', $result->get_error_message(), array( 'status' => 502 ) ) );
        }
        return rest_ensure_response( $result );
    }

    /**
     * POST /hclr/v1/booking
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function create_booking( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $property_id      = absint( $request->get_param( 'property_id' ) );
        $check_in         = sanitize_text_field( $request->get_param( 'check_in' ) );
        $check_out        = sanitize_text_field( $request->get_param( 'check_out' ) );
        $guests           = absint( $request->get_param( 'guests' ) );
        $first_name       = sanitize_text_field( $request->get_param( 'first_name' ) );
        $last_name        = sanitize_text_field( $request->get_param( 'last_name' ) );
        $email            = sanitize_email( $request->get_param( 'email' ) );
        $phone            = sanitize_text_field( $request->get_param( 'phone' ) ?? '' );
        $special_requests = sanitize_textarea_field( $request->get_param( 'special_requests' ) ?? '' );

        // Validate dates.
        $validation = Helpers::validate_dates( $check_in, $check_out );
        if ( is_wp_error( $validation ) ) {
            return rest_ensure_response( new \WP_Error( 'invalid_dates', $validation->get_error_message(), array( 'status' => 400 ) ) );
        }

        // Validate email.
        if ( ! is_email( $email ) ) {
            return rest_ensure_response( new \WP_Error( 'invalid_email', __( 'Invalid email address.', 'hclr-direct-booking' ), array( 'status' => 400 ) ) );
        }

        // Get price quote first.
        $quote = $this->client->get_quote( $property_id, $check_in, $check_out, $guests );
        if ( is_wp_error( $quote ) ) {
            return rest_ensure_response( new \WP_Error( 'quote_failed', $quote->get_error_message(), array( 'status' => 502 ) ) );
        }

        // Build OwnerRez booking payload.
        $booking_data = array(
            'propertyId'      => $property_id,
            'checkIn'         => $check_in,
            'checkOut'        => $check_out,
            'adults'          => $guests,
            'guestFirstName'  => $first_name,
            'guestLastName'   => $last_name,
            'guestEmail'      => $email,
            'guestPhone'      => $phone,
            'specialRequests' => $special_requests,
            'source'          => 'direct',
        );

        $or_booking = $this->client->create_booking( $booking_data );
        if ( is_wp_error( $or_booking ) ) {
            return rest_ensure_response( new \WP_Error( 'booking_failed', $or_booking->get_error_message(), array( 'status' => 502 ) ) );
        }

        // Save locally.
        $db      = new DB_Manager();
        $nights  = Helpers::get_nights_count( $check_in, $check_out );
        $total   = floatval( $quote['total'] ?? 0 );
        $local_id = $db->save_booking( array(
            'property_id'    => $property_id,
            'or_booking_id'  => $or_booking['id'] ?? 0,
            'check_in'       => $check_in,
            'check_out'      => $check_out,
            'guests'         => $guests,
            'guest_name'     => $first_name . ' ' . $last_name,
            'guest_email'    => $email,
            'guest_phone'    => $phone,
            'total_amount'   => $total,
            'status'         => 'pending',
            'special_requests' => $special_requests,
        ) );

        return rest_ensure_response( array(
            'booking_id'    => $local_id,
            'or_booking_id' => $or_booking['id'] ?? null,
            'status'        => 'pending',
            'total'         => $total,
            'check_in'      => $check_in,
            'check_out'     => $check_out,
            'nights'        => $nights,
            'guest_name'    => $first_name . ' ' . $last_name,
        ) );
    }

    /**
     * GET /hclr/v1/booking/{id}
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_booking( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $id = absint( $request->get_param( 'id' ) );
        $db = new DB_Manager();
        $booking = $db->get_booking( $id );

        if ( ! $booking ) {
            return rest_ensure_response( new \WP_Error( 'not_found', __( 'Booking not found.', 'hclr-direct-booking' ), array( 'status' => 404 ) ) );
        }

        // Only expose safe fields.
        return rest_ensure_response( array(
            'id'          => $booking->id,
            'property_id' => $booking->property_id,
            'check_in'    => $booking->check_in,
            'check_out'   => $booking->check_out,
            'guests'      => $booking->guests,
            'total'       => $booking->total_amount,
            'status'      => $booking->status,
        ) );
    }

    /**
     * Rate limit check for POST endpoints (max 10 per IP per hour).
     *
     * @return bool|\WP_Error
     */
    public function check_rate_limit(): bool|\WP_Error {
        $ip_key  = 'hclr_rate_' . md5( $_SERVER['REMOTE_ADDR'] ?? '' );
        $current = (int) get_transient( $ip_key );

        if ( $current >= 10 ) {
            return new \WP_Error( 'rate_limited', __( 'Too many requests. Please try again later.', 'hclr-direct-booking' ), array( 'status' => 429 ) );
        }

        set_transient( $ip_key, $current + 1, HOUR_IN_SECONDS );
        return true;
    }

    /**
     * Verify booking nonce for POST /booking.
     *
     * @param \WP_REST_Request $request Request object.
     * @return bool|\WP_Error
     */
    public function verify_booking_nonce( \WP_REST_Request $request ): bool|\WP_Error {
        $nonce = sanitize_text_field( $request->get_param( 'nonce' ) ?? '' );

        if ( ! wp_verify_nonce( $nonce, 'hclr_booking_nonce' ) ) {
            return new \WP_Error( 'invalid_nonce', __( 'Security check failed.', 'hclr-direct-booking' ), array( 'status' => 403 ) );
        }

        return $this->check_rate_limit();
    }
}
