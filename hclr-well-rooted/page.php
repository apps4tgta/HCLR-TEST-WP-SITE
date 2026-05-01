<?php
/**
 * Generic Page Template
 *
 * @package HCLR\WellRooted
 */

get_header();
?>

<main id="primary" class="site-main" role="main">
    <div class="container section">

        <?php while ( have_posts() ) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class( 'page-article' ); ?>>

                <header class="page-header" style="margin-bottom:var(--space-xl)">
                    <?php echo wp_kses_post( hclr_get_breadcrumbs() ); ?>
                    <h1><?php the_title(); ?></h1>
                </header>

                <div class="page-content entry-content" style="max-width:var(--content-width)">
                    <?php
                    the_content();

                    wp_link_pages( array(
                        'before' => '<div class="page-links">' . __( 'Pages:', 'hclr-well-rooted' ),
                        'after'  => '</div>',
                    ) );
                    ?>
                </div>

            </article>

        <?php endwhile; ?>

    </div><!-- .container -->
</main>

<?php get_footer(); ?>
