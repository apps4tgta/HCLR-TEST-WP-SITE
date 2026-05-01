<?php
/**
 * Booking Confirmation Template
 * Rendered by the [hclr_booking_confirmation] shortcode.
 *
 * Reads params from URL: booking_id, check_in, check_out, total
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$booking_id = absint( $_GET['booking_id'] ?? 0 );
$check_in   = sanitize_text_field( $_GET['check_in']  ?? '' );
$check_out  = sanitize_text_field( $_GET['check_out'] ?? '' );
$total      = floatval( $_GET['total'] ?? 0 );

// Optionally load from DB for richer data.
$booking = null;
if ( $booking_id ) {
    $db      = new \HCLR\DirectBooking\Database\DB_Manager();
    $booking = $db->get_booking( $booking_id );
}

if ( $booking ) {
    $check_in  = $booking->check_in;
    $check_out = $booking->check_out;
    $total     = floatval( $booking->total_amount );
}

$nights     = $check_in && $check_out ? \HCLR\DirectBooking\Helpers\Helpers::get_nights_count( $check_in, $check_out ) : 0;
$prop_name  = '';
if ( $booking ) {
    $pages = get_posts( array(
        'post_type'   => array( 'page', 'hclr_property' ),
        'meta_key'    => '_hclr_property_id',
        'meta_value'  => $booking->property_id,
        'numberposts' => 1,
        'fields'      => 'ids',
    ) );
    $prop_name = ! empty( $pages ) ? get_the_title( $pages[0] ) : '';
}
?>
<div class="hclr-booking-confirmation">

    <div class="confirm-icon" aria-hidden="true">🎉</div>

    <h2><?php esc_html_e( 'Booking Confirmed!', 'hclr-direct-booking' ); ?></h2>

    <p><?php esc_html_e( 'Thank you for booking with Hill Country Lakes Rentals. You will receive a confirmation email shortly with all the details.', 'hclr-direct-booking' ); ?></p>

    <?php if ( $booking_id ) : ?>
        <p><strong><?php esc_html_e( 'Booking Reference:', 'hclr-direct-booking' ); ?></strong>
           #<?php echo esc_html( $booking_id ); ?></p>
    <?php endif; ?>

    <div class="hclr-confirm-details">

        <?php if ( $prop_name ) : ?>
            <div class="detail-row">
                <span><?php esc_html_e( 'Property', 'hclr-direct-booking' ); ?></span>
                <strong><?php echo esc_html( $prop_name ); ?></strong>
            </div>
        <?php endif; ?>

        <?php if ( $check_in ) : ?>
            <div class="detail-row">
                <span><?php esc_html_e( 'Check-In', 'hclr-direct-booking' ); ?></span>
                <strong><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $check_in ) ) ); ?></strong>
            </div>
        <?php endif; ?>

        <?php if ( $check_out ) : ?>
            <div class="detail-row">
                <span><?php esc_html_e( 'Check-Out', 'hclr-direct-booking' ); ?></span>
                <strong><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $check_out ) ) ); ?></strong>
            </div>
        <?php endif; ?>

        <?php if ( $nights ) : ?>
            <div class="detail-row">
                <span><?php esc_html_e( 'Nights', 'hclr-direct-booking' ); ?></span>
                <strong><?php echo esc_html( $nights ); ?></strong>
            </div>
        <?php endif; ?>

        <?php if ( $booking ) : ?>
            <div class="detail-row">
                <span><?php esc_html_e( 'Guests', 'hclr-direct-booking' ); ?></span>
                <strong><?php echo esc_html( $booking->guests ); ?></strong>
            </div>
        <?php endif; ?>

        <?php if ( $total ) : ?>
            <div class="detail-row">
                <span><?php esc_html_e( 'Total Paid', 'hclr-direct-booking' ); ?></span>
                <strong>$<?php echo esc_html( number_format( $total, 2 ) ); ?></strong>
            </div>
        <?php endif; ?>

        <?php if ( $booking ) : ?>
            <div class="detail-row">
                <span><?php esc_html_e( 'Status', 'hclr-direct-booking' ); ?></span>
                <strong><?php echo esc_html( ucfirst( $booking->status ) ); ?></strong>
            </div>
        <?php endif; ?>

    </div>

    <p>
        <?php esc_html_e( 'Questions? Contact us at', 'hclr-direct-booking' ); ?>
        <a href="mailto:info@hillcountrylakesrentals.com">info@hillcountrylakesrentals.com</a>
    </p>

    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hclr-btn hclr-btn--primary" style="margin-top:20px;">
        <?php esc_html_e( '← Back to Home', 'hclr-direct-booking' ); ?>
    </a>

</div>
