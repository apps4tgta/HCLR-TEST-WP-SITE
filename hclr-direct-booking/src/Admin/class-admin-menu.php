<?php
/**
 * Admin Menu & Settings Pages
 *
 * @package HCLR\DirectBooking\Admin
 */

namespace HCLR\DirectBooking\Admin;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Admin_Menu
 *
 * Registers the top-level admin menu and all submenus for HCLR Direct Booking.
 */
class Admin_Menu {

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
     * Register hooks.
     *
     * @return void
     */
    public function init(): void {
        add_action( 'admin_menu', array( $this, 'register_menus' ) );
        add_action( 'admin_init', array( $this->settings, 'register' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_ajax_hclr_test_connection', array( $this, 'ajax_test_connection' ) );
        add_action( 'wp_ajax_hclr_manual_sync', array( $this, 'ajax_manual_sync' ) );
    }

    /**
     * Register all admin menus.
     *
     * @return void
     */
    public function register_menus(): void {
        add_menu_page(
            __( 'HCLR Booking', 'hclr-direct-booking' ),
            __( 'HCLR Booking', 'hclr-direct-booking' ),
            'manage_options',
            'hclr-direct-booking',
            array( $this, 'render_settings_page' ),
            'dashicons-calendar-alt',
            58
        );

        add_submenu_page(
            'hclr-direct-booking',
            __( 'Settings', 'hclr-direct-booking' ),
            __( 'Settings', 'hclr-direct-booking' ),
            'manage_options',
            'hclr-direct-booking',
            array( $this, 'render_settings_page' )
        );

        add_submenu_page(
            'hclr-direct-booking',
            __( 'Properties', 'hclr-direct-booking' ),
            __( 'Properties', 'hclr-direct-booking' ),
            'manage_options',
            'hclr-properties',
            array( $this, 'render_properties_page' )
        );

        add_submenu_page(
            'hclr-direct-booking',
            __( 'Bookings', 'hclr-direct-booking' ),
            __( 'Bookings', 'hclr-direct-booking' ),
            'manage_options',
            'hclr-bookings',
            array( $this, 'render_bookings_page' )
        );

        add_submenu_page(
            'hclr-direct-booking',
            __( 'Sync Status', 'hclr-direct-booking' ),
            __( 'Sync Status', 'hclr-direct-booking' ),
            'manage_options',
            'hclr-sync-status',
            array( $this, 'render_sync_page' )
        );
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_admin_assets( string $hook ): void {
        $hclr_pages = array(
            'toplevel_page_hclr-direct-booking',
            'hclr-booking_page_hclr-properties',
            'hclr-booking_page_hclr-bookings',
            'hclr-booking_page_hclr-sync-status',
        );

        if ( ! in_array( $hook, $hclr_pages, true ) ) {
            return;
        }

        wp_enqueue_style(
            'hclr-admin',
            HCLR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            HCLR_PLUGIN_VERSION
        );
    }

    /**
     * Render the main settings page.
     *
     * @return void
     */
    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'hclr-direct-booking' ) );
        }

        $email          = $this->settings->get_or_email();
        $token          = $this->settings->get_or_token();
        $cache_duration = $this->settings->get_cache_duration();
        $redirect_url   = $this->settings->get_booking_redirect_url();
        $min_stay       = $this->settings->get_min_stay_override();
        $max_stay       = $this->settings->get_max_stay_override();
        $last_sync      = get_option( 'hclr_last_sync', '' );
        ?>
        <div class="wrap hclr-settings-wrap">
            <h1><?php esc_html_e( 'HCLR Direct Booking — Settings', 'hclr-direct-booking' ); ?></h1>

            <?php settings_errors( Settings::OPTION_GROUP ); ?>

            <form method="post" action="options.php">
                <?php settings_fields( Settings::OPTION_GROUP ); ?>

