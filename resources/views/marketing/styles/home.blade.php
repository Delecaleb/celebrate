{{--
    Marketing site — home-page stylesheet.

    Loaded from the shell alongside core.blade.php. Sections, in page order:
      hero · trust strip · flow (4 steps) · photo band · feature rows ·
      stat band · voices · faq · finale

    Same rules as core: no colour literals, no radius literals — everything
    comes from <x-brand-tokens /> (resources/brand.json).
--}}
<style id="marketing-home">
    /* ══ HERO ══════════════════════════════════════════════════════════ */
    .hero {
        position: relative; overflow: hidden;
        padding: 3.5rem 0 6rem;
        background:
            radial-gradient(ellipse 70% 55% at 82% 8%, var(--primary-50), transparent 65%),
            radial-gradient(ellipse 55% 45% at 5% 0%, var(--secondary-50), transparent 60%);
    }
    .hero-grid {
        position: relative; z-index: 1;
        display: grid; grid-template-columns: minmax(0, 1.02fr) minmax(0, 0.98fr);
        gap: 4rem; align-items: center;
    }
    .hero h1 { color: var(--ink); margin-top: 1.5rem; }
    .hero h1 .t-serif { display: block; color: var(--primary); }
    .hero .lead { margin-top: 1.6rem; max-width: 470px; }
    .hero-ctas { margin-top: 2.3rem; display: flex; align-items: center; gap: 0.8rem; flex-wrap: wrap; }
    .hero-fineprint {
        margin-top: 1.15rem; display: flex; align-items: center; gap: 1.15rem; flex-wrap: wrap;
        font-size: 0.86rem; font-weight: 600; color: var(--muted);
    }
    .hero-fineprint span { display: inline-flex; align-items: center; gap: 0.35rem; }
    .hero-fineprint i { color: var(--ok); font-size: 1.1rem; }

    /* ── the photo collage ──────────────────────────────────────────── */
    .collage {
        position: relative;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }
    .ph {
        position: relative; overflow: hidden;
        border-radius: var(--r-lg);
        background: var(--primary-100);
        box-shadow: var(--shadow-lift);
    }
    .ph img { width: 100%; height: 100%; object-fit: cover; }
    /* a hair of rotation so the cluster feels pinned-up, not laid out */
    .ph-tall  { grid-row: span 2; aspect-ratio: 3 / 4.5; transform: rotate(-2deg); }
    .ph-wide  { aspect-ratio: 4 / 3.1; transform: rotate(1.8deg); }
    .ph-short { aspect-ratio: 4 / 3.4; transform: rotate(-1.2deg); }

    /* caption pill sitting on a photo */
    .ph-tag {
        position: absolute; bottom: 0.75rem; left: 0.75rem; right: 0.75rem;
        display: flex; align-items: center; gap: 0.45rem;
        padding: 0.5rem 0.8rem; border-radius: var(--r-pill);
        background: rgba(var(--surface-rgb) / 0.92);
        backdrop-filter: blur(8px);
        font-size: 0.78rem; font-weight: 700; color: var(--ink);
    }
    .ph-tag i { color: var(--primary); font-size: 1.05rem; }

    /* floating notification stickers */
    .sticker {
        position: absolute; z-index: 3;
        display: flex; align-items: center; gap: 0.5rem;
        padding: 0.62rem 1rem; border-radius: var(--r-pill);
        background: var(--surface); border: 1px solid var(--line);
        box-shadow: var(--shadow-card);
        font-size: 0.83rem; font-weight: 700; white-space: nowrap;
    }
    .sticker i { font-size: 1.15rem; color: var(--primary); }
    .sticker .amt { color: var(--primary); }
    .sticker-money  { top: -0.9rem; left: -1.6rem;  animation: bob 6s ease-in-out infinite; }
    .sticker-wishes { top: 44%; right: -2.2rem;     animation: bob 7.4s ease-in-out infinite 0.8s; }
    .sticker-book   { bottom: -0.9rem; left: 12%;   animation: bob 6.8s ease-in-out infinite 1.6s; }

    /* the loud one — a solid purple confetti badge */
    /* pinned to the top-right corner of the collage so it never lands on a face */
    .sticker-hbd {
        position: absolute; z-index: 3; top: -1.1rem; right: -1.5rem;
        display: flex; align-items: center; gap: 0.4rem;
        padding: 0.6rem 1.05rem; border-radius: var(--r-pill);
        background: var(--secondary-400); color: var(--secondary-900);
        font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 0.92rem;
        letter-spacing: -0.02em;
        box-shadow: var(--shadow-card);
        transform: rotate(6deg);
        animation: bob 8s ease-in-out infinite 0.4s;
    }

    @keyframes bob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-11px); } }
    .sticker-hbd { animation-name: bob-tilt; }
    @keyframes bob-tilt {
        0%, 100% { transform: rotate(6deg) translateY(0); }
        50%      { transform: rotate(6deg) translateY(-10px); }
    }

    /* ══ TRUST STRIP ═══════════════════════════════════════════════════ */
    /* Grid, not flex-wrap: three items and two hairline dividers that never
       reflow into a dangling separator on a second row. */
    .trust {
        display: grid; grid-template-columns: auto 1px auto 1px auto;
        align-items: center; justify-content: center;
        gap: 2rem;
        padding: 2.25rem 0;
        border-top: 1px solid var(--line);
        border-bottom: 1px solid var(--line);
    }
    .trust-people { display: flex; align-items: center; gap: 0.9rem; }
    .avatars { display: flex; flex-shrink: 0; }
    .avatars img {
        width: 42px; height: 42px; border-radius: 50%; object-fit: cover;
        border: 3px solid var(--surface);
    }
    .avatars img + img, .avatars .more { margin-left: -13px; }
    .avatars .more {
        width: 42px; height: 42px; border-radius: 50%;
        background: var(--primary); border: 3px solid var(--surface); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.7rem; font-weight: 700;
    }
    .trust-text { font-size: 0.92rem; line-height: 1.5; color: var(--muted); }
    .trust-text strong { color: var(--ink); font-weight: 700; }
    .trust-stars { color: var(--secondary-500); font-size: 0.95rem; letter-spacing: 0.03em; }

    .trust-sep { width: 1px; height: 42px; background: var(--line); }
    .trust-note {
        display: inline-flex; align-items: center; gap: 0.5rem;
        font-size: 0.9rem; font-weight: 600; color: var(--muted);
    }
    .trust-note i { flex-shrink: 0; font-size: 1.3rem; color: var(--primary); }

    /* below the point where three abreast stops fitting, stack them centred */
    @media (max-width: 1080px) {
        .trust { grid-template-columns: minmax(0, 1fr); justify-items: center; gap: 1.25rem; }
        .trust-sep { display: none; }
    }

    /* ══ FLOW — the four steps ═════════════════════════════════════════ */
    .flow-grid {
        display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1.2rem; margin-top: 3.4rem;
        align-items: start;
    }
    .flow-card {
        border-radius: var(--r-lg); padding: 1.9rem 1.6rem;
        border: 1px solid transparent;
        height: 100%;
    }
    .flow-card .n {
        display: inline-flex; align-items: center; justify-content: center;
        width: 44px; height: 44px; border-radius: 50%;
        font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 1.15rem;
        letter-spacing: -0.04em; margin-bottom: 1.1rem;
    }
    .flow-card h3 { font-size: 1.18rem; font-weight: 700; margin-bottom: 0.5rem; }
    .flow-card p { font-size: 0.93rem; line-height: 1.65; }

    /* four tints, all drawn from the two brand hues + the neutral ramp */
    .flow-1 { background: var(--primary-50);   border-color: var(--primary-100); }
    .flow-1 .n { background: var(--primary); color: #fff; }
    .flow-1 p  { color: var(--primary-800); }

    .flow-2 { background: var(--secondary-50); border-color: var(--secondary-100); }
    .flow-2 .n { background: var(--secondary-400); color: var(--secondary-900); }
    .flow-2 p  { color: var(--secondary-800); }

    .flow-3 { background: var(--ink-50);       border-color: var(--line); }
    .flow-3 .n { background: var(--ink); color: #fff; }
    .flow-3 p  { color: var(--muted); }

    .flow-4 { background: var(--primary); color: #fff; }
    .flow-4 .n { background: rgba(255,255,255,0.18); color: #fff; }
    .flow-4 h3 { color: #fff; }
    .flow-4 p  { color: rgba(255,255,255,0.8); }

    /* the little link-preview mock inside step 2 */
    .flow-link {
        margin-top: 1.15rem; display: flex; align-items: center; gap: 0.5rem;
        background: var(--surface); border: 1px solid var(--secondary-200);
        border-radius: var(--r-pill); padding: 0.42rem 0.42rem 0.42rem 0.85rem;
    }
    .flow-link code {
        flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        font-family: inherit; font-size: 0.78rem; font-weight: 600; color: var(--muted);
    }
    .flow-link span {
        flex-shrink: 0; padding: 0.32rem 0.7rem; border-radius: var(--r-pill);
        background: var(--ink); color: #fff; font-size: 0.72rem; font-weight: 700;
    }

    /* ══ PHOTO BAND ════════════════════════════════════════════════════ */
    .band { padding: 0 0 6.5rem; }
    .band-strip {
        display: grid; grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.85rem;
    }
    .band-strip .ph { box-shadow: none; border-radius: var(--r); }
    .band-strip .ph:nth-child(odd)  { aspect-ratio: 3 / 4; }
    .band-strip .ph:nth-child(even) { aspect-ratio: 3 / 4; margin-top: 2rem; }
    .band-strip .ph { transform: none; }
    .band-head { text-align: center; max-width: 620px; margin: 0 auto 3rem; }
    .band-head .lead { margin-top: 1.1rem; }

    /* ══ FEATURE ROWS ══════════════════════════════════════════════════ */
    .frow {
        display: grid; grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 4.5rem; align-items: center;
    }
    .frow + .frow { margin-top: 6.5rem; }
    /* every other row puts the artwork on the left */
    .frow-flip .frow-art { order: -1; }
    .frow-copy .eyebrow { margin-bottom: 1.2rem; }
    .frow-copy h2 { font-size: clamp(1.8rem, 3.2vw, 2.5rem); font-weight: 700; }
    .frow-copy .lead { margin-top: 1.1rem; }

    /* a framed product mock — .pv-* bits from core go inside */
    .mock {
        background: var(--surface); border: 1px solid var(--line);
        border-radius: var(--r-lg); padding: 1.5rem;
        box-shadow: var(--shadow-lift);
        position: relative;
    }
    .mock-tinted { background: var(--surface-2); }

    .wish-row {
        display: flex; gap: 0.7rem; align-items: flex-start;
        padding: 0.9rem 0;
    }
    .wish-row + .wish-row { border-top: 1px solid var(--line-2); }
    .wish-row .pv-av { width: 38px; height: 38px; }
    .wish-name { font-size: 0.88rem; font-weight: 700; }
    .wish-body { font-size: 0.86rem; color: var(--muted); line-height: 1.55; margin-top: 0.15rem; }
    .wish-react {
        margin-top: 0.5rem; display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.22rem 0.55rem; border-radius: var(--r-pill);
        background: var(--primary-50); color: var(--primary-700);
        font-size: 0.72rem; font-weight: 700;
    }

    .gift-row {
        display: flex; align-items: center; gap: 0.85rem;
        padding: 0.95rem 0;
    }
    .gift-row + .gift-row { border-top: 1px solid var(--line-2); }
    .gift-row .itile {
        width: 40px; height: 40px; border-radius: 13px;
        font-size: 1.15rem; margin-bottom: 0;
    }
    .gift-meta { flex: 1; min-width: 0; }
    .gift-name { font-size: 0.9rem; font-weight: 700; }
    .gift-sub { font-size: 0.79rem; color: var(--muted); margin-top: 0.1rem; }
    .gift-amt { font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 1.05rem; letter-spacing: -0.03em; flex-shrink: 0; }

    /* photobook spread mock */
    .book {
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.6rem;
    }
    .book .ph { border-radius: var(--r-sm); box-shadow: none; transform: none; aspect-ratio: 1; }
    .book-cap {
        margin-top: 1.1rem; text-align: center;
        font-family: 'DM Serif Display', Georgia, serif; font-style: italic;
        font-size: 1.15rem; color: var(--ink-700);
    }

    /* ══ STAT BAND ═════════════════════════════════════════════════════ */
    .stat-band {
        position: relative; overflow: hidden;
        background: var(--primary-900); color: #fff;
        padding: 4.5rem 0;
    }

    /* ══ VOICES ════════════════════════════════════════════════════════ */
    .voices-grid {
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1.3rem; margin-top: 3.4rem; align-items: start;
    }
    /* stagger the middle column so the wall doesn't read as a table */
    .voices-grid > :nth-child(2) { margin-top: 2.25rem; }
    .voices-grid .quote { height: auto; }
    .voice-tint { background: var(--secondary-50); border-color: var(--secondary-200); }
    .voice-tint .qmark { color: var(--secondary-400); }

    /* ══ FAQ ═══════════════════════════════════════════════════════════ */
    .faq { max-width: 820px; margin: 3.25rem auto 0; }
    .faq details {
        border: 1px solid var(--line); border-radius: var(--r);
        background: var(--surface);
        transition: border-color 0.18s;
    }
    .faq details + details { margin-top: 0.7rem; }
    .faq details[open] { border-color: var(--primary-200); background: var(--primary-50); }
    .faq summary {
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        padding: 1.2rem 1.4rem; cursor: pointer; list-style: none;
        font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 1.06rem;
        letter-spacing: -0.025em; color: var(--ink);
    }
    .faq summary::-webkit-details-marker { display: none; }
    .faq summary i { flex-shrink: 0; font-size: 1.4rem; color: var(--primary); transition: transform 0.22s; }
    .faq details[open] summary i { transform: rotate(45deg); }
    .faq .answer {
        padding: 0 1.4rem 1.35rem;
        font-size: 0.97rem; line-height: 1.7; color: var(--muted);
    }
    .faq details[open] .answer { color: var(--primary-800); }
    .faq .answer a { color: var(--primary-700); font-weight: 700; text-decoration: underline; }

    /* ══ FINALE ════════════════════════════════════════════════════════ */
    .finale { position: relative; overflow: hidden; padding: 6rem 0 7rem; text-align: center; }
    .finale-inner { position: relative; z-index: 1; max-width: 720px; margin: 0 auto; }
    .finale h2 { font-size: clamp(2rem, 4.4vw, 3.2rem); font-weight: 700; }
    .finale h2 .t-serif { color: var(--primary); }
    .finale p { margin-top: 1.25rem; font-size: 1.06rem; color: var(--muted); }
    .finale .btn { margin-top: 2.4rem; }
    .finale .cta-note { margin-top: 1.15rem; font-size: 0.87rem; color: var(--muted-2); }

    /* the core .icon-row is built for dark bands; this is the light variant,
       alternating the two brand hues so the row reads as a little parade */
    .icon-row-light span { background: var(--primary-100); color: var(--primary-700); }
    .icon-row-light span:nth-child(even) { background: var(--secondary-100); color: var(--secondary-800); }

    /* confetti-ish decoration, purely decorative */
    .confetti { position: absolute; inset: 0; pointer-events: none; }
    .confetti span {
        position: absolute; border-radius: 4px;
        width: 14px; height: 14px;
    }
    .confetti span:nth-child(1) { top: 14%; left: 8%;  background: var(--primary-300); transform: rotate(20deg); }
    .confetti span:nth-child(2) { top: 28%; left: 20%; background: var(--secondary-300); border-radius: 50%; width: 11px; height: 11px; }
    .confetti span:nth-child(3) { top: 68%; left: 12%; background: var(--primary-200); transform: rotate(-25deg); }
    .confetti span:nth-child(4) { top: 18%; right: 11%; background: var(--secondary-400); transform: rotate(35deg); }
    .confetti span:nth-child(5) { top: 46%; right: 6%;  background: var(--primary-300); border-radius: 50%; }
    .confetti span:nth-child(6) { bottom: 16%; right: 18%; background: var(--secondary-200); transform: rotate(-15deg); }
    .confetti span:nth-child(7) { bottom: 26%; left: 26%;  background: var(--primary-100); width: 20px; height: 20px; transform: rotate(12deg); }

    /* ══ RESPONSIVE ════════════════════════════════════════════════════ */
    @media (max-width: 1080px) {
        .flow-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .band-strip { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .band-strip .ph:nth-child(n+4) { display: none; }
        .voices-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .voices-grid > :nth-child(2) { margin-top: 0; }
    }

    @media (max-width: 1000px) {
        .hero { padding: 2rem 0 4.5rem; }
        .hero-grid { grid-template-columns: minmax(0, 1fr); gap: 3.75rem; }
        .hero .lead { max-width: 100%; }
        .collage { max-width: 460px; margin: 0 auto; }
        .sticker-money { left: -0.5rem; }
        .sticker-wishes { right: -0.5rem; }
        .sticker-hbd { right: 0; }

        .band { padding-bottom: 4.5rem; }
        .frow { grid-template-columns: minmax(0, 1fr); gap: 2.75rem; }
        .frow + .frow { margin-top: 4.5rem; }
        /* keep the reading order copy-then-art on narrow screens */
        .frow-flip .frow-art { order: 0; }
        .finale { padding: 4.5rem 0 5rem; }
    }

    @media (max-width: 620px) {
        .flow-grid, .voices-grid { grid-template-columns: minmax(0, 1fr); }
        .band-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .band-strip .ph:nth-child(n+3) { display: none; }
        .band-strip .ph:nth-child(even) { margin-top: 0; }
        .trust { gap: 1.5rem; padding: 1.75rem 0; }
        .trust-sep { display: none; }
        .hero-ctas .btn { width: 100%; }
        .sticker-wishes { display: none; }
        .book { gap: 0.4rem; }
        .confetti { display: none; }
    }
</style>
