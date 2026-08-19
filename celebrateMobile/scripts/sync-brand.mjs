/**
 * Generates src/theme/tokens.ts from the web app's resources/brand.json.
 *
 * brand.json is already the single source of truth for both web toolchains —
 * tailwind.config.js imports it and config/brand.php adapts it. This makes the
 * mobile app the third consumer rather than a fourth copy of the palette, so a
 * colour change in that one file still recolours everything.
 *
 * Run `npm run sync-brand` after editing resources/brand.json.
 */

import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const brandPath = resolve(here, '../../resources/brand.json');
const outPath = resolve(here, '../src/theme/tokens.ts');

const brand = JSON.parse(readFileSync(brandPath, 'utf8'));
delete brand.$comment;

/** '#7c3aed' -> 'rgba(124, 58, 237, <alpha>)' helper input */
const channels = (hex) => {
  const raw = hex.replace('#', '');
  const full = raw.length === 3 ? [...raw].map((c) => c + c).join('') : raw;
  const n = parseInt(full, 16);
  return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
};

const banner = `/**
 * GENERATED FILE — DO NOT EDIT.
 *
 * Written by scripts/sync-brand.mjs from ../../resources/brand.json, the palette
 * shared with tailwind.config.js and config/brand.php. Change colours there and
 * run \`npm run sync-brand\`.
 */
`;

const lines = [banner];
lines.push('export const brand = ' + JSON.stringify(brand, null, 2) + ' as const;\n');

// RGB channels for the few places that need an alpha (shadows, scrims).
const rgb = {};
for (const [name, value] of Object.entries(brand)) {
  if (name === 'radius') continue;
  if (typeof value === 'string') {
    rgb[name] = channels(value);
    continue;
  }
  for (const [stop, hex] of Object.entries(value)) {
    if (typeof hex === 'string' && hex.startsWith('#')) {
      rgb[`${name}-${stop}`] = channels(hex);
    }
  }
}

lines.push('/** Channel triples, for colours that need a runtime alpha. */');
lines.push('export const rgbChannels = ' + JSON.stringify(rgb, null, 2) + ' as const;\n');

lines.push(`/** rgba() from a token name in rgbChannels. */
export function alpha(token: keyof typeof rgbChannels, a: number): string {
  const [r, g, b] = rgbChannels[token];
  return \`rgba(\${r}, \${g}, \${b}, \${a})\`;
}
`);

mkdirSync(dirname(outPath), { recursive: true });
writeFileSync(outPath, lines.join('\n'));

console.log(`brand tokens written to ${outPath}`);
