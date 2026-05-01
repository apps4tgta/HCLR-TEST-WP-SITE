<?php
/**
 * Booking Form Template
 * Rendered by the [hclr_booking_form] shortcode.
 *
 * Available variables:
 *   $property_id (int)
 *   $check_in    (string Y-m-d)
 *   $check_out   (string Y-m-d)
 *   $nonce       (string hclr_booking_nonce)
 *   $rest_url    (string)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Get optional property details from post meta for summary display.
$pages = get_posts( array(
    'post_type'   => array( 'page', 'hclr_property' ),
    'meta_key'    => '_hclr_property_id',
    'meta_value'  => $property_id,
    'numberposts' => 1,
    'fields'      => 'ids',
) );
$page_id      = ! empty( $pages ) ? $pages[0] : 0;
$prop_name    = $page_id ? get_the_title( $page_id ) : __( 'Property', 'hclr-direct-booking' );
$prop_rate    = $page_id ? floatval( get_post_meta( $page_id, '_hclr_nightly_rate', true ) ) : 0;
$prop_thumb   = $page_id && has_post_thumbnail( $page_id ) ? get_the_post_thumbnail_url( $page_id, 'thumbnail' ) : '';
?>
<div class="hclr-booking-form-wrap">
    <h2><?php esc_html_e( 'Reserve Your Stay', 'hclr-direct-booking' ); ?></h2>

    <!-- Property summary -->
    <div class="hclr-booking-property-summary">
        <?php if ( $prop_thumb ) : ?>
            <img src="<?php echo esc_url( $prop_thumb ); ?>"
                 alt="<?php echo esc_attr( $prop_name ); ?>"
                 loading="lazy" />
        <?php endif; ?>
        <div>
            <h3><?php echo esc_html( $prop_name ); ?></h3>
            <?php if ( $prop_rate ) : ?>
                <p class="rate">
                    <?php
                    printf(
                        /* translators: %s: formatted price */
                        esc_html__( 'From %s / night', 'hclr-direct-booking' ),
                        '<strong>$' . esc_html( number_format( $prop_rate, 0 ) ) . '</strong>'
                    );
                    ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Booking form -->
    <form id="hclr-booking-form"
          class="hclr-booking-form"
          data-property-id="<?php echo esc_attr( $property_id ); ?>"
          novalidate>

        <input type="hidden" name="property_id" value="<?php echo esc_attr( $property_id ); ?>" />
        <input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>" />
        <input type="hidden" name="quoted_total" value="0" />

        <!-- Dates + Guests -->
        <div class="hclr-booking-dates">
            <div class="hclr-form-group">
                <label for="hclr-check-in"><?php esc_html_e( 'Check-In', 'hclr-direct-booking' ); ?> <span aria-hidden="true">*</span></label>
                <input type="date" id="hclr-check-in" name="check_in"
                       value="<?php echo esc_attr( $check_in ); ?>"
                       min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"
                       required
                       aria-required="true" />
                <span class="hclr-field-error" role="alert" style="display:none;"></span>
            </div>
            <div class="hclr-form-group">
                <label for="hclr-check-out"><?php esc_html_e( 'Check-Out', 'hclr-direct-booking' ); ?> <span aria-hidden="true">*</span></label>
                <input type="date" id="hclr-check-out" name="check_out"
                       value="<?php echo esc_attr( $check_out ); ?>"
                       min="<?php echo esc_attr( date( 'Y-m-d', strtotime( '+1 day' ) ) ); ?>"
                       required
                       aria-required="true" />
                <span class="hclr-field-error" role="alert" style="display:none;"></span>
            </div>
            <div class="hclr-form-group">
                <label for="hclr-guests"><?php esc_html_e( 'Guests', 'hclr-direct-booking' ); ?></label>
                <select id="hclr-guests" name="guests">
                    <?php for ( $i = 1; $i <= 12; $i++ ) : ?>
                        <option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <!-- Price breakdown (populated by JS) -->
        <div class="hclr-price-breakdown" style="display:none;">
            <h3><?php esc_html_e( 'Price Breakdown', 'hclr-direct-booking' ); ?></h3>
            <p class="price-loading" style="display:none;"><?php esc_html_e( 'Calculating price…', 'hclr-direct-booking' ); ?></p>
            <p class="price-error" style="display:none; color:#c0392b;"></p>
            <div class="price-details">
                <div class="price-row">
                    <span class="price-nights-label">&nbsp;</span>
                    <span class="price-subtotal">$0.00</span>
                </div>
                <div class="price-row price-discount-row" style="display:none;">
                    <span><?php esc_html_e( 'Discount (', 'hclr-direct-booking' ); ?><span class="price-discount-pct">0</span><?php esc_html_e( '%)', 'hclr-direct-booking' ); ?></span>
                    <span class="price-discount-amount">-$0.00</span>
                </div>
                <div class="price-row">
                    <span><?php esc_html_e( 'Cleaning fee:', 'hclr-direct-booking' ); ?></span>
                    <span class="price-cleaning">$0.00</span>
                </div>
                <div class="price-row">
                    <span><?php esc_html_e( 'Service fee:', 'hclr-direct-booking' ); ?></span>
                    <span class="price-service">$0.00</span>
                </div>
                <div class="price-row total">
                    <span><?php esc_html_e( 'Total:', 'hclr-direct-booking' ); ?></span>
                    <span class="price-total">$0.00</span>
                </div>
            </div>
        </div>

        <hr style="border:none;border-top:1px solid var(--wr-limestone,#D8CEBC);margin:24px 0;" />

        <!-- Guest info -->
        <h3 style="font-family:var(--font-display,'Lora',serif);color:var(--wr-deep-oak,#2E3C28);margin-bottom:16px;">
            <?php esc_html_e( 'Your Information', 'hclr-direct-booking' ); ?>
        </h3>

        <div class="hclr-form-grid-2">
            <div class="hclr-form-group">
                <label for="hclr-first-name"><?php esc_html_e( 'First Name', 'hclr-direct-booking' ); ?> *</label>
                <input type="text" id="hclr-first-name" name="first_name" required aria-required="true" autocomplete="given-name" />
                <span class="hclr-field-error" role="alert" style="display:none;"></span>
            </div>
            <div class="hclr-form-group">
                <label for="hclr-last-name"><?php esc_html_e( 'Last Name', 'hclr-direct-booking' ); ?> *</label>
                <input type="text" id="hclr-last-name" name="last_name" required aria-required="true" autocomplete="family-name" />
                <span class="hclr-field-error" role="alert" style="display:none;"></span>
            </div>
        </div>

        <div class="hclr-form-grid-2">
            <div class="hclr-form-group">
                <label for="hclr-email"><?php esc_html_e( 'Email Address', 'hclr-direct-booking' ); ?> *</label>
                <input type="email" id="hclr-email" name="email" required aria-required="true" autocomplete="email" />
                <span class="hclr-field-error" role="alert" style="display:none;"></span>
            </div>
            <div class="hclr-form-group">
                <label for="hclr-phone"><?php esc_html_e( 'Phone Number', 'hclr-direct-booking' ); ?></label>
                <input type="tel" id="hclr-phone" name="phone" autocomplete="tel" />
            </div>
        </div>

        <div class="hclr-form-group">
            <label for="hclr-special-requests"><?php esc_html_e( 'Special Requests', 'hclr-direct-booking' ); ?></label>
            <textarea id="hclr-special-requests" name="special_requests"
                      placeholder="<?php esc_attr_e( 'Any special accommodations, early check-in requests, accessibility needs…', 'hclr-direct-booking' ); ?>"></textarea>
        </div>

        <!-- Terms -->
        <div class="hclr-terms-row">
            <input type="checkbox" id="hclr-terms" name="terms" required aria-required="true" />
            <label for="hclr-terms">
                <?php
                printf(
                    /* translators: %1$s: opening link tag, %2$s: closing link tag */
                    esc_html__( 'I agree to the %1$sTerms & Conditions%2$s and cancellation policy.', 'hclr-direct-booking' ),
                    '<a href="' . esc_url( home_url( '/terms/' ) ) . '" target="_blank">',
                    '</a>'
                );
                ?>
            </label>
        </div>

        <!-- Submit -->
        <button type="submit" class="hclr-booking-submit">
            <?php esc_html_e( 'Complete Booking', 'hclr-direct-booking' ); ?>
        </button>

        <!-- Inline message -->
        <div class="hclr-booking-message" role="alert"></div>

    </form>
</div>
