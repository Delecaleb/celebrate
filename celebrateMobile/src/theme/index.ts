/**
 * The design system, ported from the web app's CSS custom properties.
 *
 * Names here are deliberately the same as the aliases in
 * resources/views/components/brand-tokens.blade.php (--primary, --ink, --muted,
 * --line, --surface, …) so a screen reads the same as its Blade counterpart and
 * the two cannot drift apart by accident.
 *
 * Colours come from tokens.ts, which is generated from resources/brand.json.
 * Never write a hex literal in a screen — add a token here instead.
 */

import { Platform } from 'react-native';
import { alpha, brand } from './tokens';

export const colors = {
  primary: brand.primary['500'],
  primaryDark: brand.primary['600'],
  primaryLight: brand.primary['100'],
  primaryFaint: brand.primary['50'],

  secondary: brand.secondary['500'],
  secondaryDark: brand.secondary['600'],
  secondaryLight: brand.secondary['100'],
  secondaryFaint: brand.secondary['50'],

  ink: brand.ink['950'],
  muted: brand.ink['500'],
  muted2: brand.ink['400'],
  line: brand.ink['200'],
  line2: brand.ink['100'],

  surface: brand.surface.base,
  surface2: brand.surface.raised,

  /*
   * The app shell holds itself to two hues, exactly as layouts/dashboard does:
   * it re-aliases ok/warn/danger onto the existing ramps rather than letting a
   * third colour in through a status chip. The literal state colours from
   * brand.json stay available below for anywhere that genuinely needs them.
   */
  ok: brand.secondary['700'],
  okTint: brand.secondary['100'],
  warn: brand.ink['500'],
  warnTint: brand.ink['100'],
  danger: brand.primary['500'],
  dangerTint: brand.primary['100'],

  onDark: 'rgba(255, 255, 255, 0.68)',
  onDark2: 'rgba(255, 255, 255, 0.46)',
  onDarkLine: 'rgba(255, 255, 255, 0.16)',
  white: '#ffffff',

  scrim: alpha('ink-950', 0.45),
} as const;

/** The literal state palette from brand.json, for true success/error surfaces. */
export const stateColors = {
  ok: brand.state.ok.base,
  okTint: brand.state.ok.tint,
  warn: brand.state.warn.base,
  warnTint: brand.state.warn.tint,
  danger: brand.state.danger.base,
  dangerTint: brand.state.danger.tint,
} as const;

/**
 * Radii.
 *
 * The app shell is square by design — tailwind.config.js flattens xl2/xl3/xl4 to
 * 0 for exactly this reason, and only pills and avatars are round. The softer
 * radii from brand.json are kept under `soft` for the celebration page, which is
 * the one surface that opts into them.
 */
export const radii = {
  none: 0,
  card: 0,
  pill: 999,
  soft: {
    sm: parseInt(brand.radius.sm, 10),
    md: parseInt(brand.radius.md, 10),
    lg: parseInt(brand.radius.lg, 10),
  },
} as const;

export const spacing = {
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 24,
  '2xl': 32,
  '3xl': 48,
} as const;

/**
 * Type scale.
 *
 * The web uses Outfit for headings and Plus Jakarta Sans for body. Those aren't
 * bundled here, so weights carry the hierarchy instead — which is why headings
 * lean on 800 and the tight letterSpacing the web sets on h1–h4.
 */
export const type = {
  eyebrow: { fontSize: 11, fontWeight: '800', letterSpacing: 1.5, textTransform: 'uppercase' },
  h1: { fontSize: 28, fontWeight: '800', letterSpacing: -0.8 },
  h2: { fontSize: 21, fontWeight: '800', letterSpacing: -0.5 },
  h3: { fontSize: 17, fontWeight: '800', letterSpacing: -0.3 },
  body: { fontSize: 15, fontWeight: '400' },
  bodyStrong: { fontSize: 15, fontWeight: '700' },
  small: { fontSize: 13, fontWeight: '400' },
  tiny: { fontSize: 11, fontWeight: '700' },
  stat: { fontSize: 26, fontWeight: '800', letterSpacing: -0.8 },
} as const;

/** Ink-tinted elevation, never neutral grey — as --shadow-* on the web. */
export const shadows = {
  soft: Platform.select({
    ios: { shadowColor: colors.ink, shadowOpacity: 0.05, shadowRadius: 2, shadowOffset: { width: 0, height: 1 } },
    android: { elevation: 1 },
    default: {},
  }),
  card: Platform.select({
    ios: { shadowColor: colors.ink, shadowOpacity: 0.1, shadowRadius: 14, shadowOffset: { width: 0, height: 6 } },
    android: { elevation: 3 },
    default: {},
  }),
  lift: Platform.select({
    ios: { shadowColor: colors.ink, shadowOpacity: 0.3, shadowRadius: 28, shadowOffset: { width: 0, height: 14 } },
    android: { elevation: 8 },
    default: {},
  }),
} as const;

/**
 * Per-celebration-type icon and label.
 *
 * The same mapping the event cards and the celebration header use on the web,
 * with the mdi- names translated to MaterialCommunityIcons glyphs (which is the
 * same icon set, so they match one for one).
 */
export const celebrationTypes = {
  birthday: { icon: 'cake-variant', label: 'Birthday' },
  wedding: { icon: 'ring', label: 'Wedding' },
  graduation: { icon: 'school', label: 'Graduation' },
  anniversary: { icon: 'heart', label: 'Anniversary' },
  memorial: { icon: 'bird', label: 'Memorial' },
  baby_shower: { icon: 'baby-carriage', label: 'Baby Shower' },
  other: { icon: 'party-popper', label: 'Celebration' },
} as const;

export type CelebrationTypeKey = keyof typeof celebrationTypes;

export function celebrationType(key?: string | null) {
  return celebrationTypes[(key ?? 'other') as CelebrationTypeKey] ?? celebrationTypes.other;
}

/** Status pill wording and colour, matching the badge-live/closed/draft classes. */
export function statusBadge(status?: string | null) {
  switch (status) {
    case 'published':
      return { label: 'Live', fg: colors.ok, bg: colors.okTint };
    case 'closed':
      return { label: 'Closed', fg: colors.muted, bg: colors.warnTint };
    default:
      return { label: 'Draft', fg: colors.muted2, bg: colors.line2 };
  }
}

export { alpha, brand };
