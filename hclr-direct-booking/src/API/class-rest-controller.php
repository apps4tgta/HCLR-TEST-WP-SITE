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
     *
     * @param Settings $settings Settings instance.
     */
    public function __construct( Settings $settings ) {
        $this->settings = $settings;
        $this->client   = new OwnerRez_Client( $settings );
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
            // Return 200 with error flag so JS can degrade gracefully (not throw on !resp.ok).
            return new \WP_REST_Response( array(
                'success'     => false,
                'error'       => $result->get_error_message(),
                'property_id' => $property_id,
                'year'        => $year,
                'month'       => $month,
                'days'        => array(),
            ), 200 );
        }

        return rest_ensure_response( array(
            'success'     => true,
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
            // Return 200 with error flag so JS can degrade gracefully.
            return new \WP_REST_Response( array(
                'success' => false,
                'error'   => $result->get_error_message(),
                'days'    => array(),
            ), 200 );
        }
        return rest_ensure_response( array(
            'success' => true,
            'days'    => $result,
        ) );
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
        return rest_ensure_response( $this->normalize_quote( $result, $check_in, $check_out ) );
    }

    /**
     * Normalize OwnerRez quote response to frontend-friendly flat format.
     *
     * OwnerRez returns a `charges` array; JS expects flat fields.
     *
     * @param array  $raw      Raw OwnerRez quote response.
     * @param string $check_in  Check-in date (Y-m-d).
     * @param string $check_out Check-out date (Y-m-d).
     * @return array
     */
    private function normalize_quote( array $raw, string $check_in, string $check_out ): array {
        $charges      = $raw['charges'] ?? array();
        $nights       = (int) round( ( strtotime( $check_out ) - strtotime( $check_in ) ) / DAY_IN_SECONDS );
        $subtotal     = 0.0;
        $cleaning_fee = 0.0;
        $service_fee  = 0.0;
        $taxes        = 0.0;
        $total        = 0.0;

        foreach ( $charges as $charge ) {
            $amount = floatval( $charge['amount'] ?? 0 );
            $type   = $charge['type'] ?? '';
            $desc   = strtolower( $charge['description'] ?? '' );

            $total += $amount;

            if ( 'rent' === $type ) {
                $subtotal += $amount;
            } elseif ( 'tax' === $type ) {
                $taxes += $amount;
            } elseif ( 'surcharge' === $type ) {
                if ( str_contains( $desc, 'cleaning' ) ) {
                    $cleaning_fee += $amount;
                } else {
                    $service_fee += $amount;
                }
            }
        }

        return array(
            'nights'       => $nights,
            'subtotal'     => $subtotal,
            'cleaning_fee' => $cleaning_fee,
            'service_fee'  => $service_fee,
            'taxes'        => $taxes,
            'total'        => $total,
            'quote_id'     => $raw['id']  ?? null,
            'quote_key'    => $raw['key'] ?? null,
            'arrival'      => $raw['arrival']   ?? $check_in,
            'departure'    => $raw['departure']  ?? $check_out,
        );
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

        // Step 1: Create guest record in OwnerRez.
        $guest = $this->client->create_guest( $first_name, $last_name, $email, $phone );
        if ( is_wp_error( $guest ) ) {
            return rest_ensure_response( new \WP_Error( 'guest_failed', $guest->get_error_message(), array( 'status' => 502 ) ) );
        }
        $guest_id = absint( $guest['id'] ?? 0 );
        if ( ! $guest_id ) {
            return rest_ensure_response( new \WP_Error( 'guest_failed', __( 'Could not create guest record.', 'hclr-direct-booking' ), array( 'status' => 502 ) ) );
        }

        // Step 2: Create v1 quote — returns hosted paymentForm URL for checkout.
        $quote = $this->client->create_v1_quote( $guest_id, $property_id, $check_in, $check_out, $guests ?: 1 );
        if ( is_wp_error( $quote ) ) {
            return rest_ensure_response( new \WP_Error( 'quote_failed', $quote->get_error_message(), array( 'status' => 502 ) ) );
        }

        $payment_form = $quote['paymentForm'] ?? '';
        $quote_id     = absint( $quote['id'] ?? 0 );

        // Derive total from charges array.
        $total = 0.0;
        foreach ( $quote['charges'] ?? array() as $charge ) {
            $total += floatval( $charge['amount'] ?? 0 );
        }

        // Save locally as pending until OwnerRez confirms payment.
        $db       = new DB_Manager();
        $nights   = Helpers::get_nights_count( $check_in, $check_out );
        $local_id = $db->save_booking( array(
            'property_id'      => $property_id,
            'or_booking_id'    => $quote_id,
            'check_in'         => $check_in,
            'check_out'        => $check_out,
            'guests'           => $guests,
            'guest_name'       => $first_name . ' ' . $last_name,
            'guest_email'      => $email,
            'guest_phone'      => $phone,
            'total_amount'     => $total,
            'status'           => 'pending',
            'special_requests' => $special_requests,
        ) );

        return rest_ensure_response( array(
            'booking_id'   => $local_id,
            'or_quote_id'  => $quote_id,
            'payment_form' => $payment_form,
            'status'       => 'pending',
            'total'        => $total,
            'check_in'     => $check_in,
            'check_out'    => $check_out,
            'nights'       => $nights,
            'guest_name'   => $first_name . ' ' . $last_name,
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

        if ( $current >= 60 ) {
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
