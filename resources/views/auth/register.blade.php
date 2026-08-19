<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create account — CelebrateMi</title>

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
            overflow-y: auto;
        }

        .brand {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 1.4rem;
            color: var(--dark);
            text-decoration: none;
            letter-spacing: -0.01em;
            flex-shrink: 0;
        }

        .form-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2.5rem 0 2rem;
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
            margin-bottom: 2.25rem;
            line-height: 1.6;
        }

        .form-sub strong {
            color: var(--dark);
            font-weight: 600;
        }

        /* ── FIELDS ─────────────────────────────────── */
        .field { margin-bottom: 1.1rem; }

        .field label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.45rem;
        }

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

        /* ── PERKS LIST ──────────────────────────────── */
        .perks {
            display: flex;
            gap: 1.25rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .perk {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.78rem;
            color: var(--muted);
        }

        .perk-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #10b981;
            flex-shrink: 0;
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
            margin-top: 1.5rem;
            transition: background 0.18s;
            letter-spacing: 0.01em;
        }
        .btn-submit:hover { background: #a8380c; }

        .terms-note {
            margin-top: 0.85rem;
            text-align: center;
            font-size: 0.75rem;
            color: var(--muted);
            opacity: 0.7;
            line-height: 1.5;
        }

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

        /* ── FOOTER ─────────────────────────────────── */
        .panel-footer {
            font-size: 0.75rem;
            color: var(--muted);
            opacity: 0.55;
            text-align: center;
            flex-shrink: 0;
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
                rgba(var(--ink-rgb) / 0.12) 55%,
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
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.82rem;
            opacity: 0.7;
        }

        .stat-bubble {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(255,255,255,0.18);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 99px;
            padding: 0.3rem 0.75rem;
            font-size: 0.78rem;
            font-weight: 500;
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

            <p class="form-eyebrow">Get started — it's free</p>
            <h1>Create your<br>account</h1>
            
            <div class="perks">
                <div class="perk"><span class="perk-dot"></span>Completely free</div>
                <div class="perk"><span class="perk-dot"></span>Set up in 60 seconds</div>
                <div class="perk"><span class="perk-dot"></span>Share with anyone</div>
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="field">
                    <label for="name">Your name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="e.g. Sandra Okafor"
                        required
                        autofocus
                        autocomplete="name"
                    >
                    @error('name')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="email">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="you@example.com"
                        required
                        autocomplete="username"
                    >
                    @error('email')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="At least 8 characters"
                        required
                        autocomplete="new-password"
                    >
                    @error('password')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Same password again"
                        required
                        autocomplete="new-password"
                    >
                    @error('password_confirmation')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-submit">Create my account →</button>

                <p class="terms-note">
                    By creating an account you agree to our terms of service.<br>
                    No spam, no hidden fees.
                </p>
            </form>

            <p class="form-switch">
                Already have an account?
                <a href="{{ route('login') }}">Sign in →</a>
            </p>

        </div>

        <p class="panel-footer">&copy; {{ date('Y') }} CelebrateMi &mdash; Making celebrations unforgettable</p>

    </div>

    <!-- Right: photo -->
    <div class="panel-right">
        <img
            src="https://images.unsplash.com/photo-1519671482749-fd09be7ccebf?w=1000&h=1400&fit=crop&q=80"
            alt="Friends celebrating together"
            loading="eager"
        >
        <div class="photo-quote">
            <blockquote>
                We used it for my mum's 60th. Everyone knew the plan, the photos were incredible.
            </blockquote>
            <div class="photo-quote-meta">
                <span class="stat-bubble">🎉 18,000+ events created</span>
                <span class="stat-bubble">⭐ 4.9 rating</span>
            </div>
        </div>
    </div>

</body>
</html>
