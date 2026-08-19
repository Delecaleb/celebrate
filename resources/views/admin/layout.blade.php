<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">

    <x-brand-tokens />

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        /* Local names → the brand palette. Colours live in resources/brand.json;
           --muted comes straight from the brand-tokens component above. */
        :root {
            --off:     var(--surface-2);
            --white:   var(--surface);
            --dark:    var(--ink);
            --accent:  var(--primary);
            --border:  var(--line);
        }

        html, body { height: 100%; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--off);
            color: var(--dark);
            -webkit-font-smoothing: antialiased;
        }

        /* ── SIDEBAR ────────────────────────────────── */
        .admin-shell {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 260px;
            background: var(--white);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 1.5rem 0;
            overflow-y: auto;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 1.5rem;
        }

        .sidebar-brand-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .sidebar-brand-text {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
        }

        .sidebar-nav {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            padding: 0 1rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--muted);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.15s;
            font-size: 0.9rem;
        }

        .nav-item:hover {
            background: var(--off);
            color: var(--dark);
        }

        .nav-item.active {
            background: var(--off);
            color: var(--accent);
            font-weight: 600;
        }

        .nav-icon {
            width: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .sidebar-footer {
            border-top: 1px solid var(--border);
            padding-top: 1rem;
            margin-top: auto;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            color: var(--muted);
            font-size: 0.9rem;
            cursor: pointer;
            transition: color 0.15s;
            text-align: left;
            font-family: inherit;
        }

        .logout-btn:hover {
            color: #dc2626;
        }

        /* ── MAIN CONTENT ──────────────────────────── */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .topbar-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--dark);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .topbar-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.5rem 0.9rem;
            background: var(--off);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.15s;
            cursor: pointer;
        }

        .topbar-btn:hover {
            background: var(--white);
            color: var(--dark);
            border-color: var(--accent);
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* ── CONTENT AREA ──────────────────────────── */
        .content {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
        }

        /* ── STATS GRID ────────────────────────────── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.15s;
        }

        .stat-card:hover {
            border-color: var(--accent);
            box-shadow: 0 4px 12px rgba(200,68,15,0.08);
        }

        .stat-card.accent-card {
            background: var(--primary-50);
            border-color: var(--accent);
        }

        .stat-icon {
            font-size: 2rem;
            color: var(--accent);
            margin-bottom: 0.75rem;
        }

        .stat-label {
            font-size: 0.82rem;
            color: var(--muted);
            margin-bottom: 0.3rem;
        }

        .stat-val {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
        }

        /* ── TABLE CARD ────────────────────────────── */
        .table-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .table-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .table-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-card-link {
            color: var(--accent);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: opacity 0.15s;
        }

        .table-card-link:hover {
            opacity: 0.8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            background: var(--off);
        }

        th {
            padding: 0.9rem 1.5rem;
            text-align: left;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        td {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
            font-size: 0.9rem;
        }

        tbody tr:hover {
            background: rgba(200,68,15,0.02);
        }

        /* ── USER CHIP ──────────────────────────────– */
        .user-chip {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .chip-avatar {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.85rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .chip-name {
            font-weight: 600;
            color: var(--dark);
        }

        .chip-email {
            font-size: 0.8rem;
            color: var(--muted);
        }

        /* ── FILTERS ────────────────────────────────– */
        .filters-bar {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .filter-input,
        .filter-select {
            padding: 0.6rem 0.9rem;
            border: 1.5px solid var(--border);
            border-radius: 6px;
            font-size: 0.85rem;
            font-family: inherit;
            background: var(--white);
            color: var(--dark);
            transition: border-color 0.15s;
        }

        .filter-input:focus,
        .filter-select:focus {
            outline: none;
            border-color: var(--accent);
        }

        .btn-filter {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.6rem 1rem;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
            font-family: inherit;
        }

        .btn-filter:hover {
            background: #a8380c;
        }

        /* ── TABLE BUTTONS ─────────────────────────── */
        .tbl-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: var(--off);
            color: var(--muted);
            text-decoration: none;
            transition: all 0.15s;
            font-size: 1rem;
        }

        .tbl-btn:hover {
            background: var(--accent);
            color: white;
        }

        /* ── BADGES ────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-blue {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-gray {
            background: #f3f4f6;
            color: #374151;
        }

        .badge-green {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-red {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-yellow {
            background: #fef3c7;
            color: #92400e;
        }

        /* ── ALERTS ────────────────────────────────── */
        .alert {
            padding: 1rem 1.2rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
        }

        .alert-success {
            background: #ecfdf5;
            border: 1px solid #d1fae5;
            color: #065f46;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #991b1b;
        }

        .alert-warning {
            background: #fffbeb;
            border: 1px solid #fef3c7;
            color: #92400e;
        }

        .alert .mdi {
            font-size: 1.2rem;
            flex-shrink: 0;
            margin-top: 0.05rem;
        }

        /* ── RESPONSIVE ────────────────────────────– */
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .admin-shell {
                flex-direction: column;
            }

            .topbar {
                padding: 1rem;
            }

            .content {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--off);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--muted);
        }
    </style>
</head>
<body>

    <div class="admin-shell">

        {{-- SIDEBAR --}}
        <div class="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <i class="mdi mdi-shield-crown"></i>
                </div>
                <div class="sidebar-brand-text">Admin</div>
            </div>

            <nav class="sidebar-nav">
                <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <div class="nav-icon"><i class="mdi mdi-view-dashboard"></i></div>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">
                    <div class="nav-icon"><i class="mdi mdi-account-group"></i></div>
                    <span>Users</span>
                </a>
                <a href="{{ route('admin.events') }}" class="nav-item {{ request()->routeIs('admin.events') ? 'active' : '' }}">
                    <div class="nav-icon"><i class="mdi mdi-calendar-star"></i></div>
                    <span>Events</span>
                </a>
                <a href="{{ route('admin.withdrawals') }}" class="nav-item {{ request()->routeIs('admin.withdrawals') ? 'active' : '' }}">
                    <div class="nav-icon"><i class="mdi mdi-bank-transfer-out"></i></div>
                    <span>Withdrawals</span>
                </a>
                <a href="{{ route('admin.frames') }}" class="nav-item {{ request()->routeIs('admin.frames') ? 'active' : '' }}">
                    <div class="nav-icon"><i class="mdi mdi-image-frame"></i></div>
                    <span>Frames</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <form action="{{ route('admin.logout') }}" method="POST" style="width: 100%;">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <i class="mdi mdi-logout"></i> Sign out
                    </button>
                </form>
            </div>
        </div>

        {{-- MAIN CONTENT --}}
        <div class="main-content">

            {{-- TOPBAR --}}
            <div class="topbar">
                <div class="topbar-left">
                    <h2 class="topbar-title">@yield('topbar-title')</h2>
                </div>
                <div class="topbar-right">
                    <div class="topbar-actions">
                        @yield('topbar-actions')
                    </div>
                </div>
            </div>

            {{-- CONTENT --}}
            <div class="content">

                {{-- Alerts --}}
                @if (session('success'))
                    <div class="alert alert-success">
                        <i class="mdi mdi-check-circle"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-error">
                        <i class="mdi mdi-alert-circle"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')

            </div>

        </div>

    </div>

    {{-- Scripts --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

</body>
</html>
