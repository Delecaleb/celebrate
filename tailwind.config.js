import { readFileSync } from 'node:fs';
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * CelebrateMi design tokens.
 *
 * Colours are NOT defined here — they come from resources/brand.json, the one
 * file shared with config/brand.php. Every utility resolves to
 *
 *     rgb(var(--primary-500-rgb, <hex from brand.json>) / <alpha-value>)
 *
 * so `bg-primary-500` follows the CSS variable that <x-brand-tokens /> injects
 * at runtime, and still renders the right colour on any page that forgot the
 * component. Change a colour → edit resources/brand.json, nothing else.
 *
 * NOTE: `fontFamily.sans` still resolves to Figtree so layouts/app and
 * layouts/guest keep rendering as they do today. Once every screen is migrated,
 * flip `sans` to 'Plus Jakarta Sans' and drop the Figtree link from those files.
 */

const brand = JSON.parse(
    readFileSync(new URL('./resources/brand.json', import.meta.url), 'utf8'),
);

/** '#7c3aed' → '124 58 237' */
const channels = (hex) => {
    const raw = hex.replace('#', '');
    const full = raw.length === 3 ? [...raw].map((c) => c + c).join('') : raw;
    const n = parseInt(full, 16);

    return `${(n >> 16) & 255} ${(n >> 8) & 255} ${n & 255}`;
};

/** One colour that tracks its CSS variable, with the JSON hex baked in as fallback. */
const token = (name, stop) =>
    `rgb(var(--${name}-${stop}-rgb, ${channels(brand[name][stop])}) / <alpha-value>)`;

/** A whole 50…900 ramp of the above. */
const ramp = (name) =>
    Object.fromEntries(Object.keys(brand[name]).map((stop) => [stop, token(name, stop)]));

export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans:    ['Figtree', ...defaultTheme.fontFamily.sans],
                body:    ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                display: ['Outfit', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                /* the identity colour — buttons, links, emphasis, icons */
                primary: ramp('primary'),

                /* the one complement — highlights, money, celebration accents */
                secondary: ramp('secondary'),

                /* warm near-black + neutral ramp; carries all hierarchy */
                ink: {
                    DEFAULT: token('ink', 950),
                    ...ramp('ink'),
                },

                line: token('ink', 200),
            },

            /*
             * Panels, cards and inputs in the *app* shell are square by design;
             * only pills and avatars are round. The marketing site opts into the
             * softer radii from brand.json instead (--r-sm / --r / --r-lg).
             * To round the app too, point these at those variables.
             */
            borderRadius: {
                xl2: '0',
                xl3: '0',
                xl4: '0',
            },

            boxShadow: {
                soft:  '0 1px 2px rgba(var(--ink-rgb, 22 18 31) / 0.05)',
                card:  '0 6px 28px -6px rgba(var(--ink-rgb, 22 18 31) / 0.10)',
                lift:  '0 28px 70px -22px rgba(var(--ink-rgb, 22 18 31) / 0.30)',
            },

            backgroundImage: {
                /* decorative background patterns */
                'pattern-dots': 'radial-gradient(currentColor 1px, transparent 1px)',
                'pattern-grid': 'linear-gradient(currentColor 1px, transparent 1px), linear-gradient(90deg, currentColor 1px, transparent 1px)',
            },

            keyframes: {
                marquee: {
                    '0%':   { transform: 'translateX(0)' },
                    '100%': { transform: 'translateX(-50%)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%':      { transform: 'translateY(-12px)' },
                },
                pop: {
                    '0%':   { transform: 'scale(0.96)', opacity: '0' },
                    '100%': { transform: 'scale(1)', opacity: '1' },
                },
            },

            animation: {
                marquee: 'marquee 38s linear infinite',
                float:   'float 6s ease-in-out infinite',
                pop:     'pop 0.3s ease-out both',
            },
        },
    },

    plugins: [forms],
};
