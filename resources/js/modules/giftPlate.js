/**
 * Alpine.js component — Gift Plate modal.
 *
 * Responsibilities:
 *  - Grid view: browse available platform gifts
 *  - Detail view: show selected gift, wallet balance, send / pay actions
 *  - External trigger: responds to the 'open-gift-detail' window event
 *    so sidebar buttons outside the component can open it directly on a gift
 */
export function giftPlate(config) {
    return {
        // ── state ──────────────────────────────────────────────────────────

        view:            'grid',   // 'grid' | 'detail'
        selected:        null,
        loading:         false,
        error:           '',
        success:         '',
        giftMessage:     '',

        // hydrated from Blade config object
        walletBalance:   config.walletBalance,
        visitorCurrency: config.visitorCurrency,
        visitorSymbol:   config.visitorSymbol,
        isAuthenticated: config.isAuthenticated,
        celebrationId:   config.celebrationId,
        sendUrl:         config.sendUrl,
        payUrl:          config.payUrl,
        csrfToken:       config.csrfToken,

        // ── navigation ─────────────────────────────────────────────────────

        /** Select a gift and navigate to the detail panel. */
        selectGift(gift) {
            this.selected    = gift;
            this.view        = 'detail';
            this.error       = '';
            this.success     = '';
            this.giftMessage = '';
        },

        /**
         * Open the modal straight onto a gift's detail panel.
         * Called by external elements (e.g. cover-photo sidebar buttons)
         * via the 'open-gift-detail' window event.
         */
        openFromExternal(gift) {
            this.selectGift(gift);

            // Wait for Alpine to update the DOM, then open the modal
            this.$nextTick(() => {
                window.dispatchEvent(
                    new CustomEvent('open-modal', { detail: 'show-gifts' })
                );
            });
        },

        /** Return to the gift grid. */
        back() {
            this.view     = 'grid';
            this.selected = null;
            this.error    = '';
            this.success  = '';
        },

        // ── helpers ────────────────────────────────────────────────────────

        /** True when the wallet balance covers the selected gift's price. */
        hasSufficientBalance() {
            return this.selected && this.walletBalance >= this.selected.price;
        },

        /** Format a number with 2 decimal places and locale thousand separators. */
        formatNum(n) {
            return Number(n).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },

        // ── actions ────────────────────────────────────────────────────────

        /** Debit the user's wallet and mark the gift as sent. */
        async sendFromWallet() {
            this.loading = true;
            this.error   = '';
            this.success = '';

            try {
                const res  = await fetch(this.sendUrl, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({
                        platform_gift_id: this.selected.id,
                        celebration_id:   this.celebrationId,
                        message:          this.giftMessage,
                    }),
                });

                const data = await res.json();

                if (data.success) {
                    this.success = data.message;
                    // Strip currency symbols and re-parse the new balance
                    this.walletBalance = parseFloat(
                        data.new_balance.replace(/[^0-9.]/g, '')
                    );
                } else {
                    this.error = data.message || 'Something went wrong.';
                }
            } catch {
                this.error = 'Network error. Please try again.';
            } finally {
                this.loading = false;
            }
        },

        /** Initialise a Paystack payment for the selected gift. */
        async initiatePayment() {
            this.loading = true;
            this.error   = '';
            this.success = '';

            try {
                const res  = await fetch(this.payUrl, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({
                        platform_gift_id: this.selected.id,
                        celebration_id:   this.celebrationId,
                        message:          this.giftMessage,
                    }),
                });

                const data = await res.json();

                if (data.success && data.authorization_url) {
                    // Hand off to Paystack checkout
                    window.location.href = data.authorization_url;
                } else {
                    this.error = data.message || 'Could not start payment.';
                }
            } catch {
                this.error = 'Network error. Please try again.';
            } finally {
                this.loading = false;
            }
        },
    };
}

// Expose on window so Alpine templates can reference it without an import
window.giftPlate = giftPlate;
