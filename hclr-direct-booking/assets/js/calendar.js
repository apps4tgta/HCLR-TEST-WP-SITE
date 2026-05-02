/**
 * HCLR Calendar Widget — JavaScript
 * Handles availability calendar, date selection, seasonal colors, and pricing.
 *
 * Uses: window.hclr_data (set via wp_localize_script)
 *   - hclr_data.rest_url
 *   - hclr_data.nonce  (wp_rest nonce)
 *   - hclr_data.booking_nonce
 */

/* global hclr_data */

class HCLRCalendarWidget {

    /**
     * @param {HTMLElement} element The .hclr-calendar-widget DOM node.
     */
    constructor(element) {
        this.widget     = element;
        this.propertyId = parseInt(element.dataset.propertyId, 10);
        this.currentDate = new Date();
        this.selectedDates = { checkIn: null, checkOut: null };
        this.calendarData  = null;
        this.minNights     = 1;

        /** Seasonal color palettes */
        this.seasons = {
            dusk_rose: {
                months: [10, 11, 12, 1, 2, 3],
                primary: '#9C7060', secondary: '#6E7E60', accent: '#C8A870',
                background: '#FDF7F0', label: 'Cozy Season',
                message: 'Peak comfort season in Hill Country', icon: '🍂',
            },
            bluebell: {
                months: [6, 7, 8],
                primary: '#7A8EA0', secondary: '#4A5E40', accent: '#C8A870',
                background: '#F0F4F8', label: 'Lake Season',
                message: 'Perfect for water activities & summer fun', icon: '💧',
            },
            cedar_sage: {
                months: [4, 5, 9],
                primary: '#6E7E60', secondary: '#4A5E40', accent: '#C8A870',
                background: '#F2EBD8', label: 'Shoulder Season',
                message: 'Great rates & comfortable weather', icon: '🌿',
            },
        };

        this.init();
    }

    init() {
        this._bindNav();
        this.loadCalendar();
    }

    /** Wire up prev/next month buttons */
    _bindNav() {
        const prev = this.widget.querySelector('.btn-prev');
        const next = this.widget.querySelector('.btn-next');
        if (prev) prev.addEventListener('click', () => this.previousMonth());
        if (next) next.addEventListener('click', () => this.nextMonth());
    }

    /** Get season key for a date string */
    detectSeason(dateString) {
        const month = new Date(dateString + 'T00:00:00').getMonth() + 1;
        for (const [key, s] of Object.entries(this.seasons)) {
            if (s.months.includes(month)) return key;
        }
        return 'cedar_sage';
    }

    /** Dominant season based on midpoint of selected range */
    getDominantSeason() {
        const { checkIn, checkOut } = this.selectedDates;
        if (!checkIn || !checkOut) return null;
        const mid = new Date((new Date(checkIn + 'T00:00:00').getTime() + new Date(checkOut + 'T00:00:00').getTime()) / 2);
        const y = mid.getFullYear(), m = String(mid.getMonth() + 1).padStart(2, '0'), d = String(mid.getDate()).padStart(2, '0');
        return this.detectSeason(`${y}-${m}-${d}`);
    }

    applySeasonalColors(seasonKey) {
        if (!seasonKey) { this.resetSeasonalColors(); return; }
        const s = this.seasons[seasonKey];
        const root = document.documentElement;
        root.style.setProperty('--hclr-seasonal-primary',    s.primary);
        root.style.setProperty('--hclr-seasonal-secondary',  s.secondary);
        root.style.setProperty('--hclr-seasonal-accent',     s.accent);
        root.style.setProperty('--hclr-seasonal-background', s.background);
        this._updateBanner(seasonKey, s);
    }

    resetSeasonalColors() {
        const root = document.documentElement;
        root.style.setProperty('--hclr-seasonal-primary',    'var(--wr-deep-oak, #2E3C28)');
        root.style.setProperty('--hclr-seasonal-secondary',  'var(--wr-live-oak, #4A5E40)');
        root.style.setProperty('--hclr-seasonal-accent',     'var(--wr-caliche-gold, #C8A870)');
        root.style.setProperty('--hclr-seasonal-background', 'var(--wr-parchment, #F2EBD8)');
        const banner = this.widget.querySelector('.seasonal-message-banner');
        if (banner) banner.style.display = 'none';
    }

    _updateBanner(seasonKey, s) {
        let banner = this.widget.querySelector('.seasonal-message-banner');
        if (!banner) return;
        banner.innerHTML = `
            <div class="seasonal-message-content">
                <span class="seasonal-icon">${s.icon}</span>
                <div class="seasonal-text">
                    <strong>${s.label}</strong>
                    <p>${s.message}</p>
                </div>
            </div>`;
        banner.style.backgroundColor  = s.background;
        banner.style.borderLeftColor  = s.primary;
        banner.style.display          = 'block';
    }

