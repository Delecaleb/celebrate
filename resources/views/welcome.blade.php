<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>CelebrateMi — Celebrate out loud</title>
    <meta name="description" content="Create a celebration page in 60 seconds. Collect wishes, gifts and money from everyone who loves you — and keep the memories forever.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        /*
          Design tokens — mirrors the "bold & festive" palette in tailwind.config.js.
          Duplicated inline on purpose: this page must render correctly even when
          the Vite bundle has not been built (see the conditional directive above).
        */
        :root {
            --ink:       #120a18;
            --ink-2:     #1d1226;
            --ink-3:     #251a2d;
            --white:     #ffffff;
            --cream:     #fff7f2;
            --muted:     #7c7185;
            --muted-d:   rgba(255,255,255,0.58);

            --party:     #ff2d8f;
            --party-lt:  #ff5aa8;
            --flame:     #ff7a18;
            --grape:     #7c3aed;
            --grape-lt:  #a855f7;
            --sun:       #ffd84d;

            --grad:      linear-gradient(120deg, #7c3aed 0%, #ff2d8f 48%, #ff7a18 100%);
            --grad-soft: linear-gradient(120deg, #a855f7 0%, #ff5aa8 50%, #ffa14d 100%);

            --r-sm: 14px;
            --r:    22px;
            --r-lg: 32px;

            --shadow-party: 0 18px 48px -12px rgba(255,45,143,0.55);
            --shadow-lift:  0 24px 64px -16px rgba(18,10,24,0.28);
            --shadow-card:  0 4px 24px rgba(18,10,24,0.07);

            --maxw: 1200px;
            --pad:  3rem;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        html, body { overflow-x: clip; }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: var(--ink);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }

        img { display: block; max-width: 100%; }

        ::selection { background: var(--party); color: #fff; }

        /* Visible keyboard focus on every interactive element */
        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        select:focus-visible {
            outline: 3px solid var(--sun);
            outline-offset: 3px;
            border-radius: 6px;
        }

        .wrap { max-width: var(--maxw); margin: 0 auto; padding-left: var(--pad); padding-right: var(--pad); }

        /* ── SHARED TYPE ────────────────────────────────────────────── */
        .display {
            font-family: 'Outfit', system-ui, sans-serif;
            font-weight: 900;
            letter-spacing: -0.04em;
            line-height: 0.94;
            text-transform: uppercase;
        }

        .grad-text {
            background: var(--grad-soft);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .section-head { max-width: 640px; }
        .section-head h2 {
            font-family: 'Outfit', system-ui, sans-serif;
            font-weight: 900;
            letter-spacing: -0.035em;
            line-height: 0.98;
            font-size: clamp(2.1rem, 4.6vw, 3.5rem);
            text-transform: uppercase;
            margin-top: 1rem;
        }
        .section-head p {
            margin-top: 1.15rem;
            font-size: 1.02rem;
            line-height: 1.7;
            color: var(--muted);
        }

        /* ── BUTTONS ────────────────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.98rem;
            font-weight: 800;
            text-decoration: none;
            border-radius: 99px;
            padding: 1.05rem 2.1rem;
            transition: transform 0.18s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.18s, background 0.18s, color 0.18s;
            white-space: nowrap;
        }

        .btn-grad {
            background: var(--grad);
            background-size: 180% 180%;
            color: #fff;
            box-shadow: var(--shadow-party);
        }
        .btn-grad:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 24px 60px -12px rgba(255,45,143,0.7); }
        .btn-grad:active { transform: translateY(-1px) scale(0.99); }

        .btn-light { background: #fff; color: var(--ink); box-shadow: 0 16px 40px -12px rgba(0,0,0,0.45); }
        .btn-light:hover { transform: translateY(-3px) scale(1.02); }

        .btn-ink { background: var(--ink); color: #fff; }
        .btn-ink:hover { transform: translateY(-2px); background: var(--ink-3); }

        .btn-outline-d {
            background: rgba(255,255,255,0.06);
            color: #fff;
            border: 1.5px solid rgba(255,255,255,0.22);
            backdrop-filter: blur(8px);
        }
        .btn-outline-d:hover { background: rgba(255,255,255,0.14); transform: translateY(-2px); }

        .btn-sm { padding: 0.65rem 1.35rem; font-size: 0.85rem; }

        /* ── NAV ────────────────────────────────────────────────────── */
        .nav-outer {
            position: sticky; top: 0; z-index: 60;
            /* Solid, not translucent: the bar sits above the hero, so a translucent
               dark over the white <body> rendered as washed-out grey. */
            background: var(--ink);
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .nav {
            max-width: var(--maxw); margin: 0 auto;
            padding: 0.9rem var(--pad);
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        }
        .brand { display: inline-flex; align-items: center; gap: 0.7rem; text-decoration: none; }
        .brand-mark {
            width: 40px; height: 40px; border-radius: 13px;
            background: var(--grad);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem;
            box-shadow: 0 8px 22px -6px rgba(255,45,143,0.7);
            flex-shrink: 0;
        }
        .brand-name {
            font-family: 'Outfit', sans-serif; font-weight: 900;
            font-size: 1.22rem; letter-spacing: -0.035em; color: #fff;
        }
        .nav-links { display: flex; align-items: center; gap: 0.35rem; }
        .nav-link {
            padding: 0.55rem 1rem; border-radius: 99px;
            font-size: 0.9rem; font-weight: 600;
            color: rgba(255,255,255,0.72); text-decoration: none;
            transition: color 0.15s, background 0.15s;
        }
        .nav-link:hover { color: #fff; background: rgba(255,255,255,0.09); }
        .nav-mid { display: flex; gap: 0.15rem; }

        /* ── HERO ───────────────────────────────────────────────────── */
        .hero {
            position: relative;
            background: var(--ink);
            overflow: hidden;
            padding: 5.5rem 0 7rem;
        }

        /* drifting colour blobs */
        .blob { position: absolute; border-radius: 50%; filter: blur(90px); pointer-events: none; }
        .blob-1 { width: 620px; height: 620px; top: -240px; left: -180px; background: rgba(124,58,237,0.55); animation: drift 20s ease-in-out infinite; }
        .blob-2 { width: 560px; height: 560px; top: 40px; right: -200px; background: rgba(255,45,143,0.45); animation: drift 26s ease-in-out infinite reverse; }
        .blob-3 { width: 460px; height: 460px; bottom: -260px; left: 42%; background: rgba(255,122,24,0.38); animation: drift 22s ease-in-out infinite; }

        /* dotted texture */
        .hero::after {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background-image: radial-gradient(rgba(255,255,255,0.10) 1px, transparent 1px);
            background-size: 26px 26px;
            mask-image: radial-gradient(ellipse 80% 60% at 50% 40%, #000 30%, transparent 78%);
            -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 40%, #000 30%, transparent 78%);
        }

        .hero-inner {
            position: relative; z-index: 2;
            max-width: var(--maxw); margin: 0 auto; padding: 0 var(--pad);
            display: grid; gap: 4rem; align-items: center;
            /* minmax(0,…) rather than plain fr: grid items default to
               min-width:auto, which lets the wide product card stretch the
               column past the viewport and push the whole hero off-screen. */
            grid-template-columns: minmax(0, 1.08fr) minmax(0, 0.92fr);
        }

        .hero-badge {
            background: rgba(255,255,255,0.09);
            border: 1px solid rgba(255,255,255,0.16);
            color: #fff;
            padding: 0.5rem 1.05rem; border-radius: 99px;
            backdrop-filter: blur(8px);
        }
        .hero-badge .dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: #22e07a; box-shadow: 0 0 0 4px rgba(34,224,122,0.22);
        }

        .hero h1 {
            font-family: 'Outfit', system-ui, sans-serif;
            font-weight: 900;
            font-size: clamp(3rem, 7.4vw, 6rem);
            line-height: 0.9;
            letter-spacing: -0.045em;
            text-transform: uppercase;
            color: #fff;
            margin: 1.6rem 0 0;
        }
        .hero h1 .line { display: block; }

        .hero-sub {
            margin-top: 1.75rem;
            font-size: 1.1rem;
            line-height: 1.72;
            color: var(--muted-d);
            max-width: 470px;
        }
        .hero-sub strong { color: #fff; font-weight: 700; }

        .hero-ctas { margin-top: 2.5rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }

        .hero-proof { margin-top: 3rem; display: flex; align-items: center; gap: 0.95rem; }
        .avatars { display: flex; }
        .avatars img {
            width: 40px; height: 40px; border-radius: 50%;
            object-fit: cover; border: 2.5px solid var(--ink);
        }
        .avatars img + img { margin-left: -13px; }
        .avatars .more {
            width: 40px; height: 40px; border-radius: 50%; margin-left: -13px;
            background: var(--grad); border: 2.5px solid var(--ink);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.68rem; font-weight: 800; color: #fff;
        }
        .proof-text { font-size: 0.86rem; line-height: 1.5; color: var(--muted-d); }
        .proof-text strong { color: #fff; font-weight: 700; }
        .stars { color: var(--sun); letter-spacing: 0.05em; }

        /* ── HERO PRODUCT CARD ──────────────────────────────────────── */
        .hero-card-stack { position: relative; }

        .hero-card {
            position: relative; z-index: 2;
            background: #fff;
            border-radius: var(--r-lg);
            padding: 1.5rem;
            box-shadow: 0 40px 90px -20px rgba(0,0,0,0.6);
            transform: rotate(-1.6deg);
        }

        .hc-top { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .hc-title { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.2rem; letter-spacing: -0.025em; }
        .hc-meta { font-size: 0.76rem; color: var(--muted); margin-top: 0.15rem; }
        .hc-live {
            display: inline-flex; align-items: center; gap: 0.35rem;
            background: #eafaf0; color: #067a45;
            font-size: 0.68rem; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase;
            padding: 0.35rem 0.7rem; border-radius: 99px; flex-shrink: 0;
        }
        .hc-live .dot { width: 6px; height: 6px; border-radius: 50%; background: #10b981; }

        .hc-raise { margin-top: 1.25rem; background: var(--cream); border-radius: var(--r); padding: 1.15rem 1.25rem; }
        .hc-raise-label { font-size: 0.68rem; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); }
        .hc-amount {
            font-family: 'Outfit', sans-serif; font-weight: 900;
            font-size: 2.35rem; letter-spacing: -0.04em; line-height: 1; margin-top: 0.4rem;
        }
        .hc-bar { margin-top: 0.85rem; height: 8px; border-radius: 99px; background: #ffe0ef; overflow: hidden; }
        .hc-bar span { display: block; height: 100%; width: 68%; border-radius: 99px; background: var(--grad); }
        .hc-bar-meta { display: flex; justify-content: space-between; margin-top: 0.5rem; font-size: 0.72rem; color: var(--muted); font-weight: 600; }

        .hc-grid { margin-top: 0.85rem; display: grid; grid-template-columns: 1fr 1fr; gap: 0.7rem; }
        .hc-stat { background: var(--cream); border-radius: var(--r-sm); padding: 0.85rem 1rem; }
        .hc-stat .n { font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 1.4rem; letter-spacing: -0.03em; }
        .hc-stat .l { font-size: 0.7rem; color: var(--muted); font-weight: 600; margin-top: 0.1rem; }

        .hc-wish {
            margin-top: 0.85rem; display: flex; gap: 0.7rem; align-items: flex-start;
            background: #fff; border: 1.5px solid #f2ecf5; border-radius: var(--r-sm); padding: 0.85rem 0.95rem;
        }
        .hc-wish-av {
            width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
            background: var(--grad-soft); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 800;
        }
        .hc-wish-name { font-size: 0.82rem; font-weight: 700; }
        .hc-wish-body { font-size: 0.8rem; color: var(--muted); line-height: 1.5; margin-top: 0.1rem; }

        /* floating chips around the card */
        .chip {
            position: absolute; z-index: 3;
            background: #fff; border-radius: 99px;
            padding: 0.6rem 1rem;
            display: flex; align-items: center; gap: 0.5rem;
            font-size: 0.8rem; font-weight: 700;
            box-shadow: var(--shadow-lift);
            white-space: nowrap;
        }
        /* Chips hang off the card's corners/edges so they never cover its text. */
        .chip-1 { top: -22px; left: -30px; animation: float 6s ease-in-out infinite; }
        .chip-2 { top: 104px; right: -46px; animation: float 7.5s ease-in-out infinite 0.8s; }
        .chip-3 { bottom: -24px; left: -28px; animation: float 6.8s ease-in-out infinite 1.6s; }
        .chip .em { font-size: 1rem; }
        .chip .amt { color: var(--party); font-weight: 800; }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50%      { transform: translateY(-13px) rotate(3deg); }
        }
        @keyframes drift {
            0%, 100% { transform: translate3d(0,0,0) scale(1); }
            50%      { transform: translate3d(30px,-24px,0) scale(1.08); }
        }
        @keyframes marquee {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* ── MARQUEE ────────────────────────────────────────────────── */
        .marquee {
            background: var(--grad);
            padding: 1.15rem 0;
            overflow: hidden;
            border-top: 3px solid var(--ink);
            border-bottom: 3px solid var(--ink);
        }
        .marquee-track {
            display: flex; width: max-content;
            animation: marquee 34s linear infinite;
        }
        .marquee:hover .marquee-track { animation-play-state: paused; }
        .marquee-group { display: flex; align-items: center; flex-shrink: 0; }
        .marquee-item {
            font-family: 'Outfit', sans-serif; font-weight: 900;
            font-size: 1.15rem; letter-spacing: -0.02em; text-transform: uppercase;
            color: #fff; padding: 0 1.4rem;
            display: inline-flex; align-items: center; gap: 0.6rem;
        }
        .marquee-item .sep { color: rgba(255,255,255,0.5); }

        /* ── FEATURES ───────────────────────────────────────────────── */
        .features { padding: 7rem 0; background: var(--white); }
        .features .eyebrow { color: var(--party); }

        .feat-grid {
            margin-top: 3.5rem;
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem;
        }

        .feat {
            position: relative; overflow: hidden;
            border-radius: var(--r-lg);
            padding: 2.1rem 1.9rem;
            background: var(--cream);
            border: 2px solid #f6ebe4;
            transition: transform 0.24s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.24s, border-color 0.24s;
        }
        .feat:hover { transform: translateY(-7px); box-shadow: var(--shadow-lift); border-color: transparent; }

        .feat-icon {
            width: 58px; height: 58px; border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.65rem; margin-bottom: 1.35rem;
            box-shadow: 0 10px 26px -8px rgba(18,10,24,0.4);
        }
        .ic-party { background: linear-gradient(135deg,#ff5aa8,#ff2d8f); }
        .ic-flame { background: linear-gradient(135deg,#ffaa70,#ff7a18); }
        .ic-grape { background: linear-gradient(135deg,#a855f7,#7c3aed); }
        .ic-sun   { background: linear-gradient(135deg,#ffe27a,#f5c518); }
        .ic-mint  { background: linear-gradient(135deg,#5eead4,#0d9488); }
        .ic-sky   { background: linear-gradient(135deg,#7dd3fc,#0284c7); }

        .feat h3 {
            font-family: 'Outfit', sans-serif; font-weight: 800;
            font-size: 1.32rem; letter-spacing: -0.025em; margin-bottom: 0.6rem;
        }
        .feat p { font-size: 0.93rem; line-height: 1.68; color: var(--muted); }

        /* the middle card is the money shot — make it pop */
        .feat.is-hero {
            background: var(--ink); border-color: var(--ink); color: #fff;
        }
        .feat.is-hero p { color: var(--muted-d); }
        .feat.is-hero::before {
            content: ''; position: absolute; top: -90px; right: -70px;
            width: 260px; height: 260px; border-radius: 50%;
            background: radial-gradient(circle, rgba(255,45,143,0.5) 0%, transparent 70%);
            pointer-events: none;
        }
        .feat.is-hero > * { position: relative; z-index: 1; }

        /* ── HOW IT WORKS ───────────────────────────────────────────── */
        .how { background: var(--ink); padding: 7rem 0; position: relative; overflow: hidden; }
        .how::before {
            content: ''; position: absolute; width: 700px; height: 700px; border-radius: 50%;
            top: -300px; right: -240px;
            background: radial-gradient(circle, rgba(124,58,237,0.4) 0%, transparent 70%);
            pointer-events: none;
        }
        .how-inner { position: relative; z-index: 1; }
        .how .eyebrow { color: var(--sun); }
        .how .section-head h2 { color: #fff; }
        .how .section-head p { color: var(--muted-d); }

        .steps { margin-top: 4rem; display: grid; grid-template-columns: repeat(3,1fr); gap: 1.75rem; }
        .step {
            background: rgba(255,255,255,0.045);
            border: 1.5px solid rgba(255,255,255,0.1);
            border-radius: var(--r-lg);
            padding: 2.1rem 1.9rem;
            transition: transform 0.24s, background 0.24s, border-color 0.24s;
        }
        .step:hover { transform: translateY(-6px); background: rgba(255,255,255,0.075); border-color: rgba(255,255,255,0.2); }
        /* `p.` so this wins over the `.step p` body rule below it. */
        p.step-num {
            font-family: 'Outfit', sans-serif; font-weight: 900;
            font-size: 3.6rem; line-height: 0.85; letter-spacing: -0.05em;
            margin-bottom: 1.2rem;
        }
        .step h3 {
            font-family: 'Outfit', sans-serif; font-weight: 800;
            font-size: 1.3rem; letter-spacing: -0.025em; color: #fff; margin-bottom: 0.6rem;
        }
        .step p { font-size: 0.93rem; line-height: 1.7; color: var(--muted-d); }

        /* ── STATS ──────────────────────────────────────────────────── */
        .stats { background: var(--cream); padding: 4.5rem 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1.5rem; text-align: center; }
        .stat-n {
            font-family: 'Outfit', sans-serif; font-weight: 900;
            font-size: clamp(2.3rem, 4.5vw, 3.4rem); letter-spacing: -0.045em; line-height: 1;
        }
        .stat-l {
            margin-top: 0.5rem; font-size: 0.78rem; font-weight: 700;
            letter-spacing: 0.09em; text-transform: uppercase; color: var(--muted);
        }

        /* ── TESTIMONIAL ────────────────────────────────────────────── */
        .quote-sec { padding: 7rem 0; background: var(--white); }
        .quote-card {
            position: relative; overflow: hidden;
            background: var(--grad);
            border-radius: var(--r-lg);
            padding: 4rem 3.5rem;
            text-align: center;
            box-shadow: var(--shadow-party);
        }
        .quote-card::before {
            content: '\201C';
            position: absolute; top: -46px; left: 34px;
            /* Georgia: Outfit renders a curly quote as two heavy blobs at 900. */
            font-family: Georgia, 'Times New Roman', serif; font-size: 15rem; font-weight: 700;
            color: rgba(255,255,255,0.16); line-height: 1; pointer-events: none;
        }
        .quote-card blockquote {
            position: relative; z-index: 1;
            font-family: 'Outfit', sans-serif; font-weight: 800;
            font-size: clamp(1.35rem, 2.7vw, 2.1rem);
            line-height: 1.3; letter-spacing: -0.03em; color: #fff;
            max-width: 780px; margin: 0 auto;
        }
        .quote-author {
            position: relative; z-index: 1;
            margin-top: 2.25rem; display: flex; align-items: center; justify-content: center; gap: 0.85rem;
        }
        .quote-author img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,0.4); }
        .qa-name { font-size: 0.95rem; font-weight: 800; color: #fff; text-align: left; }
        .qa-meta { font-size: 0.82rem; color: rgba(255,255,255,0.75); text-align: left; }

        /* ── FINAL CTA ──────────────────────────────────────────────── */
        .final { background: var(--ink); padding: 7rem 0 6rem; text-align: center; position: relative; overflow: hidden; }
        .final::before {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background:
                radial-gradient(circle at 18% 25%, rgba(255,45,143,0.35) 0%, transparent 45%),
                radial-gradient(circle at 82% 70%, rgba(255,122,24,0.3) 0%, transparent 45%),
                radial-gradient(circle at 50% 110%, rgba(124,58,237,0.4) 0%, transparent 50%);
        }
        .final-inner { position: relative; z-index: 1; }
        .final h2 {
            font-family: 'Outfit', sans-serif; font-weight: 900;
            font-size: clamp(2.4rem, 6.2vw, 5rem);
            line-height: 0.92; letter-spacing: -0.045em; text-transform: uppercase;
            color: #fff; max-width: 900px; margin: 1.25rem auto 0;
        }
        .final p.sub { margin-top: 1.5rem; font-size: 1.05rem; color: var(--muted-d); }
        .final .btn { margin-top: 2.5rem; }
        .final-note { margin-top: 1.25rem; font-size: 0.82rem; color: rgba(255,255,255,0.4); }

        .emoji-strip { display: flex; justify-content: center; gap: 0.6rem; margin-bottom: 0.5rem; }
        .emoji-strip span {
            width: 56px; height: 56px; border-radius: 18px;
            background: rgba(255,255,255,0.08);
            border: 1.5px solid rgba(255,255,255,0.14);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            animation: float 6s ease-in-out infinite;
        }
        .emoji-strip span:nth-child(2) { animation-delay: 0.4s; }
        .emoji-strip span:nth-child(3) { animation-delay: 0.8s; }
        .emoji-strip span:nth-child(4) { animation-delay: 1.2s; }
        .emoji-strip span:nth-child(5) { animation-delay: 1.6s; }

        /* ── FOOTER ─────────────────────────────────────────────────── */
        footer { background: var(--ink); border-top: 1px solid rgba(255,255,255,0.08); padding: 2.5rem 0; }
        .foot-inner {
            max-width: var(--maxw); margin: 0 auto; padding: 0 var(--pad);
            display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap;
        }
        .foot-links { display: flex; gap: 1.5rem; flex-wrap: wrap; }
        .foot-links a { font-size: 0.86rem; color: rgba(255,255,255,0.5); text-decoration: none; transition: color 0.15s; }
        .foot-links a:hover { color: #fff; }
        .foot-copy { font-size: 0.8rem; color: rgba(255,255,255,0.32); }

        /* ── RESPONSIVE ─────────────────────────────────────────────── */
        @media (max-width: 1000px) {
            :root { --pad: 1.5rem; }

            .hero { padding: 3.5rem 0 5rem; }
            .hero-inner { grid-template-columns: minmax(0, 1fr); gap: 4.5rem; }
            .hero-sub { max-width: 100%; }

            /* Leave room for the chips, which hang off both edges */
            .hero-card-stack { width: 100%; max-width: 420px; margin: 0 auto; }
            .chip-1 { top: -18px; left: 0; }
            .chip-2 { top: 104px; right: 0; }
            .chip-3 { bottom: -18px; left: 0; }

            .nav-mid { display: none; }

            .feat-grid { grid-template-columns: 1fr 1fr; }
            .steps { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 2.5rem 1.5rem; }

            .features, .how, .quote-sec { padding: 4.5rem 0; }
            .final { padding: 4.5rem 0; }
            .quote-card { padding: 3rem 1.75rem; }
            .quote-card::before { left: 10px; font-size: 11rem; top: -30px; }
        }

        @media (max-width: 620px) {
            .feat-grid { grid-template-columns: 1fr; }
            .hero-ctas .btn { width: 100%; }
            .hero-proof { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
            .chip-2 { display: none; }
            .emoji-strip span { width: 46px; height: 46px; font-size: 1.2rem; }
            .foot-inner { flex-direction: column; text-align: center; }

            /* Tighten the nav so brand + sign-in + CTA fit a 390px viewport */
            .nav { padding: 0.75rem var(--pad); gap: 0.5rem; }
            .brand-name { font-size: 1.05rem; }
            .brand-mark { width: 34px; height: 34px; border-radius: 11px; font-size: 1rem; }
            .nav-link { padding: 0.5rem 0.6rem; font-size: 0.85rem; }
            .nav .btn-sm { padding: 0.6rem 1rem; font-size: 0.8rem; }
        }

        @media (max-width: 400px) {
            /* Last resort: drop the wordmark, keep the logo tile */
            .nav .brand-name { display: none; }
        }

        /* ── REDUCED MOTION ─────────────────────────────────────────── */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.001ms !important;
                scroll-behavior: auto !important;
            }
            .marquee-track { animation: none; }
        }
    </style>
</head>
<body>

    {{-- ══ NAV ══════════════════════════════════════════════════════ --}}
    <div class="nav-outer">
        <nav class="nav">
            <a href="/" class="brand">
                <span class="brand-mark" aria-hidden="true">🎉</span>
                <span class="brand-name">CelebrateMi</span>
            </a>

            <div class="nav-mid">
                <a href="#features" class="nav-link">Features</a>
                <a href="#how" class="nav-link">How it works</a>
                <a href="#stories" class="nav-link">Stories</a>
            </div>

            @if (Route::has('login'))
                <div class="nav-links">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-grad btn-sm">Go to dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="nav-link">Sign in</a>
                        <button
                            type="button"
                            x-data
                            x-on:click="$dispatch('open-modal', 'create-event')"
                            class="btn btn-grad btn-sm"
                        >
                            Start free
                        </button>
                    @endauth
                </div>
            @endif
        </nav>
    </div>

    {{-- ══ HERO ═════════════════════════════════════════════════════ --}}
    <header class="hero">
        <div class="blob blob-1" aria-hidden="true"></div>
        <div class="blob blob-2" aria-hidden="true"></div>
        <div class="blob blob-3" aria-hidden="true"></div>

        <div class="hero-inner">
            <div>
                <span class="eyebrow hero-badge">
                    <span class="dot" aria-hidden="true"></span>
                    Free to start · Live in 60 seconds
                </span>

                <h1>
                    <span class="line">Celebrate</span>
                    <span class="line grad-text">Out Loud.</span>
                </h1>

                <p class="hero-sub">
                    One beautiful page where everyone who loves you can send
                    <strong>wishes, gifts and money</strong> — and where the memories
                    stay long after the candles are out.
                </p>

                <div class="hero-ctas">
                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', 'create-event')"
                        onclick="partyBurst(event)"
                        class="btn btn-grad"
                    >
                        Create your celebration
                    </button>

                    <a href="#how" class="btn btn-outline-d">See how it works</a>
                </div>

                <div class="hero-proof">
                    <div class="avatars" aria-hidden="true">
                        <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=80&h=80&fit=crop&q=80" alt="" loading="lazy">
                        <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=80&h=80&fit=crop&q=80" alt="" loading="lazy">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&h=80&fit=crop&q=80" alt="" loading="lazy">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&h=80&fit=crop&q=80" alt="" loading="lazy">
                        <span class="more">2.4k</span>
                    </div>
                    <p class="proof-text">
                        <span class="stars" aria-hidden="true">★★★★★</span>
                        <strong>2,400+ celebrations</strong> created this month<br>
                        <span>Birthdays, weddings, graduations &amp; more</span>
                    </p>
                </div>
            </div>

            {{-- Live celebration page preview --}}
            <div class="hero-card-stack">
                <div class="chip chip-1">
                    <span class="em" aria-hidden="true">💸</span>
                    <span>James sent <span class="amt">₦5,000</span></span>
                </div>

                <div class="chip chip-2">
                    <span class="em" aria-hidden="true">💌</span>
                    <span>12 new wishes</span>
                </div>

                <div class="chip chip-3">
                    <span class="em" aria-hidden="true">📸</span>
                    <span>Photobook ready</span>
                </div>

                <div class="hero-card">
                    <div class="hc-top">
                        <div>
                            <p class="hc-title">Sandra's 30th 🎂</p>
                            <p class="hc-meta">celebratemi.com/sandra-30</p>
                        </div>
                        <span class="hc-live"><span class="dot" aria-hidden="true"></span> Live</span>
                    </div>

                    <div class="hc-raise">
                        <p class="hc-raise-label">Gifted so far</p>
                        <p class="hc-amount">₦850,400</p>
                        <div class="hc-bar" role="img" aria-label="68 percent of goal reached">
                            <span></span>
                        </div>
                        <div class="hc-bar-meta">
                            <span>68% of ₦1.25m goal</span>
                            <span>4 days left</span>
                        </div>
                    </div>

                    <div class="hc-grid">
                        <div class="hc-stat">
                            <p class="n">245</p>
                            <p class="l">Wishes received</p>
                        </div>
                        <div class="hc-stat">
                            <p class="n">63</p>
                            <p class="l">People gifted</p>
                        </div>
                    </div>

                    <div class="hc-wish">
                        <span class="hc-wish-av" aria-hidden="true">JA</span>
                        <div>
                            <p class="hc-wish-name">James A.</p>
                            <p class="hc-wish-body">
                                "Happy birthday! Wishing you everything good this year 🎉"
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- ══ FEATURES ═════════════════════════════════════════════════ --}}
    <section class="features" id="features">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow">One page. Everything.</span>
                <h2>More than wishes.<br>It's an experience.</h2>
                <p>
                    Every celebration gets its own page — styled your way, shareable anywhere,
                    and built to collect the things that actually matter.
                </p>
            </div>

            <div class="feat-grid">
                <article class="feat">
                    <div class="feat-icon ic-party" aria-hidden="true">💌</div>
                    <h3>A wall of wishes</h3>
                    <p>
                        Friends and family leave messages, photos and reactions. Every wish lands
                        on your page in real time — no app to download.
                    </p>
                </article>

                <article class="feat is-hero">
                    <div class="feat-icon ic-flame" aria-hidden="true">💸</div>
                    <h3>Gifts &amp; real money</h3>
                    <p>
                        Receive cash gifts by card or transfer through Paystack and Stripe.
                        It lands in your wallet instantly — withdraw to your bank whenever you like.
                    </p>
                </article>

                <article class="feat">
                    <div class="feat-icon ic-grape" aria-hidden="true">🎁</div>
                    <h3>Group gifting</h3>
                    <p>
                        Add things you actually want. Guests chip in together until the
                        wish is funded — everyone sees the progress fill up.
                    </p>
                </article>

                <article class="feat">
                    <div class="feat-icon ic-sun" aria-hidden="true">📸</div>
                    <h3>Instant photobook</h3>
                    <p>
                        Every wish and photo is bound into a downloadable keepsake book.
                        One click, and the whole celebration is yours to keep.
                    </p>
                </article>

                <article class="feat">
                    <div class="feat-icon ic-mint" aria-hidden="true">🎨</div>
                    <h3>Make it yours</h3>
                    <p>
                        Pick a theme, choose a photo frame, set your own colours.
                        Your page should look like you, not like a template.
                    </p>
                </article>

                <article class="feat">
                    <div class="feat-icon ic-sky" aria-hidden="true">🌍</div>
                    <h3>Share anywhere</h3>
                    <p>
                        One link works everywhere — WhatsApp, Instagram, email, the family
                        group chat. Guests don't need an account to send love.
                    </p>
                </article>
            </div>
        </div>
    </section>

    {{-- ══ HOW IT WORKS ═════════════════════════════════════════════ --}}
    <section class="how" id="how">
        <div class="wrap how-inner">
            <div class="section-head">
                <span class="eyebrow">Ridiculously simple</span>
                <h2>Three steps.<br>Thirty seconds.</h2>
                <p>No setup call, no credit card, no fiddling with settings for an hour.</p>
            </div>

            <div class="steps">
                <div class="step">
                    <p class="step-num grad-text">01</p>
                    <h3>Create the page</h3>
                    <p>
                        Name the celebrant, pick the occasion, set the dates. Your page is live
                        the moment you hit create — with its own shareable link.
                    </p>
                </div>

                <div class="step">
                    <p class="step-num grad-text">02</p>
                    <h3>Share the link</h3>
                    <p>
                        Drop it in the group chat or on your story. Guests open it, leave a wish,
                        and send a gift in seconds — no sign-up required.
                    </p>
                </div>

                <div class="step">
                    <p class="step-num grad-text">03</p>
                    <h3>Collect &amp; keep</h3>
                    <p>
                        Watch the wishes and gifts roll in. Withdraw your money to your bank,
                        and download the photobook to keep the day forever.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ══ STATS ════════════════════════════════════════════════════ --}}
    <section class="stats">
        <div class="wrap">
            <div class="stats-grid">
                <div>
                    <p class="stat-n">2.4k</p>
                    <p class="stat-l">Celebrations</p>
                </div>
                <div>
                    <p class="stat-n">140k</p>
                    <p class="stat-l">Wishes sent</p>
                </div>
                <div>
                    <p class="stat-n">₦92m</p>
                    <p class="stat-l">Gifted &amp; withdrawn</p>
                </div>
                <div>
                    <p class="stat-n">4.9★</p>
                    <p class="stat-l">Average rating</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ══ TESTIMONIAL ══════════════════════════════════════════════ --}}
    <section class="quote-sec" id="stories">
        <div class="wrap">
            <div class="quote-card">
                <blockquote>
                    We made a page for my mum's 60th and sent it to the family group.
                    By the end of the week she had 200 wishes and enough gifted to send her
                    on the trip she'd been putting off for years. She still opens the photobook.
                </blockquote>

                <div class="quote-author">
                    <img
                        src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100&h=100&fit=crop&q=80"
                        alt=""
                        loading="lazy"
                    >
                    <div>
                        <p class="qa-name">Sarah K.</p>
                        <p class="qa-meta">Planned her mum's 60th birthday</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ══ FINAL CTA ════════════════════════════════════════════════ --}}
    <section class="final">
        <div class="wrap final-inner">
            <div class="emoji-strip" aria-hidden="true">
                <span>🎂</span>
                <span>🎁</span>
                <span>💸</span>
                <span>💌</span>
                <span>📸</span>
            </div>

            <h2>Somebody you love<br>deserves a fuss.</h2>

            <p class="sub">Free to start. No card needed. Live in under a minute.</p>

            <button
                type="button"
                x-data
                x-on:click="$dispatch('open-modal', 'create-event')"
                onclick="partyBurst(event)"
                class="btn btn-light"
            >
                Create your celebration 🎉
            </button>

            <p class="final-note">Takes less than 60 seconds · Cancel anytime</p>
        </div>
    </section>

    {{-- ══ FOOTER ═══════════════════════════════════════════════════ --}}
    <footer>
        <div class="foot-inner">
            <a href="/" class="brand">
                <span class="brand-mark" aria-hidden="true">🎉</span>
                <span class="brand-name">CelebrateMi</span>
            </a>

            <div class="foot-links">
                <a href="#features">Features</a>
                <a href="#how">How it works</a>
                <a href="#stories">Stories</a>
                @if (Route::has('login'))
                    <a href="{{ route('login') }}">Sign in</a>
                @endif
            </div>

            <p class="foot-copy">&copy; {{ date('Y') }} CelebrateMi. For the moments that matter.</p>
        </div>
    </footer>


    {{-- ══ CREATE-EVENT MODAL ═══════════════════════════════════════
         Alpine logic (celebrationForm) is unchanged — only the styling
         was updated to match the festive direction.
    ═══════════════════════════════════════════════════════════════ --}}
    <x-modal name="create-event" maxWidth="2xl" focusable>
        <div class="relative bg-white">

            {{-- Gradient header --}}
            <div class="relative overflow-hidden bg-party-gradient px-8 pt-8 pb-7 text-white">
                <button
                    type="button"
                    x-on:click="$dispatch('close')"
                    aria-label="Close"
                    class="absolute top-5 right-5 h-9 w-9 rounded-full flex items-center justify-center bg-white/20 hover:bg-white/35 transition"
                >
                    <i class="mdi mdi-close text-lg"></i>
                </button>

                <p class="text-[0.7rem] font-extrabold uppercase tracking-[0.14em] text-white/75">
                    Free · Live in 60 seconds
                </p>
                <h2 class="mt-2 font-display text-3xl font-black uppercase leading-none tracking-tight">
                    Start the party
                </h2>
                <p class="mt-2 text-sm text-white/80">
                    Tell us who we're celebrating — you can change everything later.
                </p>
            </div>

            <div
                x-data="celebrationForm()"
                x-init="loggedIn = @js(auth()->check())"
                class="px-8 py-7"
            >
                <form @submit.prevent="nextStep">

                    {{-- Step indicator --}}
                    <div class="flex items-center gap-3 mb-7">
                        <div
                            :class="step >= 1 ? 'bg-party-500 text-white shadow-party' : 'bg-ink-100 text-ink-400'"
                            class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-extrabold transition-all"
                        >1</div>

                        <div class="flex-1 h-1 rounded-full bg-ink-100 overflow-hidden">
                            <div
                                :class="step >= 2 ? 'w-full' : 'w-0'"
                                class="h-full bg-party-gradient transition-all duration-500"
                            ></div>
                        </div>

                        <div
                            :class="step >= 2 ? 'bg-party-500 text-white shadow-party' : 'bg-ink-100 text-ink-400'"
                            class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-extrabold transition-all"
                        >2</div>
                    </div>

                    {{-- ── STEP 1: Event details ── --}}
                    <div x-show="step === 1" x-transition>
                        <div class="space-y-4">
                            <div>
                                <label for="ce-name" class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">
                                    Who are we celebrating?
                                </label>
                                <input
                                    id="ce-name"
                                    type="text"
                                    x-model="form.celebrantName"
                                    placeholder="Name of the celebrant(s)"
                                    class="w-full rounded-xl2 border-2 border-ink-100 px-4 py-3 text-sm font-medium outline-none transition focus:border-party-400 focus:ring-4 focus:ring-party-100"
                                >
                            </div>

                            <div>
                                <label for="ce-type" class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">
                                    What's the occasion?
                                </label>
                                <select
                                    id="ce-type"
                                    x-model="form.eventType"
                                    class="w-full rounded-xl2 border-2 border-ink-100 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-party-400 focus:ring-4 focus:ring-party-100"
                                >
                                    <option value="">Select an occasion</option>
                                    <option value="birthday">🎂 Birthday</option>
                                    <option value="wedding">💍 Wedding</option>
                                    <option value="graduation">🎓 Graduation</option>
                                    <option value="anniversary">❤️ Anniversary</option>
                                    <option value="baby_shower">👶 Baby Shower</option>
                                    <option value="other">🎉 Other</option>
                                </select>
                            </div>

                            <div>
                                <span class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">
                                    When is it?
                                </span>
                                <div class="grid grid-cols-2 gap-3">
                                    <input
                                        type="text"
                                        x-model="form.startDate"
                                        placeholder="Start date"
                                        aria-label="Start date"
                                        class="datepicker w-full rounded-xl2 border-2 border-ink-100 px-4 py-3 text-sm font-medium outline-none transition focus:border-party-400 focus:ring-4 focus:ring-party-100"
                                    >
                                    <input
                                        type="text"
                                        x-model="form.endDate"
                                        placeholder="End date"
                                        aria-label="End date"
                                        class="datepicker w-full rounded-xl2 border-2 border-ink-100 px-4 py-3 text-sm font-medium outline-none transition focus:border-party-400 focus:ring-4 focus:ring-party-100"
                                    >
                                </div>
                            </div>

                            <div>
                                <label for="ce-title" class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">
                                    Page title
                                </label>
                                <input
                                    id="ce-title"
                                    type="text"
                                    x-model="form.eventTitle"
                                    placeholder="We'll write this for you"
                                    class="w-full rounded-xl2 border-2 border-ink-100 px-4 py-3 text-sm font-medium outline-none transition focus:border-party-400 focus:ring-4 focus:ring-party-100"
                                >
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="mt-7 w-full rounded-full bg-party-gradient py-4 text-sm font-extrabold text-white shadow-party transition hover:-translate-y-0.5 hover:shadow-lg"
                        >
                            Continue →
                        </button>
                    </div>

                    {{-- ── STEP 2: Auth (guests only) ── --}}
                    <div x-show="step === 2" x-transition>
                        <p class="mb-5 text-sm text-ink-500">
                            Almost there — create your free account to publish the page.
                        </p>

                        <div class="space-y-4">
                            <div>
                                <label for="ce-email" class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">
                                    Email address
                                </label>
                                <input
                                    id="ce-email"
                                    type="email"
                                    x-model="auth.email"
                                    placeholder="you@example.com"
                                    class="w-full rounded-xl2 border-2 border-ink-100 px-4 py-3 text-sm font-medium outline-none transition focus:border-party-400 focus:ring-4 focus:ring-party-100"
                                >
                            </div>

                            <div>
                                <label for="ce-pass" class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">
                                    Password
                                </label>
                                <input
                                    id="ce-pass"
                                    type="password"
                                    x-model="auth.password"
                                    placeholder="Create a password"
                                    class="w-full rounded-xl2 border-2 border-ink-100 px-4 py-3 text-sm font-medium outline-none transition focus:border-party-400 focus:ring-4 focus:ring-party-100"
                                >
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="submitForm"
                            class="mt-7 w-full rounded-full bg-party-gradient py-4 text-sm font-extrabold text-white shadow-party transition hover:-translate-y-0.5 hover:shadow-lg"
                        >
                            Create my celebration 🎉
                        </button>

                        <button
                            type="button"
                            @click="step = 1"
                            class="mt-3 w-full py-2 text-xs font-semibold text-ink-400 hover:text-ink-700 transition"
                        >
                            ← Back to details
                        </button>

                        <p class="mt-3 text-center text-xs text-ink-400">
                            Already have an account?
                            <a href="{{ route('login') }}" class="font-bold text-party-500 hover:underline">Sign in instead</a>
                        </p>
                    </div>

                </form>
            </div>
        </div>
    </x-modal>

    <script>
        /**
         * Confetti burst on the primary CTAs.
         * `window.confetti` only exists when the Vite bundle is built, and we skip
         * the whole thing when the visitor prefers reduced motion.
         */
        function partyBurst(event) {
            if (!window.confetti) return;
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            const rect = event.currentTarget.getBoundingClientRect();

            window.confetti({
                particleCount: 90,
                spread: 76,
                startVelocity: 42,
                scalar: 0.9,
                origin: {
                    x: (rect.left + rect.width / 2) / window.innerWidth,
                    y: (rect.top + rect.height / 2) / window.innerHeight,
                },
                colors: ['#ff2d8f', '#ff7a18', '#7c3aed', '#ffd84d', '#ffffff'],
            });
        }

        function celebrationForm() {
            return {
                step: 1,
                loggedIn: false,

                form: {
                    celebrantName: '',
                    eventType: '',
                    startDate: '',
                    endDate: '',
                    eventTitle: ''
                },

                auth: {
                    email: '',
                    password: ''
                },

                init() {
                    this.$watch('form.celebrantName', () => this.generateTitle());
                    this.$watch('form.eventType',     () => this.generateTitle());
                },

                generateTitle() {
                    const name = this.form.celebrantName?.trim();
                    const type = this.form.eventType;
                    if (!name && !type) { this.form.eventTitle = ''; return; }
                    this.form.eventTitle = `${name || 'Someone'}'s ${this.getEventLabel(type)}`;
                },

                getEventLabel(type) {
                    const map = {
                        birthday:    'Birthday',
                        wedding:     'Wedding',
                        graduation:  'Graduation',
                        anniversary: 'Anniversary',
                        baby_shower: 'Baby Shower',
                        other:       'Celebration'
                    };
                    return map[type] || 'Celebration';
                },

                nextStep() {
                    if (!this.form.celebrantName) {
                        alert('Please enter the celebrant\'s name.');
                        return;
                    }
                    if (this.loggedIn) { this.submitForm(); return; }
                    this.step = 2;
                },

                async submitForm() {
                    const response = await fetch('/create-celebration', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ ...this.form, ...this.auth })
                    });

                    const data = await response.json();
                    if (data.redirect) window.location.href = data.redirect;
                }
            }
        }
    </script>

    {{-- Alpine.js — loads from CDN if the Vite bundle isn't running --}}
    @if (!file_exists(public_path('build/manifest.json')) && !file_exists(public_path('hot')))
        <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
    @endif

</body>
</html>
