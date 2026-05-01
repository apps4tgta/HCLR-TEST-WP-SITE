<?php
/**
 * Utility Helpers
 *
 * @package HCLR\DirectBooking\Helpers
 */

namespace HCLR\DirectBooking\Helpers;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Helpers
 *
 * Static utility methods used throughout the plugin.
 */
class Helpers {

    /**
     * Format a price amount as USD currency string.
     *
     * @param float  $amount   Amount to format.
     * @param string $currency Currency code (default USD).
     * @return string
     */
    public static function format_price( float $amount, string $currency = 'USD' ): string {
        return '$' . number_format( $amount, 2 );
    }

    /**
     * Count nights between two dates.
     *
     * @param string $check_in  Check-in date (Y-m-d).
     * @param string $check_out Check-out date (Y-m-d).
     * @return int
     */
    public static function get_nights_count( string $check_in, string $check_out ): int {
        try {
            $in  = new \DateTime( $check_in );
            $out = new \DateTime( $check_out );
            return max( 0, (int) $in->diff( $out )->days );
        } catch ( \Exception $e ) {
            return 0;
        }
    }

    /**
     * Validate check-in and check-out dates.
     *
     * @param string $check_in  Check-in date (Y-m-d).
     * @param string $check_out Check-out date (Y-m-d).
     * @return true|\WP_Error
     */
    public static function validate_dates( string $check_in, string $check_out ): bool|\WP_Error {
        if ( empty( $check_in ) || empty( $check_out ) ) {
            return new \WP_Error( 'missing_dates', __( 'Check-in and check-out dates are required.', 'hclr-direct-booking' ) );
        }

        $pattern = '/^\d{4}-\d{2}-\d{2}$/';
        if ( ! preg_match( $pattern, $check_in ) || ! preg_match( $pattern, $check_out ) ) {
            return new \WP_Error( 'invalid_format', __( 'Dates must be in YYYY-MM-DD format.', 'hclr-direct-booking' ) );
        }

        try {
            $in    = new \DateTime( $check_in );
            $out   = new \DateTime( $check_out );
            $today = new \DateTime( 'today' );

            if ( $in < $today ) {
                return new \WP_Error( 'past_date', __( 'Check-in date cannot be in the past.', 'hclr-direct-booking' ) );
            }

            if ( $out <= $in ) {
                return new \WP_Error( 'invalid_range', __( 'Check-out must be after check-in.', 'hclr-direct-booking' ) );
            }
        } catch ( \Exception $e ) {
            return new \WP_Error( 'invalid_dates', __( 'Invalid date values.', 'hclr-direct-booking' ) );
        }

        return true;
    }

    /**
     * Sanitize and validate a property ID.
     *
     * @param mixed $id Raw value.
     * @return int
     */
    public static function sanitize_property_id( mixed $id ): int {
        return max( 0, absint( $id ) );
    }

    /**
     * Get OwnerRez property ID for the current page/post.
     * Checks post meta _hclr_property_id, then falls back to URL param.
     *
     * @return int
     */
    public static function get_property_id_for_page(): int {
        $post_id = get_queried_object_id();
        if ( $post_id ) {
            $meta_id = absint( get_post_meta( $post_id, '_hclr_property_id', true ) );
            if ( $meta_id ) {
                return $meta_id;
            }
        }

        // Fall back to URL query param.
        return absint( $_GET['property_id'] ?? 0 );
    }

    /**
     * Build a booking URL with date params.
     *
     * @param int    $property_id OwnerRez property ID.
     * @param string $check_in    Check-in date (Y-m-d).
     * @param string $check_out   Check-out date (Y-m-d).
     * @return string
     */
    public static function build_booking_url( int $property_id, string $check_in, string $check_out ): string {
        $base = get_option( 'hclr_booking_redirect_url', '' );
        if ( ! $base ) {
            $booking_page = get_page_by_path( 'booking' );
            $base = $booking_page ? get_permalink( $booking_page ) : home_url( '/booking/' );
        }

        return add_query_arg( array(
            'property_id' => $property_id,
            'check_in'    => rawurlencode( $check_in ),
            'check_out'   => rawurlencode( $check_out ),
        ), esc_url_raw( $base ) );
    }

    /**
     * Detect season for a given date.
     *
     * @param string $date Date string (Y-m-d).
     * @return string 'peak'|'shoulder'|'off'
     */
    public static function detect_season( string $date ): string {
        $month = (int) date( 'm', strtotime( $date ) );

        // Peak: summer (June, July, August) and holidays (Nov, Dec, Jan)
        if ( in_array( $month, array( 6, 7, 8, 11, 12, 1 ), true ) ) {
            return 'peak';
        }

        // Shoulder: spring and fall
        if ( in_array( $month, array( 3, 4, 5, 9, 10 ), true ) ) {
            return 'shoulder';
        }

        return 'off';
    }
}
