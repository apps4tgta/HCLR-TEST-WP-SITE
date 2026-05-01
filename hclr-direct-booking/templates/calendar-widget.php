<?php
/**
 * Calendar Widget Template
 * Rendered by the [hclr_availability_calendar] shortcode.
 *
 * Available variables:
 *   $property_id (int)   OwnerRez property ID
 *   $nonce       (string) wp_rest nonce
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="hclr-calendar-widget"
     data-property-id="<?php echo esc_attr( $property_id ); ?>"
     data-nonce="<?php echo esc_attr( $nonce ); ?>"
     role="region"
     aria-label="<?php esc_attr_e( 'Availability Calendar', 'hclr-direct-booking' ); ?>">

    <!-- Seasonal message banner -->
    <div class="seasonal-message-banner" style="display:none;" aria-live="polite"></div>

    <!-- Calendar container -->
    <div class="calendar-container">
        <div class="calendar-header">
            <button class="btn-prev" type="button" aria-label="<?php esc_attr_e( 'Previous month', 'hclr-direct-booking' ); ?>">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                    <path d="M9 2L4 7L9 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <h3 class="calendar-month" id="currentMonth"></h3>
            <button class="btn-next" type="button" aria-label="<?php esc_attr_e( 'Next month', 'hclr-direct-booking' ); ?>">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                    <path d="M5 2L10 7L5 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>

        <!-- Day headers -->
        <div class="calendar-days-header" aria-hidden="true">
            <?php foreach ( array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ) as $day ) : ?>
                <div class="day-name"><?php echo esc_html( $day ); ?></div>
            <?php endforeach; ?>
        </div>

        <!-- Calendar grid – populated by JS -->
        <div class="calendar-grid" id="calendarGrid" role="grid" aria-label="<?php esc_attr_e( 'Select dates', 'hclr-direct-booking' ); ?>"></div>
    </div>

    <!-- Pricing section (hidden until dates selected) -->
    <div class="pricing-section" id="pricingSection" style="display:none;" aria-live="polite">
        <h4><?php esc_html_e( 'Price Breakdown', 'hclr-direct-booking' ); ?></h4>
        <div class="price-details">
            <div class="price-row">
                <span><?php esc_html_e( 'Nightly total:', 'hclr-direct-booking' ); ?></span>
                <span id="nightlyTotal">$0.00</span>
            </div>
            <div class="price-row" id="discountRow" style="display:none;">
                <span><?php esc_html_e( 'Discount (', 'hclr-direct-booking' ); ?><span id="discountPercent">0</span><?php esc_html_e( '%)', 'hclr-direct-booking' ); ?></span>
                <span id="discountAmount">-$0.00</span>
            </div>
            <div class="price-row">
                <span><?php esc_html_e( 'Cleaning fee:', 'hclr-direct-booking' ); ?></span>
                <span id="cleaningFee">$0.00</span>
            </div>
            <div class="price-row">
                <span><?php esc_html_e( 'Service fee:', 'hclr-direct-booking' ); ?></span>
                <span id="serviceFee">$0.00</span>
            </div>
            <div class="price-row total">
                <span><?php esc_html_e( 'Total:', 'hclr-direct-booking' ); ?></span>
                <span id="totalPrice">$0.00</span>
            </div>
        </div>
        <button type="button" class="btn-reserve" id="btnReserve"
                data-property-id="<?php echo esc_attr( $property_id ); ?>">
            <?php esc_html_e( 'Continue to Booking', 'hclr-direct-booking' ); ?>
        </button>
    </div>

    <!-- Legend -->
    <div class="calendar-legend" aria-label="<?php esc_attr_e( 'Calendar legend', 'hclr-direct-booking' ); ?>">
        <div class="legend-item">
            <span class="legend-color available" aria-hidden="true"></span>
            <span><?php esc_html_e( 'Available', 'hclr-direct-booking' ); ?></span>
        </div>
        <div class="legend-item">
            <span class="legend-color selected" aria-hidden="true"></span>
            <span><?php esc_html_e( 'Selected', 'hclr-direct-booking' ); ?></span>
        </div>
        <div class="legend-item">
            <span class="legend-color unavailable" aria-hidden="true"></span>
            <span><?php esc_html_e( 'Unavailable', 'hclr-direct-booking' ); ?></span>
        </div>
    </div>

    <!-- Loading overlay -->
    <div class="calendar-loading" style="display:none;" aria-label="<?php esc_attr_e( 'Loading calendar', 'hclr-direct-booking' ); ?>">
        <div class="spinner" aria-hidden="true"></div>
        <p><?php esc_html_e( 'Loading calendar…', 'hclr-direct-booking' ); ?></p>
    </div>
</div>
