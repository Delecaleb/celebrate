<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CelebrateMi — For the moments that matter</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --white:    #ffffff;
            --off:      #faf8f5;
            --dark:     #1a1714;
            --muted:    #7a7065;
            --accent:   #c8440f;
            --accent-l: #f4ede8;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--white);
            color: var(--dark);
            -webkit-font-smoothing: antialiased;
        }

        img { display: block; width: 100%; height: 100%; object-fit: cover; }

        /* ── NAV ───────────────────────────────────── */
        nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem 3rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .brand {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 1.45rem;
            color: var(--dark);
            text-decoration: none;
            letter-spacing: -0.01em;
        }

        .nav-right { display: flex; align-items: center; gap: 0.5rem; }

        .nav-link {
            padding: 0.55rem 1.1rem;
            border-radius: 99px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--dark);
            text-decoration: none;
            transition: background 0.15s;
        }
        .nav-link:hover { background: #f0ede8; }

        .nav-btn {
            padding: 0.55rem 1.25rem;
            border-radius: 99px;
            font-size: 0.875rem;
            font-weight: 600;
            background: var(--dark);
            color: #fff;
            text-decoration: none;
            transition: opacity 0.15s;
        }
        .nav-btn:hover { opacity: 0.78; }

        /* ── HERO ──────────────────────────────────── */
        .hero {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 5rem 3rem 6rem;
        }

        .hero-left { display: flex; flex-direction: column; gap: 0; }

        .eyebrow {
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 1.5rem;
        }

        h1 {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: clamp(2.6rem, 4.5vw, 4rem);
            line-height: 1.1;
            letter-spacing: -0.02em;
            color: var(--dark);
            margin-bottom: 1.5rem;
        }

        h1 em {
            font-style: italic;
            color: var(--accent);
        }

        .hero-sub {
            font-size: 1.05rem;
            line-height: 1.75;
            color: var(--muted);
            max-width: 400px;
            margin-bottom: 2.5rem;
        }

        .hero-cta-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 3rem;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.85rem 1.85rem;
            background: var(--accent);
            color: #fff;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 99px;
            transition: background 0.18s;
        }
        .btn-primary:hover { background: #a8380c; }

        .btn-ghost {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--dark);
            text-decoration: none;
            border-bottom: 1.5px solid rgba(26,23,20,0.25);
            padding-bottom: 1px;
            transition: border-color 0.15s;
        }
        .btn-ghost:hover { border-color: var(--dark); }

        /* social proof avatars */
        .social-proof {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .avatars {
            display: flex;
        }

        .avatars img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 2.5px solid var(--white);
            object-fit: cover;
            display: inline-block;
            width: 34px;
            height: 34px;
        }
        .avatars img + img { margin-left: -10px; }

        .social-proof-text {
            font-size: 0.82rem;
            color: var(--muted);
            line-height: 1.4;
        }
        .social-proof-text strong { color: var(--dark); font-weight: 600; }

        /* hero photo side */
        .hero-photos {
            position: relative;
            height: 560px;
        }

        .photo-main {
            position: absolute;
            top: 0;
            right: 0;
            width: 78%;
            height: 100%;
            border-radius: 1.5rem;
            overflow: hidden;
        }

        .photo-small {
            position: absolute;
            bottom: 2.5rem;
            left: 0;
            width: 44%;
            height: 200px;
            border-radius: 1.25rem;
            overflow: hidden;
            border: 5px solid var(--white);
            box-shadow: 0 16px 48px rgba(0,0,0,0.14);
        }

        /* ── LOGOS / TRUST ──────────────────────────── */
        .trust-strip {
            background: var(--off);
            padding: 2.5rem 3rem;
            text-align: center;
        }

        .trust-strip p {
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 1.5rem;
        }

        .trust-logos {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2.5rem;
            flex-wrap: wrap;
        }

        .trust-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--muted);
        }
        .trust-pill .num {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 1.35rem;
            color: var(--dark);
        }

        /* ── MOMENTS GRID ───────────────────────────── */
        .moments {
            max-width: 1200px;
            margin: 0 auto;
            padding: 7rem 3rem;
        }

        .section-header {
            margin-bottom: 3.5rem;
        }

        .section-tag {
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 0.75rem;
        }

        .section-header h2 {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: clamp(2rem, 3.5vw, 3rem);
            letter-spacing: -0.02em;
            line-height: 1.1;
            max-width: 520px;
        }

        .moments-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr 1fr;
            grid-template-rows: 240px 240px;
            gap: 1.25rem;
        }

        .moment-card {
            border-radius: 1.25rem;
            overflow: hidden;
            position: relative;
            background: #e8e4de;
        }

        .moment-card.tall {
            grid-row: span 2;
        }

        .moment-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        .moment-card:hover img { transform: scale(1.03); }

        .moment-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.25rem;
            background: linear-gradient(to top, rgba(0,0,0,0.55) 0%, transparent 100%);
            color: #fff;
            font-size: 0.85rem;
            font-weight: 500;
            line-height: 1.4;
        }

        /* ── QUOTE ──────────────────────────────────── */
        .quote-section {
            background: var(--off);
            padding: 7rem 3rem;
            text-align: center;
        }

        .quote-inner {
            max-width: 680px;
            margin: 0 auto;
        }

        .quote-section blockquote {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: clamp(1.4rem, 2.5vw, 1.9rem);
            line-height: 1.45;
            letter-spacing: -0.01em;
            color: var(--dark);
            margin-bottom: 2rem;
        }

        .quote-section blockquote::before { content: '\201C'; }
        .quote-section blockquote::after  { content: '\201D'; }

        .quote-author {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .quote-author img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            display: inline-block;
            width: 44px;
            height: 44px;
        }

        .quote-author-info { text-align: left; }

        .author-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--dark);
        }

        .author-meta {
            font-size: 0.8rem;
            color: var(--muted);
        }

        /* ── HOW IT WORKS ───────────────────────────── */
        .how {
            max-width: 1200px;
            margin: 0 auto;
            padding: 7rem 3rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5rem;
            align-items: center;
        }

        .how-photo {
            border-radius: 1.5rem;
            overflow: hidden;
            height: 480px;
            background: #e8e4de;
        }

        .how-content { display: flex; flex-direction: column; gap: 2.5rem; }

        .how-content .section-header { margin-bottom: 0; }

        .step {
            display: flex;
            gap: 1.25rem;
            align-items: flex-start;
        }

        .step-num {
            flex-shrink: 0;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 50%;
            background: var(--accent-l);
            color: var(--accent);
            font-size: 0.85rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 2px;
        }

        .step-body h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .step-body p {
            font-size: 0.9rem;
            line-height: 1.68;
            color: var(--muted);
        }

        /* ── FINAL CTA ──────────────────────────────── */
        .final-cta {
            background: var(--dark);
            padding: 7rem 3rem;
            text-align: center;
        }

        .final-cta-photo-strip {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 3rem;
        }

        .strip-img {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            overflow: hidden;
            background: #333;
            border: 3px solid rgba(255,255,255,0.1);
        }
        .strip-img:nth-child(2) { margin-top: -10px; }
        .strip-img:nth-child(4) { margin-top: -14px; }

        .final-cta h2 {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: clamp(2rem, 4vw, 3.25rem);
            color: #fff;
            letter-spacing: -0.02em;
            line-height: 1.1;
            margin-bottom: 1rem;
            max-width: 560px;
            margin-left: auto;
            margin-right: auto;
        }

        .final-cta p {
            color: rgba(255,255,255,0.55);
            font-size: 1rem;
            margin-bottom: 2.5rem;
        }

        .btn-light {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.9rem 2rem;
            background: #fff;
            color: var(--dark);
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 99px;
            transition: opacity 0.15s;
        }
        .btn-light:hover { opacity: 0.88; }

        .final-note {
            margin-top: 1.25rem;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.35);
        }

        /* ── FOOTER ─────────────────────────────────── */
        footer {
            background: var(--dark);
            border-top: 1px solid rgba(255,255,255,0.07);
            padding: 2rem 3rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 100%;
        }

        footer .brand { color: rgba(255,255,255,0.5); font-size: 1.1rem; }

        footer p {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.25);
        }

        /* ── RESPONSIVE ──────────────────────────────── */
        @media (max-width: 900px) {
            nav { padding: 1.25rem 1.5rem; }

            .hero {
                grid-template-columns: 1fr;
                padding: 3.5rem 1.5rem 4rem;
                gap: 2.5rem;
            }

            .hero-sub { max-width: 100%; }

            .hero-photos { height: 320px; }

            .moments { padding: 4.5rem 1.5rem; }

            .moments-grid {
                grid-template-columns: 1fr 1fr;
                grid-template-rows: 200px 200px 200px;
            }
            .moment-card.tall { grid-row: span 1; }

            .how {
                grid-template-columns: 1fr;
                padding: 4.5rem 1.5rem;
                gap: 3rem;
            }

            .how-photo { height: 300px; }

            .quote-section { padding: 4.5rem 1.5rem; }
            .final-cta { padding: 4.5rem 1.5rem; }
            footer { flex-direction: column; gap: 0.75rem; text-align: center; padding: 2rem 1.5rem; }
        }

        @media (max-width: 560px) {
            .moments-grid {
                grid-template-columns: 1fr;
                grid-template-rows: auto;
            }
            .moment-card { height: 220px; }
            .moment-card.tall { height: 300px; }
            .trust-logos { gap: 1.5rem; }
            .final-cta-photo-strip .strip-img:nth-child(2),
            .final-cta-photo-strip .strip-img:nth-child(4) { margin-top: 0; }
        }
    </style>
