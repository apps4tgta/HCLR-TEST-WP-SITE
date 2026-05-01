<?php
/**
 * Main index template – fallback for all archive/blog views.
 *
 * @package HCLR\WellRooted
 */

get_header();
?>

<main id="primary" class="site-main" role="main">
    <div class="container section">

        <?php if ( is_home() && ! is_front_page() ) : ?>
            <header class="section-header">
                <h1><?php single_post_title(); ?></h1>
            </header>
        <?php endif; ?>

        <?php if ( have_posts() ) : ?>

            <div class="grid-3">
                <?php
                while ( have_posts() ) :
                    the_post();
                    get_template_part( 'template-parts/content/content', get_post_type() );
                endwhile;
                ?>
            </div>

            <div style="margin-top:var(--space-xl)">
                <?php the_posts_pagination( array(
                    'mid_size'  => 2,
                    'prev_text' => __( '← Previous', 'hclr-well-rooted' ),
                    'next_text' => __( 'Next →', 'hclr-well-rooted' ),
                ) ); ?>
            </div>

        <?php else : ?>
            <?php get_template_part( 'template-parts/content/content', 'none' ); ?>
        <?php endif; ?>

    </div><!-- .container -->
</main>

<?php get_footer(); ?>
