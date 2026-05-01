<?php
/**
 * Plugin Settings Registration
 *
 * @package HCLR\DirectBooking\Admin
 */

namespace HCLR\DirectBooking\Admin;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Settings
 *
 * Registers WordPress settings and provides accessor methods.
 */
class Settings {

    /**
     * Option group name.
     *
     * @var string
     */
    const OPTION_GROUP = 'hclr_direct_booking_settings';

    /**
     * Register all settings.
     *
     * @return void
     */
    public function register(): void {
        register_setting(
            self::OPTION_GROUP,
            'hclr_or_email',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_email',
                'default'           => '',
            )
        );

        register_setting(
            self::OPTION_GROUP,
            'hclr_or_token',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );

        register_setting(
            self::OPTION_GROUP,
            'hclr_cache_duration',
            array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 3600,
            )
        );

        register_setting(
            self::OPTION_GROUP,
            'hclr_booking_redirect_url',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'default'           => '',
            )
        );

        register_setting(
            self::OPTION_GROUP,
            'hclr_min_stay_override',
            array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 0,
            )
        );

        register_setting(
            self::OPTION_GROUP,
            'hclr_max_stay_override',
            array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 0,
            )
        );
    }

    /**
     * Get OwnerRez email.
     *
     * @return string
     */
    public function get_or_email(): string {
        return (string) get_option( 'hclr_or_email', '' );
    }

    /**
     * Get OwnerRez API token.
     *
     * @return string
     */
    public function get_or_token(): string {
        return (string) get_option( 'hclr_or_token', '' );
    }

    /**
     * Get cache duration in seconds.
     *
     * @return int
     */
    public function get_cache_duration(): int {
        return absint( get_option( 'hclr_cache_duration', 3600 ) );
    }

    /**
     * Get booking redirect URL.
     *
     * @return string
     */
    public function get_booking_redirect_url(): string {
        return esc_url_raw( (string) get_option( 'hclr_booking_redirect_url', '' ) );
    }

    /**
     * Get minimum stay override (0 = use OwnerRez value).
     *
     * @return int
     */
    public function get_min_stay_override(): int {
        return absint( get_option( 'hclr_min_stay_override', 0 ) );
    }

    /**
     * Get maximum stay override (0 = use OwnerRez value).
     *
     * @return int
     */
    public function get_max_stay_override(): int {
        return absint( get_option( 'hclr_max_stay_override', 0 ) );
    }

    /**
     * Check whether API credentials are configured.
     *
     * @return bool
     */
    public function has_credentials(): bool {
        return ! empty( $this->get_or_email() ) && ! empty( $this->get_or_token() );
    }
}