                <!-- API Credentials -->
                <div class="hclr-settings-section">
                    <h2><?php esc_html_e( 'OwnerRez API Credentials', 'hclr-direct-booking' ); ?></h2>
                    <p><?php esc_html_e( 'Obtain your API token from OwnerRez: Settings → Advanced Tools → Developer/API Settings.', 'hclr-direct-booking' ); ?></p>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="hclr_or_email"><?php esc_html_e( 'OwnerRez Account Email', 'hclr-direct-booking' ); ?></label>
                            </th>
                            <td>
                                <input type="email" id="hclr_or_email" name="hclr_or_email"
                                    value="<?php echo esc_attr( $email ); ?>"
                                    class="regular-text" autocomplete="off" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="hclr_or_token"><?php esc_html_e( 'OwnerRez API Token', 'hclr-direct-booking' ); ?></label>
                            </th>
                            <td>
                                <input type="password" id="hclr_or_token" name="hclr_or_token"
                                    value="<?php echo esc_attr( $token ); ?>"
                                    class="regular-text" autocomplete="new-password" />
                                <button type="button" id="hclr-test-connection" class="button button-secondary" style="margin-left:8px;">
                                    <?php esc_html_e( 'Test Connection', 'hclr-direct-booking' ); ?>
                                </button>
                                <span id="hclr-connection-result" style="margin-left:8px;"></span>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Sync Settings -->
                <div class="hclr-settings-section">
                    <h2><?php esc_html_e( 'Sync Settings', 'hclr-direct-booking' ); ?></h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="hclr_cache_duration"><?php esc_html_e( 'Cache Duration', 'hclr-direct-booking' ); ?></label>
                            </th>
                            <td>
                                <select id="hclr_cache_duration" name="hclr_cache_duration">
                                    <option value="900" <?php selected( $cache_duration, 900 ); ?>><?php esc_html_e( '15 minutes', 'hclr-direct-booking' ); ?></option>
                                    <option value="1800" <?php selected( $cache_duration, 1800 ); ?>><?php esc_html_e( '30 minutes', 'hclr-direct-booking' ); ?></option>
                                    <option value="3600" <?php selected( $cache_duration, 3600 ); ?>><?php esc_html_e( '1 hour', 'hclr-direct-booking' ); ?></option>
                                    <option value="7200" <?php selected( $cache_duration, 7200 ); ?>><?php esc_html_e( '2 hours', 'hclr-direct-booking' ); ?></option>
                                    <option value="21600" <?php selected( $cache_duration, 21600 ); ?>><?php esc_html_e( '6 hours', 'hclr-direct-booking' ); ?></option>
                                    <option value="86400" <?php selected( $cache_duration, 86400 ); ?>><?php esc_html_e( '24 hours', 'hclr-direct-booking' ); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e( 'How long to cache OwnerRez API responses.', 'hclr-direct-booking' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Manual Sync', 'hclr-direct-booking' ); ?></th>
                            <td>
                                <button type="button" id="hclr-manual-sync" class="button button-secondary">
                                    <?php esc_html_e( 'Sync Now', 'hclr-direct-booking' ); ?>
                                </button>
                                <span id="hclr-sync-result" style="margin-left:8px;"></span>
                                <?php if ( $last_sync ) : ?>
                                    <p class="description">
                                        <?php
                                        printf(
                                            /* translators: %s: datetime */
                                            esc_html__( 'Last sync: %s', 'hclr-direct-booking' ),
                                            esc_html( $last_sync )
                                        );
                                        ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Booking Settings -->
                <div class="hclr-settings-section">
                    <h2><?php esc_html_e( 'Booking Settings', 'hclr-direct-booking' ); ?></h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="hclr_booking_redirect_url"><?php esc_html_e( 'Confirmation Page URL', 'hclr-direct-booking' ); ?></label>
                            </th>
                            <td>
                                <input type="url" id="hclr_booking_redirect_url" name="hclr_booking_redirect_url"
                                    value="<?php echo esc_attr( $redirect_url ); ?>"
                                    class="regular-text" placeholder="https://..." />
                                <p class="description"><?php esc_html_e( 'Redirect here after successful booking. Leave blank to show inline confirmation.', 'hclr-direct-booking' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="hclr_min_stay_override"><?php esc_html_e( 'Min Stay Override (nights)', 'hclr-direct-booking' ); ?></label>
                            </th>
                            <td>
                                <input type="number" id="hclr_min_stay_override" name="hclr_min_stay_override"
                                    value="<?php echo esc_attr( $min_stay ); ?>"
                                    class="small-text" min="0" />
                                <p class="description"><?php esc_html_e( 'Set to 0 to use OwnerRez property minimum stay.', 'hclr-direct-booking' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="hclr_max_stay_override"><?php esc_html_e( 'Max Stay Override (nights)', 'hclr-direct-booking' ); ?></label>
                            </th>
                            <td>
                                <input type="number" id="hclr_max_stay_override" name="hclr_max_stay_override"
                                    value="<?php echo esc_attr( $max_stay ); ?>"
                                    class="small-text" min="0" />
                                <p class="description"><?php esc_html_e( 'Set to 0 to use OwnerRez property maximum stay.', 'hclr-direct-booking' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button( __( 'Save Settings', 'hclr-direct-booking' ) ); ?>
            </form>
        </div>

        <script>
        document.getElementById('hclr-test-connection').addEventListener('click', function() {
            const result = document.getElementById('hclr-connection-result');
            result.textContent = '<?php echo esc_js( __( 'Testing…', 'hclr-direct-booking' ) ); ?>';
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'hclr_test_connection',
                    nonce: '<?php echo esc_js( wp_create_nonce( 'hclr_admin_nonce' ) ); ?>'
                })
            }).then(r => r.json()).then(data => {
                result.textContent = data.success ? '✓ Connected' : '✗ ' + data.data;
                result.style.color = data.success ? 'green' : 'red';
            }).catch(() => { result.textContent = '✗ Request failed'; result.style.color = 'red'; });
        });

