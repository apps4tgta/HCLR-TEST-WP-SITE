<?php
/**
 * Search results template.
 *
 * @package HCLR\WellRooted
 */

get_header();
?>
<div class="site-content container">

    <main id="primary" class="site-main" role="main">

        <header class="archive-header section--sm">
            <h1 class="archive-title">
                <?php
                printf(
                    /* translators: %s: search query */
                    esc_html__( 'Search Results: %s', 'hclr-well-rooted' ),
                    '<em>' . esc_html( get_search_query() ) . '</em>'
                );
                ?>
            </h1>
            <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="search-form">
                <label for="search-input" class="screen-reader-text"><?php esc_html_e( 'Search', 'hclr-well-rooted' ); ?></label>
                <input id="search-input" type="search" name="s"
                       value="<?php echo esc_attr( get_search_query() ); ?>"
                       placeholder="<?php esc_attr_e( 'Refine your search…', 'hclr-well-rooted' ); ?>" />
                <button type="submit" class="btn btn--primary">
                    <?php esc_html_e( 'Search', 'hclr-well-rooted' ); ?>
                </button>
            </form>
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
