/**
 * Alpine component: slugEditor(config)
 *
 * Lets a celebration owner pick their own page URL. Checks availability against
 * the server as they type (debounced) and saves on submit.
 *
 * config = { celebrationId, current, base, checkUrl, saveUrl, csrfToken }
 */
export function slugEditor(config) {
    return {
        open:    false,
        value:   config.current || '',
        current: config.current || '',
        base:    config.base || '',

        status:  'idle',   // idle | checking | available | taken | invalid | reserved
        message: '',
        saving:  false,

        _timer: null,

        get changed() {
            return this.normalised !== this.current;
        },

        /** Mirror the server's rule: lowercase, alphanumeric, single inner hyphens. */
        get normalised() {
            return this.value
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9-]+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
        },

        get canSave() {
            return this.changed && this.status === 'available' && !this.saving;
        },

        get fullUrl() {
            return this.base + (this.normalised || this.current);
        },

        openEditor() {
            this.open   = true;
            this.value  = this.current;
            this.status = 'idle';
            this.message = '';
        },

        closeEditor() {
            this.open = false;
        },

        onInput() {
            // keep the field showing exactly what will be saved
            this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-{2,}/g, '-');

            clearTimeout(this._timer);

            const slug = this.normalised;

            if (!this.changed) { this.status = 'idle'; this.message = ''; return; }

            if (slug.length < 3) {
                this.status = 'invalid';
                this.message = 'Links need at least 3 characters.';
                return;
            }

            this.status = 'checking';
            this.message = 'Checking availability…';

            this._timer = setTimeout(() => this.check(slug), 350);
        },

        async check(slug) {
            try {
                const res  = await fetch(`${config.checkUrl}?slug=${encodeURIComponent(slug)}`, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                const data = await res.json();

                // a newer keystroke has already superseded this request
                if (slug !== this.normalised) return;

                if (data.available) {
                    this.status  = 'available';
                    this.message = 'That link is available.';
                } else {
                    this.status  = data.reason === 'reserved' ? 'reserved'
                                 : data.reason === 'invalid'  ? 'invalid'
                                 : 'taken';
                    this.message = data.reason === 'reserved' ? 'That word is reserved.'
                                 : data.reason === 'invalid'  ? 'Use lowercase letters, numbers and hyphens.'
                                 : 'That link is already taken.';
                }
            } catch {
                this.status  = 'idle';
                this.message = "Couldn't check that right now.";
            }
        },

        async save() {
            if (!this.canSave) return;

            this.saving = true;

            try {
                const res = await fetch(config.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ slug: this.normalised }),
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    this.status  = 'taken';
                    this.message = data.message || 'Could not save that link.';
                    return;
                }

                this.current = data.slug;
                this.open    = false;
                window.showAlert?.(data.message, 'success');

                // the page lives at the old URL — move the browser to the new one
                window.history.replaceState({}, '', data.url);
            } catch {
                this.message = 'Could not save that link.';
            } finally {
                this.saving = false;
            }
        },

        copy() {
            navigator.clipboard?.writeText(this.base + this.current);
            window.showAlert?.('Link copied to clipboard', 'success');
        },
    };
}

window.slugEditor = slugEditor;
