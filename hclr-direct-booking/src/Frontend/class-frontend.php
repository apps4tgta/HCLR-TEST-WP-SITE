<?php
/**
 * Frontend Class - Asset Enqueue & CPT Registration
 *
 * @package HCLR\DirectBooking\Frontend
 */

namespace HCLR\DirectBooking\Frontend;

use HCLR\DirectBooking\Admin\Settings;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Frontend
 *
 * Handles frontend asset enqueueing and WordPress registrations.
 */
class Frontend {

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
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_property_id_meta_box' ) );
        add_action( 'save_post_page', array( $this, 'save_property_id_meta' ) );
    }

    /**
     * Register hclr_property custom post type.
     *
     * @return void
     */
    public function register_post_type(): void {
        register_post_type( 'hclr_property', array(
            'labels'      => array(
                'name'               => __( 'Properties', 'hclr-direct-booking' ),
                'singular_name'      => __( 'Property', 'hclr-direct-booking' ),
                'add_new_item'       => __( 'Add New Property', 'hclr-direct-booking' ),
                'edit_item'          => __( 'Edit Property', 'hclr-direct-booking' ),
                'view_item'          => __( 'View Property', 'hclr-direct-booking' ),
            ),
            'public'       => true,
            'show_in_rest' => true,
            'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'excerpt' ),
            'menu_icon'    => 'dashicons-admin-home',
            'has_archive'  => true,
            'rewrite'      => array( 'slug' => 'properties' ),
            'menu_position' => 20,
        ) );
    }

    /**
     * Enqueue frontend CSS and JS.
     * Only loads on pages/posts that use plugin shortcodes or the property CPT.
     *
     * @return void
     */
    public function enqueue_assets(): void {
        global $post;

        $should_load = is_singular( 'hclr_property' )
            || ( $post instanceof \WP_Post && (
                has_shortcode( $post->post_content, 'hclr_availability_calendar' )
                || has_shortcode( $post->post_content, 'hclr_booking_form' )
                || has_shortcode( $post->post_content, 'hclr_property_list' )
                || has_shortcode( $post->post_content, 'hclr_booking_confirmation' )
            ) )
            || is_page_template( 'template-property.php' )
            || is_page_template( 'template-booking.php' );

        if ( ! $should_load ) {
            return;
        }

        // Swiper.js (for hero carousels).
        wp_enqueue_style(
            'swiper',
            'https://cdnjs.cloudflare.com/ajax/libs/Swiper/11.0.5/swiper-bundle.min.css',
            array(),
            '11.0.5'
        );
        wp_enqueue_script(
            'swiper',
            'https://cdnjs.cloudflare.com/ajax/libs/Swiper/11.0.5/swiper-bundle.min.js',
            array(),
            '11.0.5',
            true
        );

        // Plugin booking CSS.
        wp_enqueue_style(
            'hclr-booking',
            HCLR_PLUGIN_URL . 'assets/css/booking.css',
            array(),
            HCLR_PLUGIN_VERSION
        );

        // Calendar JS.
        wp_enqueue_script(
            'hclr-calendar',
            HCLR_PLUGIN_URL . 'assets/js/calendar.js',
            array(),
            HCLR_PLUGIN_VERSION,
            true
        );

        // Booking form JS.
        wp_enqueue_script(
            'hclr-booking-form',
            HCLR_PLUGIN_URL . 'assets/js/booking-form.js',
            array( 'hclr-calendar' ),
            HCLR_PLUGIN_VERSION,
            true
        );

        // Localize data.
        wp_localize_script( 'hclr-calendar', 'hclr_data', array(
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'rest_url'    => rest_url( 'hclr/v1' ),
            'nonce'       => wp_create_nonce( 'wp_rest' ),
            'booking_nonce' => wp_create_nonce( 'hclr_booking_nonce' ),
            'plugin_url'  => HCLR_PLUGIN_URL,
            'site_url'    => site_url(),
        ) );
    }

    /**
     * Add property ID meta box to pages.
     *
     * @return void
     */
    public function add_property_id_meta_box(): void {
        add_meta_box(
            'hclr_property_id',
            __( 'OwnerRez Property ID', 'hclr-direct-booking' ),
            array( $this, 'render_property_id_meta_box' ),
            array( 'page', 'hclr_property' ),
            'side',
            'high'
        );
    }

    /**
     * Render the property ID meta box.
     *
     * @param \WP_Post $post Current post.
     * @return void
     */
    public function render_property_id_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'hclr_property_id_nonce', 'hclr_property_id_nonce_field' );
        $value = get_post_meta( $post->ID, '_hclr_property_id', true );
        ?>
        <label for="hclr_property_id_input"><?php esc_html_e( 'OwnerRez Property ID:', 'hclr-direct-booking' ); ?></label>
        <input type="number" id="hclr_property_id_input" name="hclr_property_id"
            value="<?php echo esc_attr( $value ); ?>"
            style="width:100%;margin-top:6px;" min="1" />
        <p style="color:#666;font-size:12px;margin-top:6px;">
            <?php esc_html_e( 'The numeric ID from your OwnerRez account.', 'hclr-direct-booking' ); ?>
        </p>
        <?php
    }

    /**
     * Save property ID meta box value.
     *
     * @param int $post_id Post ID.
     * @return void
     */
    public function save_property_id_meta( int $post_id ): void {
        if ( ! isset( $_POST['hclr_property_id_nonce_field'] )
            || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hclr_property_id_nonce_field'] ) ), 'hclr_property_id_nonce' )
        ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['hclr_property_id'] ) ) {
            update_post_meta( $post_id, '_hclr_property_id', absint( $_POST['hclr_property_id'] ) );
        }
    }
}
