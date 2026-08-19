{{--
    Brand tokens.

    Emits resources/brand.json (via config/brand.php) as CSS custom properties
    on :root. Include this in the <head> of every page shell, BEFORE any
    page-specific <style> block — those blocks may add layout variables of their
    own, but they must never re-declare a colour, or they'd shadow the palette.

    Every ramp stop is emitted twice:
      --primary-500      #7c3aed     for plain CSS
      --primary-500-rgb  124 58 237  for rgb(… / alpha) and Tailwind utilities

    Short aliases (--primary, --ink, --muted, --line, --surface, …) exist so
    hand-written stylesheets read cleanly. They are the ONLY names those
    stylesheets should use.
--}}
@php
    $brand = config('brand');

    /** '#7c3aed' → '124 58 237' (space-separated channels, for rgb(… / <alpha>)). */
    $channels = static function (string $hex): string {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        [$r, $g, $b] = sscanf($hex, '%2x%2x%2x');

        return "{$r} {$g} {$b}";
    };

    $ramps = [
        'primary'   => $brand['primary'],
        'secondary' => $brand['secondary'],
        'ink'       => $brand['ink'],
    ];
@endphp
<style id="brand-tokens">
    :root {
        @foreach ($ramps as $name => $stops)
            @foreach ($stops as $stop => $hex)
        --{{ $name }}-{{ $stop }}: {{ $hex }};
        --{{ $name }}-{{ $stop }}-rgb: {{ $channels($hex) }};
            @endforeach
        @endforeach

        /* ── ALIASES — what stylesheets actually reference ────────────── */
        --primary:      var(--primary-500);
        --primary-d:    var(--primary-600);
        --primary-l:    var(--primary-100);
        --primary-xl:   var(--primary-50);
        --primary-rgb:  var(--primary-500-rgb);

        --secondary:      var(--secondary-500);
        --secondary-d:    var(--secondary-600);
        --secondary-l:    var(--secondary-100);
        --secondary-xl:   var(--secondary-50);
        --secondary-rgb:  var(--secondary-500-rgb);

        --ink:      {{ $brand['ink'][950] }};
        --ink-rgb:  {{ $channels($brand['ink'][950]) }};
        --muted:    var(--ink-500);
        --muted-2:  var(--ink-400);
        --line:     var(--ink-200);
        --line-2:   var(--ink-100);

        --surface:      {{ $brand['surface']['base'] }};
        --surface-2:    {{ $brand['surface']['raised'] }};
        --surface-rgb:  {{ $channels($brand['surface']['base']) }};

        --ok:       {{ $brand['state']['ok']['base'] }};
        --ok-l:     {{ $brand['state']['ok']['tint'] }};
        --warn:     {{ $brand['state']['warn']['base'] }};
        --warn-l:   {{ $brand['state']['warn']['tint'] }};
        --danger:   {{ $brand['state']['danger']['base'] }};
        --danger-l: {{ $brand['state']['danger']['tint'] }};

        --on-dark:      rgba(255, 255, 255, 0.68);
        --on-dark-2:    rgba(255, 255, 255, 0.46);
        --on-dark-line: rgba(255, 255, 255, 0.16);

        /* ── RADII ───────────────────────────────────────────────────── */
        --r-sm:   {{ $brand['radius']['sm'] }};
        --r:      {{ $brand['radius']['md'] }};
        --r-lg:   {{ $brand['radius']['lg'] }};
        --r-pill: {{ $brand['radius']['pill'] }};

        /* ── ELEVATION (tinted with the ink hue, never neutral grey) ──── */
        --shadow-soft: 0 1px 2px rgba(var(--ink-rgb) / 0.05);
        --shadow-card: 0 6px 28px -6px rgba(var(--ink-rgb) / 0.10);
        --shadow-lift: 0 28px 70px -22px rgba(var(--ink-rgb) / 0.30);
        --shadow-pop:  0 18px 48px -16px rgba(var(--primary-rgb) / 0.34);
    }
</style>
