<?php
/**
 * No results partial.
 *
 * @package HCLR\WellRooted
 */
?>
<section class="no-results section" style="text-align:center;padding:var(--space-2xl) 0;">
    <div class="no-results__icon" aria-hidden="true" style="font-size:3rem;margin-bottom:var(--space-sm);">🔍</div>
    <h2><?php esc_html_e( 'Nothing Found', 'hclr-well-rooted' ); ?></h2>
    <p style="max-width:420px;margin:var(--space-sm) auto var(--space-lg);color:var(--wr-flint);">
        <?php esc_html_e( 'Sorry, we couldn\'t find anything matching your search. Try different keywords or browse our properties.', 'hclr-well-rooted' ); ?>
    </p>
    <div style="display:flex;gap:var(--space-sm);justify-content:center;flex-wrap:wrap;">
        <?php if ( is_search() ) : ?>
            <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <label class="screen-reader-text"><?php esc_html_e( 'Search', 'hclr-well-rooted' ); ?></label>
                <input type="search" name="s"
                       placeholder="<?php esc_attr_e( 'Try again…', 'hclr-well-rooted' ); ?>"
                       value="<?php echo esc_attr( get_search_query() ); ?>" />
                <button type="submit" class="btn btn--primary"><?php esc_html_e( 'Search', 'hclr-well-rooted' ); ?></button>
            </form>
        <?php endif; ?>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--secondary">
            <?php esc_html_e( 'Back to Home', 'hclr-well-rooted' ); ?>
        </a>
    </div>
</section>
