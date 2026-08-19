import { format, formatDistanceToNowStrict, isValid, parseISO } from 'date-fns';

/**
 * Money, matching the web's number_format($n, 2) with the currency symbol in
 * front — "$1,250.00", "₦45,000.00".
 */
export function money(amount: number | string | null | undefined, symbol = ''): string {
  const n = typeof amount === 'string' ? parseFloat(amount) : (amount ?? 0);
  const safe = Number.isFinite(n) ? n : 0;

  return `${symbol}${safe.toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
}

/** Whole-number counts, as number_format() with no decimals. */
export function count(n: number | null | undefined): string {
  return (n ?? 0).toLocaleString('en-US');
}

function toDate(iso: string | null | undefined): Date | null {
  if (!iso) return null;
  const d = parseISO(iso);
  return isValid(d) ? d : null;
}

/** "Dec 1, 2026" — the format the event cards use. */
export function shortDate(iso: string | null | undefined): string | null {
  const d = toDate(iso);
  return d ? format(d, 'MMM d, yyyy') : null;
}

/** "Dec 1, 2026 · 3:04pm" — the ledger's format. */
export function dateTime(iso: string | null | undefined): string | null {
  const d = toDate(iso);
  return d ? format(d, "MMM d, yyyy '·' h:mmaaa") : null;
}

/** "3 days ago". Falls back to the server's own human string when unparseable. */
export function relative(iso: string | null | undefined, fallback?: string | null): string {
  const d = toDate(iso);

  if (!d) return fallback ?? '';

  return `${formatDistanceToNowStrict(d)} ago`;
}

/** For a date input: an ISO timestamp down to just the day. */
export function dateOnly(iso: string | null | undefined): string {
  const d = toDate(iso);
  return d ? format(d, 'yyyy-MM-dd') : '';
}

/** Two-letter initials for an avatar with no photo. */
export function initials(name: string | null | undefined): string {
  const parts = (name ?? '').trim().split(/\s+/).filter(Boolean);

  if (!parts.length) return '?';
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();

  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}
