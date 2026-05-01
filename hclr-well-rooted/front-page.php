<?php
/**
 * Front Page / Home Template
 *
 * @package HCLR\WellRooted
 */

get_header();

// Get all pages that have a property ID assigned.
$property_pages = new WP_Query( array(
    'post_type'      => array( 'page', 'hclr_property' ),
    'posts_per_page' => 6,
    'meta_query'     => array( array( 'key' => '_hclr_property_id', 'compare' => 'EXISTS' ) ),
    'no_found_rows'  => true,
) );
?>

<main id="primary" class="site-main" role="main">

    <!-- ════════ 1. HERO CAROUSEL ════════ -->
    <section class="hero-section" aria-label="<?php esc_attr_e( 'Hero carousel', 'hclr-well-rooted' ); ?>">
        <div class="hero-swiper swiper" id="hero-swiper" style="height:clamp(320px,55vh,620px);">
            <div class="swiper-wrapper">

                <!-- Slide 1 -->
                <div class="swiper-slide hero-slide" style="background:linear-gradient(135deg,#2E3C28 0%,#1f2818 100%);">
                    <?php if ( get_header_image() ) : ?>
                        <img src="<?php header_image(); ?>" alt="" role="presentation" class="hero-slide__img" loading="eager" />
                    <?php endif; ?>
                    <div class="hero-slide__overlay">
                        <div class="container">
                            <span class="hero-eyebrow"><?php esc_html_e( 'Well Rooted Collection', 'hclr-well-rooted' ); ?></span>
                            <h1><?php esc_html_e( 'Your Texas Hill Country Retreat', 'hclr-well-rooted' ); ?></h1>
                            <p><?php esc_html_e( 'Luxury vacation rentals on the lake. Book direct and save.', 'hclr-well-rooted' ); ?></p>
                            <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:24px;">
                                <a href="<?php echo esc_url( home_url( '/#properties' ) ); ?>" class="btn btn--gold btn--lg">
                                    <?php esc_html_e( 'View Properties', 'hclr-well-rooted' ); ?>
                                </a>
                                <a href="<?php echo esc_url( home_url( '/booking/' ) ); ?>" class="btn btn--ghost btn--lg">
                                    <?php esc_html_e( 'Check Availability', 'hclr-well-rooted' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide 2 -->
                <div class="swiper-slide hero-slide" style="background:linear-gradient(135deg,#4A5E40 0%,#2E3C28 100%);">
                    <div class="hero-slide__overlay">
                        <div class="container">
                            <span class="hero-eyebrow"><?php esc_html_e( 'Direct Booking', 'hclr-well-rooted' ); ?></span>
                            <h1><?php esc_html_e( 'No Booking Fees. Best Rate Guaranteed.', 'hclr-well-rooted' ); ?></h1>
                            <p><?php esc_html_e( 'Skip the OTAs and book directly with us for exclusive rates and personal service.', 'hclr-well-rooted' ); ?></p>
                            <a href="<?php echo esc_url( home_url( '/booking/' ) ); ?>" class="btn btn--gold btn--lg" style="margin-top:24px;">
                                <?php esc_html_e( 'Book Your Stay', 'hclr-well-rooted' ); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Slide 3 -->
                <div class="swiper-slide hero-slide" style="background:linear-gradient(135deg,#7A8EA0 0%,#2E3C28 100%);">
                    <div class="hero-slide__overlay">
                        <div class="container">
                            <span class="hero-eyebrow"><?php esc_html_e( 'Lake Travis & Beyond', 'hclr-well-rooted' ); ?></span>
                            <h1><?php esc_html_e( 'Waterfront Living in the Hill Country', 'hclr-well-rooted' ); ?></h1>
                            <p><?php esc_html_e( 'Wake up to stunning lake views, enjoy the outdoors, and make memories that last.', 'hclr-well-rooted' ); ?></p>
                            <a href="<?php echo esc_url( home_url( '/#properties' ) ); ?>" class="btn btn--secondary" style="color:#fff;border-color:#fff;margin-top:24px;">
                                <?php esc_html_e( 'Explore Properties', 'hclr-well-rooted' ); ?>
                            </a>
                        </div>
                    </div>
                </div>

            </div><!-- .swiper-wrapper -->

            <div class="swiper-button-prev" aria-label="<?php esc_attr_e( 'Previous slide', 'hclr-well-rooted' ); ?>"></div>
            <div class="swiper-button-next" aria-label="<?php esc_attr_e( 'Next slide', 'hclr-well-rooted' ); ?>"></div>
            <div class="swiper-pagination" role="tablist"></div>
        </div>
    </section>


    <!-- ════════ 2. PROPERTIES GRID ════════ -->
    <section class="section bg-parchment" id="properties" aria-label="<?php esc_attr_e( 'Our properties', 'hclr-well-rooted' ); ?>">
        <div class="container">
            <div class="section-header">
                <span class="section-eyebrow"><?php esc_html_e( 'Well Rooted Collection', 'hclr-well-rooted' ); ?></span>
                <h2><?php esc_html_e( 'Our Properties', 'hclr-well-rooted' ); ?></h2>
                <p><?php esc_html_e( 'Hand-curated Hill Country retreats — each with stunning views, premium amenities, and a direct booking guarantee.', 'hclr-well-rooted' ); ?></p>
            </div>

            <?php if ( $property_pages->have_posts() ) : ?>
                <div class="grid-3">
                    <?php while ( $property_pages->have_posts() ) : $property_pages->the_post(); ?>
                        <?php echo wp_kses_post( hclr_render_property_card( get_the_ID() ) ); ?>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            <?php else : ?>
                <p class="text-center text-muted">
                    <?php esc_html_e( 'Properties coming soon. Check back shortly!', 'hclr-well-rooted' ); ?>
                </p>
            <?php endif; ?>
        </div>
    </section>


    <!-- ════════ 3. WELL ROOTED EXPERIENCE ════════ -->
    <section class="section" aria-label="<?php esc_attr_e( 'The Well Rooted Experience', 'hclr-well-rooted' ); ?>">
        <div class="container">
            <div class="experience-layout">
                <div class="experience-text">
                    <span class="section-eyebrow"><?php esc_html_e( 'Our Philosophy', 'hclr-well-rooted' ); ?></span>
                    <h2><?php esc_html_e( 'The Well Rooted Experience', 'hclr-well-rooted' ); ?></h2>
                    <p><?php esc_html_e( 'We believe the best stays are the ones where you feel truly at home. Our properties are thoughtfully designed with local character, premium furnishings, and everything you need for a perfect Hill Country getaway.', 'hclr-well-rooted' ); ?></p>
                    <p><?php esc_html_e( 'Whether you\'re here for a weekend escape, a family reunion, or an extended retreat — Hill Country Lakes Rentals is your home away from home.', 'hclr-well-rooted' ); ?></p>
                    <a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="btn btn--secondary mt-3">
                        <?php esc_html_e( 'Our Story', 'hclr-well-rooted' ); ?>
                    </a>
                </div>
                <div class="experience-image">
                    <div class="experience-image__placeholder" aria-hidden="true"></div>
                </div>
            </div>
        </div>
    </section>


    <!-- ════════ 4. WHY BOOK DIRECT ════════ -->
    <section class="section bg-parchment why-book-section" aria-label="<?php esc_attr_e( 'Why book direct', 'hclr-well-rooted' ); ?>">
        <div class="container">
            <div class="section-header">
                <span class="section-eyebrow"><?php esc_html_e( 'Skip the Middleman', 'hclr-well-rooted' ); ?></span>
                <h2><?php esc_html_e( 'Why Book Direct?', 'hclr-well-rooted' ); ?></h2>
            </div>

            <div class="grid-4">
                <?php
                $benefits = array(
                    array( 'icon' => '💰', 'title' => __( 'No Booking Fees', 'hclr-well-rooted' ),    'text' => __( 'Keep more money in your pocket. Zero service fees when you book directly with us.', 'hclr-well-rooted' ) ),
                    array( 'icon' => '⭐', 'title' => __( 'Best Rate Guarantee', 'hclr-well-rooted' ), 'text' => __( 'We always match or beat any price you find on third-party booking sites.', 'hclr-well-rooted' ) ),
                    array( 'icon' => '📞', 'title' => __( 'Direct Support', 'hclr-well-rooted' ),      'text' => __( 'Reach us directly by phone or email — real people who know every property.', 'hclr-well-rooted' ) ),
                    array( 'icon' => '📅', 'title' => __( 'Flexible Terms', 'hclr-well-rooted' ),      'text' => __( 'More flexible cancellation and payment options when you book with us directly.', 'hclr-well-rooted' ) ),
                );
                foreach ( $benefits as $benefit ) :
                ?>
                    <div class="benefit-card">
                        <div class="benefit-card__icon" aria-hidden="true"><?php echo esc_html( $benefit['icon'] ); ?></div>
                        <h3><?php echo esc_html( $benefit['title'] ); ?></h3>
                        <p><?php echo esc_html( $benefit['text'] ); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>


    <!-- ════════ 5. TESTIMONIALS ════════ -->
    <section class="section testimonials-section" aria-label="<?php esc_attr_e( 'Guest reviews', 'hclr-well-rooted' ); ?>">
        <div class="container">
            <div class="section-header">
                <span class="section-eyebrow"><?php esc_html_e( 'Guest Reviews', 'hclr-well-rooted' ); ?></span>
                <h2><?php esc_html_e( 'What Our Guests Say', 'hclr-well-rooted' ); ?></h2>
            </div>

            <div class="grid-3">
                <?php
                $testimonials = array(
                    array( 'name' => 'Sarah M.',  'location' => 'Austin, TX',    'rating' => 5, 'text' => __( '"Absolutely stunning property. The lake views were breathtaking and the house had everything we could have needed. Will definitely be back for another stay!"', 'hclr-well-rooted' ) ),
                    array( 'name' => 'James R.',  'location' => 'Dallas, TX',    'rating' => 5, 'text' => __( '"We booked direct and saved over $300 compared to what we saw on Airbnb. The host was incredibly responsive and the check-in process was seamless."', 'hclr-well-rooted' ) ),
                    array( 'name' => 'The Garcias', 'location' => 'Houston, TX', 'rating' => 5, 'text' => __( '"Our family reunion was perfect. The property slept 12 comfortably, the kitchen was well-stocked, and the Hill Country views were unmatched. 10/10!"', 'hclr-well-rooted' ) ),
                );
                foreach ( $testimonials as $t ) :
                ?>
                    <div class="testimonial-card card">
                        <div class="card__body">
                            <div class="testimonial-stars" aria-label="<?php echo esc_attr( $t['rating'] . ' out of 5 stars' ); ?>">
                                <?php echo wp_kses_post( hclr_render_star_rating( $t['rating'] ) ); ?>
                            </div>
                            <p class="testimonial-text"><?php echo esc_html( $t['text'] ); ?></p>
                            <div class="testimonial-author">
                                <strong><?php echo esc_html( $t['name'] ); ?></strong>
                                <span><?php echo esc_html( $t['location'] ); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>


    <!-- ════════ 6. CTA BANNER ════════ -->
    <section class="section cta-section" style="background:var(--wr-deep-oak);color:#fff;" aria-label="<?php esc_attr_e( 'Call to action', 'hclr-well-rooted' ); ?>">
        <div class="container text-center">
            <span class="section-eyebrow" style="color:var(--wr-caliche-gold);"><?php esc_html_e( 'Ready to Escape?', 'hclr-well-rooted' ); ?></span>
            <h2 style="color:#fff;margin-bottom:var(--space-md);"><?php esc_html_e( 'Start Your Hill Country Stay', 'hclr-well-rooted' ); ?></h2>
            <p style="color:rgba(255,255,255,.8);max-width:520px;margin:0 auto var(--space-xl);">
                <?php esc_html_e( 'Select your dates, choose your perfect property, and book direct for the best rate guaranteed.', 'hclr-well-rooted' ); ?>
            </p>
            <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
                <a href="<?php echo esc_url( home_url( '/#properties' ) ); ?>" class="btn btn--gold btn--lg">
                    <?php esc_html_e( 'Browse Properties', 'hclr-well-rooted' ); ?>
                </a>
                <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="btn btn--ghost btn--lg">
                    <?php esc_html_e( 'Ask a Question', 'hclr-well-rooted' ); ?>
                </a>
            </div>
        </div>
    </section>

</main><!-- #primary -->

<?php get_footer(); ?>
