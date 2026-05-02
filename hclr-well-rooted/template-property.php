<?php
/**
 * Template Name: Property Page
 * Template Post Type: page
 *
 * Fully API-driven property detail page.
 * Only requires `_hclr_property_id` post meta — all other data
 * is fetched automatically from the OwnerRez API.
 *
 * @package HCLR\WellRooted
 */

// Load helpers + calendar widget class.
require_once get_stylesheet_directory() . '/inc/template-functions.php';
require_once get_stylesheet_directory() . '/inc/class-calendar-widget.php';

get_header();

// ── Only required field ─────────────────────────────────────────────────────
$pid         = get_the_ID();
$property_id = absint( get_post_meta( $pid, '_hclr_property_id', true ) );

// ── Fetch all data from OwnerRez API (cached 1 hour) ────────────────────────
$raw_data = $property_id ? hclr_get_property_api_data( $property_id ) : array();
$api      = ! empty( $raw_data ) ? hclr_normalize_property_data( $raw_data ) : array();

// ── Resolve field values: API first, then WP meta as fallback ───────────────
$prop_name     = $api['name']         ?: get_the_title();
$headline      = $api['headline']     ?: hclr_get_property_meta( '_hclr_tagline' );
$description   = $api['description'];      // will use the_content() if empty
$bedrooms      = $api['bedrooms']     ?: absint( hclr_get_property_meta( '_hclr_bedrooms', 0 ) );
$bathrooms     = $api['bathrooms']    ?: floatval( hclr_get_property_meta( '_hclr_bathrooms', 0 ) );
$sleeps        = $api['sleeps']       ?: absint( hclr_get_property_meta( '_hclr_sleeps', 0 ) );
$base_rate     = $api['base_rate']    ?: floatval( hclr_get_property_meta( '_hclr_nightly_rate', 0 ) );
$min_stay      = $api['min_stay']     ?: absint( hclr_get_property_meta( '_hclr_min_stay', 1 ) );
$checkin_time  = $api['checkin_time'] ?: hclr_get_property_meta( '_hclr_checkin_time', '3:00 PM' );
$checkout_time = $api['checkout_time'] ?: hclr_get_property_meta( '_hclr_checkout_time', '11:00 AM' );
$city          = $api['city']         ?: '';
$state         = $api['state']        ?: 'TX';
$amenities     = ! empty( $api['amenities'] ) ? $api['amenities'] : array();
$cancel_policy = hclr_get_property_meta( '_hclr_cancel_policy', __( 'Free cancellation up to 30 days before check-in.', 'hclr-well-rooted' ) );
$map_embed     = hclr_get_property_meta( '_hclr_map_embed' );

// ── Photos from API; fall back to featured image + manual gallery ────────────
$api_photos = $api['photos'] ?? array();
$gallery_images = array();

// Build unified photos list (API photos first).
foreach ( $api_photos as $photo ) {
    $gallery_images[] = array(
        'url'     => $photo['url'],
        'caption' => $photo['caption'] ?? '',
    );
}

// Add WP featured image if API returned none.
if ( empty( $gallery_images ) && has_post_thumbnail() ) {
    $gallery_images[] = array(
        'url'     => get_the_post_thumbnail_url( $pid, 'hclr-hero' ),
        'caption' => '',
    );
}

// Append any manually set gallery images.
$manual_gallery = json_decode( hclr_get_property_meta( '_hclr_gallery_images', '[]' ), true ) ?: array();
foreach ( $manual_gallery as $url ) {
    if ( $url ) {
        $gallery_images[] = array( 'url' => esc_url_raw( $url ), 'caption' => '' );
    }
}

$photo_count = max( 1, count( $gallery_images ) );
?>