        document.getElementById('hclr-manual-sync').addEventListener('click', function() {
            const result = document.getElementById('hclr-sync-result');
            result.textContent = '<?php echo esc_js( __( 'Syncing…', 'hclr-direct-booking' ) ); ?>';
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'hclr_manual_sync',
                    nonce: '<?php echo esc_js( wp_create_nonce( 'hclr_admin_nonce' ) ); ?>'
                })
            }).then(r => r.json()).then(data => {
                result.textContent = data.success ? '✓ ' + data.data : '✗ ' + data.data;
                result.style.color = data.success ? 'green' : 'red';
            }).catch(() => { result.textContent = '✗ Sync failed'; result.style.color = 'red'; });
        });
        </script>
        <?php
    }

    /**
     * Render properties list page.
     *
     * @return void
     */
    public function render_properties_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'hclr-direct-booking' ) );
        }

        $cached = get_option( 'hclr_properties_cache', array() );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Properties', 'hclr-direct-booking' ); ?></h1>
            <?php if ( empty( $cached ) ) : ?>
                <p><?php esc_html_e( 'No properties synced yet. Go to Sync Status and run a sync.', 'hclr-direct-booking' ); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'ID', 'hclr-direct-booking' ); ?></th>
                            <th><?php esc_html_e( 'Name', 'hclr-direct-booking' ); ?></th>
                            <th><?php esc_html_e( 'Bedrooms', 'hclr-direct-booking' ); ?></th>
                            <th><?php esc_html_e( 'Bathrooms', 'hclr-direct-booking' ); ?></th>
                            <th><?php esc_html_e( 'Sleeps', 'hclr-direct-booking' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $cached as $property ) : ?>
                            <tr>
                                <td><?php echo esc_html( $property['id'] ?? '' ); ?></td>
                                <td><?php echo esc_html( $property['name'] ?? '' ); ?></td>
                                <td><?php echo esc_html( $property['bedrooms'] ?? '' ); ?></td>
                                <td><?php echo esc_html( $property['bathrooms'] ?? '' ); ?></td>
                                <td><?php echo esc_html( $property['sleeps'] ?? '' ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render bookings list page.
     *
     * @return void
     */
    public function render_bookings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'hclr-direct-booking' ) );
        }

        global $wpdb;
        $table    = $wpdb->prefix . 'hclr_bookings';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $bookings = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY created_at DESC LIMIT 100" );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Bookings', 'hclr-direct-booking' ); ?></h1>
            <?php if ( empty( $bookings ) ) : ?>
                <p><?php esc_html_e( 'No bookings recorded yet.', 'hclr-direct-booking' ); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'ID', 'hclr-direct-booking' ); ?></th>
                            <th><?php esc_html_e( 'Property ID', 'hclr-direct-booking' ); ?></th>
                            <th><?php esc_html_e( 'Guest', 'hclr-direct-booking' ); ?></th>
                            <th><?php esc_html_e( 'Check-In', 'hclr-direct-booking' ); ?></th>
                            <th><?php esc_html_e( 'Check-Out', 'hclr-direct-booking' ); ?></th>
                            <th><?php esc_html_e( 'Total', 'hclr-direct-booking' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'hclr-direct-booking' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $bookings as $booking ) : ?>
                            <tr>
                                <td><?php echo esc_html( $booking->id ); ?></td>
                                <td><?php echo esc_html( $booking->property_id ); ?></td>
                                <td><?php echo esc_html( $booking->guest_name ); ?></td>
                                <td><?php echo esc_html( $booking->check_in ); ?></td>
                                <td><?php echo esc_html( $booking->check_out ); ?></td>
                                <td>$<?php echo esc_html( number_format( (float) $booking->total_amount, 2 ) ); ?></td>
                                <td><?php echo esc_html( $booking->status ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render sync status page.
     *
     * @return void
     */
    public function render_sync_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'hclr-direct-booking' ) );
        }

        $last_sync   = get_option( 'hclr_last_sync', __( 'Never', 'hclr-direct-booking' ) );
        $sync_status = get_option( 'hclr_sync_status', array() );
        $next_sync   = wp_next_scheduled( 'hclr_hourly_sync' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Sync Status', 'hclr-direct-booking' ); ?></h1>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Last Sync', 'hclr-direct-booking' ); ?></th>
                    <td><?php echo esc_html( $last_sync ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Next Scheduled Sync', 'hclr-direct-booking' ); ?></th>
                    <td><?php echo $next_sync ? esc_html( wp_date( 'Y-m-d H:i:s', $next_sync ) ) : esc_html__( 'Not scheduled', 'hclr-direct-booking' ); ?></td>
                </tr>
                <?php if ( ! empty( $sync_status ) ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Last Sync Result', 'hclr-direct-booking' ); ?></th>
                        <td><pre><?php echo esc_html( wp_json_encode( $sync_status, JSON_PRETTY_PRINT ) ); ?></pre></td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php
    }

    /**
     * AJAX: Test API connection.
     *
     * @return void
     */
    public function ajax_test_connection(): void {
        check_ajax_referer( 'hclr_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized.', 'hclr-direct-booking' ) );
        }

        if ( ! $this->settings->has_credentials() ) {
            wp_send_json_error( __( 'No credentials saved. Save settings first.', 'hclr-direct-booking' ) );
        }

        $client = new \HCLR\DirectBooking\API\OwnerRez_Client( $this->settings );
        $result = $client->get_properties();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( __( 'Connection successful!', 'hclr-direct-booking' ) );
    }

    /**
     * AJAX: Trigger manual sync.
     *
     * @return void
     */
    public function ajax_manual_sync(): void {
        check_ajax_referer( 'hclr_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized.', 'hclr-direct-booking' ) );
        }

        $sync = new \HCLR\DirectBooking\Async\Sync_Jobs( $this->settings );
        $sync->sync_all_properties();

        wp_send_json_success( __( 'Sync complete.', 'hclr-direct-booking' ) );
    }
}
