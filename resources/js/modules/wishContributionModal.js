/**
 * Alpine.js component — Wish Contribution Modal.
 *
 * Opens when any wish card dispatches the 'open-wish' window event.
 * Steps: detail → contribute → success
 */
export function wishContributionModal(config) {
    return {
        // ── state ──────────────────────────────────────────────────────────
        open:             false,
        step:             'detail',   // 'detail' | 'contribute' | 'success'
        loading:          false,
        error:            '',

        wish:             null,       // populated from the open-wish event
        amount:           '',
        note:             '',

        updatedProgress:  null,       // set after a successful contribution

        walletBalance:    config.walletBalance,
        visitorCurrency:  config.visitorCurrency,
        visitorSymbol:    config.visitorSymbol,
        isAuthenticated:  config.isAuthenticated,
        isOwner:          config.isOwner,
        walletBaseUrl:    config.walletBaseUrl,
        payBaseUrl:       config.payBaseUrl,
        csrfToken:        config.csrfToken,

        // ── derived ────────────────────────────────────────────────────────

        get progressPct() {
            const p = this.updatedProgress ?? this.wish;
            if (!p || !p.target || p.target <= 0) return 0;
            return Math.min(100, Math.round((p.current / p.target) * 100));
        },

        get displayCurrent() {
            return (this.updatedProgress ?? this.wish)?.current ?? 0;
        },

        get displayTarget() {
            return (this.updatedProgress ?? this.wish)?.target ?? 0;
        },

        get remaining() {
            return Math.max(0, this.displayTarget - this.displayCurrent);
        },

        hasSufficientBalance() {
            const n = parseFloat(this.amount);
            return !isNaN(n) && n > 0 && this.walletBalance >= n;
        },

        formatNum(n) {
            return Number(n).toLocaleString('en-US', {
                minimumFractionDigits:  2,
                maximumFractionDigits:  2,
            });
        },

        // ── lifecycle ──────────────────────────────────────────────────────

        openWish(wishData) {
            this.wish            = wishData;
            this.step            = 'detail';
            this.amount          = '';
            this.note            = '';
            this.error           = '';
            this.updatedProgress = null;
            this.open            = true;
        },

        close() {
            this.open = false;
        },

        goContribute() {
            this.error  = '';
            this.amount = this.remaining > 0 ? String(Math.round(this.remaining * 100) / 100) : '';
            this.step   = 'contribute';
        },

        goBack() {
            this.step  = 'detail';
            this.error = '';
        },

        fillRemaining() {
            this.amount = String(Math.round(this.remaining * 100) / 100);
        },

        // ── actions ────────────────────────────────────────────────────────

        async contributeFromWallet() {
            const n = parseFloat(this.amount);
            if (!n || n <= 0) { this.error = 'Please enter a valid amount.'; return; }

            this.loading = true;
            this.error   = '';

            try {
                const url  = `${this.walletBaseUrl}/${this.wish.id}/contribute/wallet`;
                const res  = await fetch(url, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({
                        amount:   this.amount,
                        currency: this.visitorCurrency,
                        message:  this.note,
                    }),
                });

                const data = await res.json();

                if (data.success) {
                    this.walletBalance   = parseFloat(data.new_balance.replace(/[^0-9.]/g, ''));
                    this.updatedProgress = data.progress;
                    this.step            = 'success';
                    this.$nextTick(() => {
                        if (window.confetti) {
                            window.confetti({
                                particleCount: 140,
                                spread:        80,
                                origin:        { y: 0.55 },
                                colors:        ['#f43f5e', '#a855f7', '#f59e0b', '#10b981', '#3b82f6'],
                            });
                        }
                    });
                } else {
                    this.error = data.message || 'Something went wrong.';
                }
            } catch {
                this.error = 'Network error. Please try again.';
            } finally {
                this.loading = false;
            }
        },

        async initiatePayment() {
            const n = parseFloat(this.amount);
            if (!n || n <= 0) { this.error = 'Please enter a valid amount.'; return; }

            this.loading = true;
            this.error   = '';

            try {
                const url  = `${this.payBaseUrl}/${this.wish.id}/contribute/pay`;
                const res  = await fetch(url, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({
                        amount:   this.amount,
                        currency: this.visitorCurrency,
                        message:  this.note,
                    }),
                });

                const data = await res.json();

                if (data.success && data.authorization_url) {
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

window.wishContributionModal = wishContributionModal;
