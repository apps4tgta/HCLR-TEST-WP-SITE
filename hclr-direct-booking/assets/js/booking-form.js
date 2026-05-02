/**
 * HCLR Booking Form — JavaScript
 * Handles form submission, real-time pricing, validation, and success/error states.
 *
 * Depends on: hclr_data (window object set by wp_localize_script)
 */

/* global hclr_data */

(function () {
    'use strict';

    /**
     * Initialise all booking forms on the page.
     */
    function initBookingForms() {
        document.querySelectorAll('#hclr-booking-form').forEach(form => {
            new HCLRBookingForm(form);
        });
    }

    class HCLRBookingForm {

        constructor(form) {
            this.form        = form;
            this.propertyId  = parseInt(form.dataset.propertyId, 10);
            this.priceTimer  = null;

            this._bindEvents();
            this._recalcFromURLParams();
        }

        _bindEvents() {
            // Date / guest changes trigger re-price
            ['check_in', 'check_out', 'guests'].forEach(name => {
                const el = this.form.querySelector(`[name="${name}"]`);
                if (el) el.addEventListener('change', () => this._schedulePriceUpdate());
            });

            // Form submission
            this.form.addEventListener('submit', e => {
                e.preventDefault();
                this._submitBooking();
            });
        }

        /** Pre-fill dates from URL params if present */
        _recalcFromURLParams() {
            const params = new URLSearchParams(window.location.search);
            const ci = params.get('check_in');
            const co = params.get('check_out');
            if (ci || co) {
                const ciEl = this.form.querySelector('[name="check_in"]');
                const coEl = this.form.querySelector('[name="check_out"]');
                if (ciEl && ci) ciEl.value = ci;
                if (coEl && co) coEl.value = co;
                this._schedulePriceUpdate();
            }
        }

        _schedulePriceUpdate() {
            clearTimeout(this.priceTimer);
            this.priceTimer = setTimeout(() => this._fetchPrice(), 400);
        }

        async _fetchPrice() {
            const checkIn  = this._val('check_in');
            const checkOut = this._val('check_out');
            const guests   = parseInt(this._val('guests') || '1', 10);

            if (!checkIn || !checkOut) return;

            this._setPriceLoading(true);

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
                        guests:      guests,
                    }),
                });

                const data = await res.json();

                if (!res.ok) {
                    this._showPriceError(data.message || 'Unable to calculate price.');
                    return;
                }

                this._renderPriceBreakdown(data);
            } catch (e) {
                this._showPriceError('Network error. Please try again.');
            } finally {
                this._setPriceLoading(false);
            }
        }

        _renderPriceBreakdown(pricing) {
            const section = this.form.querySelector('.hclr-price-breakdown');
            if (!section) return;

            const nights     = parseInt(pricing.nights ?? 0, 10);
            const subtotal   = parseFloat(pricing.nightly_total  ?? pricing.subtotal  ?? 0);
            const cleaning   = parseFloat(pricing.cleaning_fee   ?? pricing.cleaningFee ?? 0);
            const service    = parseFloat(pricing.service_fee    ?? pricing.serviceFee  ?? 0);
            const total      = parseFloat(pricing.total          ?? 0);
            const discount   = parseFloat(pricing.discount       ?? 0);

            this._setText(section, '.price-nights-label', `${nights} night${nights !== 1 ? 's' : ''}`);
            this._setText(section, '.price-subtotal',     `$${subtotal.toFixed(2)}`);
            this._setText(section, '.price-cleaning',     `$${cleaning.toFixed(2)}`);
            this._setText(section, '.price-service',      `$${service.toFixed(2)}`);
            this._setText(section, '.price-total',        `$${total.toFixed(2)}`);

            const discRow = section.querySelector('.price-discount-row');
            if (discRow) {
                if (discount > 0) {
                    const discAmt = subtotal * (discount / 100);
                    this._setText(section, '.price-discount-pct',    `${Math.round(discount)}%`);
                    this._setText(section, '.price-discount-amount', `-$${discAmt.toFixed(2)}`);
                    discRow.style.display = 'flex';
                } else {
                    discRow.style.display = 'none';
                }
            }

            section.style.display = 'block';

            // Mirror total into hidden input for confirmation page
            const totalInput = this.form.querySelector('[name="quoted_total"]');
            if (totalInput) totalInput.value = total.toFixed(2);
        }

        async _submitBooking() {
            if (!this._validateForm()) return;

            const submitBtn = this.form.querySelector('[type="submit"]');
            const msgEl     = this.form.querySelector('.hclr-booking-message');

            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            submitBtn.textContent = 'Processing…';

            const payload = {
                property_id:      this.propertyId,
                check_in:         this._val('check_in'),
                check_out:        this._val('check_out'),
                guests:           parseInt(this._val('guests') || '1', 10),
                first_name:       this._val('first_name'),
                last_name:        this._val('last_name'),
                email:            this._val('email'),
                phone:            this._val('phone'),
                special_requests: this._val('special_requests'),
                nonce:            hclr_data.booking_nonce,
            };

            try {
                const res = await fetch(`${hclr_data.rest_url}/booking`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce':   hclr_data.nonce,
                    },
                    body: JSON.stringify(payload),
                });

                const data = await res.json();

                if (!res.ok) {
                    this._showMessage(msgEl, 'error', data.message || 'Booking failed. Please try again.');
                    return;
                }

                // Success — redirect to OwnerRez hosted payment form.
                // OwnerRez collects card details and confirms the booking there.
                if (data.payment_form) {
                    window.location.href = data.payment_form;
                    return;
                }
                // Fallback: redirect to local confirmation page.
                const confirmUrl = new URL(window.location.href);
                if (hclr_data.confirm_url) {
                    confirmUrl.href = hclr_data.confirm_url;
                } else {
                    confirmUrl.pathname = '/booking-confirmation/';
                }
                confirmUrl.searchParams.set('booking_id', data.booking_id);
                confirmUrl.searchParams.set('check_in',   data.check_in);
                confirmUrl.searchParams.set('check_out',  data.check_out);
                confirmUrl.searchParams.set('total',      data.total);
                window.location.href = confirmUrl.toString();

            } catch (e) {
                this._showMessage(msgEl, 'error', 'Network error. Please check your connection and try again.');
            } finally {
                submitBtn.disabled  = false;
                submitBtn.classList.remove('loading');
                submitBtn.textContent = 'Complete Booking';
            }
        }

        _validateForm() {
            let valid = true;
            this.form.querySelectorAll('[required]').forEach(el => {
                const errEl = el.parentElement.querySelector('.hclr-field-error');
                if (!el.value.trim()) {
                    if (errEl) { errEl.textContent = 'This field is required.'; errEl.style.display = 'block'; }
                    el.style.borderColor = '#c0392b';
                    valid = false;
                } else {
                    if (errEl) errEl.style.display = 'none';
                    el.style.borderColor = '';
                }
            });

            // Email check
            const emailEl = this.form.querySelector('[name="email"]');
            if (emailEl && emailEl.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailEl.value)) {
                const err = emailEl.parentElement.querySelector('.hclr-field-error');
                if (err) { err.textContent = 'Enter a valid email address.'; err.style.display = 'block'; }
                emailEl.style.borderColor = '#c0392b';
                valid = false;
            }

            // Terms
            const terms = this.form.querySelector('[name="terms"]');
            if (terms && !terms.checked) {
                alert('Please accept the terms and conditions.');
                valid = false;
            }

            return valid;
        }

        _setPriceLoading(loading) {
            const section = this.form.querySelector('.hclr-price-breakdown');
            if (!section) return;
            const spinner = section.querySelector('.price-loading');
            if (spinner) spinner.style.display = loading ? 'block' : 'none';
        }

        _showPriceError(msg) {
            const section = this.form.querySelector('.hclr-price-breakdown');
            if (!section) return;
            const err = section.querySelector('.price-error');
            if (err) { err.textContent = msg; err.style.display = 'block'; }
            section.style.display = 'block';
        }

        _showMessage(el, type, msg) {
            if (!el) return;
            el.className       = `hclr-booking-message ${type}`;
            el.textContent     = msg;
            el.style.display   = 'block';
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        _val(name) {
            const el = this.form.querySelector(`[name="${name}"]`);
            return el ? el.value.trim() : '';
        }

        _setText(parent, selector, text) {
            const el = parent.querySelector(selector);
            if (el) el.textContent = text;
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBookingForms);
    } else {
        initBookingForms();
    }
})();
