export function celebrationCustomizer(config) {
    return {
        customizerOpen:     false,
        selectedTemplateId: config.currentTemplateId ?? null,
        savedTemplateId:    config.currentTemplateId ?? null,
        customBg:           config.customBg   ?? '',
        customText:         config.customText ?? '',
        savedCustomBg:      config.customBg   ?? '',
        savedCustomText:    config.customText ?? '',
        loading:            false,
        successMessage:     '',

        get cssVars() {
            const t  = this.activeTemplate;
            const bg = this.customBg   || (t ? t.page_bg      : '');
            const tx = this.customText || (t ? t.text_primary  : '');

            if (!t && !bg && !tx) return '';

            const bw = t && t.photo_border_style !== 'none'
                ? `${t.photo_border_width}px`
                : '0px';

            const vars = [];
            if (bg) vars.push(`--tpl-bg:${bg}`);
            if (tx) vars.push(`--tpl-text:${tx}`);

            if (t) {
                vars.push(
                    `--tpl-card:${t.card_bg}`,
                    `--tpl-text-muted:${t.text_secondary}`,
                    `--tpl-accent:${t.accent_color}`,
                    `--tpl-border-color:${t.photo_border_color}`,
                    `--tpl-border-width:${bw}`,
                    `--tpl-border-style:${t.photo_border_style}`,
                );
            }

            return vars.join(';');
        },

        get activeTemplate() {
            return config.templates.find(t => t.id === this.selectedTemplateId) ?? null;
        },

        get wishesLayout() {
            return this.activeTemplate?.wishes_layout ?? 'scroll';
        },

        /**
         * What the colour wheel should show for 'bg' or 'text'.
         *
         * A native colour input only speaks #rrggbb, so an unset custom
         * colour used to show as black. Show the theme's colour instead.
         */
        pickerValue(which) {
            const own      = which === 'bg' ? this.customBg : this.customText;
            const fromTheme = which === 'bg' ? this.activeTemplate?.page_bg : this.activeTemplate?.text_primary;
            const hex      = [own, fromTheme].find(v => /^#[0-9a-f]{6}$/i.test(v ?? ''));

            return hex ?? (which === 'bg' ? '#ffffff' : '#111111');
        },

        /**
         * Set a custom colour from a picker, a preset or a typed hex.
         *
         * Accepts "#abc", "abc" and "#aabbcc"; empty means "use the theme".
         * Returns false for anything else so the field can put back the
         * last good value.
         */
        setColour(which, raw) {
            const field = which === 'bg' ? 'customBg' : 'customText';
            let value   = String(raw ?? '').trim();

            if (value === '') {
                this[field] = '';
                return true;
            }

            if (!value.startsWith('#')) value = '#' + value;
            if (/^#[0-9a-f]{3}$/i.test(value)) {
                value = '#' + value.slice(1).split('').map(c => c + c).join('');
            }
            if (!/^#[0-9a-f]{6}$/i.test(value)) return false;

            this[field]         = value.toLowerCase();
            this.successMessage = '';
            return true;
        },

        get hasUnsavedChange() {
            return this.selectedTemplateId !== this.savedTemplateId
                || this.customBg   !== this.savedCustomBg
                || this.customText !== this.savedCustomText;
        },

        selectTemplate(id) {
            this.selectedTemplateId = id;
            this.successMessage     = '';
        },

        async applyTemplate() {
            if (!this.hasUnsavedChange) return;
            this.loading = true;

            try {
                const res = await fetch(config.applyUrl, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({
                        template_id:  this.selectedTemplateId,
                        custom_bg:    this.customBg   || null,
                        custom_text:  this.customText || null,
                    }),
                });

                if (res.ok) {
                    this.savedTemplateId = this.selectedTemplateId;
                    this.savedCustomBg   = this.customBg;
                    this.savedCustomText = this.customText;
                    this.successMessage  = 'Saved!';
                    setTimeout(() => { this.successMessage = ''; }, 2500);
                }
            } finally {
                this.loading = false;
            }
        },

        cancelPreview() {
            this.selectedTemplateId = this.savedTemplateId;
            this.customBg           = this.savedCustomBg;
            this.customText         = this.savedCustomText;
            this.successMessage     = '';
        },
    };
}

window.celebrationCustomizer = celebrationCustomizer;
