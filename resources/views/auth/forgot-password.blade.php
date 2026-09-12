<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Auth screens stay out of the index; the meta component still gives
         them icons, theme colour and a card if the link is ever shared. --}}
    <x-seo title="Reset your password — CelebrateMi" noindex />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- The landing page's type system, so signing in doesn't feel like a
         different product: Outfit for display, Plus Jakarta Sans for text. --}}
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
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
            font-family: 'Outfit', system-ui, sans-serif;
            font-weight: 700;
            font-size: 1.4rem;
            color: var(--dark);
            text-decoration: none;
            /* the wordmark's tracking on the marketing nav */
            letter-spacing: -0.045em;
        }

        .form-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 0 2rem;
        }

        h1 {
            font-family: 'Outfit', system-ui, sans-serif;
            font-weight: 700;
            font-size: 2.6rem;
            /* Outfit sets tighter and rides higher than a serif at the same
               size, so these match the marketing headings rather than the
               values the old serif needed. */
            line-height: 1.04;
            letter-spacing: -0.035em;
            margin-bottom: 0.6rem;
        }

        .form-sub {
            font-size: 0.95rem;
            color: var(--muted);
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        /* ── SENT CONFIRMATION ──────────────────────── */
        /* The reset link is sent by email, so this banner is the only thing the
           page can show for it — it gets more weight than a line of green text. */
        .status-note {
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
            margin-bottom: 1.75rem;
            padding: 0.95rem 1.1rem;
            background: var(--ok-l);
            border: 1px solid var(--ok);
            border-radius: 12px;
            color: var(--ok);
        }

        .status-note .status-icon {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--ok);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 1;
        }

        .status-note p {
            font-size: 0.86rem;
            font-weight: 500;
            line-height: 1.55;
        }

        .status-note .status-hint {
            display: block;
            margin-top: 0.3rem;
            font-weight: 400;
            opacity: 0.8;
        }

        /* ── FIELDS ─────────────────────────────────── */
        .field { margin-bottom: 1.25rem; }

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

        .field-hint {
            margin-top: 0.45rem;
            font-size: 0.78rem;
            color: var(--muted);
            opacity: 0.85;
        }

        .error-text {
            color: #dc2626;
            font-size: 0.78rem;
            margin-top: 0.35rem;
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
            font-family: 'Outfit', system-ui, sans-serif;
            font-weight: 500;
            font-size: 1.55rem;
            line-height: 1.4;
            letter-spacing: -0.025em;
            margin-bottom: 1rem;
            max-width: 440px;
        }

        .photo-quote blockquote::before { content: '\201C'; }
        .photo-quote blockquote::after  { content: '\201D'; }

        .photo-quote-by {
            margin-bottom: 1.1rem;
            font-size: 0.85rem;
            font-weight: 600;
            opacity: 0.78;
        }

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

            <h1>Reset your<br>password</h1>
            <p class="form-sub">
                Tell us the email on your account and we'll send you a link to set a new one.
            </p>

            @if (session('status'))
                <div class="status-note" role="status">
                    <span class="status-icon" aria-hidden="true">&check;</span>
                    <p>
                        {{ session('status') }}
                        <span class="status-hint">
                            It should arrive within a minute. Check your spam folder if it doesn't.
                        </span>
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
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
                    <p class="field-hint">The link expires in 60 minutes.</p>
                </div>

                <button type="submit" class="btn-submit">Email me a reset link →</button>
            </form>

            <p class="form-switch">
                Remembered it?
                <a href="{{ route('login') }}">Back to sign in →</a>
            </p>

        </div>

        <p class="panel-footer">&copy; {{ date('Y') }} CelebrateMi &mdash; Making celebrations unforgettable</p>

    </div>

    <!-- Right: photo -->
    {{-- One of the real pages on /stories, same as the register panel. The
         photo is that story's own cover. --}}
    @php $voice = app(\App\Support\StoryLibrary::class)->find('folake-at-50'); @endphp

    <div class="panel-right">
        <img
            src="{{ asset('images/hero/hero-short.webp') }}"
            alt="A woman at her baby shower, friends gathered around her chair"
            loading="eager" decoding="async"
        >

        <div class="photo-quote">
            <blockquote>
                I woke up on my birthday to three hundred messages from people I had not
                heard from in twenty years.
            </blockquote>
            
            <p class="photo-quote-meta">Your page is still there. Let's get you back in.</p>
        </div>
    </div>

</body>
</html>
