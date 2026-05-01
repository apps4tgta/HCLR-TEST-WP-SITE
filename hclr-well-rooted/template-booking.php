<?php
/**
 * Template Name: Booking Page
 * Template Post Type: page
 *
 * Booking form page — accepts URL params: property_id, check_in, check_out.
 *
 * @package HCLR\WellRooted
 */

get_header();

// Read URL params (sanitized).
$property_id = absint( $_GET['property_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
$check_in    = sanitize_text_field( $_GET['check_in']  ?? '' ); // phpcs:ignore
$check_out   = sanitize_text_field( $_GET['check_out'] ?? '' ); // phpcs:ignore
?>
<div class="site-content container">
    <main id="primary" class="site-main" role="main">

        <?php echo hclr_get_breadcrumbs(); // phpcs:ignore ?>

        <section class="section booking-page">
            <div class="section-header" style="text-align:left;">
                <p class="eyebrow"><?php esc_html_e( 'Well Rooted Collection', 'hclr-well-rooted' ); ?></p>
                <h1><?php esc_html_e( 'Complete Your Booking', 'hclr-well-rooted' ); ?></h1>
                <p><?php esc_html_e( 'You\'re booking directly — no third-party fees, just honest pricing.', 'hclr-well-rooted' ); ?></p>
            </div>

            <?php if ( $property_id ) : ?>
                <?php
                $shortcode_attrs = sprintf(
                    'property_id="%d" check_in="%s" check_out="%s"',
                    $property_id,
                    esc_attr( $check_in ),
                    esc_attr( $check_out )
                );
                echo do_shortcode( "[hclr_booking_form $shortcode_attrs]" ); // phpcs:ignore
                ?>
            <?php elseif ( have_posts() ) : ?>
                <?php while ( have_posts() ) : the_post(); ?>
                    <div class="entry-content"><?php the_content(); ?></div>
                <?php endwhile; ?>
            <?php else : ?>
                <div style="text-align:center;padding:var(--space-xl);">
                    <p><?php esc_html_e( 'No property selected. Please choose a property to book.', 'hclr-well-rooted' ); ?></p>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--primary">
                        <?php esc_html_e( 'Browse Properties', 'hclr-well-rooted' ); ?>
                    </a>
                </div>
            <?php endif; ?>
        </section>

    </main>
</div>
<?php get_footer(); ?>
