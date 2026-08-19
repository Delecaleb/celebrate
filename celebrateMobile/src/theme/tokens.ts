/**
 * GENERATED FILE — DO NOT EDIT.
 *
 * Written by scripts/sync-brand.mjs from ../../resources/brand.json, the palette
 * shared with tailwind.config.js and config/brand.php. Change colours there and
 * run `npm run sync-brand`.
 */

export const brand = {
  "primary": {
    "50": "#f7f4ff",
    "100": "#efe8ff",
    "200": "#ded1ff",
    "300": "#c4adff",
    "400": "#a680fc",
    "500": "#7c3aed",
    "600": "#6d28d9",
    "700": "#5b21b6",
    "800": "#4c1d95",
    "900": "#3c1a7a"
  },
  "secondary": {
    "50": "#fff9ec",
    "100": "#fff0cd",
    "200": "#ffdf9b",
    "300": "#ffca63",
    "400": "#ffb43a",
    "500": "#f59e0b",
    "600": "#d97e06",
    "700": "#b45c09",
    "800": "#92480e",
    "900": "#783a0f"
  },
  "ink": {
    "50": "#f8f7fb",
    "100": "#f1eff6",
    "200": "#e5e2ee",
    "300": "#cac5d8",
    "400": "#9d97ae",
    "500": "#6d6683",
    "600": "#544d68",
    "700": "#3e384e",
    "800": "#292336",
    "900": "#1d1828",
    "950": "#16121f"
  },
  "surface": {
    "base": "#ffffff",
    "raised": "#faf8fe"
  },
  "state": {
    "ok": {
      "base": "#0f7b57",
      "tint": "#e4f5ee"
    },
    "warn": {
      "base": "#9a5b00",
      "tint": "#fdf1dd"
    },
    "danger": {
      "base": "#b4232d",
      "tint": "#fdeaec"
    }
  },
  "radius": {
    "sm": "12px",
    "md": "18px",
    "lg": "26px",
    "pill": "999px"
  }
} as const;

/** Channel triples, for colours that need a runtime alpha. */
export const rgbChannels = {
  "primary-50": [
    247,
    244,
    255
  ],
  "primary-100": [
    239,
    232,
    255
  ],
  "primary-200": [
    222,
    209,
    255
  ],
  "primary-300": [
    196,
    173,
    255
  ],
  "primary-400": [
    166,
    128,
    252
  ],
  "primary-500": [
    124,
    58,
    237
  ],
  "primary-600": [
    109,
    40,
    217
  ],
  "primary-700": [
    91,
    33,
    182
  ],
  "primary-800": [
    76,
    29,
    149
  ],
  "primary-900": [
    60,
    26,
    122
  ],
  "secondary-50": [
    255,
    249,
    236
  ],
  "secondary-100": [
    255,
    240,
    205
  ],
  "secondary-200": [
    255,
    223,
    155
  ],
  "secondary-300": [
    255,
    202,
    99
  ],
  "secondary-400": [
    255,
    180,
    58
  ],
  "secondary-500": [
    245,
    158,
    11
  ],
  "secondary-600": [
    217,
    126,
    6
  ],
  "secondary-700": [
    180,
    92,
    9
  ],
  "secondary-800": [
    146,
    72,
    14
  ],
  "secondary-900": [
    120,
    58,
    15
  ],
  "ink-50": [
    248,
    247,
    251
  ],
  "ink-100": [
    241,
    239,
    246
  ],
  "ink-200": [
    229,
    226,
    238
  ],
  "ink-300": [
    202,
    197,
    216
  ],
  "ink-400": [
    157,
    151,
    174
  ],
  "ink-500": [
    109,
    102,
    131
  ],
  "ink-600": [
    84,
    77,
    104
  ],
  "ink-700": [
    62,
    56,
    78
  ],
  "ink-800": [
    41,
    35,
    54
  ],
  "ink-900": [
    29,
    24,
    40
  ],
  "ink-950": [
    22,
    18,
    31
  ],
  "surface-base": [
    255,
    255,
    255
  ],
  "surface-raised": [
    250,
    248,
    254
  ]
} as const;

/** rgba() from a token name in rgbChannels. */
export function alpha(token: keyof typeof rgbChannels, a: number): string {
  const [r, g, b] = rgbChannels[token];
  return `rgba(${r}, ${g}, ${b}, ${a})`;
}
