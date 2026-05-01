<?php
/**
 * Template helper functions for HCLR Well-Rooted theme.
 *
 * @package HCLR\WellRooted
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get a property meta value for the current post.
 *
 * @param string $key     Meta key.
 * @param mixed  $default Default value if meta is empty.
 * @return mixed
 */
function hclr_get_property_meta( string $key, $default = '' ) {
    $value = get_post_meta( get_the_ID(), $key, true );
    return ( '' !== $value && false !== $value ) ? $value : $default;
}

/**
 * Format a price as currency string.
 *
 * @param float $amount Amount to format.
 * @return string  e.g. "$250"
 */
function hclr_format_price( float $amount ): string {
    if ( $amount <= 0 ) {
        return '';
    }
    return '$' . number_format( $amount, 0 );
}

/**
 * Render star rating HTML.
 *
 * @param float $rating Rating value (0–5).
 * @return string HTML string.
 */
function hclr_render_star_rating( float $rating ): string {
    $rating  = max( 0, min( 5, $rating ) );
    $full    = floor( $rating );
    $half    = ( $rating - $full ) >= 0.5 ? 1 : 0;
    $empty   = 5 - $full - $half;
    $html    = '<span class="star-rating" aria-label="' . esc_attr( number_format( $rating, 1 ) ) . ' out of 5">';

    for ( $i = 0; $i < $full; $i++ ) {
        $html .= '<span class="star star--full" aria-hidden="true">★</span>';
    }
    if ( $half ) {
        $html .= '<span class="star star--half" aria-hidden="true">½</span>';
    }
    for ( $i = 0; $i < $empty; $i++ ) {
        $html .= '<span class="star star--empty" aria-hidden="true">☆</span>';
    }

    $html .= '</span>';
    return $html;
}

/**
 * Render a property card.
 *
 * @param int $post_id Post ID of the property page.
 * @return string HTML.
 */
