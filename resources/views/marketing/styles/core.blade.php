{{--
    Marketing site — core stylesheet.

    Shared by every page in the public shell (home, features, how-it-works,
    pricing, stories). Home-only sections live in marketing/styles/home.blade.php.

    NO COLOUR LITERALS IN HERE. Everything resolves to a variable from
    <x-brand-tokens /> (resources/brand.json). Same for radii: --r-sm / --r /
    --r-lg / --r-pill.

    House style: soft rounded surfaces, one purple accent, one warm complement,
    plenty of white space, photographs of actual people, mixed-case display type
    with a serif-italic word for warmth.
--}}
<style id="marketing-core">
    /* ══ METRICS ═══════════════════════════════════════════════════════ */
    :root {
        --maxw:  1200px;
        --pad:   3rem;
        --nav-h: 66px;
    }

    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    [x-cloak] { display: none !important; }

    html { scroll-behavior: smooth; }
    html, body { overflow-x: clip; }

    body {
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        background: var(--surface);
        color: var(--ink);
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
    }

    img { display: block; max-width: 100%; }
    ::selection { background: var(--primary); color: #fff; }

    a:focus-visible, button:focus-visible, input:focus-visible,
    select:focus-visible, textarea:focus-visible, summary:focus-visible {
        outline: 3px solid var(--primary-400);
        outline-offset: 3px;
        border-radius: 4px;
    }

    .wrap {
        max-width: var(--maxw);
        margin: 0 auto;
        padding-left: var(--pad);
        padding-right: var(--pad);
    }

    /* ══ TYPE ══════════════════════════════════════════════════════════ */
    h1, h2, h3, h4 {
        font-family: 'Outfit', system-ui, sans-serif;
        letter-spacing: -0.035em;
        line-height: 1.04;
    }

    .h-display { font-weight: 700; font-size: clamp(2.6rem, 5.6vw, 4.3rem); }
    .h-section { font-weight: 700; font-size: clamp(2rem, 3.9vw, 3.1rem); line-height: 1.06; }
    .h-card    { font-weight: 700; font-size: 1.28rem; letter-spacing: -0.03em; }

    /* the warm, human accent: one word set in serif italic */
    .t-serif {
        font-family: 'DM Serif Display', Georgia, serif;
        font-style: italic;
        font-weight: 400;
        letter-spacing: -0.015em;
    }
    .t-accent { color: var(--primary); }

    .lead {
        font-size: 1.09rem;
        line-height: 1.68;
        color: var(--muted);
        font-weight: 450;
    }
    .lead strong { color: var(--ink); font-weight: 700; }

    .section-head { max-width: 660px; }
    .section-head.is-centred { margin-left: auto; margin-right: auto; text-align: center; }
    .section-head .lead { margin-top: 1.15rem; }

    /* ══ BUTTONS ═══════════════════════════════════════════════════════ */
    .btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
        border: 1.5px solid transparent; cursor: pointer;
        font-family: inherit; font-size: 0.96rem; font-weight: 700; letter-spacing: -0.01em;
        text-decoration: none; border-radius: var(--r-pill);
        padding: 0.95rem 1.75rem; white-space: nowrap;
        transition: background 0.18s, color 0.18s, border-color 0.18s,
                    box-shadow 0.18s, transform 0.18s;
    }
    .btn i { font-size: 1.2em; line-height: 1; }
    .btn:active { transform: translateY(1px); }

    .btn-primary { background: var(--primary); color: #fff; box-shadow: 0 8px 22px -10px rgba(var(--primary-rgb) / 0.7); }
    .btn-primary:hover { background: var(--primary-d); box-shadow: 0 14px 32px -12px rgba(var(--primary-rgb) / 0.8); }

    .btn-secondary { background: var(--surface); color: var(--ink); border-color: var(--line); }
    .btn-secondary:hover { border-color: var(--ink-300); background: var(--surface-2); }

    .btn-dark { background: var(--ink); color: #fff; }
    .btn-dark:hover { background: var(--ink-800); }

    .btn-on-dark { background: #fff; color: var(--ink); }
    .btn-on-dark:hover { background: var(--primary-50); color: var(--primary-700); }

    .btn-ghost-dark { background: transparent; color: #fff; border-color: var(--on-dark-line); }
    .btn-ghost-dark:hover { border-color: #fff; background: rgba(255,255,255,0.06); }

    .btn-sm { padding: 0.62rem 1.25rem; font-size: 0.87rem; }
    .btn-lg { padding: 1.1rem 2.1rem; font-size: 1.02rem; }

    .link-arrow {
        display: inline-flex; align-items: center; gap: 0.45rem;
        font-size: 0.95rem; font-weight: 700; color: var(--primary);
        text-decoration: none;
    }
    .link-arrow i { transition: transform 0.18s; }
    .link-arrow:hover i { transform: translateX(5px); }

    /* ══ NAV — floating pill ═══════════════════════════════════════════ */
    .nav-outer {
        /* Below the modal's z-50, or the sticky bar paints over an open modal. */
        position: sticky; top: 0; z-index: 40;
        padding: 0.85rem 0;
        background: linear-gradient(var(--surface) 55%, rgba(var(--surface-rgb) / 0));
    }
    .nav {
        max-width: var(--maxw); margin: 0 auto;
        height: var(--nav-h); padding: 0 0.7rem 0 1.15rem;
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        background: rgba(var(--surface-rgb) / 0.86);
        backdrop-filter: blur(18px) saturate(1.6);
        border: 1px solid var(--line);
        border-radius: var(--r-pill);
        box-shadow: var(--shadow-card);
    }
    /* the pill needs breathing room from the viewport edge on small screens */
    @media (max-width: 1260px) { .nav { margin: 0 var(--pad); } }

    .brand { display: inline-flex; align-items: center; gap: 0.6rem; text-decoration: none; flex-shrink: 0; }
    .brand-mark {
        width: 38px; height: 38px; flex-shrink: 0;
        border-radius: 13px;
        background: var(--primary); color: #fff;
        display: flex; align-items: center; justify-content: center; font-size: 1.22rem;
        box-shadow: 0 6px 16px -6px rgba(var(--primary-rgb) / 0.75);
    }
    .brand-name {
        font-family: 'Outfit', sans-serif; font-weight: 700;
        font-size: 1.2rem; letter-spacing: -0.045em; color: var(--ink);
    }

    .nav-mid { display: flex; align-items: center; gap: 0.1rem; }
    .nav-link {
        position: relative; padding: 0.55rem 0.9rem;
        border-radius: var(--r-pill);
        font-size: 0.93rem; font-weight: 600; color: var(--muted);
        text-decoration: none; transition: color 0.16s, background 0.16s;
    }
    .nav-link:hover { color: var(--ink); background: var(--ink-50); }
    .nav-link[aria-current="page"] { color: var(--primary-700); background: var(--primary-50); }

    .nav-right { display: flex; align-items: center; gap: 0.45rem; }

    .nav-toggle {
        display: none; width: 42px; height: 42px; flex-shrink: 0;
        border-radius: 50%;
        background: var(--ink-50); border: none; cursor: pointer;
        align-items: center; justify-content: center; font-size: 1.4rem; color: var(--ink);
    }

    .nav-drawer {
        display: none;
        max-width: var(--maxw); margin: 0.6rem auto 0;
        background: var(--surface); border: 1px solid var(--line);
        border-radius: var(--r-lg); box-shadow: var(--shadow-card);
        padding: 0.9rem 1.25rem 1.25rem;
    }
    @media (max-width: 1260px) { .nav-drawer { margin-left: var(--pad); margin-right: var(--pad); } }
    .nav-drawer.is-open { display: block; }
    .nav-drawer a.nav-link {
        display: block; padding: 0.85rem 0.5rem; border-radius: var(--r-sm);
        font-size: 1rem;
    }
    .nav-drawer a.nav-link + a.nav-link { border-top: 1px solid var(--line-2); }
    .nav-drawer .drawer-cta { margin-top: 0.9rem; display: flex; flex-direction: column; gap: 0.55rem; }
    .nav-drawer .drawer-cta .btn { width: 100%; }

    /* ══ AJAX ROUTER PROGRESS BAR ══════════════════════════════════════ */
    #route-progress {
        position: fixed; top: 0; left: 0; height: 3px; width: 0;
        background: var(--primary); z-index: 100;
        opacity: 0; transition: width 0.2s ease-out, opacity 0.2s;
    }
    #route-progress.is-active { opacity: 1; }

    #view { animation: view-in 0.3s ease-out both; }
    @keyframes view-in {
        from { opacity: 0; transform: translateY(9px); }
        to   { opacity: 1; transform: none; }
    }

    /* ══ SECTION SHELLS ════════════════════════════════════════════════ */
    .sec { padding: 6.5rem 0; position: relative; }
    .sec-tight { padding: 4.5rem 0; }
    .sec-alt { background: var(--surface-2); }
    .sec-dark { background: var(--ink); color: #fff; }
    .sec-dark .lead { color: var(--on-dark); }
    .sec-dark .lead strong, .sec-dark h2, .sec-dark h3 { color: #fff; }
    .sec-inner { position: relative; z-index: 1; }

    /* ══ BACKGROUND PATTERNS ═══════════════════════════════════════════ */
    .pattern { position: absolute; inset: 0; pointer-events: none; color: var(--primary-200); }
    .pattern-dots {
        background-image: radial-gradient(currentColor 1.6px, transparent 1.6px);
        background-size: 24px 24px;
    }
    .pattern-grid {
        background-image:
            linear-gradient(currentColor 1px, transparent 1px),
            linear-gradient(90deg, currentColor 1px, transparent 1px);
        background-size: 64px 64px;
    }
    /* fade towards the edges so it never reads as wallpaper */
    .pattern-fade {
        mask-image: radial-gradient(ellipse 70% 62% at 50% 40%, #000 18%, transparent 76%);
        -webkit-mask-image: radial-gradient(ellipse 70% 62% at 50% 40%, #000 18%, transparent 76%);
    }
    .on-dark .pattern, .sec-dark .pattern { color: rgba(255,255,255,0.13); }

    /* ══ CARDS ═════════════════════════════════════════════════════════ */
    .card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--r-lg);
        padding: 2.1rem 1.95rem;
        transition: border-color 0.22s, transform 0.22s, box-shadow 0.22s;
    }
    .card:hover { border-color: var(--primary-200); transform: translateY(-5px); box-shadow: var(--shadow-card); }
    .card .h-card { margin-bottom: 0.6rem; }
    .card p { font-size: 0.97rem; line-height: 1.68; color: var(--muted); }

    .card-dark { background: var(--ink); border-color: var(--ink); color: #fff; }
    .card-dark p { color: var(--on-dark); }
    .card-dark:hover { border-color: var(--ink); }

    /* a tinted variant so a grid of cards has rhythm without new hues */
    .card-tint { background: var(--primary-50); border-color: var(--primary-100); }
    .card-tint-2 { background: var(--secondary-50); border-color: var(--secondary-100); }

    /* icon tile — MDI only */
    .itile {
        width: 54px; height: 54px; flex-shrink: 0;
        border-radius: 17px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.55rem; margin-bottom: 1.3rem;
        background: var(--primary-100); color: var(--primary-700);
    }
    .itile-solid  { background: var(--primary); color: #fff; }
    .itile-warm   { background: var(--secondary-100); color: var(--secondary-800); }
    .itile-onDark { background: rgba(255,255,255,0.12); color: #fff; }

    .grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 1.4rem; }
    .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 1.4rem; }
    .mt-grid { margin-top: 3.4rem; }

    /* ══ OCCASION STRIP ════════════════════════════════════════════════ */
    .marquee {
        background: var(--primary-50);
        border-top: 1px solid var(--primary-100);
        border-bottom: 1px solid var(--primary-100);
        padding: 1.15rem 0; overflow: hidden;
    }
    .marquee-track { display: flex; width: max-content; animation: marquee 42s linear infinite; }
    .marquee:hover .marquee-track { animation-play-state: paused; }
    .marquee-group { display: flex; align-items: center; flex-shrink: 0; }
    .marquee-item {
        display: inline-flex; align-items: center; gap: 0.55rem;
        font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 1.05rem;
        letter-spacing: -0.025em; color: var(--primary-800);
        padding: 0 1.4rem;
    }
    .marquee-item i { color: var(--primary); font-size: 1.3rem; }
    @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

    /* ══ STATS ═════════════════════════════════════════════════════════ */
    .stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 1.5rem; }
    .stat { text-align: center; }
    .stat .n {
        font-family: 'Outfit', sans-serif; font-weight: 700;
        font-size: clamp(2.3rem, 4.4vw, 3.2rem); letter-spacing: -0.05em; line-height: 1;
        color: var(--primary);
    }
    .stat .l {
        margin-top: 0.55rem; font-size: 0.85rem; font-weight: 600;
        color: var(--muted); letter-spacing: -0.005em;
    }
    .sec-dark .stat .n, .on-dark .stat .n { color: #fff; }
    .sec-dark .stat .l, .on-dark .stat .l { color: var(--on-dark); }

    /* ══ NUMBERED STEPS ════════════════════════════════════════════════ */
    .step { position: relative; }
    .step-num {
        display: inline-flex; align-items: center; justify-content: center;
        width: 52px; height: 52px; border-radius: 50%;
        background: var(--primary); color: #fff;
        font-family: 'Outfit', sans-serif; font-weight: 700;
        font-size: 1.3rem; letter-spacing: -0.04em;
        margin-bottom: 1.15rem;
    }
    .step h3 { font-weight: 700; font-size: 1.3rem; margin-bottom: 0.55rem; }
    .step p { font-size: 0.97rem; line-height: 1.7; color: var(--muted); }
    .sec-dark .step p { color: var(--on-dark); }

    /* ══ QUOTES ════════════════════════════════════════════════════════ */
    .quote {
        background: var(--surface); border: 1px solid var(--line);
        border-radius: var(--r-lg); padding: 2.3rem 2.1rem;
    }
    .quote .qmark { color: var(--primary-300); font-size: 2.2rem; line-height: 1; margin-bottom: 0.7rem; display: block; }
    .quote blockquote {
        font-family: 'Outfit', sans-serif; font-weight: 500;
        font-size: 1.16rem; line-height: 1.55; letter-spacing: -0.025em; color: var(--ink);
    }
    .quote-author { margin-top: 1.6rem; display: flex; align-items: center; gap: 0.8rem; }
    .quote-author img { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    .qa-name { font-size: 0.92rem; font-weight: 700; }
    .qa-meta { font-size: 0.83rem; color: var(--muted); margin-top: 0.1rem; }

    .quote-lg { padding: 3.4rem 3rem; text-align: center; background: var(--surface-2); border-color: var(--primary-100); }
    .quote-lg blockquote { font-size: clamp(1.35rem, 2.5vw, 2rem); max-width: 780px; margin: 0 auto; }
    .quote-lg .quote-author { justify-content: center; }
    .quote-lg .qmark { font-size: 2.6rem; }

    .price-currency {
        display: flex; align-items: center; justify-content: center; gap: 0.45rem;
        flex-wrap: wrap; text-align: center;
        margin-top: 1.75rem;
        font-size: 0.84rem; color: var(--muted);
    }
    .price-currency i { color: var(--primary); font-size: 1.05rem; line-height: 1; }

    /* ══ CHECK LISTS ═══════════════════════════════════════════════════ */
    .checks { list-style: none; display: flex; flex-direction: column; gap: 0.85rem; margin-top: 1.75rem; }
    .checks li { display: flex; align-items: flex-start; gap: 0.65rem; font-size: 0.97rem; line-height: 1.6; color: var(--muted); }
    .checks i { color: var(--primary); font-size: 1.25rem; line-height: 1.3; flex-shrink: 0; }
    .checks strong { color: var(--ink); font-weight: 700; }

    /* ══ PRICING ═══════════════════════════════════════════════════════ */
    .price-grid { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 1.4rem; align-items: start; }
    .price-card {
        background: var(--surface); border: 1px solid var(--line);
        border-radius: var(--r-lg); padding: 2.2rem 2rem; position: relative;
    }
    .price-card.is-featured {
        border-color: var(--primary); border-width: 2px;
        box-shadow: var(--shadow-card);
    }
    .price-tag {
        position: absolute; top: -14px; left: 2rem;
        background: var(--primary); color: #fff;
        font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase;
        padding: 0.4rem 0.85rem; border-radius: var(--r-pill);
    }
    .price-name { font-size: 0.95rem; font-weight: 700; color: var(--primary-700); }
    .price-amount {
        font-family: 'Outfit', sans-serif; font-weight: 700;
        font-size: 3rem; letter-spacing: -0.055em; line-height: 1;
        margin: 0.85rem 0 0.65rem;
    }
    .price-amount small {
        display: block; margin-top: 0.5rem;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.88rem; font-weight: 600; color: var(--muted); letter-spacing: 0;
    }
    .price-note { font-size: 0.92rem; color: var(--muted); line-height: 1.6; }
    .price-card .btn { width: 100%; margin-top: 1.8rem; }
    .price-card .checks { margin-top: 1.5rem; }

    /* ══ PRODUCT PREVIEW BITS (reused on inner pages) ══════════════════ */
    .pv-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
    .pv-title { font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 1.16rem; letter-spacing: -0.03em; }
    .pv-url { font-size: 0.78rem; color: var(--muted); margin-top: 0.15rem; }
    .pv-live {
        display: inline-flex; align-items: center; gap: 0.35rem; flex-shrink: 0;
        background: var(--ok-l); color: var(--ok);
        font-size: 0.68rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;
        padding: 0.34rem 0.7rem; border-radius: var(--r-pill);
    }
    .pv-live .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--ok); }

    .pv-raise { margin-top: 1.2rem; background: var(--primary-50); border-radius: var(--r); padding: 1.15rem 1.25rem; }
    .pv-label { font-size: 0.78rem; font-weight: 600; color: var(--primary-700); }
    .pv-amount {
        font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 2.2rem;
        letter-spacing: -0.05em; line-height: 1; margin-top: 0.35rem; color: var(--primary-900);
    }
    .pv-bar { margin-top: 0.85rem; height: 8px; border-radius: var(--r-pill); background: var(--primary-200); overflow: hidden; }
    .pv-bar span { display: block; height: 100%; border-radius: var(--r-pill); background: var(--primary); }
    .pv-bar-meta { display: flex; justify-content: space-between; margin-top: 0.5rem; font-size: 0.75rem; color: var(--primary-700); font-weight: 600; }

    .pv-stats { margin-top: 0.85rem; display: grid; grid-template-columns: 1fr 1fr; gap: 0.7rem; }
    .pv-stat { background: var(--surface-2); border-radius: var(--r-sm); padding: 0.85rem 1rem; }
    .pv-stat .n { font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 1.35rem; letter-spacing: -0.04em; }
    .pv-stat .l { font-size: 0.74rem; color: var(--muted); font-weight: 600; margin-top: 0.1rem; }

    .pv-wish {
        margin-top: 0.85rem; display: flex; gap: 0.7rem; align-items: flex-start;
        border: 1px solid var(--line); border-radius: var(--r-sm); padding: 0.85rem 0.95rem;
    }
    .pv-av {
        width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0; overflow: hidden;
        background: var(--secondary-100); color: var(--secondary-800);
        display: flex; align-items: center; justify-content: center;
        font-size: 0.74rem; font-weight: 700;
    }
    .pv-av img { width: 100%; height: 100%; object-fit: cover; }
    .pv-wish-name { font-size: 0.85rem; font-weight: 700; }
    .pv-wish-body { font-size: 0.83rem; color: var(--muted); line-height: 1.5; margin-top: 0.1rem; }

    /* ══ CTA BAND ══════════════════════════════════════════════════════ */
    .cta-band {
        position: relative; background: var(--primary-900); color: #fff;
        padding: 6rem 0; overflow: hidden; text-align: center;
    }
    .cta-band .sec-inner { max-width: 760px; margin: 0 auto; }
    .cta-band h2 { color: #fff; }
    .cta-band p { margin-top: 1.3rem; font-size: 1.06rem; color: var(--on-dark); }
    .cta-band .btn { margin-top: 2.3rem; }
    .cta-note { margin-top: 1.15rem; font-size: 0.86rem; color: var(--on-dark-2); }

    .icon-row { display: flex; justify-content: center; gap: 0.6rem; margin-bottom: 1.9rem; }
    .icon-row span {
        width: 54px; height: 54px; border-radius: 18px;
        background: rgba(255,255,255,0.1); color: #fff;
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
    }

    /* ══ PAGE HEADER (inner pages) ═════════════════════════════════════ */
    .page-head {
        position: relative; padding: 4.5rem 0 4rem; overflow: hidden;
        background: var(--surface-2);
        border-bottom: 1px solid var(--line);
    }
    /* `.wrap` carries `margin: 0 auto`; constrain the width without re-centring
       so page headers stay left-aligned like every other section head. */
    .page-head .sec-inner { max-width: 780px; margin-left: 0; margin-right: auto; }
    .page-head .lead { margin-top: 1.2rem; }

    /* ══ FOOTER ════════════════════════════════════════════════════════ */
    .site-footer { background: var(--ink); color: #fff; padding: 5rem 0 0; }
    .foot-grid {
        display: grid; grid-template-columns: 1.7fr 1fr 1fr 1fr; gap: 3rem;
        padding-bottom: 3.5rem;
    }
    .foot-brand .brand-name { color: #fff; }
    .foot-blurb { margin-top: 1.15rem; font-size: 0.95rem; line-height: 1.7; color: var(--on-dark); max-width: 310px; }
    .foot-social { margin-top: 1.6rem; display: flex; gap: 0.5rem; }
    .foot-social a {
        width: 40px; height: 40px; border-radius: 50%;
        background: rgba(255,255,255,0.08); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; text-decoration: none; transition: background 0.16s;
    }
    .foot-social a:hover { background: var(--primary); }

    .foot-col h4 {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.95rem; font-weight: 700; color: #fff; margin-bottom: 1.2rem;
    }
    .foot-col ul { list-style: none; display: flex; flex-direction: column; gap: 0.8rem; }
    .foot-col a { font-size: 0.94rem; color: var(--on-dark); text-decoration: none; transition: color 0.16s; }
    .foot-col a:hover { color: #fff; }

    .foot-bottom {
        border-top: 1px solid var(--on-dark-line);
        padding: 1.7rem 0 2rem;
        display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
    }
    .foot-bottom p { font-size: 0.86rem; color: var(--on-dark-2); }
    .foot-meta { display: flex; gap: 1.4rem; flex-wrap: wrap; }
    .foot-meta span { font-size: 0.86rem; color: var(--on-dark-2); display: inline-flex; align-items: center; gap: 0.4rem; }

    /* ══ MODAL SHEET (create-event) ════════════════════════════════════ */
    .sheet-head {
        position: relative; padding: 2.1rem 2.2rem 1.6rem;
        background: var(--primary-50); border-bottom: 1px solid var(--primary-100);
    }
    .sheet-head h2 { font-size: 1.9rem; font-weight: 700; }
    .sheet-head p { margin-top: 0.5rem; font-size: 0.94rem; color: var(--muted); }
    .sheet-close {
        position: absolute; top: 1.15rem; right: 1.15rem;
        width: 38px; height: 38px; border-radius: 50%;
        background: rgba(var(--surface-rgb) / 0.7); border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; color: var(--muted); transition: background 0.15s, color 0.15s;
    }
    .sheet-close:hover { background: var(--surface); color: var(--ink); }
    .sheet-body { padding: 1.9rem 2.2rem 2.2rem; }

    .steps-bar { display: flex; align-items: center; gap: 0.85rem; margin-bottom: 1.9rem; }
    .steps-bar .pip {
        width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.9rem; font-weight: 700;
        background: var(--ink-100); color: var(--muted-2);
        transition: background 0.25s, color 0.25s;
    }
    .steps-bar .pip.is-on { background: var(--primary); color: #fff; }
    .steps-bar .rail { flex: 1; height: 4px; border-radius: var(--r-pill); background: var(--ink-100); overflow: hidden; }
    .steps-bar .rail span { display: block; height: 100%; width: 0; background: var(--primary); transition: width 0.45s ease; }
    .steps-bar .rail span.is-on { width: 100%; }

    .field + .field { margin-top: 1.1rem; }
    .field-label {
        display: block; margin-bottom: 0.45rem;
        font-size: 0.85rem; font-weight: 700; color: var(--ink-700);
    }
    .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }

    /* Occasion picker — icon tiles in place of a <select> */
    .occasion-grid { display: flex; flex-wrap: wrap; gap: 0.6rem; }
    .occasion-tile {
        flex: 1 1 calc(33.333% - 0.4rem); min-width: 104px;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.45rem;
        padding: 0.95rem 0.6rem;
        font: inherit; font-size: 0.85rem; font-weight: 600; color: var(--ink-700); text-align: center;
        background: var(--surface);
        border: 1.5px solid var(--line); border-radius: var(--r-sm);
        cursor: pointer;
        transition: border-color 0.16s, background 0.16s, color 0.16s, box-shadow 0.16s, transform 0.16s;
    }
    .occasion-tile i { font-size: 1.5rem; line-height: 1; color: var(--muted); transition: color 0.16s; }
    .occasion-tile:hover { border-color: var(--primary-200); background: var(--primary-50); transform: translateY(-2px); }
    .occasion-tile:focus-visible { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-100); }
    .occasion-tile.is-on {
        border-color: var(--primary); background: var(--primary-50); color: var(--primary-700);
        box-shadow: 0 0 0 3px var(--primary-100);
    }
    .occasion-tile.is-on i { color: var(--primary); }
    .input {
        display: block; width: 100%;
        padding: 0.9rem 1.05rem;
        font-family: inherit; font-size: 0.95rem; font-weight: 500; color: var(--ink);
        background: var(--surface);
        border: 1.5px solid var(--line); border-radius: var(--r-sm);
        transition: border-color 0.16s, box-shadow 0.16s;
    }
    .input:focus {
        outline: none; border-color: var(--primary);
        box-shadow: 0 0 0 4px var(--primary-100);
    }
    .input::placeholder { color: var(--muted-2); }
    .field-hint { margin-top: 0.5rem; font-size: 0.83rem; color: var(--muted); }
    .sheet-body .btn-block { width: 100%; margin-top: 1.8rem; }
    .sheet-alt {
        margin-top: 0.9rem; text-align: center;
        font-size: 0.87rem; color: var(--muted);
    }
    .sheet-alt a, .sheet-alt button {
        background: none; border: none; cursor: pointer; font: inherit;
        color: var(--primary); font-weight: 700; text-decoration: none;
    }
    .sheet-alt a:hover, .sheet-alt button:hover { text-decoration: underline; }

    /* ══ RESPONSIVE ════════════════════════════════════════════════════ */
    @media (max-width: 1000px) {
        :root { --pad: 1.5rem; }
        .sec { padding: 4.5rem 0; }
        .grid-3 { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .foot-grid { grid-template-columns: 1fr 1fr; gap: 2.5rem; }
        .price-grid { grid-template-columns: minmax(0,1fr); }
        .stats-grid { grid-template-columns: repeat(2, minmax(0,1fr)); gap: 2.5rem 1.5rem; }
        .quote-lg { padding: 2.3rem 1.8rem; }
        .cta-band { padding: 4.25rem 0; }
        .sheet-head, .sheet-body { padding-left: 1.5rem; padding-right: 1.5rem; }
    }

    @media (max-width: 880px) {
        .nav-mid { display: none; }
        .nav-right .nav-desktop-only { display: none; }
        .nav-toggle { display: flex; }
    }

    @media (max-width: 620px) {
        .grid-3, .grid-2 { grid-template-columns: minmax(0,1fr); }
        .foot-grid { grid-template-columns: 1fr; gap: 2.25rem; }
        .foot-bottom { flex-direction: column; align-items: flex-start; }
        .h-display { font-size: clamp(2.2rem, 10vw, 2.9rem); }
        .icon-row span { width: 44px; height: 44px; font-size: 1.2rem; border-radius: 14px; }
        .field-row { grid-template-columns: 1fr; }
        .sheet-head h2 { font-size: 1.6rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration: 0.001ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.001ms !important;
            scroll-behavior: auto !important;
        }
    }
</style>
