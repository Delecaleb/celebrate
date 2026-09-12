{{--
    Stories index cards + the archived celebration page.

    Loaded after core.blade.php, so it may use the tokens and the .btn / .input
    primitives defined there — but it defines no colours of its own.
--}}
<style>
    [x-cloak] { display: none !important; }

    /* ══ STORY CARDS (index) ═══════════════════════════════════════════ */
    .story-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1.6rem;
    }

    .story-card {
        display: flex; flex-direction: column;
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--r);
        overflow: hidden;
        text-decoration: none; color: inherit;
        transition: transform 0.18s, box-shadow 0.18s, border-color 0.18s;
    }
    .story-card:hover {
        transform: translateY(-5px);
        border-color: var(--primary-200);
        box-shadow: var(--shadow-card);
    }

    .story-cover { position: relative; display: block; aspect-ratio: 16 / 10; background: var(--ink-100); }
    .story-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }

    .story-chip {
        position: absolute; left: 0.85rem; bottom: 0.85rem;
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.3rem 0.7rem; border-radius: var(--r-pill);
        background: rgba(var(--surface-rgb) / 0.92);
        font-size: 0.73rem; font-weight: 800; letter-spacing: -0.01em; color: var(--primary-700);
    }
    .story-locked {
        position: absolute; right: 0.85rem; top: 0.85rem;
        width: 30px; height: 30px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: rgba(var(--ink-rgb) / 0.55); color: #fff; font-size: 0.95rem;
    }

    .story-body { display: flex; flex-direction: column; gap: 0.5rem; padding: 1.25rem 1.35rem 1.4rem; flex: 1; }
    .story-title { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.12rem; letter-spacing: -0.03em; }
    .story-where { display: flex; align-items: center; gap: 0.3rem; font-size: 0.8rem; color: var(--muted); }
    .story-quote {
        margin-top: 0.35rem; font-size: 0.9rem; line-height: 1.62; color: var(--ink-700);
        display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden;
    }
    .story-by { font-size: 0.78rem; font-weight: 600; color: var(--muted-2); }
    .story-stats {
        display: flex; flex-wrap: wrap; gap: 0.35rem 1rem;
        margin-top: auto; padding-top: 0.9rem; border-top: 1px solid var(--line-2);
        font-size: 0.79rem; font-weight: 700; color: var(--muted);
    }
    .story-stats span { display: inline-flex; align-items: center; gap: 0.3rem; }
    .story-open {
        display: inline-flex; align-items: center; gap: 0.35rem;
        font-size: 0.83rem; font-weight: 800; color: var(--primary);
    }
    .story-card:hover .story-open i { transform: translateX(3px); }
    .story-open i { transition: transform 0.18s; }

    /* ══ LEGAL PAGES ═══════════════════════════════════════════════════ */
    .legal { max-width: 760px; }
    .legal h2 {
        margin: 2.6rem 0 0.9rem;
        font-family: 'Outfit', sans-serif; font-weight: 800;
        font-size: 1.25rem; letter-spacing: -0.03em;
    }
    .legal h2:first-child { margin-top: 0; }
    .legal p { font-size: 1rem; line-height: 1.78; color: var(--ink-700); }
    .legal p + p { margin-top: 1rem; }
    .legal ul {
        list-style: none; margin: 1rem 0 0; padding: 0;
        display: flex; flex-direction: column; gap: 0.75rem;
    }
    .legal li {
        position: relative; padding-left: 1.4rem;
        font-size: 1rem; line-height: 1.72; color: var(--ink-700);
    }
    .legal li::before {
        content: ''; position: absolute; left: 0; top: 0.62em;
        width: 6px; height: 6px; border-radius: 50%; background: var(--primary);
    }
    .legal a { color: var(--primary); font-weight: 600; text-decoration: none; }
    .legal a:hover { text-decoration: underline; }
    .legal strong { color: var(--ink); font-weight: 700; }
    .legal-date {
        margin-top: 1.2rem;
        font-size: 0.83rem; font-weight: 700; letter-spacing: 0.04em;
        text-transform: uppercase; color: var(--muted-2);
    }
    .legal-foot {
        margin-top: 3rem; padding-top: 1.6rem;
        border-top: 1px solid var(--line-2);
        font-size: 0.93rem; color: var(--muted);
    }

    .foot-alias {
        margin-top: 0.9rem;
        font-size: 0.8rem; line-height: 1.6; color: var(--muted-2);
    }

    /* ── SEARCH ────────────────────────────────────────────────────────── */
    .sr-only {
        position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
        overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0;
    }

    .story-search {
        display: flex; align-items: center; gap: 0.6rem;
        margin: 0 0 1.6rem; padding: 0.45rem 0.5rem 0.45rem 1.05rem;
        background: var(--surface);
        border: 1.5px solid var(--line); border-radius: var(--r-pill);
        transition: border-color 0.16s, box-shadow 0.16s;
    }
    .story-search:focus-within { border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-100); }
    .story-search > i { font-size: 1.15rem; color: var(--muted-2); }
    .story-search input {
        flex: 1; min-width: 0;
        padding: 0.55rem 0;
        font: inherit; font-size: 0.95rem; color: var(--ink);
        background: none; border: 0; outline: none;
    }
    .story-search input::placeholder { color: var(--muted-2); }
    .story-search input::-webkit-search-cancel-button { cursor: pointer; }
    .story-search .btn { flex-shrink: 0; padding: 0.6rem 1.3rem; }

    .story-result-note {
        margin: 0 0 1.6rem; font-size: 0.92rem; color: var(--muted);
    }
    .story-result-note strong { color: var(--ink); }
    .story-result-note a { margin-left: 0.5rem; color: var(--primary); font-weight: 700; text-decoration: none; }
    .story-result-note a:hover { text-decoration: underline; }

    .story-more { margin-top: 2.6rem; text-align: center; }
    .story-more-left { font-weight: 600; opacity: 0.65; }
    .story-all { margin-top: 2.6rem; text-align: center; font-size: 0.95rem; color: var(--muted); }

    /* A quote card that links to the page it is quoting. */
    .quote-link { margin-top: 1.3rem; font-size: 0.84rem; }

    /* ══ ARCHIVED CELEBRATION PAGE ═════════════════════════════════════ */
    .story-page { padding-bottom: 0; }

    .sp-hero { position: relative; min-height: 380px; display: flex; align-items: flex-end; overflow: hidden; }
    .sp-hero-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .sp-hero-veil {
        position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(var(--ink-rgb) / 0.28) 0%, rgba(var(--ink-rgb) / 0.86) 82%);
    }
    .sp-hero-inner { position: relative; padding-top: 5rem; padding-bottom: 2.6rem; }

    .sp-back {
        position: absolute; top: 1.4rem; left: 1.6rem; z-index: 2;
        display: inline-flex; align-items: center; gap: 0.4rem;
        padding: 0.45rem 0.9rem; border-radius: var(--r-pill);
        background: rgba(var(--surface-rgb) / 0.9);
        font-size: 0.82rem; font-weight: 700; color: var(--ink); text-decoration: none;
    }
    .sp-back:hover { background: var(--surface); }

    .sp-chip {
        display: inline-flex; align-items: center; gap: 0.4rem;
        padding: 0.32rem 0.8rem; border-radius: var(--r-pill);
        background: var(--primary); color: #fff;
        font-size: 0.76rem; font-weight: 800;
    }
    .sp-title {
        margin-top: 0.85rem;
        font-family: 'Outfit', sans-serif; font-weight: 800;
        font-size: clamp(2rem, 5vw, 3.1rem); letter-spacing: -0.04em; line-height: 1.03;
        color: #fff;
    }
    .sp-sub {
        margin-top: 0.9rem; display: flex; flex-wrap: wrap; gap: 0.4rem 1.4rem;
        font-size: 0.9rem; font-weight: 600; color: var(--on-dark);
    }
    .sp-sub span { display: inline-flex; align-items: center; gap: 0.4rem; }

    .sp-lock {
        display: flex; gap: 0.9rem; align-items: flex-start;
        margin-top: 2rem; padding: 1.15rem 1.35rem;
        background: var(--primary-50);
        border: 1px solid var(--primary-100);
        border-radius: var(--r-sm);
    }
    .sp-lock > i { font-size: 1.35rem; color: var(--primary); line-height: 1.2; }
    .sp-lock-title { font-weight: 800; font-size: 0.98rem; letter-spacing: -0.02em; }
    .sp-lock-body { margin-top: 0.3rem; font-size: 0.88rem; line-height: 1.65; color: var(--ink-700); }

    .sp-stats {
        display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1.5rem;
        margin: 2.2rem 0 0.6rem; padding: 1.6rem 0;
        border-top: 1px solid var(--line-2); border-bottom: 1px solid var(--line-2);
    }
    .sp-stats .n { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.6rem; letter-spacing: -0.04em; }
    .sp-stats .l { margin-top: 0.2rem; font-size: 0.78rem; font-weight: 700; color: var(--muted); }

    .sp-grid { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(0, 1fr); gap: 2.6rem; padding: 2.6rem 0 1rem; }
    .sp-h2 {
        display: flex; align-items: center; gap: 0.5rem;
        font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.25rem; letter-spacing: -0.03em;
        margin-bottom: 1.2rem;
    }
    .sp-h2 i { color: var(--primary); }
    .sp-h3 { margin: 1.9rem 0 0.9rem; font-weight: 800; font-size: 0.95rem; letter-spacing: -0.02em; }

    .sp-composer { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.6rem; }
    .sp-composer .input { flex: 1; background: var(--surface-2); color: var(--muted-2); cursor: not-allowed; }

    .sp-av {
        flex-shrink: 0; width: 40px; height: 40px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--primary-100); color: var(--primary-700);
        font-size: 0.8rem; font-weight: 800; letter-spacing: -0.02em;
    }
    .sp-av.is-muted { background: var(--ink-100); color: var(--muted-2); font-size: 1rem; }
    .sp-av-sm { width: 30px; height: 30px; font-size: 0.68rem; }

    .sp-wish { display: flex; gap: 0.85rem; padding: 1.1rem 0; border-top: 1px solid var(--line-2); }
    .sp-wish-body { min-width: 0; flex: 1; }
    .sp-wish-head { display: flex; align-items: baseline; justify-content: space-between; gap: 0.75rem; }
    .sp-wish-name { font-weight: 800; font-size: 0.93rem; letter-spacing: -0.02em; }
    .sp-wish-time { flex-shrink: 0; font-size: 0.76rem; color: var(--muted-2); }
    .sp-wish-text { margin-top: 0.35rem; font-size: 0.95rem; line-height: 1.7; color: var(--ink-700); }
    .sp-wish-photo { display: block; margin-top: 0.75rem; border-radius: var(--r-sm); overflow: hidden; max-width: 320px; }
    .sp-wish-photo img { display: block; width: 100%; height: auto; }
    .sp-hearts {
        display: inline-flex; align-items: center; gap: 0.3rem; margin-top: 0.6rem;
        padding: 0.2rem 0.55rem; border-radius: var(--r-pill);
        background: var(--ink-50);
        font-size: 0.76rem; font-weight: 700; color: var(--muted);
    }
    .sp-hearts i { color: var(--primary); }

    .sp-rest {
        margin-top: 1.4rem; padding-top: 1.2rem; border-top: 1px solid var(--line-2);
        font-size: 0.87rem; line-height: 1.65; color: var(--muted);
    }

    .sp-side { align-self: start; }
    .sp-raise { padding: 1.3rem 1.4rem; background: var(--surface-2); border: 1px solid var(--line); border-radius: var(--r-sm); }
    .sp-raise-label { font-size: 0.72rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted); }
    .sp-raise-amount { margin-top: 0.3rem; font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.9rem; letter-spacing: -0.04em; }
    .sp-raise-meta { margin-top: 0.55rem; font-size: 0.79rem; font-weight: 600; color: var(--muted); }

    .sp-bar { margin-top: 0.7rem; height: 7px; border-radius: var(--r-pill); background: var(--ink-100); overflow: hidden; }
    .sp-bar span { display: block; height: 100%; background: var(--primary); }
    .sp-bar-sm { height: 5px; margin-top: 0.5rem; }

    .sp-item { display: flex; gap: 0.85rem; padding: 1rem 0; border-bottom: 1px solid var(--line-2); }
    .sp-item-icon {
        flex-shrink: 0; width: 38px; height: 38px; border-radius: var(--r-sm);
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--primary-50); color: var(--primary); font-size: 1.05rem;
    }
    .sp-item-body { flex: 1; min-width: 0; }
    .sp-item-name { font-weight: 700; font-size: 0.9rem; letter-spacing: -0.02em; }
    .sp-item-meta { margin-top: 0.4rem; font-size: 0.78rem; color: var(--muted); }
    .sp-flag {
        margin-left: 0.4rem; padding: 0.1rem 0.45rem; border-radius: var(--r-pill);
        background: var(--ok-l); color: var(--ok); font-size: 0.66rem; font-weight: 800;
    }

    .sp-gifts { list-style: none; margin: 0; padding: 0; }
    .sp-gifts li {
        display: flex; align-items: center; gap: 0.6rem;
        padding: 0.6rem 0; border-bottom: 1px solid var(--line-2);
        font-size: 0.86rem;
    }
    .sp-gift-name { font-weight: 700; }
    .sp-gift-when { margin-left: auto; font-size: 0.74rem; color: var(--muted-2); }
    .sp-gift-amt { font-weight: 800; color: var(--primary); }

    .sp-side-note {
        display: flex; gap: 0.5rem; margin-top: 1.2rem;
        font-size: 0.8rem; line-height: 1.6; color: var(--muted);
    }

    .sp-more { padding: 3rem 0 3.5rem; border-top: 1px solid var(--line-2); }
    .sp-more-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.2rem; }
    .sp-more-card {
        display: flex; gap: 0.85rem; align-items: center;
        padding: 0.7rem; background: var(--surface);
        border: 1px solid var(--line); border-radius: var(--r-sm);
        text-decoration: none; color: inherit;
        transition: border-color 0.16s, transform 0.16s;
    }
    .sp-more-card:hover { border-color: var(--primary-200); transform: translateY(-3px); }
    .sp-more-card img { width: 74px; height: 62px; object-fit: cover; border-radius: calc(var(--r-sm) - 3px); flex-shrink: 0; }
    .sp-more-card span { display: flex; flex-direction: column; gap: 0.2rem; font-size: 0.78rem; color: var(--muted); }
    .sp-more-card strong { font-size: 0.92rem; letter-spacing: -0.02em; color: var(--ink); }

    /* ══ RESPONSIVE ════════════════════════════════════════════════════ */
    @media (max-width: 1000px) {
        .story-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sp-grid { grid-template-columns: minmax(0, 1fr); gap: 2.2rem; }
        .sp-more-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 620px) {
        .story-grid { grid-template-columns: minmax(0, 1fr); }
        .sp-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.3rem; }
        .sp-more-grid { grid-template-columns: minmax(0, 1fr); }
        .sp-hero { min-height: 300px; }
        .sp-back { left: 1.1rem; }
    }
</style>
