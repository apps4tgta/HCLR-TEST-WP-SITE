<?php
/**
 * Single post template.
 *
 * @package HCLR\WellRooted
 */

get_header();
?>
<div class="site-content container">
    <div class="site-content__layout">

        <main id="primary" class="site-main" role="main">

            <?php echo hclr_get_breadcrumbs(); // phpcs:ignore ?>

            <?php while ( have_posts() ) : the_post(); ?>

                <article id="post-<?php the_ID(); ?>" <?php post_class( 'post-single' ); ?>>
                    <header class="post-single__header section--sm">
                        <?php the_title( '<h1 class="post-single__title">', '</h1>' ); ?>
                        <div class="post-single__meta">
                            <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                <?php echo esc_html( get_the_date() ); ?>
                            </time>
                            <?php if ( get_the_author() ) : ?>
                                <span> &middot; </span>
                                <span><?php the_author(); ?></span>
                            <?php endif; ?>
                        </div>
                    </header>

                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="post-single__thumbnail">
                            <?php the_post_thumbnail( 'hclr-hero', array( 'alt' => get_the_title() ) ); ?>
                        </div>
                    <?php endif; ?>

                    <div class="post-single__content entry-content">
                        <?php the_content(); ?>
                    </div>

                    <?php
                    wp_link_pages( array(
                        'before' => '<nav class="page-links">' . esc_html__( 'Pages:', 'hclr-well-rooted' ),
                        'after'  => '</nav>',
                    ) );
                    ?>

                    <footer class="post-single__footer">
                        <?php
                        $tags = get_the_tags();
                        if ( $tags ) :
                        ?>
                            <div class="post-tags">
                                <?php foreach ( $tags as $tag ) : ?>
                                    <a href="<?php echo esc_url( get_tag_link( $tag ) ); ?>" class="tag-link">
                                        <?php echo esc_html( $tag->name ); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </footer>
                </article>

                <?php
                the_post_navigation( array(
                    'prev_text' => '&larr; %title',
                    'next_text' => '%title &rarr;',
                ) );
                ?>

                <?php if ( comments_open() || get_comments_number() ) : ?>
                    <div class="comments-area">
                        <?php comments_template(); ?>
                    </div>
                <?php endif; ?>

            <?php endwhile; ?>

        </main>

        <?php get_sidebar(); ?>

    </div>
</div>
<?php get_footer(); ?>
