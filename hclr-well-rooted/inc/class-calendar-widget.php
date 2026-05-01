<?php
/**
 * HCLR Calendar Widget
 *
 * Renders the interactive availability calendar HTML for a property.
 * JavaScript initialization is handled by assets/js/main.js.
 * Data is fetched from the hclr/v1/calendar REST endpoint (plugin).
 *
 * @package HCLR\WellRooted
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class HCLR_Calendar_Widget
 */
class HCLR_Calendar_Widget {

    /**
     * Render the calendar widget HTML for a given property.
     *
     * @param int $property_id OwnerRez property ID.
     * @return string HTML markup.
     */
    public static function render( int $property_id ): string {
        if ( ! $property_id ) {
            return '';
        }

        // Nonce and URLs are also injected via wp_localize_script('hclr-theme-js','hclr_theme',...)
        // but we embed them on the element too for safety (plugin shortcode context).
        $nonce       = wp_create_nonce( 'wp_rest' );
        $booking_url = get_page_link( get_page_by_path( 'booking' ) ) ?: home_url( '/booking/' );

        ob_start();
        ?>
        <div class="hclr-calendar-widget"
             data-property-id="<?php echo esc_attr( $property_id ); ?>"
             data-nonce="<?php echo esc_attr( $nonce ); ?>"
             data-rest-url="<?php echo esc_url( rest_url() ); ?>"
             data-booking-url="<?php echo esc_url( $booking_url ); ?>">

            <!-- Seasonal Message Banner -->
            <div class="hclr-cal__seasonal-msg" id="hclrSeasonalMsg" hidden aria-live="polite"></div>

            <!-- Month Navigation Header -->
            <div class="hclr-cal__header">
                <button class="hclr-cal__nav-btn hclr-cal__prev" aria-label="<?php esc_attr_e( 'Previous month', 'hclr-well-rooted' ); ?>">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M13 4L7 10L13 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <div class="hclr-cal__month-label" id="hclrMonthLabel" aria-live="polite">
                    <?php echo esc_html( date_i18n( 'F Y' ) ); ?>
                </div>

                <button class="hclr-cal__nav-btn hclr-cal__next" aria-label="<?php esc_attr_e( 'Next month', 'hclr-well-rooted' ); ?>">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M7 4L13 10L7 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <!-- Day of Week Headers -->
            <div class="hclr-cal__weekdays" aria-hidden="true">
                <?php
                $days = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );
                foreach ( $days as $day ) :
                ?>
                    <div class="hclr-cal__weekday"><?php echo esc_html( $day ); ?></div>
                <?php endforeach; ?>
            </div>

            <!-- Calendar Grid — populated by JS -->
            <div class="hclr-cal__grid" id="hclrCalGrid" role="grid"
                 aria-label="<?php esc_attr_e( 'Availability calendar', 'hclr-well-rooted' ); ?>">
                <!-- Loading placeholder -->
                <div class="hclr-cal__loading" id="hclrCalLoading">
                    <div class="hclr-cal__spinner" aria-hidden="true"></div>
                    <p><?php esc_html_e( 'Loading availability…', 'hclr-well-rooted' ); ?></p>
                </div>
            </div>

            <!-- Date Selection Summary -->
            <div class="hclr-cal__selection" id="hclrDateSelection" hidden>
                <div class="hclr-cal__selection-dates">
                    <div class="hclr-cal__selection-col">
                        <label><?php esc_html_e( 'Check-in', 'hclr-well-rooted' ); ?></label>
                        <span id="hclrCheckInDisplay">—</span>
                    </div>
                    <div class="hclr-cal__selection-arrow" aria-hidden="true">→</div>
                    <div class="hclr-cal__selection-col">
                        <label><?php esc_html_e( 'Check-out', 'hclr-well-rooted' ); ?></label>
                        <span id="hclrCheckOutDisplay">—</span>
                    </div>
                    <div class="hclr-cal__selection-col">
                        <label><?php esc_html_e( 'Nights', 'hclr-well-rooted' ); ?></label>
                        <span id="hclrNightsDisplay">—</span>
                    </div>
                </div>
            </div>

            <!-- Price Breakdown (shown after date selection) -->
            <div class="hclr-cal__pricing" id="hclrPricing" hidden>
                <h4 class="hclr-cal__pricing-title"><?php esc_html_e( 'Price Breakdown', 'hclr-well-rooted' ); ?></h4>
                <div class="hclr-cal__price-rows">
                    <div class="hclr-cal__price-row">
                        <span><?php esc_html_e( 'Nightly total', 'hclr-well-rooted' ); ?></span>
                        <span id="hclrNightlyTotal">—</span>
                    </div>
                    <div class="hclr-cal__price-row" id="hclrDiscountRow" hidden>
                        <span>
                            <?php esc_html_e( 'Discount', 'hclr-well-rooted' ); ?>
                            (<span id="hclrDiscountPct">0</span>%)
                        </span>
                        <span id="hclrDiscountAmt">—</span>
                    </div>
                    <div class="hclr-cal__price-row">
                        <span><?php esc_html_e( 'Cleaning fee', 'hclr-well-rooted' ); ?></span>
                        <span id="hclrCleaningFee">—</span>
                    </div>
                    <div class="hclr-cal__price-row">
                        <span><?php esc_html_e( 'Service fee', 'hclr-well-rooted' ); ?></span>
                        <span id="hclrServiceFee">—</span>
                    </div>
                    <div class="hclr-cal__price-row hclr-cal__price-row--total">
                        <span><?php esc_html_e( 'Total', 'hclr-well-rooted' ); ?></span>
                        <span id="hclrTotal">—</span>
                    </div>
                </div>
                <p class="hclr-cal__price-note" id="hclrPriceNote" hidden></p>
                <button class="btn btn--gold btn--full hclr-cal__reserve-btn" id="hclrReserveBtn"
                        data-property-id="<?php echo esc_attr( $property_id ); ?>">
                    <?php esc_html_e( 'Continue to Booking', 'hclr-well-rooted' ); ?>
                </button>
            </div>

            <!-- Price loading state -->
            <div class="hclr-cal__price-loading" id="hclrPriceLoading" hidden>
                <div class="hclr-cal__spinner hclr-cal__spinner--sm" aria-hidden="true"></div>
                <span><?php esc_html_e( 'Calculating price…', 'hclr-well-rooted' ); ?></span>
            </div>

            <!-- Error message -->
            <div class="hclr-cal__error" id="hclrCalError" hidden role="alert"></div>

            <!-- Legend -->
            <div class="hclr-cal__legend" aria-label="<?php esc_attr_e( 'Calendar legend', 'hclr-well-rooted' ); ?>">
                <div class="hclr-cal__legend-item">
                    <span class="hclr-cal__legend-dot hclr-cal__legend-dot--available" aria-hidden="true"></span>
                    <span><?php esc_html_e( 'Available', 'hclr-well-rooted' ); ?></span>
                </div>
                <div class="hclr-cal__legend-item">
                    <span class="hclr-cal__legend-dot hclr-cal__legend-dot--selected" aria-hidden="true"></span>
                    <span><?php esc_html_e( 'Selected', 'hclr-well-rooted' ); ?></span>
                </div>
                <div class="hclr-cal__legend-item">
                    <span class="hclr-cal__legend-dot hclr-cal__legend-dot--unavailable" aria-hidden="true"></span>
                    <span><?php esc_html_e( 'Unavailable', 'hclr-well-rooted' ); ?></span>
                </div>
            </div>

        </div><!-- .hclr-calendar-widget -->
        <?php
        return ob_get_clean();
    }
}
