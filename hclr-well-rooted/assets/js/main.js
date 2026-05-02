/**
 * HCLR Well-Rooted Theme — Main JavaScript
 *
 * Handles: sticky header, mobile nav, Swiper carousels,
 * amenity accordion, gallery lightbox, and the
 * OwnerRez calendar widget with live pricing.
 */

( function () {
    'use strict';

    /* ─────────────────────────────────────────────────
     * Utilities
     * ───────────────────────────────────────────────── */

    const $ = ( sel, ctx = document ) => ctx.querySelector( sel );
    const $$ = ( sel, ctx = document ) => Array.from( ctx.querySelectorAll( sel ) );
    const fmtDate = ( iso ) => {
        const [ y, m, d ] = iso.split( '-' );
        const dt = new Date( +y, +m - 1, +d );
        return dt.toLocaleDateString( 'en-US', { month: 'short', day: 'numeric', year: 'numeric' } );
    };
    const fmtPrice = ( n ) => '$' + Number( n ).toLocaleString( 'en-US', { maximumFractionDigits: 0 } );
    const pad2 = ( n ) => String( n ).padStart( 2, '0' );
    const toISO = ( y, m, d ) => `${ y }-${ pad2( m ) }-${ pad2( d ) }`;

    /* ─────────────────────────────────────────────────
     * Sticky Header
     * ───────────────────────────────────────────────── */
    function initStickyHeader() {
        const header = $( '.site-header' );
        if ( ! header ) return;

        const onScroll = () => header.classList.toggle( 'is-sticky', window.scrollY > 60 );
        window.addEventListener( 'scroll', onScroll, { passive: true } );
        onScroll();
    }

    /* ─────────────────────────────────────────────────
     * Mobile Navigation Hamburger
     * ───────────────────────────────────────────────── */
    function initMobileNav() {
        const btn    = $( '.nav-hamburger' );
        const drawer = $( '.mobile-nav-drawer' );
        if ( ! btn || ! drawer ) return;

        btn.addEventListener( 'click', () => {
            const open = btn.getAttribute( 'aria-expanded' ) === 'true';
            btn.setAttribute( 'aria-expanded', String( !open ) );
            drawer.classList.toggle( 'is-open', !open );
            document.body.classList.toggle( 'nav-open', !open );
        } );

        // Close on overlay click
        document.addEventListener( 'click', ( e ) => {
            if ( drawer.classList.contains( 'is-open' ) &&
                 ! drawer.contains( e.target ) &&
                 ! btn.contains( e.target ) ) {
                btn.setAttribute( 'aria-expanded', 'false' );
                drawer.classList.remove( 'is-open' );
                document.body.classList.remove( 'nav-open' );
            }
        } );

        // Close on Escape
        document.addEventListener( 'keydown', ( e ) => {
            if ( 'Escape' === e.key ) {
                btn.setAttribute( 'aria-expanded', 'false' );
                drawer.classList.remove( 'is-open' );
                document.body.classList.remove( 'nav-open' );
                btn.focus();
            }
        } );
    }

    /* ─────────────────────────────────────────────────
     * Swiper Carousels — Mobile-Optimized
     * Best practices: touch sensitivity, a11y, lazy
     * ───────────────────────────────────────────────── */
    function initSwipers() {
        if ( typeof Swiper === 'undefined' ) return;

        // Shared touch config for reliable cross-device behavior.
        const touchConfig = {
            threshold:                5,      // px of movement to trigger swipe
            touchRatio:               1,
            touchAngle:               45,     // allow ~45° diagonal swipes
            simulateTouch:            true,
            grabCursor:               true,
            allowTouchMove:           true,
            touchStartPreventDefault: false,  // don't block scroll on iOS
            passiveListeners:         true,   // smooth scrolling on Android
            longSwipesRatio:          0.4,
            longSwipesMs:             300,
            followFinger:             true,
            resistance:               true,
            resistanceRatio:          0.85,
        };

        // ── Home page hero ──
        const homeHero = $( '#home-hero-swiper' );
        if ( homeHero ) {
            new Swiper( '#home-hero-swiper', {
                ...touchConfig,
                loop:     true,
                autoplay: { delay: 5500, disableOnInteraction: true, pauseOnMouseEnter: true },
                speed:    600,
                effect:   'fade',
                fadeEffect: { crossFade: true },
                pagination: { el: homeHero.querySelector( '.swiper-pagination' ), clickable: true, type: 'bullets' },
                a11y:       { enabled: true },
                keyboard:   { enabled: true, onlyInViewport: true },
            } );
        }

        // ── Property hero ──
        const propHero = $( '#property-hero-swiper' );
        if ( propHero ) {
            const photoCount = parseInt( propHero.dataset.photoCount || '1', 10 );
            if ( photoCount <= 1 ) return; // nothing to swipe

            const propSwiper = new Swiper( '#property-hero-swiper', {
                ...touchConfig,
                loop:        true,
                speed:       450,
                loopAdditionalSlides: 2,
                watchSlidesProgress: true,  // enables lazy + preload
                navigation: {
                    nextEl:      propHero.querySelector( '.swiper-button-next' ),
                    prevEl:      propHero.querySelector( '.swiper-button-prev' ),
                    disabledClass: 'swiper-button-disabled',
                    hiddenClass:   'swiper-button-hidden',
                },
                pagination: {
                    el:        propHero.querySelector( '.swiper-pagination' ),
                    clickable: true,
                    type:      'bullets',
                    dynamicBullets: photoCount > 8,
                },
                a11y:     { enabled: true, prevSlideMessage: 'Previous photo', nextSlideMessage: 'Next photo' },
                keyboard: { enabled: true, onlyInViewport: true },
                preloadImages: false,
                lazy: {
                    loadPrevNext:       true,
                    loadPrevNextAmount: 1,
                },
                on: {
                    slideChange() {
                        const cur = propHero.querySelector( '.current-slide' );
                        if ( cur ) cur.textContent = this.realIndex + 1;
                    },
                    afterInit() {
                        const cur = propHero.querySelector( '.current-slide' );
                        if ( cur ) cur.textContent = 1;
                    },
                },
            } );
        }
    }

    /* ─────────────────────────────────────────────────
     * Amenity Accordion
     * ───────────────────────────────────────────────── */
    function initAccordion() {
        $$( '.amenity-group__toggle' ).forEach( ( btn ) => {
            btn.addEventListener( 'click', () => {
                const expanded = btn.getAttribute( 'aria-expanded' ) === 'true';
                const list     = $( '#' + btn.getAttribute( 'aria-controls' ) );
                btn.setAttribute( 'aria-expanded', String( !expanded ) );
                if ( list ) list.hidden = expanded;
            } );
        } );
    }

    /* ─────────────────────────────────────────────────
     * Gallery Lightbox (simple)
     * ───────────────────────────────────────────────── */
    function initGallery() {
        const links = $$( '.gallery-lightbox' );
        if ( ! links.length ) return;

        const overlay = document.createElement( 'div' );
        overlay.id = 'hclrLightbox';
        overlay.className = 'gallery-overlay';
        overlay.setAttribute( 'role', 'dialog' );
        overlay.setAttribute( 'aria-modal', 'true' );
        overlay.innerHTML = `
            <button class="gallery-overlay__close" aria-label="Close">&times;</button>
            <img class="gallery-overlay__img" src="" alt="" />
            <button class="gallery-overlay__prev" aria-label="Previous">&#8249;</button>
            <button class="gallery-overlay__next" aria-label="Next">&#8250;</button>
        `;
        document.body.appendChild( overlay );

        let current = 0;
        const imgs = links.map( l => l.href );

        const show = ( idx ) => {
            current = ( idx + imgs.length ) % imgs.length;
            overlay.querySelector( '.gallery-overlay__img' ).src = imgs[ current ];
            overlay.classList.add( 'is-open' );
            overlay.querySelector( '.gallery-overlay__close' ).focus();
        };

        links.forEach( ( link, i ) => link.addEventListener( 'click', ( e ) => { e.preventDefault(); show( i ); } ) );
        overlay.querySelector( '.gallery-overlay__close' ).addEventListener( 'click', () => overlay.classList.remove( 'is-open' ) );
        overlay.querySelector( '.gallery-overlay__prev' ).addEventListener( 'click', () => show( current - 1 ) );
        overlay.querySelector( '.gallery-overlay__next' ).addEventListener( 'click', () => show( current + 1 ) );
        overlay.addEventListener( 'click', ( e ) => { if ( e.target === overlay ) overlay.classList.remove( 'is-open' ); } );
        document.addEventListener( 'keydown', ( e ) => {
            if ( ! overlay.classList.contains( 'is-open' ) ) return;
            if ( 'ArrowLeft' === e.key ) show( current - 1 );
            if ( 'ArrowRight' === e.key ) show( current + 1 );
            if ( 'Escape' === e.key ) overlay.classList.remove( 'is-open' );
        } );
    }

    /* ─────────────────────────────────────────────────
     * Calendar Widget — OwnerRez Integration
     * ───────────────────────────────────────────────── */
    class HCLRCalendar {
        constructor( widget ) {
            this.widget      = widget;
            this.propertyId  = widget.dataset.propertyId;
            // Prefer global hclr_theme injected by wp_localize_script; fall back to data attrs.
            const g          = window.hclr_theme || {};
            this.nonce       = g.nonce       || widget.dataset.nonce       || '';
            this.restUrl     = g.rest_url    || widget.dataset.restUrl     || '/wp-json/';
            this.bookingUrl  = g.booking_url || widget.dataset.bookingUrl  || '/booking/';

            this.today       = new Date();
            this.today.setHours( 0, 0, 0, 0 );
            this.viewYear    = this.today.getFullYear();
            this.viewMonth   = this.today.getMonth() + 1; // 1-based

            this.availability = {};   // { "YYYY-MM-DD": { available, rate, min_stay } }
            this.loadedMonths = new Set(); // "YYYY-MM" cache keys

            this.checkIn     = null;  // ISO date string
            this.checkOut    = null;
            this.selecting   = 'checkin'; // 'checkin' | 'checkout'

            this._quoteTimer   = null;   // debounce handle
            this._quoteAbort   = null;   // AbortController for in-flight quote request

            // DOM refs
            this.grid      = widget.querySelector( '#hclrCalGrid' );
            this.monthLbl  = widget.querySelector( '#hclrMonthLabel' );
            this.prevBtn   = widget.querySelector( '.hclr-cal__prev' );
            this.nextBtn   = widget.querySelector( '.hclr-cal__next' );
            this.loading   = widget.querySelector( '#hclrCalLoading' );
            this.errEl     = widget.querySelector( '#hclrCalError' );
            this.selEl     = widget.querySelector( '#hclrDateSelection' );
            this.pricingEl = widget.querySelector( '#hclrPricing' );
            this.priceLdEl = widget.querySelector( '#hclrPriceLoading' );
            this.seaMsg    = widget.querySelector( '#hclrSeasonalMsg' );
            this.reserveBtn = widget.querySelector( '#hclrReserveBtn' );

            this.init();
        }

        init() {
            this.prevBtn.addEventListener( 'click', () => this.navigate( -1 ) );
            this.nextBtn.addEventListener( 'click', () => this.navigate( 1 ) );
            if ( this.reserveBtn ) {
                this.reserveBtn.addEventListener( 'click', () => this.handleReserve() );
            }
            this.loadMonthAndRender( this.viewYear, this.viewMonth );
        }

        navigate( delta ) {
            let m = this.viewMonth + delta;
            let y = this.viewYear;
            if ( m > 12 ) { m = 1; y++; }
            if ( m < 1  ) { m = 12; y--; }
            // Don't navigate to past months
            const min = new Date( this.today.getFullYear(), this.today.getMonth(), 1 );
            if ( new Date( y, m - 1, 1 ) < min ) return;
            this.viewYear  = y;
            this.viewMonth = m;
            this.loadMonthAndRender( y, m );
        }

        async loadMonthAndRender( year, month ) {
            const key = `${ year }-${ pad2( month ) }`;
            if ( ! this.loadedMonths.has( key ) ) {
                await this.fetchMonth( year, month );
            }
            this.renderGrid( year, month );
            this.updateMonthLabel( year, month );
        }

        async fetchMonth( year, month ) {
            this.showLoading( true );
            this.hideError();

            const key = `${ year }-${ pad2( month ) }`;
            const url = `${ this.restUrl }hclr/v1/calendar?property_id=${ this.propertyId }&year=${ year }&month=${ month }`;

            try {
                const resp = await fetch( url, {
                    headers: {
                        'X-WP-Nonce': this.nonce,
                        'Accept': 'application/json',
                    },
                } );

                if ( ! resp.ok ) {
                    throw new Error( `HTTP ${ resp.status }` );
                }

                const data = await resp.json();
                const days = data.days || {};

                // Merge into local availability store
                Object.assign( this.availability, days );
                this.loadedMonths.add( key );

            } catch ( err ) {
                this.showError( 'Unable to load calendar. Please refresh and try again.' );
                console.error( '[HCLR Calendar]', err );
            } finally {
                this.showLoading( false );
            }
        }

        renderGrid( year, month ) {
            // Clear previous day cells but keep loading/error elements
            this.grid.querySelectorAll( '.hclr-cal__day' ).forEach( el => el.remove() );

            const firstDay = new Date( year, month - 1, 1 ).getDay(); // 0=Sun
            const daysInMonth = new Date( year, month, 0 ).getDate();

            // Empty cells for offset
            for ( let i = 0; i < firstDay; i++ ) {
                const empty = document.createElement( 'div' );
                empty.className = 'hclr-cal__day hclr-cal__day--empty';
                empty.setAttribute( 'aria-hidden', 'true' );
                this.grid.appendChild( empty );
            }

            for ( let d = 1; d <= daysInMonth; d++ ) {
                const iso  = toISO( year, month, d );
                const info = this.availability[ iso ] || { available: null, rate: 0, min_stay: 1 };
                const date = new Date( year, month - 1, d );
                const isPast = date < this.today;

                const cell = document.createElement( 'button' );
                cell.type = 'button';
                cell.className = 'hclr-cal__day';
                cell.dataset.date = iso;

                const dayNum = document.createElement( 'span' );
                dayNum.className = 'hclr-cal__day-num';
                dayNum.textContent = d;
                cell.appendChild( dayNum );

                if ( info.rate > 0 ) {
                    const rateEl = document.createElement( 'span' );
                    rateEl.className = 'hclr-cal__day-rate';
                    rateEl.textContent = fmtPrice( info.rate );
                    cell.appendChild( rateEl );
                }

                // Apply states
                if ( isPast ) {
                    cell.classList.add( 'is-past' );
                    cell.disabled = true;
                    cell.setAttribute( 'aria-disabled', 'true' );
                } else if ( info.available === false ) {
                    cell.classList.add( 'is-unavailable' );
                    cell.disabled = true;
                    cell.setAttribute( 'aria-label', `${ iso } - Unavailable` );
                } else if ( info.available === null && this.loadedMonths.has( `${ year }-${ pad2( month ) }` ) ) {
                    // Month loaded but no data for this date → treat as unavailable
                    cell.classList.add( 'is-unavailable' );
                    cell.disabled = true;
                } else {
                    cell.classList.add( 'is-available' );
                    cell.setAttribute( 'aria-label', `${ fmtDate( iso ) }${ info.rate ? ' - ' + fmtPrice( info.rate ) + '/night' : '' }` );
                    cell.addEventListener( 'click', () => this.onDayClick( iso ) );
                }

                // Mark today
                if ( iso === toISO( this.today.getFullYear(), this.today.getMonth() + 1, this.today.getDate() ) ) {
                    cell.classList.add( 'is-today' );
                }

                // Mark selected range
                this.applySelectionClasses( cell, iso );

                this.grid.appendChild( cell );
            }
        }

        applySelectionClasses( cell, iso ) {
            if ( this.checkIn && iso === this.checkIn )  cell.classList.add( 'is-checkin' );
            if ( this.checkOut && iso === this.checkOut ) cell.classList.add( 'is-checkout' );
            if ( this.checkIn && this.checkOut && iso > this.checkIn && iso < this.checkOut ) {
                cell.classList.add( 'is-in-range' );
            }
        }

        onDayClick( iso ) {
            if ( this.selecting === 'checkin' || ( this.checkIn && iso <= this.checkIn ) ) {
                // Start a new selection
                this.checkIn    = iso;
                this.checkOut   = null;
                this.selecting  = 'checkout';
                this.hidePricing();
                this.updateSelection();
                this.rerenderSelectionClasses();
                this.updateSeasonalMessage( iso );
            } else {
                // Complete the selection
                this.checkOut  = iso;
                this.selecting = 'checkin';
                this.updateSelection();
                this.rerenderSelectionClasses();
                this.fetchQuote();
            }
        }

        rerenderSelectionClasses() {
            this.grid.querySelectorAll( '.hclr-cal__day[data-date]' ).forEach( cell => {
                cell.classList.remove( 'is-checkin', 'is-checkout', 'is-in-range' );
                this.applySelectionClasses( cell, cell.dataset.date );
            } );
        }

        updateSelection() {
            if ( ! this.selEl ) return;
            this.selEl.hidden = !( this.checkIn );

            const ci = this.widget.querySelector( '#hclrCheckInDisplay' );
            const co = this.widget.querySelector( '#hclrCheckOutDisplay' );
            const ni = this.widget.querySelector( '#hclrNightsDisplay' );

            if ( ci ) ci.textContent = this.checkIn ? fmtDate( this.checkIn ) : '—';
            if ( co ) co.textContent = this.checkOut ? fmtDate( this.checkOut ) : '—';

            if ( ni && this.checkIn && this.checkOut ) {
                const diff = ( new Date( this.checkOut ) - new Date( this.checkIn ) ) / 86400000;
                ni.textContent = diff > 0 ? `${ diff } night${ diff !== 1 ? 's' : '' }` : '—';
            } else if ( ni ) {
                ni.textContent = '—';
            }
        }

        fetchQuote() {
            if ( ! this.checkIn || ! this.checkOut ) return;

            // Cancel any previous debounce and in-flight request.
            clearTimeout( this._quoteTimer );
            if ( this._quoteAbort ) {
                this._quoteAbort.abort();
                this._quoteAbort = null;
            }

            this.hidePricing();
            if ( this.priceLdEl ) this.priceLdEl.hidden = false;

            // Debounce: wait 600 ms after the last date click before hitting the API.
            this._quoteTimer = setTimeout( () => this._doFetchQuote(), 600 );
        }

        async _doFetchQuote() {
            if ( ! this.checkIn || ! this.checkOut ) return;

            this._quoteAbort = new AbortController();

            const url  = `${ this.restUrl }hclr/v1/quote`;
            const body = JSON.stringify( {
                property_id: parseInt( this.propertyId, 10 ),
                check_in:    this.checkIn,
                check_out:   this.checkOut,
                guests:      1,
            } );

            try {
                const resp = await fetch( url, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce':   this.nonce,
                    },
                    body,
                    signal: this._quoteAbort.signal,
                } );

                if ( ! resp.ok ) {
                    const err = await resp.json().catch( () => ( {} ) );
                    throw new Error( err.message || `HTTP ${ resp.status }` );
                }

                const data = await resp.json();
                this.renderPricing( data );

            } catch ( err ) {
                if ( err.name === 'AbortError' ) return; // superseded by newer selection
                const note = this.widget.querySelector( '#hclrPriceNote' );
                if ( note ) {
                    note.textContent = 'Pricing unavailable. Contact us for a quote.';
                    note.hidden = false;
                }
                if ( this.pricingEl ) this.pricingEl.hidden = false;
                console.error( '[HCLR Quote]', err );
            } finally {
                if ( this.priceLdEl ) this.priceLdEl.hidden = true;
                this._quoteAbort = null;
            }
        }

        renderPricing( data ) {
            if ( ! this.pricingEl ) return;

            const set = ( id, val ) => {
                const el = this.widget.querySelector( `#${ id }` );
                if ( el ) el.textContent = val;
            };

            const nights       = data.nights        || data.num_nights       || 0;
            const nightlyTotal = data.subtotal       || data.nightly_total    || 0;
            const discount     = data.discount_pct   || data.discount         || 0;
            const discountAmt  = data.discount_amount || 0;
            const cleaning     = data.cleaning_fee   || 0;
            const service      = data.service_fee    || 0;
            const total        = data.total          || data.total_price      || 0;

            set( 'hclrNightsDisplay', nights + ( nights === 1 ? ' night' : ' nights' ) );
            set( 'hclrNightlyTotal', fmtPrice( nightlyTotal ) );
            set( 'hclrCleaningFee',  fmtPrice( cleaning ) );
            set( 'hclrServiceFee',   fmtPrice( service ) );
            set( 'hclrTotal',        fmtPrice( total ) );

            const discRow = this.widget.querySelector( '#hclrDiscountRow' );
            if ( discRow ) {
                discRow.hidden = discount <= 0;
                if ( discount > 0 ) {
                    set( 'hclrDiscountPct', discount );
                    set( 'hclrDiscountAmt', '−' + fmtPrice( discountAmt ) );
                }
            }

            this.pricingEl.hidden = false;

            // Scroll into view on mobile
            if ( window.innerWidth < 768 ) {
                this.pricingEl.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
            }
        }

        hidePricing() {
            if ( this.pricingEl ) this.pricingEl.hidden = true;
            const note = this.widget.querySelector( '#hclrPriceNote' );
            if ( note ) note.hidden = true;
        }

        handleReserve() {
            if ( ! this.checkIn || ! this.checkOut ) return;
            const params = new URLSearchParams( {
                property_id: this.propertyId,
                check_in:    this.checkIn,
                check_out:   this.checkOut,
            } );

            // Try to fill in-page booking form first
            const form = document.querySelector( '.hclr-booking-form' );
            if ( form ) {
                const ci = form.querySelector( '[name="check_in"]' );
                const co = form.querySelector( '[name="check_out"]' );
                if ( ci ) ci.value = this.checkIn;
                if ( co ) co.value = this.checkOut;
                form.scrollIntoView( { behavior: 'smooth' } );
                return;
            }

            window.location.href = this.bookingUrl + '?' + params.toString();
        }

        updateSeasonalMessage( iso ) {
            if ( ! this.seaMsg ) return;
            const month = parseInt( iso.split( '-' )[ 1 ], 10 );
            const msgs = {
                peak:     'Peak season — book early for best availability!',
                shoulder: 'Great time to visit — shoulder season rates available.',
                off:      'Off-peak — enjoy our best rates of the year.',
            };
            const season = month >= 6 && month <= 8 ? 'peak'
                         : month >= 3 && month <= 5  ? 'shoulder'
                         : month >= 9 && month <= 11 ? 'shoulder'
                         : 'off';
            this.seaMsg.textContent = msgs[ season ];
            this.seaMsg.hidden = false;
            this.seaMsg.className = `hclr-cal__seasonal-msg hclr-cal__seasonal-msg--${ season }`;
        }

        showLoading( show ) {
            if ( this.loading ) this.loading.hidden = ! show;
        }
        showError( msg ) {
            if ( this.errEl ) {
                this.errEl.textContent = msg;
                this.errEl.hidden = false;
            }
        }
        hideError() {
            if ( this.errEl ) this.errEl.hidden = true;
        }
        updateMonthLabel( year, month ) {
            if ( ! this.monthLbl ) return;
            const dt = new Date( year, month - 1, 1 );
            this.monthLbl.textContent = dt.toLocaleDateString( 'en-US', { month: 'long', year: 'numeric' } );
        }
    }

    function initCalendarWidgets() {
        $$( '.hclr-calendar-widget' ).forEach( ( widget ) => {
            if ( widget.dataset.initialized ) return;
            widget.dataset.initialized = '1';
            new HCLRCalendar( widget );
        } );
    }

    /* ─────────────────────────────────────────────────
     * Smooth Scroll for anchor links
     * ───────────────────────────────────────────────── */
    function initSmoothScroll() {
        $$( 'a[href^="#"]' ).forEach( link => {
            link.addEventListener( 'click', ( e ) => {
                const target = document.getElementById( link.getAttribute( 'href' ).slice( 1 ) );
                if ( target ) {
                    e.preventDefault();
                    target.scrollIntoView( { behavior: 'smooth' } );
                }
            } );
        } );
    }

    /* ─────────────────────────────────────────────────
     * Back to Top Button
     * ───────────────────────────────────────────────── */
    function initBackToTop() {
        const btn = $( '.back-to-top' );
        if ( ! btn ) return;
        window.addEventListener( 'scroll', () => btn.classList.toggle( 'is-visible', window.scrollY > 400 ), { passive: true } );
        btn.addEventListener( 'click', () => window.scrollTo( { top: 0, behavior: 'smooth' } ) );
    }

    /* ─────────────────────────────────────────────────
     * Boot
     * ───────────────────────────────────────────────── */
    document.addEventListener( 'DOMContentLoaded', () => {
        initStickyHeader();
        initMobileNav();
        initSwipers();
        initAccordion();
        initGallery();
        initCalendarWidgets();
        initSmoothScroll();
        initBackToTop();
    } );

} )();
