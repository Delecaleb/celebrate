<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard — CelebrateMi</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg:       #f6f2ee;
            --surface:  #ffffff;
            --dark:     #0e0b09;
            --muted:    #7a6f68;
            --accent:   #e04e12;
            --accent-d: #ba3e0d;
            --accent-lt:#fff2ec;
            --border:   #e5ddd5;
            --radius:   22px;
            --radius-sm:14px;
            --shadow:   0 4px 24px rgba(0,0,0,0.07);
            --shadow-lg:0 12px 48px rgba(0,0,0,0.12);

            /* palette */
            --purple: #7c3aed;
            --purple-lt:#f5f3ff;
            --blue:   #0284c7;
            --blue-lt:#e0f2fe;
            --green:  #059669;
            --green-lt:#d1fae5;
            --amber:  #d97706;
            --amber-lt:#fef3c7;
            --rose:   #e11d48;
            --rose-lt:#fff1f2;
        }

        [x-cloak] { display: none !important; }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: var(--bg);
            color: var(--dark);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* ── NAV ─────────────────────────────────────── */
        .topnav {
            position: sticky; top: 0; z-index: 50;
            background: rgba(246,242,238,0.88);
            backdrop-filter: blur(20px) saturate(1.6);
            border-bottom: 1px solid rgba(229,221,213,0.6);
        }
        .topnav-inner {
            max-width: 1280px; margin: 0 auto;
            padding: 0 2rem; height: 66px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .brand {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 1.45rem; text-decoration: none;
            background: linear-gradient(135deg, var(--accent) 0%, #f97316 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .nav-right { display: flex; align-items: center; gap: 0.75rem; }
        .btn-nav-create {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.55rem 1.2rem;
            background: linear-gradient(135deg, var(--accent) 0%, #f97316 100%);
            color: #fff; font-size: 0.85rem; font-weight: 700; font-family: inherit;
            border: none; border-radius: 99px; cursor: pointer;
            box-shadow: 0 4px 16px rgba(224,78,18,0.35);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-nav-create:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(224,78,18,0.45); }

        .user-trigger {
            display: flex; align-items: center; gap: 0.5rem;
            padding: 0.35rem 0.75rem 0.35rem 0.35rem;
            border: 1.5px solid var(--border); border-radius: 99px;
            background: var(--surface); font-size: 0.85rem; font-weight: 600;
            color: var(--dark); cursor: pointer; font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .user-trigger:hover { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-lt); }
        .user-avatar {
            width: 30px; height: 30px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent) 0%, #f97316 100%);
            color: #fff; font-size: 0.72rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .user-menu { position: relative; }
        .user-dropdown {
            position: absolute; top: calc(100% + 10px); right: 0;
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius-sm); box-shadow: var(--shadow-lg);
            min-width: 190px; overflow: hidden; z-index: 50;
        }
        .dropdown-item {
            display: block; padding: 0.7rem 1.1rem;
            font-size: 0.85rem; font-weight: 500; color: var(--dark); text-decoration: none;
            transition: background 0.12s; font-family: inherit;
        }
        .dropdown-item:hover { background: var(--bg); }
        .dropdown-item.danger { color: var(--rose); }
        .dropdown-item.danger:hover { background: var(--rose-lt); }
        .dropdown-divider { height: 1px; background: var(--border); }

        /* ── PAGE ─────────────────────────────────────── */
        .page { max-width: 1280px; margin: 0 auto; padding: 2.5rem 2rem 7rem; }

        /* ── HERO HEADER ──────────────────────────────── */
        .page-hero {
            position: relative; overflow: hidden;
            background: linear-gradient(135deg, #fff8f5 0%, #fef3ff 50%, #f0f9ff 100%);
            border: 1px solid var(--border); border-radius: var(--radius);
            padding: 2.5rem 3rem; margin-bottom: 2rem;
            display: flex; align-items: center; justify-content: space-between; gap: 2rem; flex-wrap: wrap;
        }
        .page-hero::before {
            content: ''; position: absolute; top: -80px; right: -60px;
            width: 340px; height: 340px; border-radius: 50%;
            background: radial-gradient(circle, rgba(224,78,18,0.12) 0%, transparent 70%);
            pointer-events: none;
        }
        .page-hero::after {
            content: ''; position: absolute; bottom: -60px; left: 20%;
            width: 260px; height: 260px; border-radius: 50%;
            background: radial-gradient(circle, rgba(124,58,237,0.08) 0%, transparent 70%);
            pointer-events: none;
        }
        .greeting-tag {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: var(--accent-lt); color: var(--accent);
            font-size: 0.78rem; font-weight: 700; letter-spacing: 0.04em;
            padding: 0.3rem 0.85rem; border-radius: 99px; margin-bottom: 0.75rem;
        }
        .page-hero h1 {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 2.6rem; letter-spacing: -0.03em; line-height: 1.08; position: relative; z-index: 1;
        }
        .page-hero h1 span {
            background: linear-gradient(135deg, var(--accent) 0%, var(--purple) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .hero-sub { font-size: 0.9rem; color: var(--muted); margin-top: 0.5rem; position: relative; z-index: 1; }
        .btn-create-hero {
            position: relative; z-index: 1;
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.9rem 1.85rem;
            background: linear-gradient(135deg, var(--accent) 0%, #f97316 100%);
            color: #fff; font-size: 0.95rem; font-weight: 700; font-family: inherit;
            border: none; border-radius: 99px; cursor: pointer; white-space: nowrap;
            box-shadow: 0 6px 24px rgba(224,78,18,0.38);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-create-hero:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(224,78,18,0.48); }

        /* ── STATS BAR ────────────────────────────────── */
        .stats-bar {
            display: grid; grid-template-columns: repeat(4,1fr);
            gap: 1rem; margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 1.5rem 1.6rem;
            display: flex; align-items: flex-start; gap: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
        .stat-icon {
            width: 46px; height: 46px; border-radius: 14px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
        }
        .stat-icon.orange { background: var(--accent-lt); color: var(--accent); }
        .stat-icon.purple { background: var(--purple-lt); color: var(--purple); }
        .stat-icon.blue   { background: var(--blue-lt);   color: var(--blue); }
        .stat-icon.green  { background: var(--green-lt);  color: var(--green); }
        .stat-body { min-width: 0; }
        .stat-label {
            font-size: 0.73rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.07em; color: var(--muted); margin-bottom: 0.45rem;
        }
        .stat-value {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 1.9rem; line-height: 1; letter-spacing: -0.02em;
        }
        .stat-sub { font-size: 0.74rem; color: var(--muted); margin-top: 0.3rem; }

        /* ── TABS ─────────────────────────────────────── */
        .tab-wrap { margin-bottom: 2.5rem; }
        .tab-bar {
            display: flex; gap: 0.35rem; overflow-x: auto;
            -webkit-overflow-scrolling: touch; scrollbar-width: none;
            padding: 0.35rem; background: var(--surface);
            border: 1px solid var(--border); border-radius: 99px;
            width: fit-content; max-width: 100%;
        }
        .tab-bar::-webkit-scrollbar { display: none; }
        .tab-btn {
            display: inline-flex; align-items: center; gap: 0.42rem;
            padding: 0.65rem 1.25rem;
            background: none; border: none;
            font-size: 0.875rem; font-weight: 600; color: var(--muted);
            cursor: pointer; white-space: nowrap; font-family: inherit;
            border-radius: 99px; transition: all 0.18s;
        }
        .tab-btn:hover { color: var(--dark); background: var(--bg); }
        .tab-btn.is-active {
            background: linear-gradient(135deg, var(--accent) 0%, #f97316 100%);
            color: #fff; box-shadow: 0 4px 14px rgba(224,78,18,0.35);
        }
        .tab-badge {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 18px; height: 18px; border-radius: 99px;
            background: #fff; color: var(--accent);
            font-size: 0.65rem; font-weight: 800; padding: 0 4px;
        }
        .tab-btn:not(.is-active) .tab-badge {
            background: var(--accent); color: #fff;
        }

        /* ── PANEL HEADER ─────────────────────────────── */
        .tab-panel-header {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; flex-wrap: wrap; margin-bottom: 1.75rem;
        }
        .panel-title {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 1.65rem; letter-spacing: -0.025em;
        }
        .panel-subtitle { font-size: 0.85rem; color: var(--muted); margin-top: 0.2rem; }

        /* ── ACTION BUTTONS ───────────────────────────── */
        .btn-create-lg {
            display: inline-flex; align-items: center; gap: 0.45rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, var(--accent) 0%, #f97316 100%);
            color: #fff; font-size: 0.88rem; font-weight: 700; font-family: inherit;
            border: none; border-radius: 99px; cursor: pointer; white-space: nowrap;
            box-shadow: 0 4px 16px rgba(224,78,18,0.32);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-create-lg:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(224,78,18,0.42); }

        /* ── EVENTS GRID ──────────────────────────────── */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.25rem;
        }
        .event-add-card {
            border: 2px dashed var(--border); border-radius: var(--radius);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 0.65rem;
            cursor: pointer; min-height: 220px; font-family: inherit; color: var(--muted);
            background: none; transition: all 0.2s;
        }
        .event-add-card:hover {
            border-color: var(--accent); background: var(--accent-lt);
            color: var(--accent); transform: translateY(-2px);
        }
        .event-add-card .add-icon { font-size: 2rem; line-height: 1; }
        .event-add-card .add-label { font-size: 0.9rem; font-weight: 700; }

        .event-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden; display: flex; flex-direction: column;
            transition: transform 0.22s, box-shadow 0.22s;
        }
        .event-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
        .event-cover { position: relative; height: 192px; overflow: hidden; flex-shrink: 0; }
        .event-cover img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.3s; }
        .event-card:hover .event-cover img { transform: scale(1.04); }
        .event-cover-gradient { width: 100%; height: 100%; }
        .status-badge {
            position: absolute; top: 0.85rem; right: 0.85rem;
            padding: 0.28rem 0.75rem; border-radius: 99px;
            font-size: 0.68rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase;
            backdrop-filter: blur(8px);
        }
        .badge-live   { background: rgba(16,185,129,0.15); color: #059669; border: 1px solid rgba(16,185,129,0.3); }
        .badge-draft  { background: rgba(217,119,6,0.12);  color: #b45309; border: 1px solid rgba(217,119,6,0.25); }
        .badge-closed { background: rgba(100,116,139,0.12); color: #475569; border: 1px solid rgba(100,116,139,0.2); }
        .event-type-icon {
            position: absolute; bottom: 0.75rem; left: 0.85rem;
            width: 34px; height: 34px; border-radius: 10px;
            background: rgba(255,255,255,0.9); backdrop-filter: blur(6px);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.05rem; color: var(--dark);
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        }
        .event-body { padding: 1.35rem; flex: 1; display: flex; flex-direction: column; }
        .event-type-tag {
            font-size: 0.73rem; font-weight: 700; color: var(--muted); margin-bottom: 0.4rem;
            display: flex; align-items: center; gap: 0.35rem; text-transform: uppercase; letter-spacing: 0.04em;
        }
        .event-title {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 1.2rem; line-height: 1.28; letter-spacing: -0.01em; margin-bottom: 0.4rem;
        }
        .event-date { font-size: 0.78rem; color: var(--muted); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.3rem; }
        .event-stats {
            display: flex; gap: 0.75rem; padding: 0.75rem 0;
            border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
            margin-bottom: 1rem;
        }
        .event-stat { display: flex; align-items: center; gap: 0.3rem; font-size: 0.78rem; color: var(--muted); }
        .event-stat strong { color: var(--dark); font-weight: 700; }
        .event-actions { display: flex; align-items: center; gap: 0.5rem; margin-top: auto; }
        .action-btn {
            flex: 1; padding: 0.58rem 0;
            background: var(--bg); border: 1px solid var(--border); border-radius: 10px;
            font-size: 0.82rem; font-weight: 700; color: var(--dark);
            text-decoration: none; text-align: center; cursor: pointer;
            font-family: inherit; transition: all 0.15s; display: block;
        }
        .action-btn:hover { background: var(--dark); color: #fff; border-color: var(--dark); }
        .action-delete {
            padding: 0.58rem 0.72rem;
            background: transparent; border: 1px solid var(--border); border-radius: 10px;
            font-size: 1rem; color: var(--rose); cursor: pointer;
            transition: all 0.15s; font-family: inherit; line-height: 1;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .action-delete:hover { background: var(--rose-lt); border-color: #fca5a5; }

        /* ── EMPTY STATE ──────────────────────────────── */
        .empty-state {
            background: var(--surface); border: 2px dashed var(--border);
            border-radius: var(--radius); padding: 5rem 2rem; text-align: center;
            grid-column: 1 / -1;
        }
        .empty-icon { font-size: 3rem; display: block; margin-bottom: 1.25rem; color: var(--border); }
        .empty-state h3 {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 1.75rem; letter-spacing: -0.02em; margin-bottom: 0.65rem;
        }
        .empty-state p { font-size: 0.95rem; color: var(--muted); max-width: 360px; margin: 0 auto 2rem; line-height: 1.7; }
        .btn-empty {
            display: inline-flex; align-items: center; gap: 0.45rem;
            padding: 0.88rem 2rem;
            background: linear-gradient(135deg, var(--accent) 0%, #f97316 100%);
            color: #fff; font-size: 0.92rem; font-weight: 700; font-family: inherit;
            border: none; border-radius: 99px; cursor: pointer;
            box-shadow: 0 4px 16px rgba(224,78,18,0.32);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-empty:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(224,78,18,0.42); }

        /* ── UPCOMING ─────────────────────────────────── */
        .upcoming-list {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden;
        }
        .upcoming-item {
            display: grid; grid-template-columns: 72px 1fr auto;
            gap: 1.25rem; align-items: center; padding: 1.3rem 1.75rem;
            border-bottom: 1px solid var(--border); transition: background 0.12s;
        }
        .upcoming-item:last-child { border-bottom: none; }
        .upcoming-item:hover { background: var(--bg); }
        .date-box {
            background: linear-gradient(135deg, var(--accent) 0%, #f97316 100%);
            color: #fff; border-radius: 14px;
            text-align: center; padding: 0.6rem 0.5rem; flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(224,78,18,0.3);
        }
        .date-day { font-family: 'DM Serif Display', Georgia, serif; font-size: 1.75rem; line-height: 1; display: block; }
        .date-month { font-size: 0.65rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; opacity: 0.85; display: block; }
        .upcoming-info-title { font-size: 0.95rem; font-weight: 700; margin-bottom: 0.25rem; }
        .upcoming-info-meta  { font-size: 0.8rem; color: var(--muted); }
        .upcoming-countdown  { font-size: 0.8rem; color: var(--accent); font-weight: 700; white-space: nowrap; margin-bottom: 0.4rem; text-align: right; }

        /* ── NOTIFICATIONS ────────────────────────────── */
        .notif-list {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden;
        }
        .notif-item {
            display: flex; gap: 1rem; align-items: flex-start;
            padding: 1.1rem 1.75rem; border-bottom: 1px solid var(--border); transition: background 0.12s;
        }
        .notif-item:last-child { border-bottom: none; }
        .notif-item.unread { background: #fffaf7; }
        .notif-item:hover  { background: var(--bg); }
        .notif-indicator-wrap { position: relative; flex-shrink: 0; }
        .notif-indicator {
            width: 40px; height: 40px; border-radius: 12px;
            background: var(--bg); border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.05rem; color: var(--muted); margin-top: 2px;
        }
        .notif-item.unread .notif-indicator {
            background: var(--accent-lt); border-color: rgba(224,78,18,0.25); color: var(--accent);
        }
        .notif-dot {
            position: absolute; top: -2px; right: -2px;
            width: 10px; height: 10px; border-radius: 50%;
            background: var(--accent); border: 2px solid var(--surface);
        }
        .notif-content { flex: 1; min-width: 0; }
        .notif-title { font-size: 0.875rem; font-weight: 700; color: var(--dark); margin-bottom: 0.2rem; }
        .notif-msg   { font-size: 0.82rem; color: var(--muted); line-height: 1.55; }
        .notif-time  { font-size: 0.73rem; color: var(--muted); margin-top: 0.35rem; opacity: 0.65; }

        /* ── WALLET ───────────────────────────────────── */
        .wallet-hero {
            background: linear-gradient(135deg, #1a0533 0%, #3b0764 40%, #581c87 100%);
            border-radius: var(--radius); padding: 2.5rem 2.75rem; color: #fff;
            margin-bottom: 1.5rem; position: relative; overflow: hidden;
        }
        .wallet-hero::before {
            content: ''; position: absolute; top: -70px; right: -70px;
            width: 260px; height: 260px; border-radius: 50%;
            background: radial-gradient(circle, rgba(167,139,250,0.3) 0%, transparent 70%);
        }
        .wallet-hero::after {
            content: ''; position: absolute; bottom: -50px; left: 30%;
            width: 200px; height: 200px; border-radius: 50%;
            background: radial-gradient(circle, rgba(224,78,18,0.2) 0%, transparent 70%);
        }
        .wallet-chip {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2);
            border-radius: 99px; padding: 0.3rem 0.85rem;
            font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;
            margin-bottom: 1.1rem; position: relative; z-index: 1;
        }
        .wallet-label {
            font-size: 0.73rem; font-weight: 700; letter-spacing: 0.09em;
            text-transform: uppercase; opacity: 0.6; margin-bottom: 0.5rem; position: relative; z-index: 1;
        }
        .wallet-balance {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 3.5rem; line-height: 1; letter-spacing: -0.03em;
            margin-bottom: 1.75rem; position: relative; z-index: 1;
        }
        .wallet-stats-row { display: flex; gap: 2.5rem; flex-wrap: wrap; position: relative; z-index: 1; }
        .wallet-stat-item { }
        .wallet-stat-label {
            font-size: 0.7rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; opacity: 0.55; margin-bottom: 0.25rem;
        }
        .wallet-stat-val { font-size: 1.15rem; font-weight: 800; }
        .wallet-sep { width: 1px; background: rgba(255,255,255,0.15); align-self: stretch; }

        .tx-list {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden;
        }
        .tx-list-header {
            padding: 1.1rem 1.75rem; border-bottom: 1px solid var(--border);
            font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted);
        }
        .tx-item {
            display: flex; align-items: center; gap: 1rem;
            padding: 1.1rem 1.75rem; border-bottom: 1px solid var(--border); transition: background 0.12s;
        }
        .tx-item:last-child { border-bottom: none; }
        .tx-item:hover { background: var(--bg); }
        .tx-icon {
            width: 42px; height: 42px; border-radius: 13px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; flex-shrink: 0;
        }
        .tx-icon.credit { background: var(--green-lt); color: var(--green); }
        .tx-icon.debit  { background: var(--rose-lt);  color: var(--rose); }
        .tx-info   { flex: 1; min-width: 0; }
        .tx-desc   { font-size: 0.875rem; font-weight: 600; color: var(--dark); }
        .tx-meta   { font-size: 0.75rem; color: var(--muted); margin-top: 0.15rem; }
        .tx-right  { text-align: right; flex-shrink: 0; }
        .tx-amount { font-size: 0.92rem; font-weight: 800; }
        .tx-amount.credit { color: var(--green); }
        .tx-amount.debit  { color: var(--rose); }
        .tx-status { font-size: 0.72rem; margin-top: 0.12rem; font-weight: 600; }
        .tx-status.completed { color: var(--green); }
        .tx-status.pending   { color: var(--amber); }
        .tx-status.failed    { color: var(--rose); }

        /* ── BALANCE SUMMARY ──────────────────────────── */
        .balance-summary-card {
            background: linear-gradient(135deg, var(--bg) 0%, #ede8ff 100%);
            border: 1px solid var(--border); border-radius: var(--radius);
            padding: 1.5rem 1.75rem; display: flex; align-items: center;
            justify-content: space-between; margin-bottom: 1.5rem;
        }
        .balance-summary-label {
            font-size: 0.73rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.07em; color: var(--muted); margin-bottom: 0.35rem;
        }
        .balance-summary-val {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 2rem; letter-spacing: -0.025em;
        }

        /* ── FORM / INPUTS ────────────────────────────── */
        .form-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 2rem 2.25rem;
        }
        .pfield { margin-bottom: 1.1rem; }
        .pfield label { display: block; font-size: 0.82rem; font-weight: 700; color: var(--dark); margin-bottom: 0.4rem; }
        .pfield input, .pfield select {
            width: 100%; border: 1.5px solid var(--border); border-radius: 12px;
            padding: 0.75rem 1rem; font-size: 0.875rem; font-family: inherit;
            color: var(--dark); background: var(--surface); outline: none; transition: border-color 0.15s, box-shadow 0.15s;
        }
        .pfield input:focus, .pfield select:focus {
            border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-lt);
        }
        .btn-submit-form {
            width: 100%; padding: 0.92rem;
            background: linear-gradient(135deg, var(--accent) 0%, #f97316 100%);
            color: #fff; border: none; border-radius: 99px;
            font-size: 0.92rem; font-weight: 700; font-family: inherit;
            cursor: pointer; margin-top: 1.25rem;
            box-shadow: 0 4px 16px rgba(224,78,18,0.3);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-submit-form:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(224,78,18,0.4); }
        .btn-submit-form:disabled { opacity: 0.5; cursor: default; transform: none; }

        /* ── BANK ACCOUNT LIST ────────────────────────── */
        .bank-list {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden; margin-bottom: 1.5rem;
        }
        .bank-item {
            display: flex; align-items: center; gap: 1rem;
            padding: 1.1rem 1.75rem; border-bottom: 1px solid var(--border);
        }
        .bank-item:last-child { border-bottom: none; }
        .bank-icon {
            width: 44px; height: 44px; border-radius: 14px;
            background: var(--blue-lt); border: 1px solid rgba(2,132,199,0.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; color: var(--blue); flex-shrink: 0;
        }
        .bank-info { flex: 1; min-width: 0; }
        .bank-name { font-size: 0.875rem; font-weight: 700; color: var(--dark); display: flex; align-items: center; gap: 0.5rem; }
        .bank-meta { font-size: 0.78rem; color: var(--muted); margin-top: 0.15rem; }
        .default-badge {
            display: inline-flex; padding: 0.12rem 0.6rem; border-radius: 99px;
            background: var(--green-lt); color: var(--green);
            font-size: 0.65rem; font-weight: 800; letter-spacing: 0.04em;
        }
        .bank-actions { display: flex; gap: 0.5rem; flex-shrink: 0; }
        .icon-btn {
            width: 34px; height: 34px; border-radius: 10px;
            background: var(--bg); border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.95rem; cursor: pointer; transition: all 0.15s;
            color: var(--dark); font-family: inherit;
        }
        .icon-btn:hover { background: var(--dark); color: #fff; border-color: var(--dark); }
        .icon-btn.danger { color: var(--rose); }
        .icon-btn.danger:hover { background: var(--rose-lt); border-color: #fca5a5; color: var(--rose); }
        .icon-btn.accent { color: var(--accent); }
        .icon-btn.accent:hover { background: var(--accent-lt); border-color: rgba(224,78,18,0.3); color: var(--accent); }

        /* ── WITHDRAWAL BADGE ─────────────────────────── */
        .wd-badge {
            display: inline-block; padding: 0.15rem 0.6rem; border-radius: 99px;
            font-size: 0.68rem; font-weight: 800;
        }

        /* ── DISCOVER ─────────────────────────────────── */
        .discover-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px,1fr)); gap: 1.25rem; }
        .discover-card {
            background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
            overflow: hidden; transition: transform 0.22s, box-shadow 0.22s;
            text-decoration: none; display: block; color: inherit;
        }
        .discover-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
        .discover-cover { height: 152px; overflow: hidden; position: relative; }
        .discover-cover img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.3s; }
        .discover-card:hover .discover-cover img { transform: scale(1.05); }
        .discover-cover-grad { width: 100%; height: 100%; }
        .discover-type-badge {
            position: absolute; top: 0.7rem; left: 0.7rem;
            background: rgba(14,11,9,0.65); backdrop-filter: blur(6px);
            color: #fff; border-radius: 99px; padding: 0.22rem 0.65rem;
            font-size: 0.72rem; font-weight: 700; display: flex; align-items: center; gap: 0.3rem;
        }
        .discover-body { padding: 1.1rem 1.25rem; }
        .discover-title {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 1.08rem; letter-spacing: -0.01em; margin-bottom: 0.35rem;
        }
        .discover-meta { font-size: 0.78rem; color: var(--muted); }
        .discover-link {
            display: block; margin-top: 0.9rem; text-align: center; padding: 0.55rem;
            background: var(--bg); border: 1px solid var(--border); border-radius: 10px;
            font-size: 0.82rem; font-weight: 700; color: var(--dark); transition: all 0.15s;
        }
        .discover-card:hover .discover-link { background: var(--dark); color: #fff; border-color: var(--dark); }

        /* ── FLASH ────────────────────────────────────── */
        .flash-ok {
            background: var(--green-lt); border: 1px solid rgba(5,150,105,0.3);
            border-radius: var(--radius-sm); padding: 0.95rem 1.3rem; margin-bottom: 1.5rem;
            font-size: 0.875rem; color: #065f46; font-weight: 500;
            display: flex; align-items: center; gap: 0.65rem;
        }
        .flash-err {
            background: var(--rose-lt); border: 1px solid rgba(225,29,72,0.25);
            border-radius: var(--radius-sm); padding: 0.95rem 1.3rem; margin-bottom: 1.5rem;
            font-size: 0.875rem; color: #9f1239; font-weight: 500;
            display: flex; align-items: center; gap: 0.65rem;
        }

        /* ── RESPONSIVE ───────────────────────────────── */
        @media (max-width: 900px) { .stats-bar { grid-template-columns: repeat(2,1fr); } }
        @media (max-width: 640px) {
            .topnav-inner { padding: 0 1.25rem; }
            .page { padding: 1.5rem 1.25rem 5rem; }
            .page-hero { padding: 1.75rem 1.5rem; }
            .page-hero h1 { font-size: 2rem; }
            .stats-bar { grid-template-columns: repeat(2,1fr); gap: 0.75rem; }
            .wallet-balance { font-size: 2.75rem; }
            .tab-bar { border-radius: var(--radius-sm); }
            .upcoming-item { grid-template-columns: 60px 1fr; }
            .upcoming-item > :last-child { display: none; }
        }
    </style>
</head>
<body x-data="{
    tab: '{{ session('active_tab', 'events') }}',
    editingBank: { id: null, bank_name: '', account_number: '', account_name: '' },
    bulkUploadOpen: false
}">

    {{-- ── NAV ───────────────────────────────────────── --}}
    <header class="topnav">
        <div class="topnav-inner">
            <a href="{{ url('/') }}" class="brand">CelebrateMi</a>
            <div class="nav-right">
                @if(auth()->user()->account_type === 'admin')
                    <a href="{{ route('admin.dashboard') }}"
                       style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.5rem 1rem;font-size:0.82rem;font-weight:700;color:var(--purple);border:1.5px solid rgba(124,58,237,0.25);border-radius:99px;text-decoration:none;background:var(--purple-lt);transition:all 0.15s;">
                        <i class="mdi mdi-shield-crown-outline"></i> Admin
                    </a>
                @endif
                <div class="user-menu" x-data="{ open: false }" @click.outside="open = false">
                    <button class="user-trigger" @click="open = !open">
                        <div class="user-avatar">
                            {{ strtoupper(substr(auth()->user()->first_name ?? auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <span>{{ auth()->user()->first_name ?? auth()->user()->name }}</span>
                        <i class="mdi mdi-chevron-down" style="opacity:.4;font-size:1rem;flex-shrink:0"></i>
                    </button>
                    <div class="user-dropdown" x-show="open" x-transition x-cloak>
                        <a href="{{ route('profile.edit') }}" class="dropdown-item">
                            <i class="mdi mdi-account-outline" style="margin-right:0.4rem;opacity:.65"></i>Profile settings
                        </a>
                        <a href="{{ url('/') }}" class="dropdown-item">
                            <i class="mdi mdi-home-outline" style="margin-right:0.4rem;opacity:.65"></i>Home page
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item danger" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;">
                                <i class="mdi mdi-logout" style="margin-right:0.4rem"></i>Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- ── PAGE ─────────────────────────────────────── --}}
    <div class="page">

        {{-- Hero header --}}
        <div class="page-hero">
            <div>
                <div class="greeting-tag">
                    <i class="mdi mdi-weather-sunny" style="font-size:1rem"></i>
                    {{ $greeting }},
                <span class="text-sm font-medium text-muted" style="margin-left: 0.5rem;">[{{ auth()->user()->account_type }}]</span>
                </div>
                <h1>Welcome back, <span>{{ auth()->user()->first_name ?? auth()->user()->name }}</span></h1>
                <p class="hero-sub">Here's what's happening with your celebrations today.</p>
            </div>
            <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center">
                <button class="btn-create-hero" x-on:click="$dispatch('open-modal', 'create-event')">
                    <i class="mdi mdi-plus-circle-outline" style="font-size:1.1rem"></i>
                    New celebration
                </button>
                @if (auth()->user()->account_type === 'corporate')
                    <button class="btn-create-hero"
                        style="background:linear-gradient(135deg,#7c3aed 0%,#a855f7 100%);box-shadow:0 6px 20px rgba(124,58,237,0.35)"
                        @click="bulkUploadOpen = true">
                        <i class="mdi mdi-upload" style="font-size:1.1rem"></i>
                        Bulk Upload
                    </button>
                @endif
            </div>
        </div>

        {{-- Stats bar --}}
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-icon orange"><i class="mdi mdi-calendar-star"></i></div>
                <div class="stat-body">
                    <p class="stat-label">Total events</p>
                    <p class="stat-value">{{ $stats['total'] }}</p>
                    <p class="stat-sub">{{ $stats['published'] }} live</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="mdi mdi-eye-outline"></i></div>
                <div class="stat-body">
                    <p class="stat-label">Page views</p>
                    <p class="stat-value">{{ number_format($stats['views']) }}</p>
                    <p class="stat-sub">across all pages</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="mdi mdi-message-outline"></i></div>
                <div class="stat-body">
                    <p class="stat-label">Messages</p>
                    <p class="stat-value">{{ number_format($stats['messages']) }}</p>
                    <p class="stat-sub">wishes received</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="mdi mdi-wallet-outline"></i></div>
                <div class="stat-body">
                    <p class="stat-label">Wallet</p>
                    <p class="stat-value" style="font-size:1.55rem;">{{ $currencySymbol }}{{ number_format($walletDisplay, 2) }}</p>
                    <p class="stat-sub">available balance</p>
                </div>
            </div>
        </div>

        {{-- ── TAB BAR ────────────────────────────────── --}}
        <div class="tab-wrap">
            <div class="tab-bar">
                <button class="tab-btn" :class="{ 'is-active': tab === 'events' }" @click="tab = 'events'">
                    <i class="mdi mdi-calendar-star"></i> My Events
                </button>
                <button class="tab-btn" :class="{ 'is-active': tab === 'upcoming' }" @click="tab = 'upcoming'">
                    <i class="mdi mdi-calendar-clock"></i> Upcoming
                </button>
                <button class="tab-btn" :class="{ 'is-active': tab === 'activity' }" @click="tab = 'activity'">
                    <i class="mdi {{ $unreadCount > 0 ? 'mdi-bell' : 'mdi-bell-outline' }}"></i> Activity
                    @if ($unreadCount > 0)
                        <span class="tab-badge">{{ $unreadCount }}</span>
                    @endif
                </button>
                <button class="tab-btn" :class="{ 'is-active': tab === 'wallet' }" @click="tab = 'wallet'">
                    <i class="mdi mdi-wallet-outline"></i> Wallet
                </button>
                <button class="tab-btn" :class="{ 'is-active': tab === 'withdrawals' }" @click="tab = 'withdrawals'">
                    <i class="mdi mdi-bank-transfer-out"></i> Withdrawals
                </button>
                <button class="tab-btn" :class="{ 'is-active': tab === 'bank' }" @click="tab = 'bank'">
                    <i class="mdi mdi-bank-outline"></i> Bank Account
                </button>
                <button class="tab-btn" :class="{ 'is-active': tab === 'discover' }" @click="tab = 'discover'">
                    <i class="mdi mdi-compass-outline"></i> Discover
                </button>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- TAB 1 · MY EVENTS                          --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div x-show="tab === 'events'">
            <div class="events-grid">

                <button class="event-add-card" x-on:click="$dispatch('open-modal', 'create-event')">
                    <i class="mdi mdi-plus-circle-outline add-icon"></i>
                    <span class="add-label">New celebration</span>
                </button>

                @forelse ($celebrations as $celebration)
                    @php
                        $types = [
                            'birthday'    => ['icon' => 'mdi-cake-variant',  'label' => 'Birthday',    'grad' => 'linear-gradient(135deg,#fde68a 0%,#f87171 100%)'],
                            'wedding'     => ['icon' => 'mdi-ring',           'label' => 'Wedding',     'grad' => 'linear-gradient(135deg,#ddd6fe 0%,#c084fc 100%)'],
                            'graduation'  => ['icon' => 'mdi-school',         'label' => 'Graduation',  'grad' => 'linear-gradient(135deg,#a7f3d0 0%,#059669 100%)'],
                            'anniversary' => ['icon' => 'mdi-heart',          'label' => 'Anniversary', 'grad' => 'linear-gradient(135deg,#fecaca 0%,#f43f5e 100%)'],
                            'memorial'    => ['icon' => 'mdi-dove',           'label' => 'Memorial',    'grad' => 'linear-gradient(135deg,#e2e8f0 0%,#94a3b8 100%)'],
                            'other'       => ['icon' => 'mdi-party-popper',   'label' => 'Celebration', 'grad' => 'linear-gradient(135deg,#fed7aa 0%,#f97316 100%)'],
                        ];
                        $t = $types[$celebration->celebration_type] ?? $types['other'];

                        [$badgeClass, $badgeLabel] = match($celebration->status) {
                            'published' => ['badge-live',   'Live'],
                            'closed'    => ['badge-closed', 'Closed'],
                            default     => ['badge-draft',  'Draft'],
                        };

                        $displayDate = null;
                        if ($celebration->event_date)
                            $displayDate = \Carbon\Carbon::parse($celebration->event_date)->format('M j, Y');
                        elseif ($celebration->start_date)
                            $displayDate = \Carbon\Carbon::parse($celebration->start_date)->format('M j, Y');
                    @endphp

                    <div class="event-card">
                        <div class="event-cover">
                            @if ($celebration->cover_photo)
                                <img src="{{ asset('storage/' . $celebration->cover_photo) }}" alt="{{ $celebration->title }}">
                            @else
                                <div class="event-cover-gradient" style="background:{{ $t['grad'] }};"></div>
                            @endif
                            <span class="status-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                            <div class="event-type-icon"><i class="mdi {{ $t['icon'] }}"></i></div>
                        </div>

                        <div class="event-body">
                            <p class="event-type-tag">
                                <i class="mdi {{ $t['icon'] }}" style="font-size:0.85rem"></i>
                                {{ $t['label'] }}
                            </p>
                            <h3 class="event-title">{{ $celebration->title }}</h3>
                            @if ($displayDate)
                                <p class="event-date">
                                    <i class="mdi mdi-calendar-outline"></i> {{ $displayDate }}
                                </p>
                            @endif
                            <div class="event-stats">
                                <div class="event-stat">
                                    <i class="mdi mdi-eye-outline"></i>
                                    <strong>{{ number_format($celebration->view_count) }}</strong>
                                    <span>views</span>
                                </div>
                                <div class="event-stat">
                                    <i class="mdi mdi-message-outline"></i>
                                    <strong>{{ number_format($celebration->comment_count) }}</strong>
                                    <span>msgs</span>
                                </div>
                                @if (($celebration->gifts_count ?? 0) > 0)
                                    <div class="event-stat">
                                        <i class="mdi mdi-gift-outline"></i>
                                        <strong>{{ $celebration->gifts_count }}</strong>
                                        <span>gifts</span>
                                    </div>
                                @endif
                            </div>
                            <div class="event-actions">
                                <a href="{{ route('celebrations.show', $celebration->slug) }}"
                                   class="action-btn" target="_blank">
                                    <i class="mdi mdi-eye" style="margin-right:0.25rem"></i>View
                                </a>
                                <a href="{{ route('celebrant.edit', $celebration->slug) }}"
                                   class="action-btn">
                                    <i class="mdi mdi-pencil" style="margin-right:0.25rem"></i>Edit
                                </a>
                                <form method="POST"
                                      action="{{ route('celebrant.destroy', $celebration->slug) }}"
                                      x-data
                                      @submit.prevent="confirm('Delete &quot;{{ addslashes($celebration->title) }}&quot;? This cannot be undone.') && $el.submit()">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-delete" title="Delete">
                                        <i class="mdi mdi-trash-can-outline"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="mdi mdi-calendar-star empty-icon"></i>
                        <h3>No celebrations yet</h3>
                        <p>Your event pages will appear here. Create your first one — it only takes a minute and it's completely free.</p>
                        <button class="btn-empty" x-on:click="$dispatch('open-modal', 'create-event')">
                            <i class="mdi mdi-plus"></i> Create your first celebration
                        </button>
                    </div>
                @endforelse

            </div>
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- TAB 2 · UPCOMING EVENTS                    --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div x-show="tab === 'upcoming'" style="display:none">
            <div class="tab-panel-header">
                <div>
                    <h2 class="panel-title">Upcoming events</h2>
                    <p class="panel-subtitle">Your published celebrations with future dates</p>
                </div>
            </div>

            @if ($upcoming->isEmpty())
                <div class="empty-state" style="grid-column:unset">
                    <i class="mdi mdi-calendar-blank empty-icon"></i>
                    <h3>Nothing upcoming</h3>
                    <p>Publish a celebration with a future date and it will appear here.</p>
                    <button class="btn-empty" @click="tab = 'events'">
                        <i class="mdi mdi-arrow-left"></i> Go to My Events
                    </button>
                </div>
            @else
                <div class="upcoming-list">
                    @foreach ($upcoming as $cel)
                        @php
                            $targetDate = $cel->event_date ?? $cel->start_date;
                            $carbon     = $targetDate ? \Carbon\Carbon::parse($targetDate) : null;
                            $daysAway   = $carbon ? max(0, (int) $carbon->startOfDay()->diffInDays(now()->startOfDay(), false)) : null;
                            $utypes = [
                                'birthday'    => ['icon' => 'mdi-cake-variant', 'label' => 'Birthday'],
                                'wedding'     => ['icon' => 'mdi-ring',          'label' => 'Wedding'],
                                'graduation'  => ['icon' => 'mdi-school',        'label' => 'Graduation'],
                                'anniversary' => ['icon' => 'mdi-heart',         'label' => 'Anniversary'],
                                'memorial'    => ['icon' => 'mdi-dove',          'label' => 'Memorial'],
                                'other'       => ['icon' => 'mdi-party-popper',  'label' => 'Celebration'],
                            ];
                            $ut = $utypes[$cel->celebration_type] ?? $utypes['other'];
                        @endphp
                        <div class="upcoming-item">
                            @if ($carbon)
                                <div class="date-box">
                                    <span class="date-day">{{ $carbon->format('j') }}</span>
                                    <span class="date-month">{{ $carbon->format('M') }}</span>
                                </div>
                            @else
                                <div class="date-box" style="background:linear-gradient(135deg,#94a3b8,#64748b)">
                                    <span class="date-day">?</span>
                                    <span class="date-month">TBD</span>
                                </div>
                            @endif
                            <div>
                                <p class="upcoming-info-title">{{ $cel->title }}</p>
                                <p class="upcoming-info-meta">
                                    <i class="mdi {{ $ut['icon'] }}" style="margin-right:0.2rem"></i>{{ $ut['label'] }}
                                </p>
                            </div>
                            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.5rem">
                                @if ($daysAway !== null)
                                    <span class="upcoming-countdown">
                                        @if ($daysAway === 0) 🎉 Today!
                                        @elseif ($daysAway === 1) Tomorrow
                                        @else In {{ $daysAway }} days
                                        @endif
                                    </span>
                                @endif
                                <a href="{{ route('celebrations.show', $cel->slug) }}"
                                   class="action-btn" style="flex:unset;padding:0.42rem 1rem;font-size:0.78rem;white-space:nowrap"
                                   target="_blank">View <i class="mdi mdi-arrow-right"></i></a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- TAB 3 · ACTIVITY                           --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div x-show="tab === 'activity'" style="display:none">
            <div class="tab-panel-header">
                <div>
                    <h2 class="panel-title">Activity</h2>
                    <p class="panel-subtitle">
                        @if ($unreadCount > 0)
                            <span style="color:var(--accent);font-weight:700">{{ $unreadCount }} unread</span> notification{{ $unreadCount === 1 ? '' : 's' }}
                        @else
                            You're all caught up ✓
                        @endif
                    </p>
                </div>
            </div>

            @if ($notifications->isEmpty())
                <div class="empty-state" style="grid-column:unset">
                    <i class="mdi mdi-bell-off-outline empty-icon"></i>
                    <h3>No notifications yet</h3>
                    <p>When guests view your page, send wishes, or give gifts you'll see it here.</p>
                </div>
            @else
                <div class="notif-list">
                    @foreach ($notifications as $notif)
                        @php
                            $nicons = [
                                'gift'     => 'mdi-gift',
                                'comment'  => 'mdi-message',
                                'reaction' => 'mdi-heart',
                                'view'     => 'mdi-eye',
                                'payment'  => 'mdi-cash',
                                'system'   => 'mdi-information',
                            ];
                            $nicon = $nicons[$notif->type] ?? 'mdi-bell';
                        @endphp
                        <div class="notif-item {{ $notif->is_read ? '' : 'unread' }}">
                            <div class="notif-indicator-wrap">
                                <div class="notif-indicator"><i class="mdi {{ $nicon }}"></i></div>
                                @if (!$notif->is_read)
                                    <span class="notif-dot"></span>
                                @endif
                            </div>
                            <div class="notif-content">
                                <p class="notif-title">{{ $notif->title }}</p>
                                @if ($notif->message)
                                    <p class="notif-msg">{{ $notif->message }}</p>
                                @endif
                                <p class="notif-time">{{ $notif->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- TAB 4 · WALLET                             --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div x-show="tab === 'wallet'" style="display:none">
            <div class="tab-panel-header">
                <div>
                    <h2 class="panel-title">Wallet</h2>
                    <p class="panel-subtitle">Your gift contributions and balance</p>
                </div>
                <button class="btn-create-lg" @click="$dispatch('open-modal', 'fund-wallet')">
                    <i class="mdi mdi-plus"></i> Fund Wallet
                </button>
            </div>

            @if (session('success') && session('active_tab') === 'wallet')
                <div class="flash-ok"><i class="mdi mdi-check-circle"></i> {{ session('success') }}</div>
            @endif
            @if (session('error') && session('active_tab') === 'wallet')
                <div class="flash-err"><i class="mdi mdi-alert-circle"></i> {{ session('error') }}</div>
            @endif

            <div class="wallet-hero">
                <div class="wallet-chip"><i class="mdi mdi-wallet" style="font-size:0.9rem"></i> {{ $userCurrency }} Wallet</div>
                <p class="wallet-label">Available balance</p>
                <p class="wallet-balance">{{ $currencySymbol }}{{ number_format($walletDisplay, 2) }}</p>
                <div class="wallet-stats-row">
                    <div class="wallet-stat-item">
                        <p class="wallet-stat-label">Total credited</p>
                        <p class="wallet-stat-val">{{ $currencySymbol }}{{ number_format($totalCredited, 2) }}</p>
                    </div>
                    <div class="wallet-sep"></div>
                    <div class="wallet-stat-item">
                        <p class="wallet-stat-label">Total debited</p>
                        <p class="wallet-stat-val">{{ $currencySymbol }}{{ number_format($totalDebited, 2) }}</p>
                    </div>
                </div>
            </div>

            @if ($walletTransactions->isEmpty())
                <div class="empty-state" style="grid-column:unset">
                    <i class="mdi mdi-wallet-outline empty-icon"></i>
                    <h3>No transactions yet</h3>
                    <p>Gift contributions and withdrawals will show up here as a full transaction history.</p>
                </div>
            @else
                <div class="tx-list">
                    <div class="tx-list-header">Transaction history</div>
                    @foreach ($walletTransactions as $tx)
                        <div class="tx-item">
                            <div class="tx-icon {{ $tx->type }}">
                                <i class="mdi {{ $tx->type === 'credit' ? 'mdi-arrow-down-bold' : 'mdi-arrow-up-bold' }}"></i>
                            </div>
                            <div class="tx-info">
                                <p class="tx-desc">{{ $tx->description ?: ($tx->type === 'credit' ? 'Credit received' : 'Debit') }}</p>
                                <p class="tx-meta">
                                    {{ $tx->created_at->format('M j, Y · g:ia') }}
                                    @if ($tx->reference) · Ref: {{ $tx->reference }} @endif
                                </p>
                            </div>
                            <div class="tx-right">
                                <p class="tx-amount {{ $tx->type }}">
                                    {{ $tx->type === 'credit' ? '+' : '−' }}{{ $currencySymbol }}{{ number_format($tx->amount, 2) }}
                                </p>
                                <p class="tx-status {{ $tx->status }}">{{ ucfirst($tx->status) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- TAB 5 · WITHDRAWALS                        --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div x-show="tab === 'withdrawals'" style="display:none">
            <div class="tab-panel-header">
                <div>
                    <h2 class="panel-title">Withdrawals</h2>
                    <p class="panel-subtitle">Transfer your wallet balance to your bank account</p>
                </div>
                @if ($bankAccounts->isNotEmpty())
                    <button class="btn-create-lg" @click="$dispatch('open-modal', 'request-withdrawal')">
                        <i class="mdi mdi-bank-transfer-out"></i> Request withdrawal
                    </button>
                @endif
            </div>

            @if (session('success') && session('active_tab') === 'withdrawals')
                <div class="flash-ok"><i class="mdi mdi-check-circle"></i> {{ session('success') }}</div>
            @endif
            @if (session('error') && session('active_tab') === 'withdrawals')
                <div class="flash-err"><i class="mdi mdi-alert-circle"></i> {{ session('error') }}</div>
            @endif

            <div style="max-width:580px">
                <div class="balance-summary-card">
                    <div>
                        <p class="balance-summary-label">Available balance</p>
                        <p class="balance-summary-val">{{ $currencySymbol }}{{ number_format($walletDisplay, 2) }}</p>
                    </div>
                    <div style="width:52px;height:52px;border-radius:16px;background:rgba(124,58,237,0.1);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:var(--purple)">
                        <i class="mdi mdi-wallet"></i>
                    </div>
                </div>

                @if ($bankAccounts->isEmpty())
                    <div class="form-card" style="text-align:center;padding:3rem 2rem">
                        <div style="width:60px;height:60px;border-radius:18px;background:var(--bg);margin:0 auto 1.25rem;display:flex;align-items:center;justify-content:center;font-size:1.75rem;color:var(--muted)">
                            <i class="mdi mdi-bank-outline"></i>
                        </div>
                        <p style="font-size:0.92rem;color:var(--muted);margin-bottom:1.5rem;line-height:1.65">You need to save a bank account before you can withdraw.</p>
                        <button class="btn-create-lg" style="width:auto" @click="tab = 'bank'">
                            <i class="mdi mdi-plus"></i> Add bank account
                        </button>
                    </div>
                @endif
            </div>

            @if ($withdrawals->isNotEmpty())
                <div style="max-width:720px;margin-top: {{ $bankAccounts->isEmpty() ? '2rem' : '0' }}">
                    <h3 style="font-family:'DM Serif Display',serif;font-size:1.2rem;letter-spacing:-0.02em;margin-bottom:1rem">Withdrawal history</h3>
                    <div class="tx-list">
                        @foreach ($withdrawals as $wd)
                            @php
                                $wdBadge = match($wd->status) {
                                    'completed'  => ['bg' => '#d1fae5', 'color' => '#065f46',  'label' => 'Completed'],
                                    'processing' => ['bg' => '#dbeafe', 'color' => '#1e40af',  'label' => 'Processing'],
                                    'failed'     => ['bg' => '#fee2e2', 'color' => '#991b1b',  'label' => 'Failed'],
                                    'rejected'   => ['bg' => '#fee2e2', 'color' => '#991b1b',  'label' => 'Rejected'],
                                    default      => ['bg' => '#fef3c7', 'color' => '#92400e',  'label' => 'Pending'],
                                };
                                $wdSymbol = $wd->original_currency
                                    ? config("currency.currencies.{$wd->original_currency}.symbol", $wd->original_currency)
                                    : $currencySymbol;
                            @endphp
                            <div class="tx-item">
                                <div class="tx-icon debit"><i class="mdi mdi-arrow-up-bold"></i></div>
                                <div class="tx-info">
                                    <p class="tx-desc">
                                        {{ $wd->bank_name ?? $wd->bankAccount->bank_name }} —
                                        ••{{ substr($wd->bank_account_number ?? $wd->bankAccount->account_number ?? '0000', -4) }}
                                    </p>
                                    <p class="tx-meta">
                                        {{ $wd->bank_account_name ?? $wd->bankAccount->account_name ?? '' }} ·
                                        {{ $wd->created_at->format('M j, Y · g:ia') }}
                                    </p>
                                    @if ($wd->note)
                                        <p class="tx-meta" style="color:var(--rose);margin-top:0.15rem">
                                            <i class="mdi mdi-alert-circle-outline" style="font-size:0.8rem"></i>
                                            {{ $wd->note }}
                                        </p>
                                    @endif
                                </div>
                                <div class="tx-right">
                                    <p class="tx-amount debit">−{{ $wdSymbol }}{{ number_format($wd->original_amount ?? $wd->amount, 2) }}</p>
                                    <span class="wd-badge" style="background:{{ $wdBadge['bg'] }};color:{{ $wdBadge['color'] }}">
                                        {{ $wdBadge['label'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- TAB 6 · BANK ACCOUNT                       --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div x-show="tab === 'bank'" style="display:none">
            <div class="tab-panel-header">
                <div>
                    <h2 class="panel-title">Bank account</h2>
                    <p class="panel-subtitle">Saved details used for withdrawal payments</p>
                </div>
                <button class="btn-create-lg" @click="$dispatch('open-modal', 'add-bank')">
                    <i class="mdi mdi-plus"></i> Add bank account
                </button>
            </div>

            @if (session('success') && session('active_tab') === 'bank')
                <div class="flash-ok"><i class="mdi mdi-check-circle"></i> {{ session('success') }}</div>
            @endif

            <div style="max-width:640px">
                @if ($bankAccounts->isEmpty())
                    <div class="empty-state" style="grid-column:unset">
                        <i class="mdi mdi-bank-off-outline empty-icon"></i>
                        <h3>No bank account saved</h3>
                        <p>Add a bank account so we can process your withdrawal requests quickly.</p>
                        <button class="btn-empty" @click="$dispatch('open-modal', 'add-bank')">
                            <i class="mdi mdi-plus"></i> Add bank account
                        </button>
                    </div>
                @else
                    <div class="bank-list">
                        @foreach ($bankAccounts as $ba)
                            <div class="bank-item">
                                <div class="bank-icon"><i class="mdi mdi-bank"></i></div>
                                <div class="bank-info">
                                    <div class="bank-name">
                                        {{ $ba->bank_name }}
                                        @if ($ba->is_default)
                                            <span class="default-badge">Default</span>
                                        @endif
                                    </div>
                                    <p class="bank-meta">{{ $ba->account_number }} · {{ $ba->account_name }}</p>
                                </div>
                                <div class="bank-actions">
                                    @unless ($ba->is_default)
                                        <form method="POST" action="{{ route('bank-accounts.default', $ba) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="icon-btn accent" title="Set as default">
                                                <i class="mdi mdi-star-outline"></i>
                                            </button>
                                        </form>
                                    @endunless
                                    <button class="icon-btn"
                                        title="Edit"
                                        @click="
                                            editingBank = {
                                                id: {{ $ba->id }},
                                                bank_name: '{{ addslashes($ba->bank_name) }}',
                                                account_number: '{{ $ba->account_number }}',
                                                account_name: '{{ addslashes($ba->account_name) }}'
                                            };
                                            $dispatch('open-modal', 'edit-bank')
                                        ">
                                        <i class="mdi mdi-pencil-outline"></i>
                                    </button>
                                    <form method="POST" action="{{ route('bank-accounts.destroy', $ba) }}"
                                          x-data
                                          @submit.prevent="confirm('Remove {{ addslashes($ba->bank_name) }} ({{ $ba->account_number }})? This cannot be undone.') && $el.submit()">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="icon-btn danger" title="Remove">
                                            <i class="mdi mdi-trash-can-outline"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- TAB 7 · DISCOVER                           --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div x-show="tab === 'discover'" style="display:none">
            <div class="tab-panel-header">
                <div>
                    <h2 class="panel-title">Discover</h2>
                    <p class="panel-subtitle">Public celebrations happening right now</p>
                </div>
            </div>

            @if ($discover->isEmpty())
                <div class="empty-state" style="grid-column:unset">
                    <i class="mdi mdi-compass-off-outline empty-icon"></i>
                    <h3>Nothing to discover yet</h3>
                    <p>When people share public celebration pages they'll appear here.</p>
                    <button class="btn-empty" @click="$dispatch('open-modal', 'create-event')">
                        <i class="mdi mdi-plus"></i> Create a public event
                    </button>
                </div>
            @else
                <div class="discover-grid">
                    @foreach ($discover as $cel)
                        @php
                            $dtypes = [
                                'birthday'    => ['icon' => 'mdi-cake-variant', 'label' => 'Birthday',    'grad' => 'linear-gradient(135deg,#fde68a 0%,#f87171 100%)'],
                                'wedding'     => ['icon' => 'mdi-ring',          'label' => 'Wedding',     'grad' => 'linear-gradient(135deg,#ddd6fe 0%,#c084fc 100%)'],
                                'graduation'  => ['icon' => 'mdi-school',        'label' => 'Graduation',  'grad' => 'linear-gradient(135deg,#a7f3d0 0%,#059669 100%)'],
                                'anniversary' => ['icon' => 'mdi-heart',         'label' => 'Anniversary', 'grad' => 'linear-gradient(135deg,#fecaca 0%,#f43f5e 100%)'],
                                'memorial'    => ['icon' => 'mdi-dove',          'label' => 'Memorial',    'grad' => 'linear-gradient(135deg,#e2e8f0 0%,#94a3b8 100%)'],
                                'other'       => ['icon' => 'mdi-party-popper',  'label' => 'Celebration', 'grad' => 'linear-gradient(135deg,#fed7aa 0%,#f97316 100%)'],
                            ];
                            $dt = $dtypes[$cel->celebration_type] ?? $dtypes['other'];
                            $celDate = $cel->event_date ?? $cel->start_date;
                        @endphp
                        <a href="{{ route('celebrations.show', $cel->slug) }}" class="discover-card" target="_blank">
                            <div class="discover-cover">
                                @if ($cel->cover_photo)
                                    <img src="{{ asset('storage/' . $cel->cover_photo) }}" alt="{{ $cel->title }}">
                                @else
                                    <div class="discover-cover-grad" style="background:{{ $dt['grad'] }}"></div>
                                @endif
                                <span class="discover-type-badge">
                                    <i class="mdi {{ $dt['icon'] }}"></i> {{ $dt['label'] }}
                                </span>
                            </div>
                            <div class="discover-body">
                                <h3 class="discover-title">{{ $cel->title }}</h3>
                                <p class="discover-meta">
                                    @if ($celDate)
                                        <i class="mdi mdi-calendar-outline"></i>
                                        {{ \Carbon\Carbon::parse($celDate)->format('M j, Y') }}
                                    @endif
                                </p>
                                <span class="discover-link">
                                    <i class="mdi mdi-party-popper"></i> Join the celebration
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

    </div>{{-- /page --}}

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- MODAL: Create celebration                       --}}
    {{-- ═══════════════════════════════════════════════ --}}
    <x-modal name="create-event" maxWidth="2xl" focusable>
        <div class="p-6 relative">
            <div class="absolute top-4 right-4">
                <button x-on:click="$dispatch('close')"
                    class="rounded-full h-10 w-10 flex items-center justify-center bg-gray-100 hover:bg-gray-200 transition">
                    <i class="mdi mdi-close text-gray-600"></i>
                </button>
            </div>
            <h2 class="text-xl font-bold text-gray-900">New Celebration</h2>
            <p class="mt-1 text-sm text-gray-500">Fill in the details — your page goes live instantly.</p>
            <div x-data="celebrationForm()" x-init="loggedIn = @js(auth()->check())" class="mt-6">
                <form @submit.prevent="nextStep">
                    <div class="flex items-center gap-3 mb-6">
                        <div :class="step >= 1 ? 'bg-rose-500 text-white' : 'bg-gray-200 text-gray-500'"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-colors">1</div>
                        <div class="flex-1 h-px bg-gray-200">
                            <div :class="step >= 2 ? 'w-full bg-rose-400' : 'w-0'" class="h-full transition-all duration-300"></div>
                        </div>
                        <div :class="step >= 2 ? 'bg-rose-500 text-white' : 'bg-gray-200 text-gray-500'"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-colors">2</div>
                    </div>
                    <div x-show="step === 1" x-transition>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name of celebrant</label>
                                <input type="text" x-model="form.celebrantName" placeholder="e.g. Sandra"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-rose-400 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">What are you celebrating?</label>
                                <select x-model="form.eventType"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-rose-400 bg-white">
                                    <option value="">Select an occasion</option>
                                    <option value="birthday">Birthday</option>
                                    <option value="wedding">Wedding</option>
                                    <option value="graduation">Graduation</option>
                                    <option value="anniversary">Anniversary</option>
                                    <option value="baby_shower">Baby Shower</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">When is it?</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="text" x-model="form.startDate" placeholder="Start date"
                                        class="datepicker w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-rose-400">
                                    <input type="text" x-model="form.endDate" placeholder="End date"
                                        class="datepicker w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-rose-400">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Page title</label>
                                <input type="text" x-model="form.eventTitle" placeholder="Auto-generated from name &amp; type"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-rose-400">
                            </div>
                        </div>
                        <button type="submit"
                            class="mt-6 w-full bg-rose-500 hover:bg-rose-600 text-white py-3.5 rounded-xl font-semibold text-sm transition">
                            Continue
                        </button>
                    </div>
                    <div x-show="step === 2" x-transition>
                        <p class="text-sm text-gray-600 mb-4">Almost there — sign into your account to publish.</p>
                        <div class="space-y-4">
                            <input type="email" x-model="auth.email" placeholder="Email address"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-rose-400">
                            <input type="password" x-model="auth.password" placeholder="Password"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-rose-400">
                        </div>
                        <button type="button" @click="submitForm"
                            class="mt-6 w-full bg-rose-500 hover:bg-rose-600 text-white py-3.5 rounded-xl font-semibold text-sm transition">
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
            <div class="absolute top-4 right-4">
                <button x-on:click="$dispatch('close')"
                    class="rounded-full h-10 w-10 flex items-center justify-center bg-gray-100 hover:bg-gray-200 transition">
                    <i class="mdi mdi-close text-gray-600"></i>
                </button>
            </div>
            <h2 class="text-xl font-bold text-gray-900">Request withdrawal</h2>
            <p class="mt-1 text-sm text-gray-500">
                Balance: <strong>{{ $currencySymbol }}{{ number_format($walletDisplay, 2) }}</strong>
            </p>

            <form method="POST" action="{{ route('withdrawals.store') }}" class="mt-6">
                @csrf
                <div style="margin-bottom:1.1rem">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Amount to withdraw ({{ $userCurrency }})
                    </label>
                    <input type="number"
                           name="amount"
                           placeholder="e.g. 5000"
                           min="100"
                           step="1"
                           value="{{ old('amount') }}"
                           required
                           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-rose-400">
                    <p class="text-xs text-gray-400 mt-1">Minimum {{ $currencySymbol }}100</p>
                </div>
                <div style="margin-bottom:1.5rem">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Send to</label>
                    <select name="bank_account_id" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-rose-400 bg-white">
                        <option value="">Select bank account</option>
                        @foreach ($bankAccounts as $ba)
                            <option value="{{ $ba->id }}"
                                {{ $ba->is_default ? 'selected' : '' }}>
                                {{ $ba->bank_name }} — {{ $ba->account_number }}{{ $ba->is_default ? ' (default)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="w-full bg-rose-600 hover:bg-rose-700 text-white py-3.5 rounded-xl font-semibold text-sm transition flex items-center justify-center gap-2">
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
            <div class="absolute top-4 right-4">
                <button x-on:click="$dispatch('close')"
                    class="rounded-full h-10 w-10 flex items-center justify-center bg-gray-100 hover:bg-gray-200 transition">
                    <i class="mdi mdi-close text-gray-600"></i>
                </button>
            </div>
            <h2 class="text-xl font-bold text-gray-900">Add bank account</h2>
            <p class="mt-1 text-sm text-gray-500">Details must match your bank records exactly.</p>

            <form method="POST" action="{{ route('bank-accounts.store') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bank name</label>
                    <select name="bank_name" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-rose-400 bg-white">
                        <option value="">Select your bank</option>
                        @foreach ([
                            'Access Bank','First Bank','GT Bank','UBA','Zenith Bank',
                            'Fidelity Bank','Sterling Bank','Stanbic IBTC','FCMB',
                            'Polaris Bank','Wema Bank','Union Bank',
                            'Opay','Palmpay','Kuda Bank','Moniepoint','Carbon','VBank'
                        ] as $bankName)
                            <option value="{{ $bankName }}">{{ $bankName }}</option>
                        @endforeach
                    </select>
                    @error('bank_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account number</label>
                    <input type="text" name="account_number"
                           placeholder="10-digit NUBAN" maxlength="10" inputmode="numeric"
                           pattern="[0-9]{10}" value="{{ old('account_number') }}" required
                           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-rose-400">
                    @error('account_number')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account holder name</label>
                    <input type="text" name="account_name"
                           placeholder="Exactly as on your bank records"
                           value="{{ old('account_name') }}" required
                           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-rose-400">
                    <p class="text-xs text-gray-400 mt-1">Must match exactly to avoid failed transfers.</p>
                    @error('account_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit"
                    class="w-full bg-rose-600 hover:bg-rose-700 text-white py-3.5 rounded-xl font-semibold text-sm transition flex items-center justify-center gap-2">
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
            <div class="absolute top-4 right-4">
                <button x-on:click="$dispatch('close')"
                    class="rounded-full h-10 w-10 flex items-center justify-center bg-gray-100 hover:bg-gray-200 transition">
                    <i class="mdi mdi-close text-gray-600"></i>
                </button>
            </div>
            <h2 class="text-xl font-bold text-gray-900">Edit bank account</h2>
            <p class="mt-1 text-sm text-gray-500">Update your saved bank details.</p>

            <form method="POST"
                  :action="'/bank-accounts/' + editingBank.id"
                  class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bank name</label>
                    <select name="bank_name" x-model="editingBank.bank_name" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-rose-400 bg-white">
                        <option value="">Select your bank</option>
                        @foreach ([
                            'Access Bank','First Bank','GT Bank','UBA','Zenith Bank',
                            'Fidelity Bank','Sterling Bank','Stanbic IBTC','FCMB',
                            'Polaris Bank','Wema Bank','Union Bank',
                            'Opay','Palmpay','Kuda Bank','Moniepoint','Carbon','VBank'
                        ] as $bankName)
                            <option value="{{ $bankName }}">{{ $bankName }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account number</label>
                    <input type="text" name="account_number"
                           x-model="editingBank.account_number"
                           placeholder="10-digit NUBAN" maxlength="10" inputmode="numeric"
                           pattern="[0-9]{10}" required
                           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-rose-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account holder name</label>
                    <input type="text" name="account_name"
                           x-model="editingBank.account_name"
                           placeholder="Exactly as on your bank records" required
                           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-rose-400">
                </div>
                <button type="submit"
                    class="w-full bg-rose-600 hover:bg-rose-700 text-white py-3.5 rounded-xl font-semibold text-sm transition flex items-center justify-center gap-2">
                    <i class="mdi mdi-content-save-outline"></i> Save changes
                </button>
            </form>
        </div>
    </x-modal>

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- MODAL: Fund Wallet                             --}}
    {{-- ═══════════════════════════════════════════════ --}}
    <x-modal name="fund-wallet" maxWidth="md" focusable>
        <div class="p-6 relative" x-data="walletFundForm()">
            <div class="absolute top-4 right-4">
                <button x-on:click="$dispatch('close')"
                    class="rounded-full h-10 w-10 flex items-center justify-center bg-gray-100 hover:bg-gray-200 transition">
                    <i class="mdi mdi-close text-gray-600"></i>
                </button>
            </div>
            <h2 class="text-xl font-bold text-gray-900">Fund Wallet</h2>
            <p class="mt-1 text-sm text-gray-500">
                Current balance: <strong>{{ $currencySymbol }}{{ number_format($walletDisplay, 2) }}</strong>
            </p>

            <div x-show="error" x-cloak class="mt-4 flash-err" style="border-radius:0.6rem;padding:0.75rem 1rem">
                <i class="mdi mdi-alert-circle"></i> <span x-text="error"></span>
            </div>

            <div class="mt-6" style="margin-bottom:1.1rem">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Amount ({{ $userCurrency }})
                </label>
                <input
                    type="number"
                    x-model="amount"
                    min="{{ $userCurrency === 'NGN' ? 100 : 1 }}"
                    step="any"
                    placeholder="{{ $userCurrency === 'NGN' ? 'e.g. 5000' : 'e.g. 10.00' }}"
                    :disabled="loading"
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-rose-400"
                >
                <p class="text-xs text-gray-400 mt-1">
                    Minimum {{ $currencySymbol }}{{ $userCurrency === 'NGN' ? '100' : '1.00' }}
                </p>
            </div>

            <button
                @click="submit()"
                :disabled="loading || !amount"
                class="w-full bg-rose-600 hover:bg-rose-700 text-white py-3.5 rounded-xl font-semibold text-sm transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span x-show="!loading"><i class="mdi mdi-credit-card-outline"></i> Proceed to Payment</span>
                <span x-show="loading" x-cloak><i class="mdi mdi-loading mdi-spin"></i> Redirecting...</span>
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
        style="position:fixed;inset:0;z-index:200;display:flex;flex-direction:column;background:#0f0d0a"
    >
        {{-- Top bar --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 2rem;border-bottom:1px solid rgba(255,255,255,0.08);flex-shrink:0">
            <div style="display:flex;align-items:center;gap:1rem">
                <div style="width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,#7c3aed,#a855f7);display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff">
                    <i class="mdi mdi-upload"></i>
                </div>
                <div>
                    <p style="font-family:'DM Serif Display',serif;font-size:1.15rem;color:#fff;line-height:1.2">Bulk Upload Celebrants</p>
                    <p style="font-size:0.75rem;color:rgba(255,255,255,0.45);margin-top:0.1rem" x-text="step === 'preview' ? previewRows.length + ' rows parsed — review before importing' : 'Upload an Excel file (.xlsx / .xls)'"></p>
                </div>
            </div>
            <button @click="bulkUploadOpen = false; reset()"
                style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,0.08);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.7);font-size:1.1rem;transition:background 0.15s"
                onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='rgba(255,255,255,0.08)'">
                <i class="mdi mdi-close"></i>
            </button>
        </div>

        {{-- Body --}}
        <div style="flex:1;display:flex;overflow:hidden">

            {{-- LEFT: Form panel (1/4) --}}
            <div style="width:25%;min-width:280px;border-right:1px solid rgba(255,255,255,0.08);padding:2rem 1.75rem;overflow-y:auto;display:flex;flex-direction:column;gap:1.5rem">

                {{-- File drop zone --}}
                <div>
                    <p style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.4);margin-bottom:0.75rem">Excel file</p>
                    <label
                        for="bu-file"
                        @dragover.prevent="dragging = true"
                        @dragleave="dragging = false"
                        @drop.prevent="dragging = false; handleFileDrop($event)"
                        :style="dragging ? 'border-color:#a855f7;background:rgba(168,85,247,0.1)' : ''"
                        style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.6rem;border:2px dashed rgba(255,255,255,0.15);border-radius:16px;padding:2rem 1rem;cursor:pointer;transition:all 0.2s;text-align:center"
                    >
                        <i class="mdi mdi-file-excel-outline" style="font-size:2.5rem;color:#a855f7"></i>
                        <span style="font-size:0.82rem;font-weight:600;color:rgba(255,255,255,0.7)" x-text="fileName || 'Drop file here or click to browse'"></span>
                        <span style="font-size:0.72rem;color:rgba(255,255,255,0.35)">.xlsx or .xls only</span>
                    </label>
                    <input id="bu-file" type="file" accept=".xlsx,.xls" style="display:none" @change="handleFileSelect($event)">
                </div>

                {{-- Options --}}
                <div style="display:flex;flex-direction:column;gap:0.85rem">
                    <p style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.4)">Options</p>
                    <label style="display:flex;align-items:center;gap:0.75rem;cursor:pointer">
                        <div style="position:relative;width:42px;height:24px;flex-shrink:0"
                             @click="sendEmail = !sendEmail">
                            <div :style="sendEmail ? 'background:#7c3aed' : 'background:rgba(255,255,255,0.12)'"
                                 style="position:absolute;inset:0;border-radius:99px;transition:background 0.2s"></div>
                            <div :style="sendEmail ? 'transform:translateX(18px)' : 'transform:translateX(2px)'"
                                 style="position:absolute;top:2px;width:20px;height:20px;background:#fff;border-radius:50%;transition:transform 0.2s;box-shadow:0 1px 4px rgba(0,0,0,0.3)"></div>
                        </div>
                        <span style="font-size:0.85rem;color:rgba(255,255,255,0.7)">Send email summary when done</span>
                    </label>
                </div>

                {{-- Expected columns --}}
                <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.1rem">
                    <p style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.35);margin-bottom:0.7rem">Required columns</p>
                    <div style="display:flex;flex-direction:column;gap:0.35rem">
                        @foreach(['Organisation UUID','First Name','Last Name','Email','Celebration Type','Celebration Date'] as $col)
                            <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.78rem;color:rgba(255,255,255,0.55)">
                                <i class="mdi mdi-check-circle" style="color:#a855f7;font-size:0.85rem"></i> {{ $col }}
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
                <div x-show="error" x-cloak style="background:rgba(225,29,72,0.15);border:1px solid rgba(225,29,72,0.3);border-radius:12px;padding:0.9rem;font-size:0.82rem;color:#fca5a5;display:flex;align-items:flex-start;gap:0.5rem">
                    <i class="mdi mdi-alert-circle" style="margin-top:0.1rem;flex-shrink:0"></i>
                    <span x-text="error"></span>
                </div>

                {{-- Action buttons --}}
                <div style="margin-top:auto;display:flex;flex-direction:column;gap:0.75rem">
                    <button
                        x-show="step === 'upload'"
                        @click="loadPreview()"
                        :disabled="!file || loading"
                        style="width:100%;padding:0.85rem;border-radius:12px;border:none;font-size:0.9rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.5rem;transition:all 0.15s"
                        :style="!file || loading ? 'background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.35);cursor:not-allowed' : 'background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;box-shadow:0 4px 16px rgba(124,58,237,0.4)'"
                    >
                        <i class="mdi" :class="loading ? 'mdi-loading mdi-spin' : 'mdi-eye-outline'"></i>
                        <span x-text="loading ? 'Parsing...' : 'Preview data'"></span>
                    </button>

                    <template x-if="step === 'preview'">
                        <div style="display:flex;flex-direction:column;gap:0.6rem">
                            <button
                                @click="confirmImport()"
                                :disabled="importing || okCount === 0"
                                style="width:100%;padding:0.85rem;border-radius:12px;border:none;font-size:0.9rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.5rem;transition:all 0.15s"
                                :style="importing || okCount === 0 ? 'background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.35);cursor:not-allowed' : 'background:linear-gradient(135deg,#059669,#10b981);color:#fff;box-shadow:0 4px 16px rgba(5,150,105,0.4)'"
                            >
                                <i class="mdi" :class="importing ? 'mdi-loading mdi-spin' : 'mdi-check-bold'"></i>
                                <span x-text="importing ? 'Importing...' : 'Confirm import (' + okCount + ' valid rows)'"></span>
                            </button>
                            <button
                                @click="step = 'upload'; previewRows = []; previewHeaders = []"
                                style="width:100%;padding:0.75rem;border-radius:12px;border:1px solid rgba(255,255,255,0.15);background:none;color:rgba(255,255,255,0.6);font-size:0.85rem;font-weight:600;cursor:pointer;transition:all 0.15s"
                                onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='none'">
                                <i class="mdi mdi-arrow-left"></i> Change file
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- RIGHT: Preview panel (3/4) --}}
            <div style="flex:1;overflow:hidden;display:flex;flex-direction:column">

                {{-- Upload placeholder --}}
                <div x-show="step === 'upload'" style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:1.25rem;color:rgba(255,255,255,0.2)">
                    <i class="mdi mdi-table-large" style="font-size:5rem"></i>
                    <p style="font-size:1rem;font-weight:600">Preview will appear here</p>
                    <p style="font-size:0.85rem">Select an Excel file and click "Preview data"</p>
                </div>

                {{-- Preview table --}}
                <div x-show="step === 'preview'" x-cloak style="flex:1;overflow:auto;padding:1.5rem 2rem">
                    {{-- Summary chips --}}
                    <div style="display:flex;gap:0.75rem;margin-bottom:1.25rem;flex-wrap:wrap">
                        <div style="display:flex;align-items:center;gap:0.5rem;background:rgba(5,150,105,0.15);border:1px solid rgba(5,150,105,0.3);border-radius:99px;padding:0.35rem 0.9rem;font-size:0.78rem;font-weight:700;color:#34d399">
                            <i class="mdi mdi-check-circle"></i>
                            <span x-text="okCount + ' valid'"></span>
                        </div>
                        <div x-show="errCount > 0" style="display:flex;align-items:center;gap:0.5rem;background:rgba(225,29,72,0.15);border:1px solid rgba(225,29,72,0.3);border-radius:99px;padding:0.35rem 0.9rem;font-size:0.78rem;font-weight:700;color:#fca5a5">
                            <i class="mdi mdi-alert-circle"></i>
                            <span x-text="errCount + ' with errors (will be skipped)'"></span>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.5rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:99px;padding:0.35rem 0.9rem;font-size:0.78rem;font-weight:600;color:rgba(255,255,255,0.5)">
                            <i class="mdi mdi-table-row"></i>
                            <span x-text="previewRows.length + ' total rows'"></span>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div style="border:1px solid rgba(255,255,255,0.08);border-radius:14px;overflow:hidden">
                        <table style="width:100%;border-collapse:collapse;font-size:0.8rem">
                            <thead>
                                <tr style="background:rgba(255,255,255,0.06)">
                                    <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:rgba(255,255,255,0.4);font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;white-space:nowrap;border-bottom:1px solid rgba(255,255,255,0.08)">#</th>
                                    <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:rgba(255,255,255,0.4);font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;white-space:nowrap;border-bottom:1px solid rgba(255,255,255,0.08)">Status</th>
                                    <template x-for="h in previewHeaders" :key="h">
                                        <th x-text="h" style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:rgba(255,255,255,0.4);font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;white-space:nowrap;border-bottom:1px solid rgba(255,255,255,0.08)"></th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, idx) in previewRows" :key="idx">
                                    <tr :style="row._status === 'error' ? 'background:rgba(225,29,72,0.06)' : (idx % 2 === 0 ? '' : 'background:rgba(255,255,255,0.02)')"
                                        style="border-bottom:1px solid rgba(255,255,255,0.05);transition:background 0.1s"
                                        onmouseover="this.style.background='rgba(168,85,247,0.08)'" onmouseout="this.style.background=''">
                                        <td x-text="row._row" style="padding:0.7rem 1rem;color:rgba(255,255,255,0.3);white-space:nowrap"></td>
                                        <td style="padding:0.7rem 1rem;white-space:nowrap">
                                            <template x-if="row._status === 'ok'">
                                                <span style="display:inline-flex;align-items:center;gap:0.3rem;font-size:0.7rem;font-weight:700;color:#34d399;background:rgba(5,150,105,0.15);border:1px solid rgba(5,150,105,0.25);border-radius:99px;padding:0.15rem 0.6rem">
                                                    <i class="mdi mdi-check"></i> Valid
                                                </span>
                                            </template>
                                            <template x-if="row._status === 'error'">
                                                <span :title="'Missing: ' + row._errors.join(', ')" style="display:inline-flex;align-items:center;gap:0.3rem;font-size:0.7rem;font-weight:700;color:#fca5a5;background:rgba(225,29,72,0.15);border:1px solid rgba(225,29,72,0.25);border-radius:99px;padding:0.15rem 0.6rem;cursor:help">
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
                    <div style="width:80px;height:80px;border-radius:50%;background:rgba(5,150,105,0.2);display:flex;align-items:center;justify-content:center">
                        <i class="mdi mdi-check-bold" style="font-size:2.5rem;color:#34d399"></i>
                    </div>
                    <p style="font-family:'DM Serif Display',serif;font-size:1.5rem;color:#fff" x-text="doneMsg"></p>
                    <button @click="bulkUploadOpen = false; reset()"
                        style="padding:0.75rem 2rem;border-radius:99px;border:none;background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;font-size:0.9rem;font-weight:700;cursor:pointer">
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
                        body: JSON.stringify({ amount: amt }),
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
