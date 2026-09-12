/**
 * Alpine.js component — Gift Plate modal.
 *
 * Responsibilities:
 *  - Grid view: browse available platform gifts
 *  - Detail view: the basket, wallet balance, send / pay actions
 *  - External trigger: responds to the 'open-gift-detail' window event
 *    so sidebar buttons outside the component can open it directly on a gift
 */
import { postJson, failureMessage } from './http';
import { loadPaystack, payInline } from './paystackInline';
import { giftConfetti } from './giftConfetti';

export function giftPlate(config) {
    return {
        // ── state ──────────────────────────────────────────────────────────

        view:            'grid',   // 'grid' | 'detail'

        /**
         * The basket: one entry per gift, in the order they were first tapped.
         * Tapping a tile adds one of that gift, so several different gifts can
         * go together in a single payment.
         */
        cart:            [],
        maxQuantity:     99,
        maxLines:        20,
        loading:         false,
        error:           '',
        success:         '',
        giftMessage:     '',

        // Guests pay by card without an account — these go with the payment.
        guestName:       '',
        guestEmail:      '',

        // hydrated from Blade config object
        walletBalance:   config.walletBalance,
        visitorCurrency: config.visitorCurrency,
        visitorSymbol:   config.visitorSymbol,
        isAuthenticated: config.isAuthenticated,
        celebrationId:   config.celebrationId,
        sendUrl:         config.sendUrl,
        payUrl:          config.payUrl,
        confirmUrl:      config.confirmUrl,
        csrfToken:       config.csrfToken,

        // ── navigation ─────────────────────────────────────────────────────

        /**
         * Tap a gift on the grid.
         *
         * Tapping adds one; tapping the same gift again adds another. The grid
         * stays put so a basket can be built by tapping, which is the whole
         * point — leaving for the detail panel on the first tap would make a
         * second one impossible.
         */
        tapGift(gift) {
            const line = this.lineFor(gift.id);

            if (line) {
                this.bump(gift.id, 1);

                return;
            }

            if (this.cart.length >= this.maxLines) {
                this.error = `You can send up to ${this.maxLines} different gifts at once.`;

                return;
            }

            this.cart.push({ gift, quantity: 1 });
            this.error = '';
        },

        lineFor(id) {
            return this.cart.find((line) => line.gift.id === id);
        },

        /** Move one gift's count, dropping the line when it reaches zero. */
        bump(id, by) {
            const line = this.lineFor(id);

            if (! line) {
                return;
            }

            const next = line.quantity + by;

            if (next < 1) {
                this.removeLine(id);

                return;
            }

            line.quantity = Math.min(this.maxQuantity, next);
            this.error    = '';
        },

        removeLine(id) {
            this.cart = this.cart.filter((line) => line.gift.id !== id);

            if (this.cart.length === 0) {
                this.view = 'grid';
            }
        },

        /** Open the detail panel once there is something to pay for. */
        review() {
            if (! this.cart.length) {
                return;
            }

            this.view  = 'detail';
            this.error = '';
        },

        /** Empty the basket and start again. */
        clearPick() {
            this.cart  = [];
            this.view  = 'grid';
            this.error = '';
        },

        /** How many of a given gift are in the basket, for the tile badge. */
        pickedCount(id) {
            return this.lineFor(id)?.quantity ?? 0;
        },

        /** Open straight onto one gift — used by the buttons outside the modal. */
        selectGift(gift) {
            this.cart        = [{ gift, quantity: 1 }];
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

        /** Return to the gift grid, keeping the pick so it can be adjusted. */
        back() {
            this.view    = 'grid';
            this.error   = '';
            this.success = '';
        },

        // ── helpers ────────────────────────────────────────────────────────

        /** What is actually being charged for the whole basket. */
        get cartTotal() {
            return this.cart.reduce((sum, line) => sum + line.gift.price * line.quantity, 0);
        },

        /** The total, formatted, for every place that shows a price. */
        get cartTotalLabel() {
            return this.visitorSymbol + this.formatNum(this.cartTotal);
        },

        /** Gifts, counting quantity — "3 gifts" means three things, not three lines. */
        get cartCount() {
            return this.cart.reduce((sum, line) => sum + line.quantity, 0);
        },

        /** How the basket is described in one phrase. */
        get cartLabel() {
            if (this.cart.length === 1) {
                const line = this.cart[0];

                return line.quantity > 1
                    ? `${line.quantity} × ${line.gift.name}`
                    : line.gift.name;
            }

            return `${this.cartCount} gifts`;
        },

        /** What goes on the wire: gifts and counts, never prices. */
        get cartItems() {
            return this.cart.map((line) => ({
                platform_gift_id: line.gift.id,
                quantity:         line.quantity,
            }));
        },

        /** True when the wallet balance covers the whole basket. */
        hasSufficientBalance() {
            return this.cart.length > 0 && this.walletBalance >= this.cartTotal;
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
                const { res, data, token } = await postJson(this.sendUrl, {
                    items:          this.cartItems,
                    celebration_id: this.celebrationId,
                    message:        this.giftMessage,
                }, this.csrfToken);

                // postJson may have had to go and get a live one.
                this.csrfToken = token;

                if (data?.success) {
                    this.success = data.message;
                    // Strip currency symbols and re-parse the new balance
                    this.walletBalance = parseFloat(
                        data.new_balance.replace(/[^0-9.]/g, '')
                    );
                } else {
                    this.error = failureMessage(res, data, 'The gift could not be sent. Please try again.');
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

        /** Initialise a Paystack payment for the basket. */
        async initiatePayment() {
            if (!this.isAuthenticated && !this.canGuestPay) {
                this.error = 'Please add your name and email.';
                return;
            }

            this.loading = true;
            this.error   = '';
            this.success = '';

            try {
                const { res, data, token } = await postJson(this.payUrl, {
                    items:          this.cartItems,
                    celebration_id: this.celebrationId,
                    message:        this.giftMessage,
                    guest_name:     this.isAuthenticated ? null : this.guestName.trim(),
                    guest_email:    this.isAuthenticated ? null : this.guestEmail.trim(),
                }, this.csrfToken);

                this.csrfToken = token;

                if (! data?.success) {
                    this.error   = failureMessage(res, data, 'Could not start payment. Please try again.');
                    this.loading = false;

                    return;
                }

                // Naira goes through the inline checkout, which keeps the payer
                // on the celebration page. Stripe still redirects, and so does
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

        /**
         * Card details are typed into Paystack's own frame, never ours — the
         * page has no access to them, which is what keeps this out of PCI
         * scope.
         */
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

            this.success = 'Confirming your payment…';
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
                    this.error   = '';
                    this.success = data.message;

                    // The payer is already looking at the celebration page, so
                    // the confetti belongs here and now — not after a reload
                    // they would watch it play to an empty room. Reload only
                    // once it has finished, to bring the gift itself in.
                    await giftConfetti();

                    window.location.href = data.redirect_url || window.location.href;

                    return;
                }

                // 202 means paid but not yet confirmed — the webhook and
                // payments:reconcile will finish it. Not an error to the payer.
                if (res.status === 202) {
                    this.error   = '';
                    this.success = data?.message || 'Your payment went through. The gift will appear shortly.';
                } else {
                    this.success = '';
                    this.error   = failureMessage(res, data, 'We could not confirm that payment. Please contact support.');
                }
            } catch {
                this.success = '';
                this.error   = 'Your payment may have gone through, but we could not reach the site to confirm it. Please refresh before trying again.';
            } finally {
                this.loading = false;
            }
        },
    };
}

// Expose on window so Alpine templates can reference it without an import
window.giftPlate = giftPlate;
