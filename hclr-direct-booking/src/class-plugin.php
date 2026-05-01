<?php
/**
 * Main Plugin Bootstrap Class
 *
 * @package HCLR\DirectBooking
 */

namespace HCLR\DirectBooking;

use HCLR\DirectBooking\Admin\Admin_Menu;
use HCLR\DirectBooking\Admin\Settings;
use HCLR\DirectBooking\API\REST_Controller;
use HCLR\DirectBooking\Frontend\Frontend;
use HCLR\DirectBooking\Frontend\Shortcodes;
use HCLR\DirectBooking\Async\Sync_Jobs;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Plugin
 *
 * Singleton bootstrap class. Instantiates and wires up all sub-components.
 */
class Plugin {

    /**
     * Singleton instance.
     *
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;

    /**
     * Settings instance.
     *
     * @var Settings
     */
    public Settings $settings;

    /**
     * Get singleton instance.
     *
     * @return Plugin
     */
    public static function get_instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Plugin constructor. Private to enforce singleton.
     */
    private function __construct() {
        $this->settings = new Settings();
        $this->init();
    }

    /**
     * Initialize all plugin components.
     *
     * @return void
     */
    private function init(): void {
        // Admin components.
        if ( is_admin() ) {
            $admin_menu = new Admin_Menu( $this->settings );
            $admin_menu->init();
        }

        // REST API.
        $rest = new REST_Controller( $this->settings );
        add_action( 'rest_api_init', array( $rest, 'register_routes' ) );

        // Frontend / Shortcodes.
        $frontend = new Frontend( $this->settings );
        $frontend->init();

        $shortcodes = new Shortcodes( $this->settings );
        $shortcodes->init();

        // Background sync.
        $sync = new Sync_Jobs( $this->settings );
        $sync->init();
    }
}
