{{--
    Dashboard shell.

    Pages live in resources/views/dashboard/partials/*.blade.php and are injected
    into <main id="view">. DashboardController returns the full shell on a normal
    request and just the partial (as JSON) when the AJAX router asks for it, so
    moving between rail items never reloads the page. The links stay real
    <a href> values, so everything still works with JavaScript disabled.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Signed-in pages: never indexed, but links out of them still count. --}}
    <x-seo :title="$title" noindex />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <x-brand-tokens />

    <style>
        /*
          Dashboard — CelebrateMi design system.

          Layout : fixed left rail on ≥1024px, slide-over drawer below that.
          Colour : exactly TWO hues, both injected by the brand-tokens component
                   in the head above (resources/brand.json).
                     --primary    purple → identity, CTAs, emphasis, destructive
                     --secondary  gold   → the one complement; money in, live, done
                   Everything else is the neutral ink/line ramp. Semantic tokens
                   (--ok / --warn / --danger) are ALIASES onto those two so no third
                   hue can sneak in through a badge or a status chip.
          Space  : hierarchy comes from white space + 1px rules, not from fills.

          NO COLOUR LITERALS BELOW. Change colours in resources/brand.json.
        */
        :root {
            /* kept for the rules further down that still say --teal */
            --teal:   var(--secondary-700);
            --teal-d: var(--secondary-800);
            --teal-l: var(--secondary-100);

            /* semantic aliases — no new hues */
            --ok:       var(--teal);    --ok-l:     var(--teal-l);
            --warn:     var(--muted);   --warn-l:   var(--line-2);
            --danger:   var(--primary); --danger-l: var(--primary-l);

            --rail-w:   264px;
            --topbar-h: 68px;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        [x-cloak] { display: none !important; }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: var(--surface);
            color: var(--ink);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4 { font-family: 'Outfit', system-ui, sans-serif; letter-spacing: -0.03em; line-height: 1.08; }
        a { color: inherit; }
        ::selection { background: var(--primary); color: #fff; }

        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible {
            outline: 2px solid var(--primary); outline-offset: 2px;
        }

        .text-muted { color: var(--muted); }
        .accent     { color: var(--primary); }
        .danger     { color: var(--danger); }

        .eyebrow {
            font-size: 0.66rem; font-weight: 800; letter-spacing: 0.14em;
            text-transform: uppercase; color: var(--muted-2);
        }

        /* ══ SHELL ═══════════════════════════════════════ */
        .shell { display: flex; min-height: 100vh; }

        .rail {
            position: fixed; top: 0; bottom: 0; left: 0; z-index: 70;
            width: var(--rail-w); flex-shrink: 0;
            display: flex; flex-direction: column;
            background: var(--surface); border-right: 1px solid var(--line);
        }
        .main {
            flex: 1; min-width: 0;
            margin-left: var(--rail-w);
            display: flex; flex-direction: column;
        }

        /* ── rail head ── */
        .rail-head {
            display: flex; align-items: center; gap: 0.6rem;
            height: var(--topbar-h); padding: 0 1.5rem;
            border-bottom: 1px solid var(--line); flex-shrink: 0;
        }
        .rail-mark {
            width: 30px; height: 30px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: var(--primary); color: #fff; font-size: 1rem;
        }
        .brand {
            font-family: 'Outfit', sans-serif; font-weight: 900;
            font-size: 1.08rem; letter-spacing: -0.035em;
            color: var(--ink); text-decoration: none;
        }

        /* ── AJAX router: progress bar + page transition ── */
        #route-progress {
            position: fixed; top: 0; left: 0; height: 3px; width: 0;
            background: var(--primary); z-index: 200;
            opacity: 0; transition: width 0.2s ease-out, opacity 0.2s;
        }
        #route-progress.is-active { opacity: 1; }

        #view { animation: view-in 0.24s ease-out both; }
        @keyframes view-in {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: none; }
        }

        /* ── rail nav ── */
        .rail-nav {
            flex: 1; overflow-y: auto; padding: 1.75rem 0.85rem 1.5rem;
            scrollbar-width: thin; scrollbar-color: var(--line) transparent;
        }
        .rail-nav::-webkit-scrollbar { width: 6px; }
        .rail-nav::-webkit-scrollbar-thumb { background: var(--line); }
        .nav-group + .nav-group { margin-top: 1.75rem; }
        .nav-group-label { padding: 0 0.9rem; margin-bottom: 0.6rem; }
        .nav-link {
            position: relative; display: flex; align-items: center; gap: 0.7rem;
            width: 100%; padding: 0.62rem 0.9rem;
            font-family: inherit; font-size: 0.87rem; font-weight: 600;
            color: var(--muted); text-align: left; text-decoration: none;
            background: none; border: 0; cursor: pointer;
            transition: background 0.14s, color 0.14s;
        }
        .nav-link::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0;
            width: 2px; background: transparent;
        }
        .nav-link i { font-size: 1.15rem; width: 1.15rem; flex-shrink: 0; line-height: 1; }
        .nav-link:hover { background: var(--surface-2); color: var(--ink); }
        /* Active state keys off aria-current, which pageRouter.js maintains as it
           swaps pages — a class would go stale on client-side navigation. */
        .nav-link[aria-current="page"] { background: var(--primary-xl); color: var(--primary); }
        .nav-link[aria-current="page"]::before { background: var(--primary); }
        .nav-count {
            margin-left: auto; min-width: 20px; height: 20px; padding: 0 6px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 999px; background: var(--primary); color: #fff;
            font-size: 0.64rem; font-weight: 800;
        }

        /* ── rail foot ── */
        .rail-foot { padding: 0.85rem; border-top: 1px solid var(--line); flex-shrink: 0; }
        .rail-admin {
            display: flex; align-items: center; gap: 0.6rem;
            padding: 0.62rem 0.9rem; margin-bottom: 0.5rem;
            font-size: 0.84rem; font-weight: 700; color: var(--teal);
            background: var(--teal-l); text-decoration: none;
        }
        .rail-admin i { font-size: 1.1rem; }

        .user-menu { position: relative; }
        .user-trigger {
            display: flex; align-items: center; gap: 0.65rem; width: 100%;
            padding: 0.55rem 0.6rem;
            background: none; border: 0; cursor: pointer;
            font-family: inherit; text-align: left;
            transition: background 0.14s;
        }
        .user-trigger:hover { background: var(--surface-2); }
        .user-avatar {
            width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
            background: var(--primary); color: #fff;
            font-size: 0.8rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
        }
        .user-name { font-size: 0.85rem; font-weight: 700; color: var(--ink); line-height: 1.2; }
        .user-role { font-size: 0.7rem; color: var(--muted-2); margin-top: 0.12rem; text-transform: capitalize; }
        .user-dropdown {
            position: absolute; bottom: calc(100% + 8px); left: 0; right: 0; z-index: 80;
            overflow: hidden;
            background: var(--surface); border: 1px solid var(--line);
            box-shadow: 0 18px 48px -16px rgba(20,11,18,0.28);
        }
        .dropdown-item {
            display: block; width: 100%; text-align: left;
            padding: 0.7rem 1rem; font-family: inherit;
            font-size: 0.85rem; font-weight: 500; color: var(--ink);
            text-decoration: none; background: none; border: 0; cursor: pointer;
            transition: background 0.12s;
        }
        .dropdown-item:hover { background: var(--surface-2); }
        .dropdown-item.danger { color: var(--danger); }
        .dropdown-item.danger:hover { background: var(--danger-l); }
        .dropdown-divider { height: 1px; background: var(--line); }

        /* ── scrim (mobile drawer) ── */
        .rail-scrim {
            position: fixed; inset: 0; z-index: 65;
            background: rgba(20,11,18,0.42); backdrop-filter: blur(2px);
        }

        /* ══ TOPBAR ══════════════════════════════════════ */
        .topbar {
            position: sticky; top: 0; z-index: 50;
            height: var(--topbar-h); flex-shrink: 0;
            display: flex; align-items: center; gap: 1rem;
            padding: 0 2.75rem;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(14px) saturate(1.4);
            border-bottom: 1px solid var(--line);
        }
        .topbar-title { font-size: 1.02rem; font-weight: 800; letter-spacing: -0.025em; }
        .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 0.6rem; }
        .rail-toggle {
            display: none; align-items: center; justify-content: center;
            width: 38px; height: 38px; flex-shrink: 0;
            background: none; border: 1px solid var(--line);
            color: var(--ink); font-size: 1.25rem; cursor: pointer;
        }

        /* ══ CONTENT ═════════════════════════════════════ */
        .content { padding: 3rem 2.75rem 6rem; max-width: 1240px; }

        .page-head {
            display: flex; align-items: flex-end; justify-content: space-between;
            gap: 1.5rem; flex-wrap: wrap; margin-bottom: 2.75rem;
        }
        .page-head h1 { font-weight: 900; font-size: clamp(1.75rem, 3vw, 2.35rem); letter-spacing: -0.04em; }
        .page-head .sub { font-size: 0.92rem; color: var(--muted); margin-top: 0.6rem; max-width: 46ch; }
        .greeting-tag {
            display: inline-flex; align-items: center; gap: 0.4rem;
            font-size: 0.68rem; font-weight: 800; letter-spacing: 0.13em;
            text-transform: uppercase; color: var(--primary); margin-bottom: 0.85rem;
        }
        .head-actions { display: flex; gap: 0.65rem; flex-wrap: wrap; align-items: center; }

        /* ══ BUTTONS ═════════════════════════════════════ */
        .btn-solid, .btn-create-hero, .btn-create-lg, .btn-empty {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.45rem;
            font-family: inherit; font-weight: 700; white-space: nowrap;
            border: 1px solid transparent; border-radius: 999px; cursor: pointer;
            background: var(--primary); color: #fff;
            padding: 0.72rem 1.5rem; font-size: 0.87rem;
            text-decoration: none; transition: background 0.15s;
        }
        .btn-solid:hover, .btn-create-hero:hover,
        .btn-create-lg:hover, .btn-empty:hover { background: var(--primary-d); }
        .btn-solid:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-create-lg, .btn-empty { padding: 0.6rem 1.25rem; font-size: 0.84rem; }
        .btn-block { width: 100%; }

        .btn-quiet {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.45rem;
            padding: 0.7rem 1.35rem; border-radius: 999px;
            font-family: inherit; font-size: 0.87rem; font-weight: 700;
            background: var(--surface); color: var(--ink);
            border: 1px solid var(--line); cursor: pointer; text-decoration: none;
            transition: border-color 0.15s;
        }
        .btn-quiet:hover { border-color: var(--ink); }

        .icon-btn, .action-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem;
            padding: 0.48rem 0.85rem; font-family: inherit; font-size: 0.78rem; font-weight: 600;
            background: var(--surface); color: var(--ink);
            border: 1px solid var(--line); border-radius: 0;
            cursor: pointer; text-decoration: none; transition: border-color 0.15s, color 0.15s;
        }
        .icon-btn:hover, .action-btn:hover { border-color: var(--ink); }

        /* ══ STATS ═══════════════════════════════════════ */
        /* Each page brings its own summary cards, so the bar has to sit happily
           at two, three or four across. */
        .stats-bar {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem;
            margin-bottom: 3rem;
        }
        .stats-bar.cols-2 { grid-template-columns: repeat(2, 1fr); }
        .stats-bar.cols-3 { grid-template-columns: repeat(3, 1fr); }
        .stat-card {
            position: relative; background: var(--surface); border: 1px solid var(--line);
            padding: 1.6rem 1.6rem 1.7rem;
        }
        .stat-icon {
            width: 34px; height: 34px; margin-bottom: 1.5rem;
            display: flex; align-items: center; justify-content: center; font-size: 1.05rem;
            background: var(--primary-l); color: var(--primary);
        }
        /* legacy colour variants collapse onto the two-hue palette */
        .stat-icon.orange, .stat-icon.purple, .stat-icon.blue { background: var(--primary-l); color: var(--primary); }
        .stat-icon.green  { background: var(--teal-l); color: var(--teal); }
        .stat-label { font-size: 0.66rem; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted); margin-bottom: 0.55rem; }
        .stat-value { font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 1.85rem; line-height: 1; letter-spacing: -0.045em; }
        .stat-sub { font-size: 0.74rem; color: var(--muted-2); margin-top: 0.5rem; }

        /* ══ PANEL HEADER ════════════════════════════════ */
        .tab-panel-header {
            display: flex; align-items: flex-end; justify-content: space-between;
            gap: 1.25rem; flex-wrap: wrap; margin-bottom: 1.75rem;
        }
        .panel-title { font-weight: 800; font-size: 1.32rem; letter-spacing: -0.03em; }
        .panel-subtitle { font-size: 0.88rem; color: var(--muted); margin-top: 0.4rem; }

        /* ══ EVENTS ══════════════════════════════════════ */
        .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(288px,1fr)); gap: 1.5rem; }
        .event-add-card {
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.7rem;
            min-height: 240px; cursor: pointer; font-family: inherit; color: var(--muted-2);
            background: var(--surface-2); border: 1px dashed var(--line);
            transition: border-color 0.18s, color 0.18s, background 0.18s;
        }
        .event-add-card:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-xl); }
        .event-add-card .add-icon { font-size: 1.7rem; line-height: 1; }
        .event-add-card .add-label { font-size: 0.86rem; font-weight: 700; }

        .event-card {
            display: flex; flex-direction: column; overflow: hidden;
            background: var(--surface); border: 1px solid var(--line);
            transition: border-color 0.18s, box-shadow 0.18s;
        }
        .event-card:hover { border-color: var(--ink); box-shadow: 0 14px 40px -22px rgba(20,11,18,0.35); }
        .event-cover { position: relative; height: 172px; overflow: hidden; flex-shrink: 0; background: var(--surface-2); }
        .event-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .event-cover-gradient { width: 100%; height: 100%; background: var(--primary-l); }
        .status-badge {
            position: absolute; top: 0.8rem; right: 0.8rem;
            padding: 0.28rem 0.65rem; border-radius: 999px;
            font-size: 0.62rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase;
        }
        .badge-live   { background: var(--teal);    color: #fff; }
        .badge-draft  { background: var(--surface); color: var(--muted); border: 1px solid var(--line); }
        .badge-closed { background: var(--ink);     color: #fff; }
        .event-type-icon {
            position: absolute; bottom: 0.8rem; left: 0.8rem;
            width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
            background: var(--surface); font-size: 1rem; color: var(--primary);
        }
        .event-body { padding: 1.35rem 1.4rem 1.4rem; flex: 1; display: flex; flex-direction: column; }
        .event-type-tag { font-size: 0.62rem; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: var(--primary); margin-bottom: 0.6rem; }
        .event-title { font-weight: 800; font-size: 1.05rem; letter-spacing: -0.025em; margin-bottom: 0.4rem; }
        .event-date { font-size: 0.78rem; color: var(--muted); display: flex; align-items: center; gap: 0.35rem; }
        .event-stats { display: flex; gap: 1.25rem; margin-top: 1.25rem; padding-top: 1.1rem; border-top: 1px solid var(--line); }
        .event-stat { font-size: 0.76rem; color: var(--muted); display: flex; align-items: center; gap: 0.3rem; }
        .event-stat strong { color: var(--ink); font-weight: 700; }
        .event-actions { display: flex; gap: 0.45rem; margin-top: 1.1rem; }
        .event-actions > .action-btn { flex: 1; }
        /* the delete control is a bare <button> inside a <form> — keep it square
           and hugging its content instead of stretching like the other actions */
        .event-actions > form { flex: 0 0 auto; display: flex; }
        .action-delete {
            display: inline-flex; align-items: center; justify-content: center;
            width: 34px; height: 34px; padding: 0;
            background: var(--surface); color: var(--muted);
            border: 1px solid var(--line); border-radius: 0;
            font-size: 1rem; cursor: pointer; transition: border-color 0.15s, background 0.15s, color 0.15s;
        }
        .action-delete:hover { border-color: var(--primary); background: var(--primary-l); color: var(--primary); }

        /* ══ EMPTY ═══════════════════════════════════════ */
        .empty-state {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center; padding: 5rem 1.5rem;
            background: var(--surface); border: 1px dashed var(--line);
        }
        .empty-icon { font-size: 2.3rem; color: var(--muted-2); margin-bottom: 1rem; }
        .empty-state h3 { font-size: 1.1rem; font-weight: 800; margin-bottom: 0.5rem; }
        .empty-state p { font-size: 0.88rem; color: var(--muted); max-width: 380px; line-height: 1.65; }
        .empty-state .btn-empty { margin-top: 1.6rem; }

        /* ══ UPCOMING ════════════════════════════════════ */
        .upcoming-list { background: var(--surface); border: 1px solid var(--line); }
        .upcoming-item {
            display: grid; grid-template-columns: 72px 1fr auto; gap: 1.35rem;
            align-items: center; padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--line); text-decoration: none; color: inherit;
        }
        .upcoming-item:first-child { border-top: 0; }
        .upcoming-item:hover { background: var(--surface-2); }
        .date-box { text-align: center; border: 1px solid var(--line); padding: 0.6rem 0.25rem; }
        .date-day { font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 1.35rem; line-height: 1; display: block; }
        .date-month { font-size: 0.62rem; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); margin-top: 0.25rem; display: block; }
        .upcoming-info-title { font-weight: 700; font-size: 0.96rem; }
        .upcoming-info-meta { font-size: 0.78rem; color: var(--muted); margin-top: 0.25rem; }
        .upcoming-countdown { font-size: 0.7rem; font-weight: 800; letter-spacing: 0.09em; text-transform: uppercase; color: var(--primary); white-space: nowrap; }

        /* ══ NOTIFICATIONS ═══════════════════════════════ */
        .notif-list { background: var(--surface); border: 1px solid var(--line); }
        .notif-item { display: flex; gap: 1rem; padding: 1.15rem 1.5rem; border-top: 1px solid var(--line); }
        .notif-item:first-child { border-top: 0; }
        .notif-item.unread { background: var(--primary-xl); }
        .notif-indicator {
            width: 34px; height: 34px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 0.95rem;
            background: var(--surface-2); color: var(--muted);
        }
        .notif-item.unread .notif-indicator { background: var(--primary-l); color: var(--primary); }
        .notif-content { min-width: 0; flex: 1; }
        .notif-title { font-weight: 700; font-size: 0.9rem; }
        .notif-msg { font-size: 0.85rem; color: var(--muted); margin-top: 0.2rem; line-height: 1.6; }
        .notif-time { font-size: 0.72rem; color: var(--muted-2); margin-top: 0.45rem; }
        .notif-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--primary); flex-shrink: 0; margin-top: 0.6rem; }

        /* ══ WALLET ══════════════════════════════════════ */
        .wallet-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px,1fr)); gap: 1.25rem; margin-bottom: 2.5rem; }
        /* Both wallet cards sit on the primary ramp — no second hue. They read
           apart by depth and by pattern: the local card is the brand purple with
           a dot grid, the global card a deeper stop with a diagonal weave. */
        .wallet-hero {
            position: relative; overflow: hidden;
            background: var(--primary-600); color: #fff;
            padding: 2.25rem 2.25rem 2.5rem;
        }
        .wallet-hero.is-global { background: var(--primary-800); }

        .wallet-hero::before {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background-image: radial-gradient(rgba(255,255,255,0.16) 1.5px, transparent 1.5px);
            background-size: 22px 22px;
            mask-image: radial-gradient(ellipse 60% 70% at 88% 40%, #000 10%, transparent 72%);
            -webkit-mask-image: radial-gradient(ellipse 60% 70% at 88% 40%, #000 10%, transparent 72%);
        }
        .wallet-hero.is-global::before {
            background-image: repeating-linear-gradient(
                135deg,
                rgba(255,255,255,0.11) 0 2px,
                transparent 2px 13px
            );
            background-size: auto;
            mask-image: linear-gradient(205deg, #000 8%, transparent 76%);
            -webkit-mask-image: linear-gradient(205deg, #000 8%, transparent 76%);
        }

        /* a soft lift in the top-left so the flat fill doesn't read as a slab */
        .wallet-hero::after {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background: radial-gradient(ellipse 70% 90% at 0% 0%, rgba(255,255,255,0.13), transparent 60%);
        }
        .wallet-hero > * { position: relative; z-index: 1; }
        .wallet-label { font-size: 0.66rem; font-weight: 800; letter-spacing: 0.13em; text-transform: uppercase; color: var(--on-dark); margin-top: 1.5rem; }
        .wallet-balance { font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 2.85rem; line-height: 1; letter-spacing: -0.05em; margin-top: 0.7rem; }
        .wallet-chip {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.32rem 0.75rem; border-radius: 999px;
            border: 1px solid var(--on-dark-line); color: #fff;
            font-size: 0.68rem; font-weight: 700;
        }

        /* ══ TRANSACTIONS ════════════════════════════════ */
        .tx-list { background: var(--surface); border: 1px solid var(--line); }
        .tx-list-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.05rem 1.5rem; border-bottom: 1px solid var(--line);
            font-size: 0.66rem; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted);
        }
        .tx-item { display: flex; align-items: center; gap: 1rem; padding: 1.1rem 1.5rem; border-top: 1px solid var(--line); }
        .tx-item:first-of-type { border-top: 0; }
        .tx-icon {
            width: 34px; height: 34px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 1rem;
            background: var(--teal-l); color: var(--teal);
        }
        .tx-icon.debit { background: var(--surface-2); color: var(--muted); }
        .tx-info { min-width: 0; flex: 1; }
        .tx-desc { font-size: 0.89rem; font-weight: 600; }
        .tx-from {
            display: flex; align-items: center; gap: 0.28rem;
            font-size: 0.79rem; font-weight: 600; color: var(--primary);
            margin-top: 0.18rem;
        }
        .tx-from i { font-size: 0.95rem; line-height: 1; }
        .tx-meta { font-size: 0.75rem; color: var(--muted); margin-top: 0.2rem; }
        .tx-right { text-align: right; flex-shrink: 0; }
        .tx-amount { font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 1rem; letter-spacing: -0.025em; color: var(--teal); }
        .tx-amount.debit { color: var(--ink); }
        .tx-status { font-size: 0.68rem; color: var(--muted); margin-top: 0.25rem; }

        .wd-badge {
            display: inline-flex; align-items: center; gap: 0.3rem;
            padding: 0.24rem 0.6rem; border-radius: 999px;
            font-size: 0.64rem; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase;
            background: var(--line-2); color: var(--muted);
        }

        /* ══ QUIET DISCLOSURE ════════════════════════════ */
        /* For things that should be reachable but not advertised — the wallet's
           full ledger. No fill, no border, muted until you go looking for it. */
        .ledger { margin-top: 2.5rem; }
        .link-quiet {
            display: inline-flex; align-items: center; gap: 0.35rem;
            padding: 0.35rem 0; background: none; border: 0; cursor: pointer;
            font-family: inherit; font-size: 0.78rem; font-weight: 600;
            color: var(--muted-2); text-decoration: none;
            transition: color 0.15s;
        }
        .link-quiet:hover { color: var(--ink); }
        .link-quiet i { font-size: 0.95rem; }

        /* ══ BANK ════════════════════════════════════════ */
        .bank-list { display: grid; gap: 1rem; }
        .bank-item { display: flex; align-items: center; gap: 1.15rem; padding: 1.35rem 1.5rem; background: var(--surface); border: 1px solid var(--line); }
        .bank-icon {
            width: 42px; height: 42px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
            background: var(--primary-l); color: var(--primary);
        }
        .bank-info { min-width: 0; flex: 1; }
        .bank-name { font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 0.55rem; flex-wrap: wrap; }
        .bank-meta { font-size: 0.79rem; color: var(--muted); margin-top: 0.25rem; }
        .bank-actions { display: flex; gap: 0.4rem; flex-shrink: 0; }
        .default-badge {
            padding: 0.2rem 0.6rem; border-radius: 999px;
            background: var(--teal-l); color: var(--teal);
            font-size: 0.6rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase;
        }

        /* ══ DISCOVER ════════════════════════════════════ */
        .discover-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(256px,1fr)); gap: 1.5rem; }
        .discover-card {
            display: flex; flex-direction: column; overflow: hidden; text-decoration: none; color: inherit;
            background: var(--surface); border: 1px solid var(--line); transition: border-color 0.18s, box-shadow 0.18s;
        }
        .discover-card:hover { border-color: var(--ink); box-shadow: 0 14px 40px -22px rgba(20,11,18,0.35); }
        .discover-cover { position: relative; height: 158px; overflow: hidden; background: var(--surface-2); }
        .discover-cover img { width: 100%; height: 100%; object-fit: cover; }
        .discover-cover-grad { width: 100%; height: 100%; background: var(--primary-l); }
        .discover-type-badge {
            position: absolute; top: 0.8rem; left: 0.8rem;
            padding: 0.26rem 0.65rem; border-radius: 999px;
            background: var(--surface);
            font-size: 0.6rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--primary);
        }
        .discover-body { padding: 1.2rem 1.3rem 1.35rem; }
        .discover-title { font-weight: 800; font-size: 1rem; letter-spacing: -0.025em; }
        .discover-meta { font-size: 0.77rem; color: var(--muted); margin-top: 0.4rem; }
        .discover-link { font-size: 0.78rem; font-weight: 700; color: var(--primary); margin-top: 1rem; display: inline-flex; align-items: center; gap: 0.35rem; }

        /* ══ FORMS ═══════════════════════════════════════ */
        .form-card { background: var(--surface); border: 1px solid var(--line); padding: 1.75rem; }

        .m-label {
            display: block; margin-bottom: 0.45rem;
            font-size: 0.68rem; font-weight: 800; letter-spacing: 0.1em;
            text-transform: uppercase; color: var(--muted);
        }
        .m-input {
            display: block; width: 100%;
            padding: 0.75rem 0.9rem;
            font-family: inherit; font-size: 0.9rem; font-weight: 500; color: var(--ink);
            background: var(--surface); border: 1px solid var(--line); border-radius: 0;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .m-input:focus { outline: none; border-color: var(--primary); box-shadow: inset 0 0 0 1px var(--primary); }
        .m-input::placeholder { color: var(--muted-2); }
        .m-input:disabled { background: var(--surface-2); }
        .m-hint  { margin-top: 0.4rem; font-size: 0.76rem; color: var(--muted-2); }
        .m-error { margin-top: 0.4rem; font-size: 0.76rem; color: var(--danger); }
        .m-field + .m-field { margin-top: 1.1rem; }

        /* Payout account name — filled in by the bank, never typed */
        .bank-name-field[readonly] { background: var(--surface-2); cursor: default; }
        .bank-name-field.is-verified { border-color: var(--ok); color: var(--ink); font-weight: 700; }
        .m-hint.bank-ok { color: var(--ok); font-weight: 600; }

        /* Occasion picker — icon tiles in place of a <select> */
        .occasion-grid { display: flex; flex-wrap: wrap; gap: 0.55rem; }
        .occasion-tile {
            flex: 1 1 calc(33.333% - 0.37rem); min-width: 98px;
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.4rem;
            padding: 0.85rem 0.5rem;
            font: inherit; font-size: 0.8rem; font-weight: 700; color: var(--ink); text-align: center;
            background: var(--surface);
            border: 1px solid var(--line); border-radius: 0;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s, color 0.15s, box-shadow 0.15s;
        }
        .occasion-tile i { font-size: 1.4rem; line-height: 1; color: var(--muted); transition: color 0.15s; }
        .occasion-tile:hover { border-color: var(--primary); background: var(--primary-l); }
        .occasion-tile:focus-visible { outline: none; border-color: var(--primary); box-shadow: inset 0 0 0 1px var(--primary); }
        .occasion-tile.is-on {
            border-color: var(--primary); background: var(--primary-l); color: var(--primary);
            box-shadow: inset 0 0 0 1px var(--primary);
        }
        .occasion-tile.is-on i { color: var(--primary); }

        .m-title { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.3rem; letter-spacing: -0.03em; }
        .m-sub   { margin-top: 0.5rem; font-size: 0.86rem; color: var(--muted); line-height: 1.6; }
        .m-close {
            position: absolute; top: 1.1rem; right: 1.1rem;
            width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
            background: var(--surface-2); border: 0; cursor: pointer;
            color: var(--muted); font-size: 1.1rem; transition: background 0.15s, color 0.15s;
        }
        .m-close:hover { background: var(--line-2); color: var(--ink); }

        .step {
            width: 30px; height: 30px; border-radius: 999px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; font-weight: 800;
            background: var(--line-2); color: var(--muted);
            transition: background 0.2s, color 0.2s;
        }
        .step.is-active { background: var(--primary); color: #fff; }

        /* A label for a control that a sighted user reads from its icon. */
        .sr-only {
            position: absolute; width: 1px; height: 1px;
            padding: 0; margin: -1px; overflow: hidden;
            clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
        }

        /* ══ PROFILE ═════════════════════════════════════
           An identity band across the top, then the things you can change.
           The band is part of the form, not a picture of it: the avatar is the
           control, and the name updates as you type. */
        .pf-identity {
            display: flex; align-items: center; gap: 1.6rem; flex-wrap: wrap;
            padding: 1.75rem;
            background: var(--surface); border: 1px solid var(--line);
        }
        .pf-avatar-wrap { position: relative; flex-shrink: 0; }
        .pf-avatar {
            width: 92px; height: 92px; border-radius: 999px; overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            background: var(--primary); color: #fff;
            font-family: 'Outfit', sans-serif; font-weight: 900;
            font-size: 2.05rem; letter-spacing: -0.05em; line-height: 1;
            user-select: none;
        }
        .pf-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .pf-avatar-btn {
            position: absolute; right: -3px; bottom: -3px;
            width: 32px; height: 32px; border-radius: 999px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem;
            background: var(--ink); color: var(--surface);
            border: 3px solid var(--surface);
            cursor: pointer; transition: background 0.15s;
        }
        .pf-avatar-btn:hover { background: var(--primary); }
        .pf-avatar-wrap input[type="file"]:focus-visible + .pf-avatar-btn {
            outline: 2px solid var(--primary); outline-offset: 2px;
        }

        .pf-id-main { flex: 1; min-width: 0; }
        .pf-name {
            font-family: 'Outfit', sans-serif; font-weight: 900;
            font-size: 1.65rem; letter-spacing: -0.045em; line-height: 1.08;
            overflow-wrap: anywhere;
        }
        .pf-email { font-size: 0.88rem; color: var(--muted); margin-top: 0.35rem; overflow-wrap: anywhere; }
        .pf-chips { display: flex; flex-wrap: wrap; gap: 0.45rem; margin-top: 1rem; }
        .pf-chip {
            display: inline-flex; align-items: center; gap: 0.35rem;
            padding: 0.3rem 0.6rem;
            font-size: 0.71rem; font-weight: 700; letter-spacing: 0.01em;
            background: var(--surface-2); color: var(--muted);
            border: 1px solid var(--line);
        }
        .pf-chip i { font-size: 0.92rem; line-height: 1; }
        .pf-chip.is-good { background: var(--teal-l); color: var(--teal); border-color: rgba(13,110,99,0.18); }

        .pf-drop { font-size: 0.76rem; color: var(--muted-2); margin-top: 0.7rem; }
        .pf-drop button {
            font: inherit; font-weight: 700; color: var(--danger);
            background: none; border: 0; padding: 0; cursor: pointer; text-decoration: underline;
        }

        .pf-section { margin-top: 2.9rem; }
        .pf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; }
        .pf-actions {
            display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
            margin-top: 1.75rem;
        }
        .pf-actions .note { font-size: 0.78rem; color: var(--muted-2); }

        /* A preference is a row you flip, not a field you fill. */
        .pf-toggle-row {
            display: flex; align-items: center; gap: 1.25rem;
            padding: 1.2rem 1.4rem;
            background: var(--surface); border: 1px solid var(--line);
        }
        .pf-toggle-copy { flex: 1; min-width: 0; }
        .pf-toggle-title { font-size: 0.93rem; font-weight: 700; }
        .pf-toggle-sub { font-size: 0.79rem; color: var(--muted-2); margin-top: 0.28rem; line-height: 1.55; }
        .pf-switch { position: relative; width: 48px; height: 27px; flex-shrink: 0; }
        .pf-switch input {
            position: absolute; inset: 0; width: 100%; height: 100%;
            margin: 0; opacity: 0; cursor: pointer; z-index: 1;
        }
        .pf-switch i {
            position: absolute; inset: 0; border-radius: 999px;
            background: var(--line-2); transition: background 0.18s;
        }
        .pf-switch i::after {
            content: ''; position: absolute; top: 3px; left: 3px;
            width: 21px; height: 21px; border-radius: 999px; background: #fff;
            box-shadow: 0 1px 3px rgba(20,11,18,0.25);
            transition: transform 0.18s;
        }
        .pf-switch input:checked + i { background: var(--teal); }
        .pf-switch input:checked + i::after { transform: translateX(21px); }
        .pf-switch input:focus-visible + i { box-shadow: 0 0 0 3px var(--primary-l); }

        @media (max-width: 720px) {
            .pf-grid { grid-template-columns: 1fr; }
            .pf-identity { gap: 1.2rem; padding: 1.4rem; }
            .pf-name { font-size: 1.4rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            .pf-switch i, .pf-switch i::after { transition: none; }
        }

        .flash-ok, .flash-err {
            display: flex; align-items: center; gap: 0.55rem;
            padding: 0.85rem 1.1rem; margin-bottom: 1.5rem;
            font-size: 0.86rem; font-weight: 600;
        }
        .flash-ok  { background: var(--teal-l);    color: var(--teal);    border: 1px solid rgba(13,110,99,0.2); }
        .flash-err { background: var(--primary-l); color: var(--primary); border: 1px solid rgba(225,29,99,0.2); }

        .loading, .importing { opacity: 0.6; pointer-events: none; }

        /* ══ RESPONSIVE ══════════════════════════════════ */
        @media (max-width: 1180px) {
            .stats-bar { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 1023px) {
            .rail {
                transform: translateX(-100%);
                transition: transform 0.26s cubic-bezier(0.4,0,0.2,1);
                box-shadow: 0 0 60px rgba(20,11,18,0.2);
            }
            .rail.is-open { transform: translateX(0); }
            .main { margin-left: 0; }
            .rail-toggle { display: inline-flex; }
            .topbar  { padding: 0 1.5rem; }
            .content { padding: 2.25rem 1.5rem 5rem; }
        }
        @media (max-width: 640px) {
            .topbar  { padding: 0 1.1rem; }
            .topbar-title { display: none; }
            .content { padding: 1.75rem 1.1rem 4.5rem; }
            .stats-bar { grid-template-columns: 1fr; gap: 1rem; margin-bottom: 2.25rem; }
            .stat-icon { margin-bottom: 1.1rem; }
            .page-head { margin-bottom: 2rem; }
            .wallet-hero { padding: 1.75rem 1.5rem 2rem; }
            .wallet-balance { font-size: 2.25rem; }
            .upcoming-item { grid-template-columns: 62px 1fr; padding: 1.1rem 1.15rem; }
            .upcoming-item > :last-child { display: none; }
            .tx-item, .notif-item, .bank-item { padding: 1.1rem 1.15rem; }
            .event-actions { flex-wrap: wrap; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition-duration: 0.001ms !important; animation-duration: 0.001ms !important; }
        }
    </style>
</head>
<body
    x-data="{
        navOpen: false,
        heading: @js($heading),
        editingBank: { id: null, bank_name: '', bank_code: '', account_number: '', account_name: '' },
        bulkUploadOpen: false
    }"
    {{--
        pageRouter.js sets aria-current on the new rail link before it fires
        route-changed, so the topbar title can just read it back off the DOM.
    --}}
    @route-changed.window="
        navOpen = false;
        heading = document.querySelector('a.nav-link[aria-current=\'page\'] span')?.textContent.trim() || heading
    "
>

<x-impersonation-banner />

<div id="route-progress" aria-hidden="true"></div>

    {{-- ── SCRIM (mobile only) ───────────────────────── --}}
    <div class="rail-scrim" x-show="navOpen" x-cloak x-transition.opacity
         @click="navOpen = false"></div>

    <div class="shell">

        {{-- ══ LEFT RAIL ═════════════════════════════════ --}}
        <aside class="rail" :class="{ 'is-open': navOpen }"
               @keydown.escape.window="navOpen = false">

            <div class="rail-head">
                <div class="rail-mark"><i class="mdi mdi-party-popper"></i></div>
                <a href="{{ url('/') }}" class="brand">CelebrateMi</a>
            </div>

            <nav class="rail-nav">
                @foreach ($navGroups as $groupLabel => $items)
                    <div class="nav-group">
                        <p class="nav-group-label eyebrow">{{ $groupLabel }}</p>
                        @foreach ($items as $item)
                            @php
                                // Activity swaps to a filled bell while anything is unread.
                                $icon  = ($item['badge'] ?? null) === 'unread' && $unreadCount > 0
                                    ? 'mdi-bell'
                                    : $item['icon'];
                                $count = ($item['badge'] ?? null) === 'unread' ? $unreadCount : 0;
                            @endphp

                            <a
                                href="{{ route($item['route']) }}"
                                data-nav
                                class="nav-link"
                                @if ($nav === $item['page']) aria-current="page" @endif
                            >
                                <i class="mdi {{ $icon }}"></i>
                                <span>{{ $item['label'] }}</span>
                                @if ($count > 0)
                                    <span class="nav-count">{{ $count }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>

            <div class="rail-foot">
                @if (auth()->user()->account_type === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="rail-admin">
                        <i class="mdi mdi-shield-crown-outline"></i> Admin console
                    </a>
                @endif

                <div class="user-menu" x-data="{ open: false }" @click.outside="open = false">
                    <button class="user-trigger" @click="open = !open">
                        <div class="user-avatar">
                            {{ strtoupper(substr(auth()->user()->first_name ?? auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <div style="min-width:0;flex:1">
                            <p class="user-name">{{ auth()->user()->first_name ?? auth()->user()->name }}</p>
                            <p class="user-role">{{ auth()->user()->account_type }} account</p>
                        </div>
                        <i class="mdi mdi-chevron-up" style="opacity:.35;font-size:1.1rem;flex-shrink:0"
                           :style="open ? 'transform:rotate(180deg)' : ''"></i>
                    </button>
                    <div class="user-dropdown" x-show="open" x-transition x-cloak>
                        <a href="{{ route('profile.edit') }}" class="dropdown-item">
                            <i class="mdi mdi-account-outline" style="margin-right:0.5rem;opacity:.6"></i>Profile settings
                        </a>
                        <a href="{{ url('/') }}" class="dropdown-item">
                            <i class="mdi mdi-home-outline" style="margin-right:0.5rem;opacity:.6"></i>Home page
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item danger">
                                <i class="mdi mdi-logout" style="margin-right:0.5rem"></i>Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        {{-- ══ MAIN ══════════════════════════════════════ --}}
        <div class="main">

            {{-- ── TOPBAR ───────────────────────────────── --}}
            <header class="topbar">
                <button class="rail-toggle" @click="navOpen = true" aria-label="Open menu">
                    <i class="mdi mdi-menu"></i>
                </button>
                <p class="topbar-title" x-text="heading">{{ $heading }}</p>

                <div class="topbar-right">
                    @if (auth()->user()->account_type === 'corporate')
                        <button class="btn-quiet" @click="bulkUploadOpen = true">
                            <i class="mdi mdi-upload"></i>
                            <span>Bulk upload</span>
                        </button>
                    @endif
                    <button class="btn-solid" x-on:click="$dispatch('open-modal', 'create-event')">
                        <i class="mdi mdi-plus"></i>
                        New celebration
                    </button>
                </div>
            </header>

            {{-- ── CONTENT ──────────────────────────────── --}}
            {{--
                Nothing page-specific lives out here. Each partial opens with its
                own heading and its own summary cards, so no page repeats another
                page's numbers.
            --}}
            <div class="content">

                {{-- ══ ROUTED PAGE ══════════════════════════ --}}
                {{-- Swapped in place by resources/js/modules/pageRouter.js. --}}
                <main id="view">
                    @include("dashboard.partials.$page")
                </main>
            </div>{{-- /content --}}
        </div>{{-- /main --}}
    </div>{{-- /shell --}}

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- MODAL: Create celebration                       --}}
    {{-- ═══════════════════════════════════════════════ --}}
    <x-modal name="create-event" maxWidth="2xl" focusable>
        <div class="p-6 relative">
            <button type="button" class="m-close" x-on:click="$dispatch('close')">
                <i class="mdi mdi-close"></i>
            </button>
            <h2 class="m-title">New celebration</h2>
            <p class="m-sub">Fill in the details — your page goes live instantly.</p>

            <div x-data="celebrationForm()" x-init="loggedIn = @js(auth()->check())" style="margin-top:1.75rem">
                <form @submit.prevent="nextStep">
                    <div style="display:flex;align-items:center;gap:0.85rem;margin-bottom:1.75rem">
                        <div class="step" :class="{ 'is-active': step >= 1 }">1</div>
                        <div style="flex:1;height:1px;background:var(--line)">
                            <div :style="step >= 2 ? 'width:100%' : 'width:0'"
                                 style="height:1px;background:var(--primary);transition:width 0.3s"></div>
                        </div>
                        <div class="step" :class="{ 'is-active': step >= 2 }">2</div>
                    </div>

                    <div x-show="step === 1" x-transition>
                        <div class="m-field">
                            <label class="m-label">Name of celebrant</label>
                            <input type="text" class="m-input" x-model="form.celebrantName" placeholder="e.g. Sandra">
                        </div>
                        <div class="m-field">
                            <span class="m-label" id="dash-type-label">What are you celebrating?</span>
                            <div class="occasion-grid" role="radiogroup" aria-labelledby="dash-type-label">
                                <template x-for="opt in eventTypes" :key="opt.value">
                                    <button type="button"
                                            class="occasion-tile"
                                            :class="form.eventType === opt.value && 'is-on'"
                                            role="radio"
                                            :aria-checked="form.eventType === opt.value ? 'true' : 'false'"
                                            @click="form.eventType = opt.value">
                                        <i class="mdi" :class="opt.icon" aria-hidden="true"></i>
                                        <span x-text="opt.label"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div class="m-field">
                            <label class="m-label">When is it?</label>
                            {{-- Two date boxes side by side read as one; each gets its own name. --}}
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem">
                                <div>
                                    <label for="dash-start" style="display:block;margin-bottom:0.3rem;font-size:0.8rem;font-weight:600;color:var(--muted)">
                                        <i class="mdi mdi-calendar-start" aria-hidden="true"></i> Starts on
                                    </label>
                                    <input id="dash-start" type="text" class="datepicker m-input" x-model="form.startDate" placeholder="Pick the start date">
                                </div>
                                <div>
                                    <label for="dash-end" style="display:block;margin-bottom:0.3rem;font-size:0.8rem;font-weight:600;color:var(--muted)">
                                        <i class="mdi mdi-calendar-end" aria-hidden="true"></i> Ends on <span style="font-weight:400">(optional)</span>
                                    </label>
                                    <input id="dash-end" type="text" class="datepicker m-input" x-model="form.endDate" placeholder="Pick the end date">
                                </div>
                            </div>
                            <p style="margin-top:0.4rem;font-size:0.78rem;color:var(--muted)">A one-day celebration only needs the start date.</p>
                        </div>
                        <div class="m-field">
                            <label class="m-label">Page title</label>
                            <input type="text" class="m-input" x-model="form.eventTitle" placeholder="Auto-generated from name &amp; type">
                        </div>
                        <button type="submit" class="btn-solid btn-block" style="margin-top:1.75rem">
                            Continue
                        </button>
                    </div>

                    <div x-show="step === 2" x-transition>
                        <p class="m-sub" style="margin-top:0;margin-bottom:1.25rem">Almost there — sign into your account to publish.</p>
                        <div class="m-field">
                            <label class="m-label">Email address</label>
                            <input type="email" class="m-input" x-model="auth.email" placeholder="you@example.com">
                        </div>
                        <div class="m-field">
                            <label class="m-label">Password</label>
                            <input type="password" class="m-input" x-model="auth.password" placeholder="••••••••">
                        </div>
                        <button type="button" class="btn-solid btn-block" style="margin-top:1.75rem" @click="submitForm">
                            Create my celebration page
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </x-modal>

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- MODAL: Request withdrawal                       --}}
    {{-- ═══════════════════════════════════════════════ --}}
    <x-modal name="request-withdrawal" maxWidth="lg" focusable>
        <div class="p-6 relative">
            <button type="button" class="m-close" x-on:click="$dispatch('close')">
                <i class="mdi mdi-close"></i>
            </button>
            <h2 class="m-title">Request withdrawal</h2>
            <p class="m-sub">
                @if ($hasLocalWallet)
                    Local wallet: <strong>{{ $currencySymbol }}{{ number_format($localDisplay, 2) }}</strong>
                    <br>Global USD wallet: <strong>${{ number_format($globalDisplay, 2) }}</strong>
                @else
                    Available: <strong>${{ number_format($globalDisplay, 2) }}</strong>
                @endif
            </p>

            <form method="POST" action="{{ route('withdrawals.store') }}" style="margin-top:1.75rem"
                  x-data="{ walletType: '{{ $hasLocalWallet ? 'local' : 'global' }}' }">
                @csrf

                @if ($hasLocalWallet)
                    <div class="m-field">
                        <label class="m-label">Select wallet</label>
                        <select name="wallet_type" class="m-input" x-model="walletType" required>
                            <option value="local">Local wallet ({{ $currencySymbol }}{{ number_format($localDisplay, 2) }})</option>
                            <option value="global">Global USD wallet (${{ number_format($globalDisplay, 2) }})</option>
                        </select>
                    </div>
                @else
                    {{-- USD checkout works here, so there is only the one wallet. --}}
                    <input type="hidden" name="wallet_type" value="global">
                @endif

                <div class="m-field">
                    <label class="m-label">
                        Amount to withdraw (<span x-text="walletType === 'local' ? '{{ $userCurrency }}' : 'USD'"></span>)
                    </label>
                    <input type="number" name="amount" class="m-input"
                           placeholder="e.g. 50" min="1" step="0.01"
                           value="{{ old('amount') }}" required>
                    <p class="m-hint">Minimum 1 <span x-text="walletType === 'local' ? '{{ $currencySymbol }}' : '$'"></span></p>
                </div>

                <div class="m-field">
                    <label class="m-label">Send to</label>
                    <select name="bank_account_id" class="m-input" required>
                        <option value="">Select bank account</option>
                        @foreach ($bankAccounts as $ba)
                            <option value="{{ $ba->id }}" {{ $ba->is_default ? 'selected' : '' }}>
                                {{ $ba->bank_name }} — {{ $ba->account_number }}{{ $ba->is_default ? ' (default)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-solid btn-block" style="margin-top:1.75rem">
                    <i class="mdi mdi-bank-transfer-out"></i> Submit request
                </button>
            </form>
        </div>
    </x-modal>

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- MODAL: Add bank account                         --}}
    {{-- ═══════════════════════════════════════════════ --}}
    <x-modal name="add-bank" maxWidth="lg" focusable>
        <div class="p-6 relative">
            <button type="button" class="m-close" x-on:click="$dispatch('close')">
                <i class="mdi mdi-close"></i>
            </button>
            <h2 class="m-title">Add bank account</h2>
            <p class="m-sub">Details must match your bank records exactly.</p>
            <form method="POST" action="{{ route('bank-accounts.store') }}" style="margin-top:1.75rem"
                  x-data="bankAccountForm()"
                  @open-modal.window="$event.detail === 'add-bank' && seed()"
                  @submit="status !== 'ok' && $event.preventDefault()">
                @csrf
                <div class="m-field">
                    <label class="m-label">Bank name</label>
                    <select name="bank_code" class="m-input" x-model="bankCode" @change="lookup()" required>
                        <option value="">Select your bank</option>
                        @foreach ($banks as $bank)
                            <option value="{{ $bank['code'] }}">{{ $bank['name'] }}</option>
                        @endforeach
                    </select>
                    @error('bank_code')<p class="m-error">{{ $message }}</p>@enderror
                </div>
                <div class="m-field">
                    <label class="m-label">Account number</label>
                    <input type="text" name="account_number" class="m-input"
                           x-model="accountNumber" @input="lookup()"
                           placeholder="10-digit NUBAN" maxlength="10" inputmode="numeric"
                           pattern="[0-9]{10}" value="{{ old('account_number') }}" required>
                    @error('account_number')<p class="m-error">{{ $message }}</p>@enderror
                </div>
                <div class="m-field">
                    <label class="m-label">Account holder name</label>
                    {{-- Read-only on purpose: this is whatever the bank says it
                         is, and the same lookup runs again server-side. --}}
                    <input type="text" name="account_name" class="m-input bank-name-field"
                           x-model="accountName" :class="status === 'ok' && 'is-verified'"
                           placeholder="Fetched from your bank" readonly required>
                    <p class="m-hint" x-show="status === 'idle'">
                        Pick your bank and enter the account number — we will fetch the name from your bank.
                    </p>
                    <p class="m-hint" x-show="status === 'loading'" x-cloak>
                        <i class="mdi mdi-loading mdi-spin"></i> Checking with your bank…
                    </p>
                    <p class="m-hint bank-ok" x-show="status === 'ok'" x-cloak>
                        <i class="mdi mdi-check-circle"></i> Confirmed by your bank.
                    </p>
                    <p class="m-error" x-show="status === 'error'" x-text="error" x-cloak></p>
                    @error('account_name')<p class="m-error">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn-solid btn-block" style="margin-top:1.75rem"
                        :disabled="status !== 'ok'">
                    <i class="mdi mdi-content-save-outline"></i> Save bank account
                </button>
            </form>
        </div>
    </x-modal>

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- MODAL: Edit bank account                        --}}
    {{-- ═══════════════════════════════════════════════ --}}
    <x-modal name="edit-bank" maxWidth="lg" focusable>
        <div class="p-6 relative">
            <button type="button" class="m-close" x-on:click="$dispatch('close')">
                <i class="mdi mdi-close"></i>
            </button>
            <h2 class="m-title">Edit bank account</h2>
            <p class="m-sub">Update your saved bank details.</p>

            <form method="POST" :action="'/bank-accounts/' + editingBank.id" style="margin-top:1.75rem"
                  x-data="bankAccountForm()"
                  @open-modal.window="$event.detail === 'edit-bank' && seed(editingBank)"
                  @submit="status !== 'ok' && $event.preventDefault()">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <div class="m-field">
                    <label class="m-label">Bank name</label>
                    <select name="bank_code" class="m-input" x-model="bankCode" @change="lookup()" required>
                        <option value="">Select your bank</option>
                        @foreach ($banks as $bank)
                            <option value="{{ $bank['code'] }}">{{ $bank['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="m-field">
                    <label class="m-label">Account number</label>
                    <input type="text" name="account_number" class="m-input"
                           x-model="accountNumber" @input="lookup()"
                           placeholder="10-digit NUBAN" maxlength="10" inputmode="numeric"
                           pattern="[0-9]{10}" required>
                </div>
                <div class="m-field">
                    <label class="m-label">Account holder name</label>
                    <input type="text" name="account_name" class="m-input bank-name-field"
                           x-model="accountName" :class="status === 'ok' && 'is-verified'"
                           placeholder="Fetched from your bank" readonly required>
                    <p class="m-hint" x-show="status === 'idle'">
                        Pick your bank and enter the account number — we will fetch the name from your bank.
                    </p>
                    <p class="m-hint" x-show="status === 'loading'" x-cloak>
                        <i class="mdi mdi-loading mdi-spin"></i> Checking with your bank…
                    </p>
                    <p class="m-hint bank-ok" x-show="status === 'ok'" x-cloak>
                        <i class="mdi mdi-check-circle"></i> Confirmed by your bank.
                    </p>
                    <p class="m-error" x-show="status === 'error'" x-text="error" x-cloak></p>
                </div>
                <button type="submit" class="btn-solid btn-block" style="margin-top:1.75rem"
                        :disabled="status !== 'ok'">
                    <i class="mdi mdi-content-save-outline"></i> Save changes
                </button>
            </form>
        </div>
    </x-modal>

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- MODAL: Fund Wallet                              --}}
    {{-- ═══════════════════════════════════════════════ --}}
    <x-modal name="fund-wallet" maxWidth="md" focusable>
        <div class="p-6 relative" x-data="walletFundForm()">
            <button type="button" class="m-close" x-on:click="$dispatch('close')">
                <i class="mdi mdi-close"></i>
            </button>
            <h2 class="m-title">Fund wallet</h2>
            <p class="m-sub">
                @if ($hasLocalWallet)
                    Local: <strong>{{ $currencySymbol }}{{ number_format($localDisplay, 2) }}</strong>
                    <br>Global: <strong>${{ number_format($globalDisplay, 2) }}</strong>
                @else
                    Balance: <strong>${{ number_format($globalDisplay, 2) }}</strong>
                @endif
            </p>

            <div x-show="error" x-cloak class="flash-err" style="margin-top:1.25rem;margin-bottom:0">
                <i class="mdi mdi-alert-circle"></i> <span x-text="error"></span>
            </div>

            <div style="margin-top:1.75rem" x-data="{ walletType: '{{ $hasLocalWallet ? 'local' : 'global' }}' }">
                @if ($hasLocalWallet)
                    <div class="m-field">
                        <label class="m-label">Select wallet to fund</label>
                        <select class="m-input" x-model="wallet_type" @change="walletType = $event.target.value" required>
                            <option value="local">Local wallet ({{ $userCurrency }})</option>
                            <option value="global">Global USD wallet (USD)</option>
                        </select>
                    </div>
                @endif

                <div class="m-field">
                    <label class="m-label">
                        Amount (<span x-text="walletType === 'local' ? '{{ $userCurrency }}' : 'USD'"></span>)
                    </label>
                    <input type="number" class="m-input" x-model="amount"
                           min="1" step="any" placeholder="e.g. 50" :disabled="loading">
                    <p class="m-hint">
                        Minimum 1 <span x-text="walletType === 'local' ? '{{ $currencySymbol }}' : '$'"></span>
                    </p>
                </div>
            </div>

            {{-- The wallet picked decides the gateway: local currency goes
                 through Paystack, USD through Stripe. Either can be paused
                 from Settings → Payments, independently. --}}
            @php
                $fundOpen = [
                    'local'  => \App\Support\PaymentGateways::canCheckout($userCurrency),
                    'global' => \App\Support\PaymentGateways::canCheckout('USD'),
                ];
                $fundDefault = $hasLocalWallet ? 'local' : 'global';
            @endphp
            <div x-show="!({{ Js::from($fundOpen) }})[wallet_type || '{{ $fundDefault }}']" x-cloak
                 class="flash-err" style="margin-top:1.25rem;margin-bottom:0">
                <i class="mdi mdi-pause-circle-outline"></i>
                <span>Card top-ups for this wallet are paused right now. Please try again a little later.</span>
            </div>

            <button class="btn-solid btn-block" style="margin-top:1.75rem"
                    @click="submit()"
                    :disabled="loading || !amount || !({{ Js::from($fundOpen) }})[wallet_type || '{{ $fundDefault }}']">
                <span x-show="!loading"><i class="mdi mdi-credit-card-outline"></i> Proceed to payment</span>
                <span x-show="loading" x-cloak><i class="mdi mdi-loading mdi-spin"></i> Redirecting…</span>
            </button>
        </div>
    </x-modal>

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- OVERLAY: Bulk Upload Celebrants (corporate)    --}}
    {{-- ═══════════════════════════════════════════════ --}}
    @if (auth()->user()->account_type === 'corporate')
    <div
        x-show="bulkUploadOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-data="bulkUploadPanel()"
        @keydown.escape.window="if(bulkUploadOpen) { bulkUploadOpen = false; reset(); }"
        style="position:fixed;inset:0;z-index:200;display:flex;flex-direction:column;background:var(--ink)"
    >
        {{-- Top bar --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 2rem;border-bottom:1px solid rgba(255,255,255,0.08);flex-shrink:0">
            <div style="display:flex;align-items:center;gap:1rem">
                <div style="width:38px;height:38px;background:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff">
                    <i class="mdi mdi-upload"></i>
                </div>
                <div>
                    <p style="font-family:'Outfit',sans-serif;font-weight:800;font-size:1.1rem;color:#fff;line-height:1.2">Bulk upload celebrants</p>
                    <p style="font-size:0.75rem;color:rgba(255,255,255,0.45);margin-top:0.15rem" x-text="step === 'preview' ? previewRows.length + ' rows parsed — review before importing' : 'Upload an Excel file (.xlsx / .xls)'"></p>
                </div>
            </div>
            <button @click="bulkUploadOpen = false; reset()"
                style="width:36px;height:36px;background:rgba(255,255,255,0.08);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.7);font-size:1.1rem;transition:background 0.15s"
                onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='rgba(255,255,255,0.08)'">
                <i class="mdi mdi-close"></i>
            </button>
        </div>

        {{-- Body --}}
        <div style="flex:1;display:flex;overflow:hidden">

            {{-- LEFT: Form panel --}}
            <div style="width:25%;min-width:300px;border-right:1px solid rgba(255,255,255,0.08);padding:2rem 1.75rem;overflow-y:auto;display:flex;flex-direction:column;gap:1.75rem">

                {{-- File drop zone --}}
                <div>
                    <p style="font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.12em;color:rgba(255,255,255,0.4);margin-bottom:0.85rem">Excel file</p>
                    <label
                        for="bu-file"
                        @dragover.prevent="dragging = true"
                        @dragleave="dragging = false"
                        @drop.prevent="dragging = false; handleFileDrop($event)"
                        :style="dragging ? 'border-color:var(--primary);background:rgba(225,29,99,0.12)' : ''"
                        style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.7rem;border:1px dashed rgba(255,255,255,0.18);padding:2.25rem 1rem;cursor:pointer;transition:all 0.2s;text-align:center"
                    >
                        <i class="mdi mdi-file-excel-outline" style="font-size:2.25rem;color:var(--primary)"></i>
                        <span style="font-size:0.82rem;font-weight:600;color:rgba(255,255,255,0.7)" x-text="fileName || 'Drop file here or click to browse'"></span>
                        <span style="font-size:0.72rem;color:rgba(255,255,255,0.35)">.xlsx or .xls only</span>
                    </label>
                    <input id="bu-file" type="file" accept=".xlsx,.xls" style="display:none" @change="handleFileSelect($event)">
                </div>

                {{-- Options --}}
                <div style="display:flex;flex-direction:column;gap:0.85rem">
                    <p style="font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.12em;color:rgba(255,255,255,0.4)">Options</p>
                    <label style="display:flex;align-items:center;gap:0.75rem;cursor:pointer">
                        <div style="position:relative;width:42px;height:24px;flex-shrink:0" @click="sendEmail = !sendEmail">
                            <div :style="sendEmail ? 'background:var(--primary)' : 'background:rgba(255,255,255,0.14)'"
                                 style="position:absolute;inset:0;border-radius:99px;transition:background 0.2s"></div>
                            <div :style="sendEmail ? 'transform:translateX(18px)' : 'transform:translateX(2px)'"
                                 style="position:absolute;top:2px;width:20px;height:20px;background:#fff;border-radius:50%;transition:transform 0.2s"></div>
                        </div>
                        <span style="font-size:0.85rem;color:rgba(255,255,255,0.7)">Send email summary when done</span>
                    </label>
                </div>

                {{-- Expected columns --}}
                <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);padding:1.15rem">
                    <p style="font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.12em;color:rgba(255,255,255,0.35);margin-bottom:0.75rem">Required columns</p>
                    <div style="display:flex;flex-direction:column;gap:0.4rem">
                        @foreach(['Organisation UUID','First Name','Last Name','Email','Celebration Type','Celebration Date'] as $col)
                            <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.78rem;color:rgba(255,255,255,0.55)">
                                <i class="mdi mdi-check-circle" style="color:#4fb3a6;font-size:0.85rem"></i> {{ $col }}
                            </div>
                        @endforeach
                        <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.78rem;color:rgba(255,255,255,0.35)">
                            <i class="mdi mdi-circle-outline" style="font-size:0.85rem"></i> Phone (optional)
                        </div>
                        <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.78rem;color:rgba(255,255,255,0.35)">
                            <i class="mdi mdi-circle-outline" style="font-size:0.85rem"></i> Photo filename (optional)
                        </div>
                    </div>
                </div>

                {{-- Error message --}}
                <div x-show="error" x-cloak style="background:rgba(225,29,99,0.15);border:1px solid rgba(225,29,99,0.3);padding:0.9rem;font-size:0.82rem;color:#f8a8c4;display:flex;align-items:flex-start;gap:0.5rem">
                    <i class="mdi mdi-alert-circle" style="margin-top:0.1rem;flex-shrink:0"></i>
                    <span x-text="error"></span>
                </div>

                {{-- Action buttons --}}
                <div style="margin-top:auto;display:flex;flex-direction:column;gap:0.75rem">
                    <button
                        x-show="step === 'upload'"
                        @click="loadPreview()"
                        :disabled="!file || loading"
                        style="width:100%;padding:0.85rem;border:none;border-radius:999px;font-family:inherit;font-size:0.88rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.5rem;transition:all 0.15s"
                        :style="!file || loading ? 'background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.35);cursor:not-allowed' : 'background:var(--primary);color:#fff'"
                    >
                        <i class="mdi" :class="loading ? 'mdi-loading mdi-spin' : 'mdi-eye-outline'"></i>
                        <span x-text="loading ? 'Parsing…' : 'Preview data'"></span>
                    </button>

                    <template x-if="step === 'preview'">
                        <div style="display:flex;flex-direction:column;gap:0.6rem">
                            <button
                                @click="confirmImport()"
                                :disabled="importing || okCount === 0"
                                style="width:100%;padding:0.85rem;border:none;border-radius:999px;font-family:inherit;font-size:0.88rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.5rem;transition:all 0.15s"
                                :style="importing || okCount === 0 ? 'background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.35);cursor:not-allowed' : 'background:var(--teal);color:#fff'"
                            >
                                <i class="mdi" :class="importing ? 'mdi-loading mdi-spin' : 'mdi-check-bold'"></i>
                                <span x-text="importing ? 'Importing…' : 'Confirm import (' + okCount + ' valid rows)'"></span>
                            </button>
                            <button
                                @click="step = 'upload'; previewRows = []; previewHeaders = []"
                                style="width:100%;padding:0.75rem;border:1px solid rgba(255,255,255,0.15);border-radius:999px;background:none;color:rgba(255,255,255,0.6);font-family:inherit;font-size:0.85rem;font-weight:600;cursor:pointer;transition:all 0.15s"
                                onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='none'">
                                <i class="mdi mdi-arrow-left"></i> Change file
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- RIGHT: Preview panel --}}
            <div style="flex:1;overflow:hidden;display:flex;flex-direction:column">

                {{-- Upload placeholder --}}
                <div x-show="step === 'upload'" style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:1.25rem;color:rgba(255,255,255,0.2)">
                    <i class="mdi mdi-table-large" style="font-size:4.5rem"></i>
                    <p style="font-size:1rem;font-weight:600">Preview will appear here</p>
                    <p style="font-size:0.85rem">Select an Excel file and click "Preview data"</p>
                </div>

                {{-- Preview table --}}
                <div x-show="step === 'preview'" x-cloak style="flex:1;overflow:auto;padding:1.75rem 2rem">
                    {{-- Summary chips --}}
                    <div style="display:flex;gap:0.75rem;margin-bottom:1.5rem;flex-wrap:wrap">
                        <div style="display:flex;align-items:center;gap:0.5rem;background:rgba(13,110,99,0.2);border:1px solid rgba(13,110,99,0.4);border-radius:99px;padding:0.35rem 0.9rem;font-size:0.78rem;font-weight:700;color:#4fb3a6">
                            <i class="mdi mdi-check-circle"></i>
                            <span x-text="okCount + ' valid'"></span>
                        </div>
                        <div x-show="errCount > 0" style="display:flex;align-items:center;gap:0.5rem;background:rgba(225,29,99,0.15);border:1px solid rgba(225,29,99,0.3);border-radius:99px;padding:0.35rem 0.9rem;font-size:0.78rem;font-weight:700;color:#f8a8c4">
                            <i class="mdi mdi-alert-circle"></i>
                            <span x-text="errCount + ' with errors (will be skipped)'"></span>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.5rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:99px;padding:0.35rem 0.9rem;font-size:0.78rem;font-weight:600;color:rgba(255,255,255,0.5)">
                            <i class="mdi mdi-table-row"></i>
                            <span x-text="previewRows.length + ' total rows'"></span>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div style="border:1px solid rgba(255,255,255,0.08);overflow:hidden">
                        <table style="width:100%;border-collapse:collapse;font-size:0.8rem">
                            <thead>
                                <tr style="background:rgba(255,255,255,0.06)">
                                    <th style="padding:0.75rem 1rem;text-align:left;font-weight:800;color:rgba(255,255,255,0.4);font-size:0.68rem;text-transform:uppercase;letter-spacing:0.08em;white-space:nowrap;border-bottom:1px solid rgba(255,255,255,0.08)">#</th>
                                    <th style="padding:0.75rem 1rem;text-align:left;font-weight:800;color:rgba(255,255,255,0.4);font-size:0.68rem;text-transform:uppercase;letter-spacing:0.08em;white-space:nowrap;border-bottom:1px solid rgba(255,255,255,0.08)">Status</th>
                                    <template x-for="h in previewHeaders" :key="h">
                                        <th x-text="h" style="padding:0.75rem 1rem;text-align:left;font-weight:800;color:rgba(255,255,255,0.4);font-size:0.68rem;text-transform:uppercase;letter-spacing:0.08em;white-space:nowrap;border-bottom:1px solid rgba(255,255,255,0.08)"></th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, idx) in previewRows" :key="idx">
                                    <tr :style="row._status === 'error' ? 'background:rgba(225,29,99,0.07)' : (idx % 2 === 0 ? '' : 'background:rgba(255,255,255,0.02)')"
                                        style="border-bottom:1px solid rgba(255,255,255,0.05);transition:background 0.1s">
                                        <td x-text="row._row" style="padding:0.7rem 1rem;color:rgba(255,255,255,0.3);white-space:nowrap"></td>
                                        <td style="padding:0.7rem 1rem;white-space:nowrap">
                                            <template x-if="row._status === 'ok'">
                                                <span style="display:inline-flex;align-items:center;gap:0.3rem;font-size:0.7rem;font-weight:700;color:#4fb3a6;background:rgba(13,110,99,0.2);border:1px solid rgba(13,110,99,0.35);border-radius:99px;padding:0.15rem 0.6rem">
                                                    <i class="mdi mdi-check"></i> Valid
                                                </span>
                                            </template>
                                            <template x-if="row._status === 'error'">
                                                <span :title="'Missing: ' + row._errors.join(', ')" style="display:inline-flex;align-items:center;gap:0.3rem;font-size:0.7rem;font-weight:700;color:#f8a8c4;background:rgba(225,29,99,0.15);border:1px solid rgba(225,29,99,0.28);border-radius:99px;padding:0.15rem 0.6rem;cursor:help">
                                                    <i class="mdi mdi-alert"></i> Error
                                                </span>
                                            </template>
                                        </td>
                                        <template x-for="h in previewHeaders" :key="h">
                                            <td x-text="row[h] ?? '—'" style="padding:0.7rem 1rem;color:rgba(255,255,255,0.7);white-space:nowrap;max-width:200px;overflow:hidden;text-overflow:ellipsis"></td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Success state --}}
                <div x-show="step === 'done'" x-cloak style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:1.25rem">
                    <div style="width:80px;height:80px;border-radius:50%;background:rgba(13,110,99,0.25);display:flex;align-items:center;justify-content:center">
                        <i class="mdi mdi-check-bold" style="font-size:2.4rem;color:#4fb3a6"></i>
                    </div>
                    <p style="font-family:'Outfit',sans-serif;font-weight:800;font-size:1.45rem;color:#fff" x-text="doneMsg"></p>
                    <button @click="bulkUploadOpen = false; reset()"
                        style="padding:0.75rem 2rem;border-radius:99px;border:none;background:var(--primary);color:#fff;font-family:inherit;font-size:0.9rem;font-weight:700;cursor:pointer">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
    function celebrationForm() {
        return {
            step: 1,
            loggedIn: false,
            /* Occasion picker — rendered as icon tiles, not a dropdown. */
            eventTypes: [
                { value: 'birthday',    label: 'Birthday',    icon: 'mdi-cake-variant'  },
                { value: 'wedding',     label: 'Wedding',     icon: 'mdi-ring'          },
                { value: 'graduation',  label: 'Graduation',  icon: 'mdi-school'        },
                { value: 'anniversary', label: 'Anniversary', icon: 'mdi-heart'         },
                { value: 'baby_shower', label: 'Baby Shower', icon: 'mdi-baby-carriage' },
                { value: 'other',       label: 'Other',       icon: 'mdi-party-popper'  },
            ],
            form: { celebrantName: '', eventType: '', startDate: '', endDate: '', eventTitle: '' },
            auth: { email: '', password: '' },
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
                return {
                    birthday: 'Birthday', wedding: 'Wedding', graduation: 'Graduation',
                    anniversary: 'Anniversary', baby_shower: 'Baby Shower', other: 'Celebration'
                }[type] || 'Celebration';
            },
            nextStep() {
                if (!this.form.celebrantName) { alert("Please enter the celebrant's name."); return; }
                if (this.loggedIn) { this.submitForm(); return; }
                this.step = 2;
            },
            async submitForm() {
                const res  = await fetch('/create-celebration', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ ...this.form, ...this.auth })
                });
                const data = await res.json();
                if (data.redirect) window.location.href = data.redirect;
            }
        }
    }

    /**
     * Add / edit payout account.
     *
     * The account holder name is never typed — pick a bank, type ten digits,
     * and we ask the bank who owns it. Until that comes back the save button
     * stays disabled, and the server runs the same lookup again before it
     * writes anything, so a hand-crafted POST gets the same answer.
     */
    function bankAccountForm() {
        return {
            bankCode: '',
            accountNumber: '',
            accountName: '',
            status: 'idle',   // idle | loading | ok | error
            error: '',
            timer: null,
            seq: 0,           // drops responses that arrive after a newer request

            /** Reopening the modal starts clean — or on the account being edited. */
            seed(account) {
                this.bankCode      = account?.bank_code || '';
                this.accountNumber = account?.account_number || '';
                this.accountName   = '';
                this.status        = 'idle';
                this.error         = '';
                this.lookup();
            },

            lookup() {
                clearTimeout(this.timer);
                this.seq++;
                this.accountName = '';
                this.error       = '';

                this.accountNumber = (this.accountNumber || '').replace(/[^0-9]/g, '').slice(0, 10);

                if (!this.bankCode || this.accountNumber.length !== 10) {
                    this.status = 'idle';
                    return;
                }

                this.status = 'loading';
                this.timer  = setTimeout(() => this.resolve(), 350);
            },

            async resolve() {
                const attempt = this.seq;

                try {
                    const res = await fetch('{{ route('bank-accounts.resolve') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            bank_code: this.bankCode,
                            account_number: this.accountNumber
                        })
                    });

                    const data = await res.json().catch(() => ({}));
                    if (attempt !== this.seq) return;

                    if (!res.ok) {
                        this.status = 'error';
                        this.error  = data.errors
                            ? Object.values(data.errors)[0][0]
                            : (data.message || 'We could not confirm this account.');
                        return;
                    }

                    this.accountName = data.account_name;
                    this.status      = 'ok';
                } catch (e) {
                    if (attempt !== this.seq) return;
                    this.status = 'error';
                    this.error  = 'Could not reach us just then — check your connection and try again.';
                }
            }
        }
    }

    function bulkUploadPanel() {
        return {
            step: 'upload', // 'upload' | 'preview' | 'done'
            file: null,
            fileName: '',
            dragging: false,
            loading: false,
            importing: false,
            sendEmail: false,
            previewRows: [],
            previewHeaders: [],
            error: '',
            doneMsg: '',

            get okCount()  { return this.previewRows.filter(r => r._status === 'ok').length; },
            get errCount() { return this.previewRows.filter(r => r._status === 'error').length; },

            handleFileSelect(e) {
                const f = e.target.files[0];
                if (f) { this.file = f; this.fileName = f.name; this.error = ''; }
            },
            handleFileDrop(e) {
                const f = e.dataTransfer.files[0];
                if (f) { this.file = f; this.fileName = f.name; this.error = ''; }
            },

            async loadPreview() {
                if (!this.file) return;
                this.loading = true;
                this.error   = '';
                const fd = new FormData();
                fd.append('excel_file', this.file);
                fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                try {
                    const res  = await fetch('{{ route("celebrant.bulkUpload.preview") }}', { method: 'POST', body: fd });
                    const data = await res.json();
                    if (data.error) { this.error = data.error; return; }
                    this.previewHeaders = (data.headers || []).filter(h => !['_row','_status','_errors'].includes(h));
                    this.previewRows    = data.rows || [];
                    this.step           = 'preview';
                } catch (e) {
                    this.error = 'Failed to parse file. Please check the format and try again.';
                } finally {
                    this.loading = false;
                }
            },

            async confirmImport() {
                if (this.okCount === 0) return;
                this.importing = true;
                this.error     = '';
                const fd = new FormData();
                fd.append('excel_file', this.file);
                fd.append('send_email', this.sendEmail ? '1' : '0');
                fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                try {
                    const res  = await fetch('{{ route("celebrant.bulkUpload") }}', { method: 'POST', body: fd });
                    if (res.redirected || res.ok) {
                        // Grab flash message from redirect or default
                        this.doneMsg = this.okCount + ' celebrant' + (this.okCount === 1 ? '' : 's') + ' imported successfully!';
                        this.step    = 'done';
                    } else {
                        this.error = 'Import failed. Please try again.';
                    }
                } catch (e) {
                    this.error = 'Network error during import.';
                } finally {
                    this.importing = false;
                }
            },

            reset() {
                this.step = 'upload'; this.file = null; this.fileName = '';
                this.previewRows = []; this.previewHeaders = [];
                this.error = ''; this.loading = false; this.importing = false;
            },
        }
    }

    function walletFundForm() {
        return {
            amount: '',
            // Only offered where a local wallet exists; USD users fund the one wallet.
            wallet_type: '{{ $hasLocalWallet ? 'local' : 'global' }}',
            loading: false,
            error: '',
            async submit() {
                const amt = parseFloat(this.amount);
                if (!amt || amt <= 0) { this.error = 'Please enter a valid amount.'; return; }
                this.loading = true;
                this.error = '';
                try {
                    const res = await fetch('{{ route("wallet.fund") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ amount: amt, wallet_type: this.wallet_type }),
                    });
                    const data = await res.json();
                    if (data.success && data.authorization_url) {
                        window.location.href = data.authorization_url;
                    } else {
                        this.error = data.message || 'Something went wrong. Please try again.';
                        this.loading = false;
                    }
                } catch (e) {
                    this.error = 'Network error. Please try again.';
                    this.loading = false;
                }
            },
        }
    }
    </script>

</body>
</html>