    async loadCalendar() {
        this.showLoading(true);
        const year  = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth() + 1;

        // Build a 2-month window for availability check
        const startDate = `${year}-${String(month).padStart(2,'0')}-01`;
        const endDate   = new Date(year, month + 1, 0); // last day of next month
        const endStr    = `${endDate.getFullYear()}-${String(endDate.getMonth() + 1).padStart(2,'0')}-${String(endDate.getDate()).padStart(2,'0')}`;

        try {
            const url = `${hclr_data.rest_url}/availability?property_id=${this.propertyId}&check_in=${startDate}&check_out=${endStr}`;
            const res = await fetch(url, {
                headers: { 'X-WP-Nonce': hclr_data.nonce },
            });

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                this.showError(err.message || `Error ${res.status}`);
                return;
            }

            const data = await res.json();
            // Normalise: data may be { items: [...] } or { data: [...] } or flat array
            const items = Array.isArray(data) ? data : (data.items || data.data || []);

            // Build a lookup: { 'YYYY-MM-DD': { available: bool, rate: float } }
            this.calendarData = {};
            items.forEach(day => {
                const key = day.date || day.checkIn;
                if (!key) return;
                this.calendarData[key] = {
                    available:    !!(day.available ?? day.isAvailable ?? true),
                    nightly_rate: parseFloat(day.nightlyRate ?? day.nightly_rate ?? 0),
                };
            });

            this.renderCalendar();
        } catch (e) {
            console.error('[HCLR Calendar]', e);
            this.showError('Failed to load calendar. Please refresh.');
        } finally {
            this.showLoading(false);
        }
    }

    renderCalendar() {
        const year  = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();
        const firstDay     = new Date(year, month, 1).getDay();
        const daysInMonth  = new Date(year, month + 1, 0).getDate();
        const today        = new Date(); today.setHours(0,0,0,0);

        // Update header
        const header = this.widget.querySelector('#currentMonth');
        if (header) header.textContent = this.currentDate.toLocaleString('default', { month: 'long', year: 'numeric' });

        const grid = this.widget.querySelector('#calendarGrid');
        if (!grid) return;
        grid.innerHTML = '';

        // Empty leading cells
        for (let i = 0; i < firstDay; i++) {
            grid.appendChild(document.createElement('div'));
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            const btn     = document.createElement('button');
            btn.type      = 'button';
            btn.className = 'calendar-day';
            btn.textContent = day;
            btn.dataset.date = dateStr;

            const dateObj = new Date(dateStr + 'T00:00:00');
            const dayData = this.calendarData ? this.calendarData[dateStr] : null;
            const pastDay = dateObj < today;

            if (pastDay || (dayData && !dayData.available)) {
                btn.classList.add('unavailable');
                btn.disabled = true;
            } else {
                btn.classList.add('available');
                if (dayData?.nightly_rate) btn.title = `$${dayData.nightly_rate.toFixed(0)}/night`;
                btn.addEventListener('click', () => this.selectDate(dateStr));
            }

            if (dateStr === this.selectedDates.checkIn || dateStr === this.selectedDates.checkOut) {
                btn.classList.add('selected');
            } else if (this.selectedDates.checkIn && this.selectedDates.checkOut) {
                const ci = new Date(this.selectedDates.checkIn + 'T00:00:00');
                const co = new Date(this.selectedDates.checkOut + 'T00:00:00');
                if (dateObj > ci && dateObj < co) btn.classList.add('in-range');
            }

            // Mark today
            if (dateStr === today.toISOString().split('T')[0]) btn.classList.add('today');

            grid.appendChild(btn);
        }
    }

    selectDate(dateStr) {
        const { checkIn, checkOut } = this.selectedDates;

        if (!checkIn) {
            this.selectedDates.checkIn = dateStr;
            this.renderCalendar();
            this.applySeasonalColors(this.detectSeason(dateStr));
            return;
        }

        if (checkIn && !checkOut) {
            const ci  = new Date(checkIn + 'T00:00:00');
            const sel = new Date(dateStr + 'T00:00:00');

            if (sel.getTime() === ci.getTime()) {
                this.selectedDates.checkIn = null;
                this.renderCalendar();
                this.resetSeasonalColors();
                return;
            }

            if (sel < ci) {
                this.selectedDates.checkOut = checkIn;
                this.selectedDates.checkIn  = dateStr;
            } else {
                this.selectedDates.checkOut = dateStr;
            }

            this.renderCalendar();
            const season = this.getDominantSeason();
            if (season) this.applySeasonalColors(season); else this.resetSeasonalColors();
            this.fetchQuote();
            return;
        }

        // Both exist — restart
        this.selectedDates = { checkIn: dateStr, checkOut: null };
        this.renderCalendar();
        this.resetSeasonalColors();
    }

    async fetchQuote() {
        const { checkIn, checkOut } = this.selectedDates;
        if (!checkIn || !checkOut) return;

        this.showLoading(true);
        try {
            const res = await fetch(`${hclr_data.rest_url}/quote`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce':   hclr_data.nonce,
                },
                body: JSON.stringify({
                    property_id: this.propertyId,
                    check_in:    checkIn,
                    check_out:   checkOut,
                    guests:      1,
                }),
            });

            const result = await res.json();
            if (!res.ok) {
                console.warn('[HCLR Quote]', result.message);
                return;
            }

            this.displayPricing(result);
        } catch (e) {
            console.error('[HCLR Quote]', e);
        } finally {
            this.showLoading(false);
        }
    }

    displayPricing(pricing) {
        const section = this.widget.querySelector('#pricingSection');
        if (!section) return;

        const season  = this.getDominantSeason();
        const s       = this.seasons[season];

        const $ = id => section.querySelector('#' + id);

        // Handle both OwnerRez format and our calculated format
        const nightlyTotal  = parseFloat(pricing.nightly_total ?? pricing.subtotal ?? pricing.total ?? 0);
        const cleaningFee   = parseFloat(pricing.cleaning_fee  ?? pricing.cleaningFee ?? 0);
        const serviceFee    = parseFloat(pricing.service_fee   ?? pricing.serviceFee  ?? 0);
        const total         = parseFloat(pricing.total         ?? 0);
        const discount      = parseFloat(pricing.discount      ?? 0);
        const nights        = parseInt(pricing.nights          ?? 0, 10);

        if ($('nightlyTotal'))  $('nightlyTotal').textContent  = `$${nightlyTotal.toFixed(2)}`;
        if ($('cleaningFee'))   $('cleaningFee').textContent   = `$${cleaningFee.toFixed(2)}`;
        if ($('serviceFee'))    $('serviceFee').textContent    = `$${serviceFee.toFixed(2)}`;
        if ($('totalPrice'))    $('totalPrice').textContent    = `$${total.toFixed(2)}`;

        const discountRow = section.querySelector('#discountRow');
        if (discountRow) {
            if (discount > 0) {
                const discAmt = nightlyTotal * (discount / 100);
                const discPct = section.querySelector('#discountPercent');
                const discAmt2 = section.querySelector('#discountAmount');
                if (discPct) discPct.textContent = Math.round(discount);
                if (discAmt2) discAmt2.textContent = `-$${discAmt.toFixed(2)}`;
                discountRow.style.display = 'flex';
            } else {
                discountRow.style.display = 'none';
            }
        }

        // Season badge
        let badge = section.querySelector('.seasonal-pricing-note');
        if (!badge) {
            badge = document.createElement('div');
            badge.className = 'seasonal-pricing-note';
            const details = section.querySelector('.price-details');
            if (details) details.prepend(badge);
        }
        if (s && season !== 'cedar_sage') {
            badge.innerHTML = `<span class="season-badge" style="background:${s.primary};color:#fff">${s.icon} ${s.label}</span>`;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }

        // Reserve button — pass dates to booking form
        const btnReserve = section.querySelector('#btnReserve') || section.querySelector('.btn-reserve');
        if (btnReserve) {
            btnReserve.onclick = () => this.handleReserve();
        }

        section.style.display = 'block';
        section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    handleReserve() {
        const { checkIn, checkOut } = this.selectedDates;
        if (!checkIn || !checkOut) { alert('Please select check-in and check-out dates.'); return; }

        // Try to populate a booking form on the same page first
        const form = document.querySelector('#hclr-booking-form');
        if (form) {
            const ciInput = form.querySelector('[name="check_in"]');
            const coInput = form.querySelector('[name="check_out"]');
            if (ciInput) ciInput.value = checkIn;
            if (coInput) coInput.value = checkOut;
            form.scrollIntoView({ behavior: 'smooth' });
            return;
        }

        // Otherwise navigate to booking page
        const url = new URL(hclr_data.site_url + '/booking/');
        url.searchParams.set('property_id', this.propertyId);
        url.searchParams.set('check_in',    checkIn);
        url.searchParams.set('check_out',   checkOut);
        window.location.href = url.toString();
    }

    previousMonth() { this.currentDate.setMonth(this.currentDate.getMonth() - 1); this.loadCalendar(); }
    nextMonth()     { this.currentDate.setMonth(this.currentDate.getMonth() + 1); this.loadCalendar(); }

    showLoading(show) {
        const el = this.widget.querySelector('.calendar-loading');
        if (el) el.style.display = show ? 'flex' : 'none';
    }

    showError(msg) {
        const grid = this.widget.querySelector('#calendarGrid');
        if (grid) grid.innerHTML = `<div class="calendar-error">${msg}</div>`;
    }
}

/** Auto-initialise plugin calendar widgets (identified by #calendarGrid inside them).
 *  Theme-rendered widgets use #hclrCalGrid and are driven by main.js — skip those. */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.hclr-calendar-widget').forEach(el => {
        if (el.querySelector('#calendarGrid')) {
            new HCLRCalendarWidget(el);
        }
    });
});
