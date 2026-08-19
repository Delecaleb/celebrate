<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in — CelebrateMi</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <x-brand-tokens />

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        /* Local names → the brand palette. Colours live in resources/brand.json;
           --muted comes straight from the brand-tokens component above. */
        :root {
            --off:    var(--surface-2);
            --white:  var(--surface);
            --dark:   var(--ink);
            --accent: var(--primary);
            --border: var(--line);
        }

        html, body { height: 100%; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--off);
            color: var(--dark);
            display: flex;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* ── LEFT PANEL ─────────────────────────────── */
        .panel-left {
            width: 100%;
            max-width: 520px;
            display: flex;
            flex-direction: column;
            padding: 2.5rem 3rem;
            background: var(--off);
        }

        .brand {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 1.4rem;
            color: var(--dark);
            text-decoration: none;
            letter-spacing: -0.01em;
        }

        .form-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 0 2rem;
        }

        .form-eyebrow {
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 0.75rem;
        }

        h1 {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 2.6rem;
            line-height: 1.08;
            letter-spacing: -0.025em;
            margin-bottom: 0.6rem;
        }

        .form-sub {
            font-size: 0.95rem;
            color: var(--muted);
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        /* ── FIELDS ─────────────────────────────────── */
        .field { margin-bottom: 1.25rem; }

        .field-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.45rem;
        }

        .field label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.45rem;
        }

        .field-header label { margin-bottom: 0; }

        .forgot-link {
            font-size: 0.78rem;
            color: var(--muted);
            text-decoration: none;
            transition: color 0.15s;
        }
        .forgot-link:hover { color: var(--accent); }

        .field input[type="email"],
        .field input[type="password"],
        .field input[type="text"] {
            width: 100%;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 0.82rem 1rem;
            font-size: 0.9rem;
            font-family: inherit;
            color: var(--dark);
            background: var(--white);
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .field input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(200, 68, 15, 0.1);
        }
        .field input::placeholder { color: #bbb5af; }

        .error-text {
            color: #dc2626;
            font-size: 0.78rem;
            margin-top: 0.35rem;
        }

        /* ── REMEMBER ME ────────────────────────────── */
        .remember {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.75rem;
        }
        .remember input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--accent);
            cursor: pointer;
        }
        .remember label {
            font-size: 0.85rem;
            color: var(--muted);
            cursor: pointer;
            user-select: none;
        }

        /* ── SUBMIT ─────────────────────────────────── */
        .btn-submit {
            width: 100%;
            padding: 0.9rem;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 99px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.18s;
            letter-spacing: 0.01em;
        }
        .btn-submit:hover { background: #a8380c; }

        .form-switch {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
            color: var(--muted);
        }
        .form-switch a {
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
        }
        .form-switch a:hover { text-decoration: underline; }

        /* ── DIVIDER ─────────────────────────────────── */
        .divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.75rem 0;
            color: var(--muted);
            font-size: 0.78rem;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* ── FOOTER ─────────────────────────────────── */
        .panel-footer {
            font-size: 0.75rem;
            color: var(--muted);
            opacity: 0.55;
            text-align: center;
        }

        /* ── RIGHT PHOTO PANEL ──────────────────────── */
        .panel-right {
            flex: 1;
            position: relative;
            overflow: hidden;
            display: flex;
        }

        .panel-right img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .panel-right::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to top,
                rgba(var(--ink-rgb) / 0.78) 0%,
                rgba(var(--ink-rgb) / 0.15) 55%,
                transparent 100%
            );
        }

        .photo-quote {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 10;
            padding: 3rem;
            color: #fff;
        }

        .photo-quote blockquote {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 1.55rem;
            line-height: 1.4;
            margin-bottom: 1rem;
            max-width: 440px;
        }

        .photo-quote blockquote::before { content: '\201C'; }
        .photo-quote blockquote::after  { content: '\201D'; }

        .photo-quote-meta {
            font-size: 0.82rem;
            opacity: 0.65;
        }

        /* ── RESPONSIVE ──────────────────────────────── */
        @media (max-width: 860px) {
            .panel-right { display: none; }
            .panel-left {
                max-width: 100%;
                min-height: 100vh;
                padding: 2rem 1.75rem;
            }
        }
    </style>
</head>
<body>

    <!-- Left: form -->
    <div class="panel-left">

        <a href="{{ url('/') }}" class="brand">CelebrateMi</a>

        <div class="form-area">

            <p class="form-eyebrow">Welcome back</p>
            <h1>Sign in to<br>your account</h1>
            <p class="form-sub">Pick up where you left off.</p>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="field">
                    <label for="email">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="you@example.com"
                        required
                        autofocus
                        autocomplete="username"
                    >
                    @error('email')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <div class="field-header">
                        <label for="password">Password</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
                        @endif
                    </div>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                    @error('password')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="remember">
                    <input type="checkbox" id="remember_me" name="remember">
                    <label for="remember_me">Keep me signed in</label>
                </div>

                <button type="submit" class="btn-submit">Sign in →</button>
            </form>

            <p class="form-switch">
                No account yet?
                @if (Route::has('register'))
                    <a href="{{ route('register') }}">Create one free →</a>
                @endif
            </p>

        </div>

        <p class="panel-footer">&copy; {{ date('Y') }} CelebrateMi &mdash; Making celebrations unforgettable</p>

    </div>

    <!-- Right: photo -->
    <div class="panel-right">
        <img
            src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=1000&h=1400&fit=crop&q=80"
            alt="Birthday celebration"
            loading="eager"
        >
        <div class="photo-quote">
            <blockquote>
                Every moment worth celebrating deserves a page of its own.
            </blockquote>
            <p class="photo-quote-meta">Joined by 2,400+ families this month</p>
        </div>
    </div>

</body>
</html>
