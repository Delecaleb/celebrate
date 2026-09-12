/**
 * Alpine.js component — Wish Contribution Modal.
 *
 * Opens when any wish card dispatches the 'open-wish' window event.
 * Steps: detail → contribute → success
 */
import { postJson, failureMessage } from './http';
import { loadPaystack, payInline } from './paystackInline';
import { giftConfetti } from './giftConfetti';

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

        // Guests contribute by card without an account.
        guestName:        '',
        guestEmail:       '',

        updatedProgress:  null,       // set after a successful contribution

        walletBalance:    config.walletBalance,
        visitorCurrency:  config.visitorCurrency,
        visitorSymbol:    config.visitorSymbol,
        isAuthenticated:  config.isAuthenticated,
        isOwner:          config.isOwner,
        walletBaseUrl:    config.walletBaseUrl,
        payBaseUrl:       config.payBaseUrl,
        confirmUrl:       config.confirmUrl,
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

        /** True once a guest has given us somewhere to send the receipt. */
        get canGuestPay() {
            return this.guestName.trim().length > 1
                && /^\S+@\S+\.\S+$/.test(this.guestEmail.trim());
        },

        async initiatePayment() {
            const n = parseFloat(this.amount);
            if (!n || n <= 0) { this.error = 'Please enter a valid amount.'; return; }

            if (!this.isAuthenticated && !this.canGuestPay) {
                this.error = 'Please add your name and email.';
                return;
            }

            this.loading = true;
            this.error   = '';

            try {
                const { res, data, token } = await postJson(
                    `${this.payBaseUrl}/${this.wish.id}/contribute/pay`,
                    {
                        amount:      this.amount,
                        currency:    this.visitorCurrency,
                        message:     this.note,
                        guest_name:  this.isAuthenticated ? null : this.guestName.trim(),
                        guest_email: this.isAuthenticated ? null : this.guestEmail.trim(),
                    },
                    this.csrfToken
                );

                this.csrfToken = token;

                if (! data?.success) {
                    this.error   = failureMessage(res, data, 'Could not start payment. Please try again.');
                    this.loading = false;

                    return;
                }

                // Naira pays in place. Stripe still redirects, and so does
                // Paystack if its script could not be fetched.
                if (data.provider === 'paystack' && data.access_code && await loadPaystack()) {
                    await this.payWithPaystack(data.access_code, data.reference);

                    return;
                }

                if (data.authorization_url) {
                    window.location.href = data.authorization_url;

                    return;
                }

                this.error   = 'Could not start payment. Please try again.';
                this.loading = false;
            } catch {
                this.error   = 'Network error. Please try again.';
                this.loading = false;
            }
        },

        /** Card details go into Paystack's own frame, never this page. */
        async payWithPaystack(accessCode, reference) {
            const result = await payInline(accessCode);

            if (result.outcome === 'cancelled') {
                this.error   = 'Payment cancelled. Nothing has been charged.';
                this.loading = false;

                return;
            }

            if (result.outcome === 'error') {
                this.error   = result.message || 'The payment could not be completed. Please try again.';
                this.loading = false;

                return;
            }

            await this.confirmPayment(result.reference || reference);
        },

        /** Ask our server what actually happened, and act on that. */
        async confirmPayment(reference) {
            try {
                const { res, data, token } = await postJson(
                    this.confirmUrl, { reference }, this.csrfToken
                );

                this.csrfToken = token;

                if (data?.success) {
                    this.error = '';
                    this.step  = 'success';

                    await giftConfetti();

                    // Reload so the registry bar shows the new total.
                    window.location.href = data.redirect_url || window.location.href;

                    return;
                }

                // 202 is paid-but-unconfirmed: the webhook and
                // payments:reconcile finish it. Not a failure to the payer.
                if (res.status === 202) {
                    this.error = '';
                    this.step  = 'success';
                } else {
                    this.error = failureMessage(res, data, 'We could not confirm that payment. Please contact support.');
                }
            } catch {
                this.error = 'Your payment may have gone through, but we could not reach the site to confirm it. Please refresh before trying again.';
            } finally {
                this.loading = false;
            }
        },
    };
}

window.wishContributionModal = wishContributionModal;