<main id="primary" class="site-main property-page" role="main">

    <!-- ════════════════════════════════════════
         HERO CAROUSEL — Images from OwnerRez API
         ════════════════════════════════════════ -->
    <section class="property-hero" aria-label="<?php esc_attr_e( 'Property photos', 'hclr-well-rooted' ); ?>">

        <div class="swiper property-hero__swiper" id="property-hero-swiper"
             data-photo-count="<?php echo esc_attr( $photo_count ); ?>">
            <div class="swiper-wrapper">

                <?php if ( ! empty( $gallery_images ) ) : ?>
                    <?php foreach ( $gallery_images as $i => $photo ) : ?>
                        <div class="swiper-slide" role="group"
                             aria-label="<?php echo esc_attr( sprintf( __( 'Photo %1$d of %2$d', 'hclr-well-rooted' ), $i + 1, $photo_count ) ); ?>">
                            <img src="<?php echo esc_url( $photo['url'] ); ?>"
                                 alt="<?php echo $photo['caption'] ? esc_attr( $photo['caption'] ) : esc_attr( $prop_name ); ?>"
                                 class="property-hero__img"
                                 <?php echo 0 === $i ? '' : 'loading="lazy"'; ?>
                                 width="1400" height="600" />
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="swiper-slide">
                        <div class="property-hero__placeholder" role="img"
                             aria-label="<?php esc_attr_e( 'Property photo unavailable', 'hclr-well-rooted' ); ?>"></div>
                    </div>
                <?php endif; ?>

            </div><!-- .swiper-wrapper -->

            <!-- Navigation arrows -->
            <?php if ( $photo_count > 1 ) : ?>
                <button class="swiper-button-prev" aria-label="<?php esc_attr_e( 'Previous photo', 'hclr-well-rooted' ); ?>"></button>
                <button class="swiper-button-next" aria-label="<?php esc_attr_e( 'Next photo', 'hclr-well-rooted' ); ?>"></button>
                <div class="swiper-pagination" role="tablist"></div>
                <div class="property-hero__counter" aria-live="polite" aria-atomic="true">
                    <span class="current-slide">1</span> / <span class="total-slides"><?php echo esc_html( $photo_count ); ?></span>
                </div>
            <?php endif; ?>

            <!-- Overlay label -->
            <div class="property-hero__overlay" aria-hidden="true">
                <div class="property-hero__label"><?php esc_html_e( 'Well Rooted Collection', 'hclr-well-rooted' ); ?></div>
                <?php if ( $headline ) : ?>
                    <p class="property-hero__tagline"><?php echo esc_html( $headline ); ?></p>
                <?php endif; ?>
            </div>
        </div><!-- .swiper -->

    </section><!-- .property-hero -->


    <div class="container">

        <!-- ════════════════════════════════════════
             PROPERTY HEADER
             ════════════════════════════════════════ -->
        <header class="property-header section--sm">
            <?php echo wp_kses_post( hclr_get_breadcrumbs() ); ?>

            <div class="property-header__inner">
                <div class="property-header__title">
                    <h1><?php echo esc_html( $prop_name ); ?></h1>
                    <?php if ( $headline ) : ?>
                        <p class="property-tagline"><?php echo esc_html( $headline ); ?></p>
                    <?php endif; ?>
                    <?php if ( $city || $state ) : ?>
                        <p class="property-header__location">
                            📍 <?php echo esc_html( trim( "$city, $state", ', ' ) ); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="property-header__key-details" aria-label="<?php esc_attr_e( 'Key details', 'hclr-well-rooted' ); ?>">
                    <?php if ( $base_rate ) : ?>
                        <div class="property-header__detail">
                            <strong><?php echo esc_html( hclr_format_price( $base_rate ) ); ?></strong>
                            <span><?php esc_html_e( '/ night', 'hclr-well-rooted' ); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $bedrooms ) : ?>
                        <div class="property-header__detail">
                            <strong><?php echo esc_html( $bedrooms ); ?></strong>
                            <span><?php esc_html_e( 'Beds', 'hclr-well-rooted' ); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $bathrooms ) : ?>
                        <div class="property-header__detail">
                            <strong><?php echo esc_html( $bathrooms ); ?></strong>
                            <span><?php esc_html_e( 'Baths', 'hclr-well-rooted' ); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $sleeps ) : ?>
                        <div class="property-header__detail">
                            <strong><?php echo esc_html( $sleeps ); ?></strong>
                            <span><?php esc_html_e( 'Guests', 'hclr-well-rooted' ); ?></span>
                        </div>
                    <?php endif; ?>
                    <a href="#book" class="btn btn--gold btn--sm">
                        <?php esc_html_e( 'Book Now', 'hclr-well-rooted' ); ?>
                    </a>
                </div>
            </div>
        </header>


        <!-- ════════════════════════════════════════
             AT A GLANCE
             ════════════════════════════════════════ -->
        <section class="property-glance section--sm" aria-label="<?php esc_attr_e( 'At a glance', 'hclr-well-rooted' ); ?>">
            <div class="at-a-glance-grid">
                <?php
                $glance_items = array();
                if ( $bedrooms )  $glance_items[] = array( 'icon' => '🛏', 'value' => $bedrooms,                              'label' => _n( 'Bedroom', 'Bedrooms', $bedrooms, 'hclr-well-rooted' ) );
                if ( $bathrooms ) $glance_items[] = array( 'icon' => '🚿', 'value' => $bathrooms,                             'label' => _n( 'Bathroom', 'Bathrooms', (int) $bathrooms, 'hclr-well-rooted' ) );
                if ( $sleeps )    $glance_items[] = array( 'icon' => '👥', 'value' => $sleeps,                                'label' => __( 'Guests Max', 'hclr-well-rooted' ) );
                if ( $min_stay )  $glance_items[] = array( 'icon' => '📅', 'value' => sprintf( _n( '%d night', '%d nights', $min_stay, 'hclr-well-rooted' ), $min_stay ), 'label' => __( 'Min. Stay', 'hclr-well-rooted' ) );
                if ( $base_rate ) $glance_items[] = array( 'icon' => '💵', 'value' => hclr_format_price( $base_rate ),        'label' => __( 'Starting / Night', 'hclr-well-rooted' ) );
                if ( $checkin_time )  $glance_items[] = array( 'icon' => '🕐', 'value' => $checkin_time,   'label' => __( 'Check-in', 'hclr-well-rooted' ) );
                if ( $checkout_time ) $glance_items[] = array( 'icon' => '🕙', 'value' => $checkout_time,  'label' => __( 'Check-out', 'hclr-well-rooted' ) );

                foreach ( $glance_items as $item ) :
                ?>
                    <div class="at-a-glance-item">
                        <span class="at-a-glance-item__icon" aria-hidden="true"><?php echo esc_html( $item['icon'] ); ?></span>
                        <span class="at-a-glance-item__value"><?php echo esc_html( $item['value'] ); ?></span>
                        <span class="at-a-glance-item__label"><?php echo esc_html( $item['label'] ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>


        <!-- ════════════════════════════════════════
             DESCRIPTION + AMENITIES
             ════════════════════════════════════════ -->
        <section class="property-content section--sm"
                 aria-label="<?php esc_attr_e( 'Property description and amenities', 'hclr-well-rooted' ); ?>">
            <div class="property-content__layout">

                <!-- Description (API data or WP editor content) -->
                <div class="property-description">
                    <h2><?php esc_html_e( 'About This Property', 'hclr-well-rooted' ); ?></h2>
                    <div class="entry-content">
                        <?php if ( $description ) : ?>
                            <?php echo wp_kses_post( wpautop( $description ) ); ?>
                        <?php else : ?>
                            <?php
                            while ( have_posts() ) : the_post();
                                the_content();
                            endwhile;
                            ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Amenities (from API) -->
                <?php if ( ! empty( $amenities ) ) : ?>
                <div class="property-amenities">
                    <h2><?php esc_html_e( 'Amenities', 'hclr-well-rooted' ); ?></h2>
                    <div class="amenities-accordion" id="amenitiesAccordion">
                        <?php
                        // Determine if amenities are categorized.
                        $first = reset( $amenities );
                        if ( is_array( $first ) && isset( $first['category'] ) ) {
                            // Group by category.
                            $grouped = array();
                            foreach ( $amenities as $a ) {
                                $cat = $a['category'] ?? __( 'General', 'hclr-well-rooted' );
                                $grouped[ $cat ][] = $a['name'] ?? $a;
                            }
                            foreach ( $grouped as $cat => $items ) :
                                $cat_id = 'amenity-' . sanitize_title( $cat );
                        ?>
                            <div class="amenity-group">
                                <button class="amenity-group__toggle" type="button"
                                        aria-expanded="false"
                                        aria-controls="<?php echo esc_attr( $cat_id ); ?>">
                                    <?php echo esc_html( $cat ); ?>
                                    <span class="amenity-group__arrow" aria-hidden="true">▼</span>
                                </button>
                                <ul class="amenity-group__list" id="<?php echo esc_attr( $cat_id ); ?>" hidden>
                                    <?php foreach ( $items as $item ) : ?>
                                        <li>
                                            <span class="amenity-icon" aria-hidden="true"><?php echo esc_html( hclr_get_amenity_icon( $item ) ); ?></span>
                                            <?php echo esc_html( $item ); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                        <?php } else { ?>
                            <ul class="amenity-flat-list">
                                <?php foreach ( $amenities as $amenity ) :
                                    $name = is_array( $amenity ) ? ( $amenity['name'] ?? $amenity[0] ?? '' ) : $amenity;
                                    if ( ! $name ) continue;
                                ?>
                                    <li>
                                        <span class="amenity-icon" aria-hidden="true"><?php echo esc_html( hclr_get_amenity_icon( $name ) ); ?></span>
                                        <?php echo esc_html( $name ); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php } ?>
                    </div><!-- .amenities-accordion -->
                </div>
                <?php endif; ?>

            </div><!-- .property-content__layout -->
        </section>


        <!-- ════════════════════════════════════════
             GALLERY — Room Stories (API photos)
             ════════════════════════════════════════ -->
        <?php if ( count( $gallery_images ) > 1 ) : ?>
        <section class="property-gallery section--sm"
                 aria-label="<?php esc_attr_e( 'Property photo gallery', 'hclr-well-rooted' ); ?>">
            <h2><?php esc_html_e( 'Room Stories', 'hclr-well-rooted' ); ?></h2>
            <div class="gallery-grid">
                <?php foreach ( array_slice( $gallery_images, 0, 6 ) as $i => $photo ) : ?>
                    <div class="gallery-item <?php echo 0 === $i ? 'gallery-item--featured' : ''; ?>">
                        <a href="<?php echo esc_url( $photo['url'] ); ?>"
                           class="gallery-lightbox"
                           aria-label="<?php echo esc_attr( $photo['caption'] ?: sprintf( __( 'Photo %d', 'hclr-well-rooted' ), $i + 1 ) ); ?>">
                            <img src="<?php echo esc_url( $photo['url'] ); ?>"
                                 alt="<?php echo esc_attr( $photo['caption'] ?: '' ); ?>"
                                 loading="lazy" />
                            <span class="gallery-item__overlay" aria-hidden="true">
                                <span class="gallery-item__zoom">⊕</span>
                            </span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>


        <!-- ════════════════════════════════════════
             AVAILABILITY CALENDAR — Built-in Widget
             (Fetches real-time data from OwnerRez API)
             ════════════════════════════════════════ -->
        <?php if ( $property_id ) : ?>
        <section class="property-availability section--sm" id="availability"
                 aria-label="<?php esc_attr_e( 'Check availability and pricing', 'hclr-well-rooted' ); ?>"
                 style="background:rgba(242,235,216,.45);border-radius:var(--radius-lg);padding:var(--space-xl);margin:var(--space-xl) 0;">

            <div style="text-align:center;margin-bottom:var(--space-lg);">
                <p class="eyebrow"><?php esc_html_e( 'Real-Time Availability', 'hclr-well-rooted' ); ?></p>
                <h2><?php esc_html_e( 'Check Availability & Pricing', 'hclr-well-rooted' ); ?></h2>
                <p style="color:var(--wr-cedar-sage);">
                    <?php esc_html_e( 'Select your arrival and departure dates to see live rates.', 'hclr-well-rooted' ); ?>
                </p>
            </div>

            <?php echo HCLR_Calendar_Widget::render( $property_id ); // phpcs:ignore ?>

        </section>
        <?php endif; ?>


        <!-- ════════════════════════════════════════
             BOOKING FORM
             ════════════════════════════════════════ -->
        <?php if ( $property_id ) : ?>
        <section class="property-booking section--sm" id="book"
                 aria-label="<?php esc_attr_e( 'Book this property', 'hclr-well-rooted' ); ?>">
            <?php echo do_shortcode( '[hclr_booking_form property_id="' . esc_attr( $property_id ) . '"]' ); // phpcs:ignore ?>
        </section>

        <?php else : ?>

        <section class="property-booking section--sm" id="book">
            <div style="text-align:center;padding:var(--space-xl);background:var(--wr-chalk-bluff);border-radius:var(--radius-md);">
                <h2><?php esc_html_e( 'Interested in This Property?', 'hclr-well-rooted' ); ?></h2>
                <p><?php esc_html_e( 'Contact us directly for availability and the best rates.', 'hclr-well-rooted' ); ?></p>
                <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="btn btn--primary btn--lg">
                    <?php esc_html_e( 'Contact Us', 'hclr-well-rooted' ); ?>
                </a>
            </div>
        </section>

        <?php endif; ?>


        <!-- ════════════════════════════════════════
             LOCATION
             ════════════════════════════════════════ -->
        <section class="property-location section--sm"
                 aria-label="<?php esc_attr_e( 'Property location', 'hclr-well-rooted' ); ?>">
            <h2><?php esc_html_e( 'Location', 'hclr-well-rooted' ); ?></h2>
            <?php if ( $city || $state ) : ?>
                <p class="property-address">📍 <?php echo esc_html( trim( "$city, $state", ', ' ) ); ?></p>
            <?php endif; ?>
            <div class="map-container">
                <?php if ( $map_embed ) : ?>
                    <?php
                    echo wp_kses( $map_embed, array(
                        'iframe' => array(
                            'src' => true, 'width' => true, 'height' => true,
                            'frameborder' => true, 'style' => true,
                            'allowfullscreen' => true, 'loading' => true, 'title' => true,
                            'referrerpolicy' => true,
                        ),
                    ) );
                    ?>
                <?php else : ?>
                    <div class="map-placeholder" aria-label="<?php esc_attr_e( 'Map coming soon', 'hclr-well-rooted' ); ?>">
                        <p><?php esc_html_e( 'Texas Hill Country, TX', 'hclr-well-rooted' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </section>


        <!-- ════════════════════════════════════════
             POLICIES (from API + fallbacks)
             ════════════════════════════════════════ -->
        <section class="property-policies section--sm"
                 aria-label="<?php esc_attr_e( 'Property policies', 'hclr-well-rooted' ); ?>">
            <h2><?php esc_html_e( 'Policies', 'hclr-well-rooted' ); ?></h2>
            <div class="policies-grid">
                <div class="policy-item">
                    <div class="policy-item__label"><?php esc_html_e( 'Check-In', 'hclr-well-rooted' ); ?></div>
                    <div class="policy-item__value">🕐 <?php echo esc_html( $checkin_time ); ?></div>
                </div>
                <div class="policy-item">
                    <div class="policy-item__label"><?php esc_html_e( 'Check-Out', 'hclr-well-rooted' ); ?></div>
                    <div class="policy-item__value">🕙 <?php echo esc_html( $checkout_time ); ?></div>
                </div>
                <?php if ( $min_stay ) : ?>
                <div class="policy-item">
                    <div class="policy-item__label"><?php esc_html_e( 'Minimum Stay', 'hclr-well-rooted' ); ?></div>
                    <div class="policy-item__value">📅 <?php echo esc_html( sprintf( _n( '%d night', '%d nights', $min_stay, 'hclr-well-rooted' ), $min_stay ) ); ?></div>
                </div>
                <?php endif; ?>
                <div class="policy-item">
                    <div class="policy-item__label"><?php esc_html_e( 'Cancellation', 'hclr-well-rooted' ); ?></div>
                    <div class="policy-item__value">🔄 <?php echo esc_html( $cancel_policy ); ?></div>
                </div>
            </div>
        </section>


        <!-- ════════════════════════════════════════
             SIMILAR PROPERTIES
             ════════════════════════════════════════ -->
        <?php
        $similar = new WP_Query( array(
            'post_type'      => array( 'page', 'hclr_property' ),
            'posts_per_page' => 3,
            'post__not_in'   => array( $pid ),
            'meta_query'     => array( array( 'key' => '_hclr_property_id', 'compare' => 'EXISTS' ) ),
            'no_found_rows'  => true,
        ) );

        if ( $similar->have_posts() ) :
        ?>
        <section class="similar-properties section--sm"
                 aria-label="<?php esc_attr_e( 'You may also like', 'hclr-well-rooted' ); ?>">
            <h2><?php esc_html_e( 'You May Also Like', 'hclr-well-rooted' ); ?></h2>
            <div class="similar-properties-grid">
                <?php while ( $similar->have_posts() ) : $similar->the_post(); ?>
                    <?php echo hclr_render_property_card( get_the_ID() ); // phpcs:ignore ?>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </section>
        <?php endif; ?>

    </div><!-- .container -->

    <!-- Back to top -->
    <button class="back-to-top" aria-label="<?php esc_attr_e( 'Back to top', 'hclr-well-rooted' ); ?>">↑</button>

</main><!-- #primary -->

<?php get_footer(); ?>
