<?php
/**
 * 404 error page template.
 *
 * @package HCLR\WellRooted
 */

get_header();
?>
<div class="site-content container">
    <main id="primary" class="site-main" role="main">
        <section class="error-404 section" style="text-align:center;padding:var(--space-2xl) 0;">
            <div class="error-404__icon" aria-hidden="true" style="font-size:5rem;margin-bottom:var(--space-md);">🌲</div>
            <h1 style="font-size:clamp(2rem,6vw,4rem);color:var(--wr-deep-oak);margin-bottom:var(--space-sm);">404</h1>
            <h2 style="font-size:1.5rem;margin-bottom:var(--space-md);">
                <?php esc_html_e( 'Lost in the Hill Country', 'hclr-well-rooted' ); ?>
            </h2>
            <p style="max-width:500px;margin:0 auto var(--space-lg);color:var(--wr-flint);">
                <?php esc_html_e( "The page you're looking for doesn't exist or has been moved. Let us help you find your way back.", 'hclr-well-rooted' ); ?>
            </p>
            <div style="display:flex;gap:var(--space-sm);justify-content:center;flex-wrap:wrap;">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--primary">
                    <?php esc_html_e( '← Back to Home', 'hclr-well-rooted' ); ?>
                </a>
                <a href="<?php echo esc_url( home_url( '/properties/' ) ); ?>" class="btn btn--secondary">
                    <?php esc_html_e( 'Browse Properties', 'hclr-well-rooted' ); ?>
                </a>
            </div>
        </section>
    </main>
</div>
<?php get_footer(); ?>
