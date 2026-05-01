<?php
/**
 * Template Name: Booking Confirmation
 * Template Post Type: page
 *
 * Shows booking confirmation after a successful reservation.
 * URL params: booking_id, property_id, check_in, check_out, total.
 *
 * @package HCLR\WellRooted
 */

get_header();

// Read URL params.
$booking_id  = absint( $_GET['booking_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
$property_id = absint( $_GET['property_id'] ?? 0 ); // phpcs:ignore
$check_in    = sanitize_text_field( $_GET['check_in']  ?? '' ); // phpcs:ignore
$check_out   = sanitize_text_field( $_GET['check_out'] ?? '' ); // phpcs:ignore
$total       = floatval( $_GET['total'] ?? 0 ); // phpcs:ignore

$nights = 0;
if ( $check_in && $check_out ) {
    $nights = (int) ( ( strtotime( $check_out ) - strtotime( $check_in ) ) / 86400 );
}

$fmt_in  = $check_in  ? date_i18n( get_option( 'date_format' ), strtotime( $check_in ) )  : '';
$fmt_out = $check_out ? date_i18n( get_option( 'date_format' ), strtotime( $check_out ) ) : '';
?>
<div class="site-content container">
    <main id="primary" class="site-main section" role="main">

        <div class="booking-confirmation">

            <div class="booking-confirmation__icon" aria-hidden="true">🎉</div>

            <h1><?php esc_html_e( 'Booking Confirmed!', 'hclr-well-rooted' ); ?></h1>
            <p style="color:var(--wr-flint);margin-bottom:var(--space-lg);">
                <?php esc_html_e( 'Thank you for booking directly with Hill Country Lakes Rentals. A confirmation email is on its way.', 'hclr-well-rooted' ); ?>
            </p>

            <?php if ( $check_in ) : ?>
            <div class="booking-confirmation__details">

                <?php if ( $booking_id ) : ?>
                <div class="booking-confirmation__row">
                    <span><?php esc_html_e( 'Confirmation #', 'hclr-well-rooted' ); ?></span>
                    <strong><?php echo esc_html( $booking_id ); ?></strong>
                </div>
                <?php endif; ?>

                <?php if ( $fmt_in ) : ?>
                <div class="booking-confirmation__row">
                    <span><?php esc_html_e( 'Check-in', 'hclr-well-rooted' ); ?></span>
                    <span><?php echo esc_html( $fmt_in ); ?></span>
                </div>
                <?php endif; ?>

                <?php if ( $fmt_out ) : ?>
                <div class="booking-confirmation__row">
                    <span><?php esc_html_e( 'Check-out', 'hclr-well-rooted' ); ?></span>
                    <span><?php echo esc_html( $fmt_out ); ?></span>
                </div>
                <?php endif; ?>

                <?php if ( $nights > 0 ) : ?>
                <div class="booking-confirmation__row">
                    <span><?php esc_html_e( 'Nights', 'hclr-well-rooted' ); ?></span>
                    <span><?php echo esc_html( $nights ); ?></span>
                </div>
                <?php endif; ?>

                <?php if ( $total > 0 ) : ?>
                <div class="booking-confirmation__row">
                    <span><?php esc_html_e( 'Total Paid', 'hclr-well-rooted' ); ?></span>
                    <span><?php echo esc_html( hclr_format_price( $total ) ); ?></span>
                </div>
                <?php endif; ?>

            </div>
            <?php endif; ?>

            <p>
                <?php
                printf(
                    /* translators: %s: email link */
                    esc_html__( 'Questions? Contact us at %s', 'hclr-well-rooted' ),
                    '<a href="mailto:info@hillcountrylakesrentals.com">info@hillcountrylakesrentals.com</a>'
                );
                ?>
            </p>

            <div style="display:flex;gap:var(--space-sm);justify-content:center;flex-wrap:wrap;margin-top:var(--space-lg);">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--primary">
                    <?php esc_html_e( '← Back to Home', 'hclr-well-rooted' ); ?>
                </a>
                <a href="<?php echo esc_url( home_url( '/properties/' ) ); ?>" class="btn btn--secondary">
                    <?php esc_html_e( 'Browse More Properties', 'hclr-well-rooted' ); ?>
                </a>
            </div>

        </div>

    </main>
</div>
<?php get_footer(); ?>
