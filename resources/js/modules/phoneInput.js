/**
 * Alpine component — the phone field with a country picker.
 *
 * What it is for: people type their number the way they say it, which is
 * "0803…" at home and "+234 803…" abroad. Both mean the same number, and only
 * one spelling can be stored — so the visible field takes whatever they type
 * and a hidden field carries E.164 to the server.
 *
 * No third-party widget: flags are drawn from the ISO code as regional
 * indicator letters, so there are no images to load and nothing to block.
 */
export function phoneInput(config) {
    return {
        countries: config.countries,
        iso:       config.country,
        national:  config.national || '',

        open:      false,
        search:    '',
        highlight: 0,

        // ── what the server receives ───────────────────────────────────────

        get country() {
            return this.countries.find((c) => c.iso === this.iso) ?? this.countries[0];
        },

        /** "+2348031234567", or empty while there is nothing to send. */
        get e164() {
            const digits = this.national.replace(/\D/g, '').replace(/^0+/, '');

            return digits ? `+${this.country.dial}${digits}` : '';
        },

        /**
         * Settable, so a form holding this field can bind to it with x-model:
         * writing a full number back in picks the country out of it again.
         */
        set e164(value) {
            const digits = String(value ?? '').replace(/\D/g, '');

            if (! digits) {
                this.national = '';

                return;
            }

            // Longest dialling code first: +1 must not win over +1868.
            const match = [...this.countries]
                .sort((a, b) => b.dial.length - a.dial.length)
                .find((c) => digits.startsWith(c.dial));

            if (match) {
                this.iso      = match.iso;
                this.national = digits.slice(match.dial.length);
            } else {
                this.national = digits;
            }
        },

        // ── the list ───────────────────────────────────────────────────────

        get matches() {
            const term = this.search.trim().toLowerCase();

            if (! term) {
                return this.countries;
            }

            // Typing "234" or "+234" should find Nigeria as readily as "nig".
            const digits = term.replace(/\D/g, '');

            return this.countries.filter((c) =>
                c.name.toLowerCase().includes(term)
                || c.iso.toLowerCase() === term
                || (digits && c.dial.startsWith(digits))
            );
        },

        /** 🇳🇬 from "NG" — regional indicator letters, no image files. */
        flag(iso) {
            return iso
                .toUpperCase()
                .replace(/./g, (char) => String.fromCodePoint(127397 + char.charCodeAt(0)));
        },

        // ── interaction ────────────────────────────────────────────────────

        toggle() {
            this.open = ! this.open;

            if (this.open) {
                this.search    = '';
                this.highlight = Math.max(0, this.matches.findIndex((c) => c.iso === this.iso));

                this.$nextTick(() => {
                    this.$refs.search?.focus();
                    this.scrollToHighlight();
                });
            }
        },

        choose(iso) {
            this.iso   = iso;
            this.open  = false;

            // Straight back to typing the number, which is what they came for.
            this.$nextTick(() => this.$refs.number?.focus());
        },

        move(step) {
            const last = this.matches.length - 1;

            if (last < 0) {
                return;
            }

            this.highlight = Math.min(last, Math.max(0, this.highlight + step));
            this.scrollToHighlight();
        },

        chooseHighlighted() {
            const country = this.matches[this.highlight];

            if (country) {
                this.choose(country.iso);
            }
        },

        scrollToHighlight() {
            this.$nextTick(() => {
                this.$refs.list?.children[this.highlight]?.scrollIntoView({ block: 'nearest' });
            });
        },

        /**
         * Typing or pasting a full international number switches the country
         * rather than leaving +234 sitting in front of +44.
         */
        onNumberInput() {
            const raw = this.national.trim();

            if (! raw.startsWith('+') && ! raw.startsWith('00')) {
                return;
            }

            const digits = raw.replace(/^00/, '').replace(/\D/g, '');

            // Longest dialling code first: +1 must not win over +1868.
            const match = [...this.countries]
                .sort((a, b) => b.dial.length - a.dial.length)
                .find((c) => digits.startsWith(c.dial));

            if (match) {
                this.iso      = match.iso;
                this.national = digits.slice(match.dial.length);
            }
        },
    };
}

window.phoneInput = phoneInput;