function hclr_render_property_card( int $post_id ): string {
    $title        = get_the_title( $post_id );
    $permalink    = get_permalink( $post_id );
    $thumbnail    = get_the_post_thumbnail( $post_id, 'hclr-property-card', array( 'class' => 'property-card__img', 'alt' => esc_attr( $title ) ) );
    $tagline      = get_post_meta( $post_id, '_hclr_tagline', true );
    $nightly_rate = floatval( get_post_meta( $post_id, '_hclr_nightly_rate', true ) );
    $bedrooms     = absint( get_post_meta( $post_id, '_hclr_bedrooms', true ) );
    $sleeps       = absint( get_post_meta( $post_id, '_hclr_sleeps', true ) );
    $rating       = floatval( get_post_meta( $post_id, '_hclr_rating', true ) ?: 5 );

    ob_start();
    ?>
    <article class="property-card">
        <a href="<?php echo esc_url( $permalink ); ?>" class="property-card__image-link" tabindex="-1" aria-hidden="true">
            <?php if ( $thumbnail ) : ?>
                <?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput ?>
            <?php else : ?>
                <div class="property-card__img-placeholder"></div>
            <?php endif; ?>
        </a>
        <div class="property-card__body">
            <?php if ( $rating ) : ?>
                <div class="property-card__rating">
                    <?php echo hclr_render_star_rating( $rating ); // phpcs:ignore ?>
                    <span class="property-card__rating-num"><?php echo esc_html( number_format( $rating, 1 ) ); ?></span>
                </div>
            <?php endif; ?>
            <h3 class="property-card__title">
                <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
            </h3>
            <?php if ( $tagline ) : ?>
                <p class="property-card__tagline"><?php echo esc_html( $tagline ); ?></p>
            <?php endif; ?>
            <div class="property-card__meta">
                <?php if ( $bedrooms ) : ?>
                    <span><?php echo esc_html( $bedrooms ); ?> BR</span>
                <?php endif; ?>
                <?php if ( $sleeps ) : ?>
                    <span>Sleeps <?php echo esc_html( $sleeps ); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="property-card__footer">
            <?php if ( $nightly_rate ) : ?>
                <span class="property-card__rate">
                    <strong><?php echo esc_html( hclr_format_price( $nightly_rate ) ); ?></strong>
                    <span><?php esc_html_e( '/ night', 'hclr-well-rooted' ); ?></span>
                </span>
            <?php endif; ?>
            <a href="<?php echo esc_url( $permalink ); ?>" class="btn btn--primary btn--sm">
                <?php esc_html_e( 'View Property', 'hclr-well-rooted' ); ?>
            </a>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

/**
 * Get breadcrumb HTML for the current page.
 *
 * @return string HTML.
 */
function hclr_get_breadcrumbs(): string {
    if ( is_front_page() ) {
        return '';
    }

    $items   = array();
    $items[] = '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'hclr-well-rooted' ) . '</a>';

    if ( is_singular() ) {
        $post_type = get_post_type();
        if ( 'hclr_property' === $post_type ) {
            $items[] = '<a href="' . esc_url( get_post_type_archive_link( 'hclr_property' ) ) . '">'
                       . esc_html__( 'Properties', 'hclr-well-rooted' ) . '</a>';
        }
        $items[] = '<span>' . esc_html( get_the_title() ) . '</span>';
    } elseif ( is_page() ) {
        $items[] = '<span>' . esc_html( get_the_title() ) . '</span>';
    } elseif ( is_archive() ) {
        $items[] = '<span>' . esc_html( get_the_archive_title() ) . '</span>';
    } elseif ( is_search() ) {
        $items[] = '<span>' . sprintf(
            /* translators: %s: search query */
            esc_html__( 'Search: %s', 'hclr-well-rooted' ),
            esc_html( get_search_query() )
        ) . '</span>';
    } elseif ( is_404() ) {
        $items[] = '<span>' . esc_html__( '404 – Page Not Found', 'hclr-well-rooted' ) . '</span>';
    }

    if ( count( $items ) < 2 ) {
        return '';
    }

    $html  = '<nav class="breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'hclr-well-rooted' ) . '">';
    $html .= '<ol class="breadcrumbs__list">';
    foreach ( $items as $i => $item ) {
        $is_last  = ( $i === count( $items ) - 1 );
        $html    .= '<li class="breadcrumbs__item' . ( $is_last ? ' breadcrumbs__item--current' : '' ) . '"'
                    . ( $is_last ? ' aria-current="page"' : '' ) . '>'
                    . $item . '</li>';
    }
    $html .= '</ol>';
    $html .= '</nav>';

    return $html;
}

/**
 * Get an emoji icon for a named amenity.
 *
 * @param string $amenity Amenity name.
 * @return string Emoji or blank.
 */
function hclr_get_amenity_icon( string $amenity ): string {
    $amenity_lower = strtolower( $amenity );
    $icons = array(
        'wifi'       => '📶',
        'pool'       => '🏊',
        'hot tub'    => '♨️',
        'jacuzzi'    => '♨️',
        'fireplace'  => '🔥',
        'fire pit'   => '🔥',
        'kitchen'    => '🍳',
        'grill'      => '🍖',
        'bbq'        => '🍖',
        'parking'    => '🅿️',
        'garage'     => '🏠',
        'washer'     => '🧺',
        'dryer'      => '🧺',
        'tv'         => '📺',
        'cable'      => '📺',
        'netflix'    => '📺',
        'ac'         => '❄️',
        'air'        => '❄️',
        'heat'       => '🌡️',
        'pet'        => '🐾',
        'dog'        => '🐾',
        'boat'       => '⛵',
        'kayak'      => '🛶',
        'lake'       => '🏞️',
        'river'      => '🌊',
        'mountain'   => '⛰️',
        'trail'      => '🥾',
        'deck'       => '🪵',
        'porch'      => '🪵',
        'patio'      => '☀️',
        'game'       => '🎮',
        'workspace'  => '💻',
        'office'     => '💻',
    );

    foreach ( $icons as $keyword => $icon ) {
        if ( str_contains( $amenity_lower, $keyword ) ) {
            return $icon;
        }
    }

    return '✓';
}

/**
 * Fallback navigation for when no menu is assigned.
 *
 * @param string $location Menu location slug.
 * @return void
 */
function hclr_fallback_nav( string $location = 'primary' ): void {
    if ( 'footer' === $location ) {
        echo '<ul class="footer-nav">';
        echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'hclr-well-rooted' ) . '</a></li>';
        echo '<li><a href="#">' . esc_html__( 'Properties', 'hclr-well-rooted' ) . '</a></li>';
        echo '<li><a href="#">' . esc_html__( 'About', 'hclr-well-rooted' ) . '</a></li>';
        echo '<li><a href="#">' . esc_html__( 'Contact', 'hclr-well-rooted' ) . '</a></li>';
        echo '</ul>';
    } else {
        echo '<ul class="primary-nav__list">';
        echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'hclr-well-rooted' ) . '</a></li>';
        echo '<li><a href="#">' . esc_html__( 'Properties', 'hclr-well-rooted' ) . '</a></li>';
        echo '<li><a href="#">' . esc_html__( 'About', 'hclr-well-rooted' ) . '</a></li>';
        echo '<li><a href="#">' . esc_html__( 'Contact', 'hclr-well-rooted' ) . '</a></li>';
        echo '</ul>';
    }
}

