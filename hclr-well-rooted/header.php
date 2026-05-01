<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main-content"><?php esc_html_e( 'Skip to content', 'hclr-well-rooted' ); ?></a>

<!-- ════════════════ SITE HEADER ════════════════ -->
<header class="site-header" id="site-header" role="banner">
    <div class="container">

        <!-- Logo -->
        <a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php bloginfo( 'name' ); ?> – <?php esc_attr_e( 'Home', 'hclr-well-rooted' ); ?>">
            <?php
            $logo = get_custom_logo();
            if ( $logo ) :
                echo wp_kses_post( $logo );
            else :
            ?>
            <span class="site-logo__text">
                <?php bloginfo( 'name' ); ?>
                <span class="site-logo__sub"><?php esc_html_e( 'Well Rooted Collection', 'hclr-well-rooted' ); ?></span>
            </span>
            <?php endif; ?>
        </a>

        <!-- Primary Navigation -->
        <nav id="primary-navigation" aria-label="<?php esc_attr_e( 'Primary menu', 'hclr-well-rooted' ); ?>">
            <?php
            wp_nav_menu( array(
                'theme_location'  => 'primary',
                'menu_id'         => 'primary-menu',
                'menu_class'      => 'primary-nav',
                'container'       => false,
                'fallback_cb'     => 'hclr_fallback_nav',
            ) );
            ?>
        </nav>

        <!-- Header CTA -->
        <a href="<?php echo esc_url( home_url( '/booking/' ) ); ?>" class="btn btn--gold btn--sm nav-cta" aria-label="<?php esc_attr_e( 'Book Now', 'hclr-well-rooted' ); ?>">
            <?php esc_html_e( 'Book Now', 'hclr-well-rooted' ); ?>
        </a>

        <!-- Mobile hamburger -->
        <button class="nav-hamburger" id="nav-hamburger" aria-controls="mobile-nav-drawer" aria-expanded="false" aria-label="<?php esc_attr_e( 'Open navigation menu', 'hclr-well-rooted' ); ?>">
            <span></span><span></span><span></span>
        </button>

    </div><!-- .container -->
</header><!-- .site-header -->

<!-- Mobile nav drawer -->
<div class="mobile-nav-drawer" id="mobile-nav-drawer" aria-hidden="true" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Navigation menu', 'hclr-well-rooted' ); ?>">
    <?php
    wp_nav_menu( array(
        'theme_location' => 'primary',
        'menu_class'     => 'mobile-nav-list',
        'container'      => false,
        'fallback_cb'    => false,
    ) );
    ?>
    <a href="<?php echo esc_url( home_url( '/booking/' ) ); ?>" class="btn btn--gold" style="margin-top:24px; display:block; text-align:center;">
        <?php esc_html_e( 'Book Now', 'hclr-well-rooted' ); ?>
    </a>
</div>

<!-- Main content begins -->
<div id="page" class="site">
    <div id="main-content" class="site-content">
