/**
 * Alpine.js component — Celebrant wishlist form.
 *
 * Allows the celebration owner to add, remove, and save wish items.
 * All server URLs and CSRF tokens are read from window.CelebrationConfig
 * so no Blade values are embedded directly in the JS.
 */
export function wishlistForm() {
    return {
        // ── state ──────────────────────────────────────────────────────────

        loading:        false,
        successMessage: '',
        errorMessage:   '',

        /** Each wish entry: name, optional amount, optional image + preview URL. */
        wishes: [
            { name: '', amount: '', image: null, preview: '' },
        ],

        // ── wish management ────────────────────────────────────────────────

        addWish() {
            this.wishes.push({ name: '', amount: '', image: null, preview: '' });
        },

        removeWish(index) {
            this.wishes.splice(index, 1);
        },

        handleImage(event, index) {
            const file = event.target.files[0];
            if (!file) return;

            this.wishes[index].image   = file;
            this.wishes[index].preview = URL.createObjectURL(file);
        },

        // ── submission ─────────────────────────────────────────────────────

        async submitForm() {
            this.loading        = true;
            this.successMessage = '';
            this.errorMessage   = '';

            try {
                const { celebrationId, wishesUrl, csrfToken } = window.CelebrationConfig;

                const formData = new FormData();

                this.wishes.forEach((wish, index) => {
                    formData.append(`wishlist[${index}][name]`,   wish.name);
                    formData.append(`wishlist[${index}][amount]`, wish.amount);

                    if (wish.image) {
                        formData.append(`wishlist[${index}][image]`, wish.image);
                    }
                });

                // Append once — celebration_id applies to the whole batch
                formData.append('celebration_id', celebrationId);
                formData.append('_token',         csrfToken);

                const response = await fetch(wishesUrl, {
                    method: 'POST',
                    body:   formData,
                });

                const data = await response.json();

                if (data.success) {
                    this.successMessage = data.message;
                    // Reset form to a single blank wish
                    this.wishes = [{ name: '', amount: '', image: null, preview: '' }];
                } else {
                    this.errorMessage = data.message || 'Something went wrong';
                }

            } catch {
                this.errorMessage = 'Server error occurred';
            } finally {
                this.loading = false;
            }
        },
    };
}

// Expose on window so Alpine templates can reference it without an import
window.wishlistForm = wishlistForm;
