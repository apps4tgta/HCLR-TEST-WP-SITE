<?php
/**
 * Shortcodes
 *
 * @package HCLR\DirectBooking\Frontend
 */

namespace HCLR\DirectBooking\Frontend;

use HCLR\DirectBooking\Admin\Settings;
use HCLR\DirectBooking\Helpers\Helpers;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Shortcodes
 *
 * Registers and handles all plugin shortcodes.
 *
 * Available shortcodes:
 *  [hclr_availability_calendar property_id="123"]
 *  [hclr_booking_form property_id="123" check_in="" check_out=""]
 *  [hclr_property_info property_id="123" field="name"]
 *  [hclr_property_list]
 *  [hclr_booking_confirmation]
 */
class Shortcodes {

    /**
     * Settings instance.
     *
     * @var Settings
     */
    private Settings $settings;

    /**
     * Constructor.
     *
     * @param Settings $settings Plugin settings.
     */
    public function __construct( Settings $settings ) {
        $this->settings = $settings;
    }

    /**
     * Register all shortcodes.
     *
     * @return void
     */
    public function init(): void {
        add_shortcode( 'hclr_availability_calendar', array( $this, 'availability_calendar' ) );
        add_shortcode( 'hclr_booking_form', array( $this, 'booking_form' ) );
        add_shortcode( 'hclr_property_info', array( $this, 'property_info' ) );
        add_shortcode( 'hclr_property_list', array( $this, 'property_list' ) );
        add_shortcode( 'hclr_booking_confirmation', array( $this, 'booking_confirmation' ) );
    }