/**
 * Get full property data from OwnerRez API (with photos).
 *
 * Uses the HCLR Direct Booking plugin's REST endpoint when available,
 * otherwise returns an empty array. Data is transient-cached for 1 hour.
 *
 * @param int $property_id OwnerRez property ID.
 * @return array Property data from API, or empty array on failure.
 */
function hclr_get_property_api_data( int $property_id ): array {
    if ( ! $property_id ) {
        return array();
    }

    $cache_key = 'hclr_prop_full_' . $property_id;
    $cached    = get_transient( $cache_key );
    if ( false !== $cached ) {
        return $cached;
    }

    // If the plugin is active, call the client directly.
    if ( class_exists( '\HCLR\DirectBooking\Plugin' ) ) {
        try {
            $settings = \HCLR\DirectBooking\Plugin::get_instance()->settings;
            $client   = new \HCLR\DirectBooking\API\OwnerRez_Client( $settings );
            $data     = $client->get_property_full( $property_id );

            if ( ! is_wp_error( $data ) && is_array( $data ) ) {
                set_transient( $cache_key, $data, HOUR_IN_SECONDS );
                return $data;
            }
        } catch ( \Throwable $e ) {
            error_log( '[HCLR Theme] Failed to fetch property data: ' . $e->getMessage() );
        }
    }

    return array();
}

/**
 * Extract a display-ready property array from raw OwnerRez API data.
 *
 * Maps OwnerRez field names to consistent theme-friendly keys.
 *
 * @param array $raw Raw API property data.
 * @return array Normalized data.
 */
function hclr_normalize_property_data( array $raw ): array {
    return array(
        'name'          => sanitize_text_field( $raw['name']            ?? $raw['title'] ?? '' ),
        'headline'      => sanitize_text_field( $raw['headline']        ?? $raw['tagline'] ?? '' ),
        'description'   => wp_kses_post( $raw['description']            ?? $raw['body'] ?? '' ),
        'bedrooms'      => absint( $raw['bedrooms']                     ?? $raw['bedroomCount'] ?? 0 ),
        'bathrooms'     => floatval( $raw['bathrooms']                  ?? $raw['bathroomCount'] ?? 0 ),
        'sleeps'        => absint( $raw['sleeps']                       ?? $raw['maxOccupancy'] ?? 0 ),
        'min_stay'      => absint( $raw['minimumStay']                  ?? $raw['minStay'] ?? 1 ),
        'base_rate'     => floatval( $raw['baseRate']                   ?? $raw['nightlyRate'] ?? 0 ),
        'cleaning_fee'  => floatval( $raw['cleaningFee']                ?? 0 ),
        'address'       => sanitize_text_field( $raw['address']         ?? $raw['city'] ?? '' ),
        'city'          => sanitize_text_field( $raw['city']            ?? '' ),
        'state'         => sanitize_text_field( $raw['state']           ?? '' ),
        'lat'           => floatval( $raw['latitude']                   ?? $raw['lat'] ?? 0 ),
        'lng'           => floatval( $raw['longitude']                  ?? $raw['lng'] ?? 0 ),
        'amenities'     => (array) ( $raw['amenities']                  ?? array() ),
        'photos'        => (array) ( $raw['photos']                     ?? array() ),
        'checkin_time'  => sanitize_text_field( $raw['checkInTime']     ?? '3:00 PM' ),
        'checkout_time' => sanitize_text_field( $raw['checkOutTime']    ?? '11:00 AM' ),
    );
}

/**
 * Render the site logo or text fallback.
 *
 * @return void
 */
function hclr_render_logo(): void {
    $logo_id = get_theme_mod( 'custom_logo' );
    if ( $logo_id ) {
        echo get_custom_logo();
        return;
    }
    ?>
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo__text" rel="home">
        <span class="site-logo__name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
        <span class="site-logo__tagline"><?php esc_html_e( 'Well Rooted Collection', 'hclr-well-rooted' ); ?></span>
    </a>
    <?php
}