</head>
<body>

    <!-- Nav -->
    <nav>
        <a href="/" class="brand">Celebrate</a>

        @if (Route::has('login'))
            <div class="nav-right">
                @auth
                    <a href="{{ url('/dashboard') }}" class="nav-btn">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="nav-link">Sign in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="nav-btn">Get started</a>
                    @endif
                @endauth
            </div>
        @endif
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-left">
            <p class="eyebrow">For the moments that matter</p>
            <h1>Life's too short to let the good stuff <em>go unnoticed</em></h1>
            <p class="hero-sub">
                Plan celebrations, invite the people you love, and hold onto the memories
                long after the candles have been blown out.
            </p>

            <div class="hero-cta-group">
                <button
                    x-data
                    x-on:click="$dispatch('open-modal', 'create-event')"
                    class="btn-primary"
                >
                    Create your event now →
                </button>
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="btn-ghost">Already have an account</a>
                @endif
            </div>

            <div class="social-proof">
                <div class="avatars">
                    <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=60&h=60&fit=crop&q=80" alt="User">
                    <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=60&h=60&fit=crop&q=80" alt="User">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=60&h=60&fit=crop&q=80" alt="User">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=60&h=60&fit=crop&q=80" alt="User">
                </div>
                <p class="social-proof-text">
                    <strong>2,400+ families</strong> celebrated something<br>meaningful this month
                </p>
            </div>
        </div>

        <div class="hero-photos">
            <div class="photo-main">
                <img
                    src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=800&h=1000&fit=crop&q=80"
                    alt="Birthday celebration"
                >
            </div>
            <div class="photo-small">
                <img
                    src="https://images.unsplash.com/photo-1527529482837-4698179dc6ce?w=400&h=300&fit=crop&q=80"
                    alt="Friends celebrating"
                >
            </div>
        </div>
    </section>

    <!-- Trust strip -->
    <div class="trust-strip">
        <p>At a glance</p>
        <div class="trust-logos">
            <div class="trust-pill"><span class="num">2.4k</span> families joined</div>
            <div class="trust-pill"><span class="num">18k</span> events planned</div>
            <div class="trust-pill"><span class="num">140k</span> photos shared</div>
            <div class="trust-pill"><span class="num">4.9★</span> average rating</div>
        </div>
    </div>

    <!-- Moments Grid -->
    <section class="moments">
        <div class="section-header">
            <p class="section-tag">Real moments</p>
            <h2>Every kind of celebration deserves its spotlight</h2>
        </div>

        <div class="moments-grid">
            <div class="moment-card tall">
                <img
                    src="https://images.unsplash.com/photo-1519671482749-fd09be7ccebf?w=600&h=800&fit=crop&q=80"
                    alt="Birthday party"
                >
                <div class="moment-caption">🎂 She didn't want a big deal. We made it one anyway.</div>
            </div>

            <div class="moment-card">
                <img
                    src="https://images.unsplash.com/photo-1529543544282-ea669407fca3?w=600&h=400&fit=crop&q=80"
                    alt="Friends toasting"
                >
                <div class="moment-caption">🥂 The whole crew, same table, finally.</div>
            </div>

            <div class="moment-card">
                <img
                    src="https://images.unsplash.com/photo-1536640712-4d4c36ff0e4e?w=600&h=400&fit=crop&q=80"
                    alt="Anniversary dinner"
                >
                <div class="moment-caption">❤️ Ten years and it keeps getting better.</div>
            </div>

            <div class="moment-card">
                <img
                    src="https://images.unsplash.com/photo-1513151233558-d860c5398176?w=600&h=400&fit=crop&q=80"
                    alt="Party decorations"
                >
                <div class="moment-caption">🎉 Graduated. Celebrated. Won't forget it.</div>
            </div>

            <div class="moment-card">
                <img
                    src="https://images.unsplash.com/photo-1524117074681-31bd4de22ad3?w=600&h=400&fit=crop&q=80"
                    alt="Family gathering"
                >
                <div class="moment-caption">👨‍👩‍👧‍👦 The whole family, together again.</div>
            </div>
        </div>
    </section>

    <!-- Quote -->
    <section class="quote-section">
        <div class="quote-inner">
            <blockquote>
                We used Celebrate for my mum's 60th. Everyone knew the plan, the RSVP chaos was gone,
                and we ended up with 200 photos in one place. Best birthday she's ever had.
            </blockquote>
            <div class="quote-author">
                <img
                    src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=88&h=88&fit=crop&q=80"
                    alt="Sarah K."
                >
                <div class="quote-author-info">
                    <p class="author-name">Sarah K.</p>
                    <p class="author-meta">Chicago, IL &mdash; planned her mum's 60th</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section class="how">
        <div class="how-photo">
            <img
                src="https://images.unsplash.com/photo-1521543832500-49e68a4b6327?w=700&h=800&fit=crop&q=80"
                alt="Planning a celebration"
            >
        </div>

        <div class="how-content">
            <div class="section-header">
                <p class="section-tag">Simple by design</p>
                <h2>Set it up in ten minutes, enjoy it forever</h2>
            </div>

            <div class="step">
                <div class="step-num">1</div>
                <div class="step-body">
                    <h4>Create your event</h4>
                    <p>Name it, set the date, add the details. Takes about as long as it takes to write the group chat message you've been putting off.</p>
                </div>
            </div>

            <div class="step">
                <div class="step-num">2</div>
                <div class="step-body">
                    <h4>Invite your people</h4>
                    <p>Send a link. Guests RSVP, see the plan, and actually show up on time. No more "wait, when was it again?"</p>
                </div>
            </div>

            <div class="step">
                <div class="step-num">3</div>
                <div class="step-body">
                    <h4>Relive it afterward</h4>
                    <p>Everyone uploads their photos in one shared album. The memories live somewhere better than a phone you'll eventually lose.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="final-cta">
        <div class="final-cta-photo-strip">
            <div class="strip-img">
                <img src="https://images.unsplash.com/photo-1499952127939-9bbf5af6c51c?w=100&h=100&fit=crop&q=80" alt="">
            </div>
            <div class="strip-img">
                <img src="https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=100&h=100&fit=crop&q=80" alt="">
            </div>
            <div class="strip-img">
                <img src="https://images.unsplash.com/photo-1489424731084-a5d8b219a5bb?w=100&h=100&fit=crop&q=80" alt="">
            </div>
            <div class="strip-img">
                <img src="https://images.unsplash.com/photo-1464863979621-258859e62245?w=100&h=100&fit=crop&q=80" alt="">
            </div>
            <div class="strip-img">
                <img src="https://images.unsplash.com/photo-1500917293891-ef795e70e1f6?w=100&h=100&fit=crop&q=80" alt="">
            </div>
        </div>

        <h2>Your next celebration is waiting.</h2>
        <p>Free to start. No credit card needed. Just the people you love.</p>

        <button
            x-data
            x-on:click="$dispatch('open-modal', 'create-event')"
            class="btn-light"
        >
            Create your event now — it's free →
        </button>
        <p class="final-note">No account needed to start. Takes less than 60 seconds.</p>
    </section>

    <!-- Footer -->
    <footer>
        <a href="/" class="brand">Celebrate</a>
        <p>&copy; {{ date('Y') }} Celebrate. Made for the moments that matter.</p>
    </footer>


    {{-- ── CREATE-EVENT MODAL (ported from home.blade.php) ──────────── --}}
    <x-modal name="create-event" maxWidth="2xl" focusable>
        <div class="p-6 relative">

            <div class="absolute top-4 right-4">
                <button x-on:click="$dispatch('close')" class="rounded-full h-10 w-10 flex items-center justify-center bg-gray-100 hover:bg-gray-200 transition">
                    <i class="mdi mdi-close text-gray-600"></i>
                </button>
            </div>

            <h2 class="text-xl font-bold text-gray-900">Create Your Celebration</h2>
            <p class="mt-1 text-sm text-gray-500">Fill in the details to create your unique celebration page — completely free.</p>

            <div
                x-data="celebrationForm()"
                x-init="loggedIn = @js(auth()->check())"
                class="mt-6"
            >
                <form @submit.prevent="nextStep">

                    {{-- Step indicator --}}
                    <div class="flex items-center gap-3 mb-6">
                        <div :class="step >= 1 ? 'bg-rose-500 text-white' : 'bg-gray-200 text-gray-500'"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-colors">
                            1
                        </div>
                        <div class="flex-1 h-px bg-gray-200">
                            <div :class="step >= 2 ? 'w-full bg-rose-400' : 'w-0'" class="h-full transition-all duration-300"></div>
                        </div>
                        <div :class="step >= 2 ? 'bg-rose-500 text-white' : 'bg-gray-200 text-gray-500'"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-colors">
                            2
                        </div>
                    </div>

                    {{-- STEP 1: Event details --}}
                    <div x-show="step === 1" x-transition>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name of celebrant</label>
                                <input
                                    type="text"
                                    x-model="form.celebrantName"
                                    placeholder="e.g. Sandra"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-rose-400 focus:border-transparent outline-none"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">What are you celebrating?</label>
                                <select
                                    x-model="form.eventType"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-rose-400 focus:border-transparent outline-none bg-white"
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">When is it?</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <input
                                        type="text"
                                        x-model="form.startDate"
                                        placeholder="Start date"
                                        class="datepicker w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-rose-400 outline-none"
                                    >
                                    <input
                                        type="text"
                                        x-model="form.endDate"
                                        placeholder="End date"
                                        class="datepicker w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-rose-400 outline-none"
                                    >
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Page title</label>
                                <input
                                    type="text"
                                    x-model="form.eventTitle"
                                    placeholder="Auto-generated from name & type"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-rose-400 outline-none"
                                >
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="mt-6 w-full bg-rose-500 hover:bg-rose-600 text-white py-3.5 rounded-xl font-semibold text-sm transition"
                        >
                            Continue →
                        </button>

                    </div>

                    {{-- STEP 2: Auth (only shown to guests) --}}
                    <div x-show="step === 2" x-transition>

                        <p class="text-sm text-gray-600 mb-4">
                            Almost there — just create your free account to publish the page.
                        </p>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                                <input
                                    type="email"
                                    x-model="auth.email"
                                    placeholder="you@example.com"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-rose-400 outline-none"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                <input
                                    type="password"
                                    x-model="auth.password"
                                    placeholder="Create a password"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-rose-400 outline-none"
                                >
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="submitForm"
                            class="mt-6 w-full bg-rose-500 hover:bg-rose-600 text-white py-3.5 rounded-xl font-semibold text-sm transition"
                        >
                            Create my celebration page 🎉
                        </button>

                        <p class="mt-3 text-center text-xs text-gray-400">
                            Already have an account?
                            <a href="{{ route('login') }}" class="text-rose-500 hover:underline">Sign in instead</a>
                        </p>

                    </div>

                </form>
            </div>
        </div>
    </x-modal>

    <script>
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

    {{-- Alpine.js — loads from CDN if vite bundle isn't running --}}
    @if (!file_exists(public_path('build/manifest.json')) && !file_exists(public_path('hot')))
        <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
    @endif

</body>
</html>
