        </div><!-- #main-content .site-content -->
    </div><!-- #page .site -->

<!-- ════════════════ SITE FOOTER ════════════════ -->
<footer class="site-footer" id="site-footer" role="contentinfo">
    <div class="container">

        <div class="footer-grid">

            <!-- Column 1: Brand -->
            <div class="footer-col footer-col--brand">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="footer-logo">
                    <?php
                    $logo = get_custom_logo();
                    if ( $logo ) {
                        echo wp_kses_post( $logo );
                    } else {
                        bloginfo( 'name' );
                    }
                    ?>
                </a>
                <p class="footer-tagline">
                    <?php esc_html_e( 'Experience the Texas Hill Country. Luxury vacation rentals with stunning lake views, extended stay options, and direct booking savings.', 'hclr-well-rooted' ); ?>
                </p>
                <div class="footer-social" aria-label="<?php esc_attr_e( 'Social media links', 'hclr-well-rooted' ); ?>">
                    <a href="#" aria-label="<?php esc_attr_e( 'Facebook', 'hclr-well-rooted' ); ?>" rel="noopener noreferrer">f</a>
                    <a href="#" aria-label="<?php esc_attr_e( 'Instagram', 'hclr-well-rooted' ); ?>" rel="noopener noreferrer">ig</a>
                    <a href="#" aria-label="<?php esc_attr_e( 'Pinterest', 'hclr-well-rooted' ); ?>" rel="noopener noreferrer">p</a>
                </div>
            </div>

            <!-- Column 2: Quick Links -->
            <div class="footer-col footer-col--nav">
                <h4 class="footer-heading"><?php esc_html_e( 'Quick Links', 'hclr-well-rooted' ); ?></h4>
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'footer',
                    'menu_class'     => 'footer-nav',
                    'container'      => false,
                    'depth'          => 1,
                    'fallback_cb'    => false,
                ) );

                // Fallback if no menu set.
                if ( ! has_nav_menu( 'footer' ) ) :
                ?>
                <ul class="footer-nav">
                    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'hclr-well-rooted' ); ?></a></li>
                    <li><a href="<?php echo esc_url( home_url( '/properties/' ) ); ?>"><?php esc_html_e( 'Properties', 'hclr-well-rooted' ); ?></a></li>
                    <li><a href="<?php echo esc_url( home_url( '/booking/' ) ); ?>"><?php esc_html_e( 'Book Now', 'hclr-well-rooted' ); ?></a></li>
                    <li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'About', 'hclr-well-rooted' ); ?></a></li>
                    <li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact', 'hclr-well-rooted' ); ?></a></li>
                </ul>
                <?php endif; ?>
            </div>

            <!-- Column 3: Contact -->
            <div class="footer-col footer-col--contact">
                <h4 class="footer-heading"><?php esc_html_e( 'Contact & Info', 'hclr-well-rooted' ); ?></h4>
                <div class="footer-contact">
                    <p>📍 <?php esc_html_e( 'Texas Hill Country, TX', 'hclr-well-rooted' ); ?></p>
                    <p>📞 <a href="tel:+15125550100">(512) 555-0100</a></p>
                    <p>✉️ <a href="mailto:info@hillcountrylakesrentals.com">info@hillcountrylakesrentals.com</a></p>
                </div>

                <div style="margin-top:20px;">
                    <h4 class="footer-heading"><?php esc_html_e( 'Check Availability', 'hclr-well-rooted' ); ?></h4>
                    <a href="<?php echo esc_url( home_url( '/booking/' ) ); ?>" class="btn btn--gold btn--sm">
                        <?php esc_html_e( 'Book Direct', 'hclr-well-rooted' ); ?>
                    </a>
                </div>
            </div>

        </div><!-- .footer-grid -->

        <!-- Bottom bar -->
        <div class="footer-bottom">
            <p>
                &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
                <?php bloginfo( 'name' ); ?>.
                <?php esc_html_e( 'All Rights Reserved.', 'hclr-well-rooted' ); ?>
            </p>
            <nav class="footer-bottom-nav" aria-label="<?php esc_attr_e( 'Legal links', 'hclr-well-rooted' ); ?>">
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'footer-legal',
                    'container'      => false,
                    'depth'          => 1,
                    'fallback_cb'    => false,
                    'items_wrap'     => '%3$s',
                    'walker'         => new Walker_Nav_Menu(),
                ) );

                if ( ! has_nav_menu( 'footer-legal' ) ) :
                ?>
                    <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'hclr-well-rooted' ); ?></a>
                    <a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>"><?php esc_html_e( 'Terms', 'hclr-well-rooted' ); ?></a>
                <?php endif; ?>
            </nav>
        </div><!-- .footer-bottom -->

    </div><!-- .container -->
</footer><!-- .site-footer -->

<?php wp_footer(); ?>

</body>
</html>