    /**
     * [hclr_availability_calendar] shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function availability_calendar( array $atts ): string {
        $atts = shortcode_atts( array(
            'property_id' => Helpers::get_property_id_for_page(),
        ), $atts, 'hclr_availability_calendar' );

        $property_id = absint( $atts['property_id'] );
        if ( ! $property_id ) {
            return '<p class="hclr-error">' . esc_html__( 'No property ID specified.', 'hclr-direct-booking' ) . '</p>';
        }

        $nonce = wp_create_nonce( 'wp_rest' );

        ob_start();
        include HCLR_PLUGIN_DIR . 'templates/calendar-widget.php';
        return ob_get_clean();
    }

    /**
     * [hclr_booking_form] shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function booking_form( array $atts ): string {
        $atts = shortcode_atts( array(
            'property_id' => Helpers::get_property_id_for_page(),
            'check_in'    => sanitize_text_field( $_GET['check_in'] ?? '' ),
            'check_out'   => sanitize_text_field( $_GET['check_out'] ?? '' ),
        ), $atts, 'hclr_booking_form' );

        $property_id = absint( $atts['property_id'] );
        if ( ! $property_id ) {
            return '<p class="hclr-error">' . esc_html__( 'No property ID specified.', 'hclr-direct-booking' ) . '</p>';
        }

        $check_in  = sanitize_text_field( $atts['check_in'] );
        $check_out = sanitize_text_field( $atts['check_out'] );
        $nonce     = wp_create_nonce( 'hclr_booking_nonce' );
        $rest_url  = rest_url( 'hclr/v1' );

        ob_start();
        include HCLR_PLUGIN_DIR . 'templates/booking-form.php';
        return ob_get_clean();
    }

    /**
     * [hclr_property_info] shortcode.
     * Returns a single piece of property metadata.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function property_info( array $atts ): string {
        $atts = shortcode_atts( array(
            'property_id' => Helpers::get_property_id_for_page(),
            'field'       => 'name',
        ), $atts, 'hclr_property_info' );

        $property_id = absint( $atts['property_id'] );
        $field       = sanitize_key( $atts['field'] );

        if ( ! $property_id ) {
            return '';
        }

        // Map field name to post meta key.
        $meta_map = array(
            'name'       => 'post_title',
            'rate'       => '_hclr_nightly_rate',
            'bedrooms'   => '_hclr_bedrooms',
            'bathrooms'  => '_hclr_bathrooms',
            'sleeps'     => '_hclr_sleeps',
            'tagline'    => '_hclr_tagline',
            'address'    => '_hclr_address',
        );

        // Find post by property ID.
        $posts = get_posts( array(
            'post_type'   => array( 'page', 'hclr_property' ),
            'meta_key'    => '_hclr_property_id',
            'meta_value'  => $property_id,
            'numberposts' => 1,
            'fields'      => 'ids',
        ) );

        if ( empty( $posts ) ) {
            return '';
        }

        $post_id = $posts[0];

        if ( 'name' === $field ) {
            return esc_html( get_the_title( $post_id ) );
        }

        if ( 'rate' === $field ) {
            $rate = get_post_meta( $post_id, '_hclr_nightly_rate', true );
            return $rate ? esc_html( Helpers::format_price( (float) $rate ) ) : '';
        }

        $meta_key = $meta_map[ $field ] ?? '';
        if ( ! $meta_key ) {
            return '';
        }

        return esc_html( (string) get_post_meta( $post_id, $meta_key, true ) );
    }

    /**
     * [hclr_property_list] shortcode.
     * Outputs a grid of all properties.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function property_list( array $atts ): string {
        $atts = shortcode_atts( array(
            'columns'  => '3',
            'per_page' => '12',
        ), $atts, 'hclr_property_list' );

        $columns  = absint( $atts['columns'] );
        $per_page = absint( $atts['per_page'] );

        // Query pages that have a property ID set.
        $query = new \WP_Query( array(
            'post_type'      => array( 'page', 'hclr_property' ),
            'posts_per_page' => $per_page,
            'meta_query'     => array(
                array(
                    'key'     => '_hclr_property_id',
                    'compare' => 'EXISTS',
                ),
            ),
            'no_found_rows'  => true,
        ) );

        if ( ! $query->have_posts() ) {
            return '<p class="hclr-notice">' . esc_html__( 'No properties found.', 'hclr-direct-booking' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="hclr-property-list hclr-columns-<?php echo esc_attr( $columns ); ?>">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <?php
                $pid         = get_the_ID();
                $property_id = absint( get_post_meta( $pid, '_hclr_property_id', true ) );
                $rate        = floatval( get_post_meta( $pid, '_hclr_nightly_rate', true ) );
                $bedrooms    = absint( get_post_meta( $pid, '_hclr_bedrooms', true ) );
                $sleeps      = absint( get_post_meta( $pid, '_hclr_sleeps', true ) );
                ?>
                <article class="hclr-property-card">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="hclr-property-card__image">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail( 'hclr-property-card', array( 'alt' => esc_attr( get_the_title() ) ) ); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <div class="hclr-property-card__body">
                        <h3 class="hclr-property-card__title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        <div class="hclr-property-card__meta">
                            <?php if ( $bedrooms ) : ?>
                                <span><?php echo esc_html( $bedrooms ); ?> <?php esc_html_e( 'BR', 'hclr-direct-booking' ); ?></span>
                            <?php endif; ?>
                            <?php if ( $sleeps ) : ?>
                                <span><?php esc_html_e( 'Sleeps', 'hclr-direct-booking' ); ?> <?php echo esc_html( $sleeps ); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ( $rate ) : ?>
                            <div class="hclr-property-card__rate">
                                <?php echo esc_html( Helpers::format_price( $rate ) ); ?>
                                <span><?php esc_html_e( '/night', 'hclr-direct-booking' ); ?></span>
                            </div>
                        <?php endif; ?>
                        <a href="<?php the_permalink(); ?>" class="hclr-btn hclr-btn--primary">
                            <?php esc_html_e( 'View Property', 'hclr-direct-booking' ); ?>
                        </a>
                    </div>
                </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * [hclr_booking_confirmation] shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function booking_confirmation( array $atts ): string {
        $booking_id = absint( $_GET['booking_id'] ?? 0 );

        if ( ! $booking_id ) {
            return '<p class="hclr-notice">' . esc_html__( 'No booking reference found.', 'hclr-direct-booking' ) . '</p>';
        }

        ob_start();
        include HCLR_PLUGIN_DIR . 'templates/booking-confirmation.php';
        return ob_get_clean();
    }
}
