<?php
/**
 * Archive template (blog, category, tag, author, date).
 *
 * @package HCLR\WellRooted
 */

get_header();
?>
<div class="site-content container">

    <main id="primary" class="site-main" role="main">

        <?php echo hclr_get_breadcrumbs(); // phpcs:ignore ?>

        <header class="archive-header section--sm">
            <?php the_archive_title( '<h1 class="archive-title">', '</h1>' ); ?>
            <?php the_archive_description( '<p class="archive-description">', '</p>' ); ?>
        </header>

        <?php if ( have_posts() ) : ?>

            <div class="posts-grid grid-3">
                <?php while ( have_posts() ) : the_post(); ?>

                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
                        <?php if ( has_post_thumbnail() ) : ?>
                            <a href="<?php the_permalink(); ?>" class="post-card__img-link" tabindex="-1">
                                <?php the_post_thumbnail( 'hclr-property-card', array( 'class' => 'post-card__img' ) ); ?>
                            </a>
                        <?php endif; ?>
                        <div class="post-card__body">
                            <time class="post-card__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                <?php echo esc_html( get_the_date() ); ?>
                            </time>
                            <?php the_title( '<h2 class="post-card__title"><a href="' . esc_url( get_the_permalink() ) . '">', '</a></h2>' ); ?>
                            <div class="post-card__excerpt"><?php the_excerpt(); ?></div>
                            <a href="<?php the_permalink(); ?>" class="btn btn--primary btn--sm">
                                <?php esc_html_e( 'Read More', 'hclr-well-rooted' ); ?>
                            </a>
                        </div>
                    </article>

                <?php endwhile; ?>
            </div>

            <?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?>

        <?php else : ?>
            <?php get_template_part( 'template-parts/content/content', 'none' ); ?>
        <?php endif; ?>

    </main>

</div>
<?php get_footer(); ?>
