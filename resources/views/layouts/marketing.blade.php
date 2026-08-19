{{--
    Marketing shell for the public site.

    Pages live in resources/views/marketing/partials/*.blade.php and are injected
    into <main id="view">. MainController returns the full shell on a normal
    request and just the partial (as JSON) when the AJAX router asks for it, so
    navigation never reloads the page. Links stay real <a href> values, so the
    site still works with JavaScript disabled.

    Styling: <x-brand-tokens /> emits the palette from resources/brand.json, then
    marketing/styles/{core,home}.blade.php consume it. Everything is inlined so
    the shell renders correctly with or without a Vite build.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description ?? 'Create a celebration page in 60 seconds. Collect wishes, gifts and money from everyone who loves you.' }}">
    <meta name="theme-color" content="{{ config('brand.primary.500') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@1&family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        {{-- No build available: pull the icon font and Alpine straight from the CDN --}}
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
        <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
    @endif

    {{-- Palette first — every stylesheet below reads its variables. --}}
    <x-brand-tokens />
    @include('marketing.styles.core')
    @include('marketing.styles.home')
</head>
<body>

<div id="route-progress" aria-hidden="true"></div>

{{-- ══ NAV ══════════════════════════════════════════════════════════ --}}
<div class="nav-outer" x-data="{ open: false }" @route-changed.window="open = false">
    <nav class="nav" aria-label="Main">
        <a href="{{ route('home') }}" data-nav class="brand">
            <span class="brand-mark"><i class="mdi mdi-party-popper"></i></span>
            <span class="brand-name">CelebrateMi</span>
        </a>

        <div class="nav-mid">
            @foreach ($navItems as $item)
                <a
                    href="{{ route($item['route']) }}"
                    data-nav
                    class="nav-link"
                    @if ($nav === $item['route']) aria-current="page" @endif
                >{{ $item['label'] }}</a>
            @endforeach
        </div>

        <div class="nav-right">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-sm">
                    <i class="mdi mdi-view-dashboard-outline"></i> Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="nav-link nav-desktop-only">Sign in</a>
                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-modal', 'create-event')"
                    class="btn btn-primary btn-sm"
                >Create a page</button>
            @endauth

            <button
                type="button"
                class="nav-toggle"
                :aria-expanded="open.toString()"
                aria-controls="nav-drawer"
                aria-label="Toggle menu"
                @click="open = !open"
            >
                <i class="mdi" :class="open ? 'mdi-close' : 'mdi-menu'"></i>
            </button>
        </div>
    </nav>

    {{-- Mobile drawer --}}
    <div id="nav-drawer" class="nav-drawer" :class="open && 'is-open'" x-cloak>
        @foreach ($navItems as $item)
            <a
                href="{{ route($item['route']) }}"
                data-nav
                class="nav-link"
                @if ($nav === $item['route']) aria-current="page" @endif
            >{{ $item['label'] }}</a>
        @endforeach

        <div class="drawer-cta">
            @guest
                <a href="{{ route('login') }}" class="btn btn-secondary">
                    <i class="mdi mdi-login-variant"></i> Sign in
                </a>
            @endguest
        </div>
    </div>
</div>

{{-- ══ PAGE VIEW (swapped by the AJAX router) ═══════════════════════ --}}
<main id="view">
    @include("marketing.partials.$page")
</main>

{{-- ══ FOOTER ═══════════════════════════════════════════════════════ --}}
<footer class="site-footer">
    <div class="wrap">
        <div class="foot-grid">
            <div class="foot-brand">
                <a href="{{ route('home') }}" data-nav class="brand">
                    <span class="brand-mark"><i class="mdi mdi-party-popper"></i></span>
                    <span class="brand-name">CelebrateMi</span>
                </a>

                <p class="foot-blurb">
                    One page to collect wishes, gifts and money from everyone who loves you —
                    and keep the memories long after the day is over.
                </p>

                <div class="foot-social">
                    <a href="https://instagram.com" aria-label="Instagram" rel="noopener" target="_blank"><i class="mdi mdi-instagram"></i></a>
                    <a href="https://x.com" aria-label="X" rel="noopener" target="_blank"><i class="mdi mdi-twitter"></i></a>
                    <a href="https://facebook.com" aria-label="Facebook" rel="noopener" target="_blank"><i class="mdi mdi-facebook"></i></a>
                    <a href="https://wa.me/" aria-label="WhatsApp" rel="noopener" target="_blank"><i class="mdi mdi-whatsapp"></i></a>
                </div>
            </div>

            <div class="foot-col">
                <h4>Product</h4>
                <ul>
                    <li><a href="{{ route('features') }}" data-nav>Features</a></li>
                    <li><a href="{{ route('how-it-works') }}" data-nav>How it works</a></li>
                    <li><a href="{{ route('pricing') }}" data-nav>Pricing</a></li>
                </ul>
            </div>

            <div class="foot-col">
                <h4>Occasions</h4>
                <ul>
                    <li><a href="{{ route('features') }}" data-nav>Birthdays</a></li>
                    <li><a href="{{ route('features') }}" data-nav>Weddings</a></li>
                    <li><a href="{{ route('features') }}" data-nav>Graduations</a></li>
                    <li><a href="{{ route('features') }}" data-nav>Anniversaries</a></li>
                </ul>
            </div>

            <div class="foot-col">
                <h4>Company</h4>
                <ul>
                    <li><a href="{{ route('stories') }}" data-nav>Stories</a></li>
                    @guest
                        <li><a href="{{ route('login') }}">Sign in</a></li>
                        @if (Route::has('register'))
                            <li><a href="{{ route('register') }}">Create account</a></li>
                        @endif
                    @else
                        <li><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    @endguest
                    <li><a href="mailto:hello@celebratemi.com">Contact us</a></li>
                </ul>
            </div>
        </div>

        <div class="foot-bottom">
            <p>&copy; {{ date('Y') }} CelebrateMi. All rights reserved.</p>
            <div class="foot-meta">
                <span><i class="mdi mdi-shield-check-outline"></i> Secure payments</span>
                <span><i class="mdi mdi-credit-card-outline"></i> Paystack &amp; Stripe</span>
            </div>
        </div>
    </div>
</footer>

{{-- ══ CREATE-EVENT MODAL ═══════════════════════════════════════════ --}}
<x-modal name="create-event" maxWidth="2xl" focusable>
    <div style="background: var(--surface)">

        <div class="sheet-head">
            <button type="button" x-on:click="$dispatch('close')" aria-label="Close" class="sheet-close">
                <i class="mdi mdi-close"></i>
            </button>

            <h2>Let's throw a party</h2>
            <p>Tell us who we're celebrating — everything can be changed later.</p>
        </div>

        <div
            x-data="celebrationForm()"
            x-init="loggedIn = @js(auth()->check())"
            class="sheet-body"
        >
            <form @submit.prevent="nextStep">

                {{-- Step indicator --}}
                <div class="steps-bar" aria-hidden="true">
                    <span class="pip" :class="step >= 1 && 'is-on'">1</span>
                    <span class="rail"><span :class="step >= 2 && 'is-on'"></span></span>
                    <span class="pip" :class="step >= 2 && 'is-on'">2</span>
                </div>

                {{-- STEP 1 --}}
                <div x-show="step === 1" x-transition>
                    <div class="field">
                        <label class="field-label" for="ce-name">Who are we celebrating?</label>
                        <input id="ce-name" type="text" class="input" x-model="form.celebrantName" placeholder="e.g. Sandra">
                    </div>

                    <div class="field">
                        <label class="field-label" for="ce-type">What's the occasion?</label>
                        <select id="ce-type" class="input" x-model="form.eventType">
                            <option value="">Select an occasion</option>
                            <option value="birthday">Birthday</option>
                            <option value="wedding">Wedding</option>
                            <option value="graduation">Graduation</option>
                            <option value="anniversary">Anniversary</option>
                            <option value="baby_shower">Baby shower</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="field">
                        <span class="field-label">When is it?</span>
                        <div class="field-row">
                            <input type="text" class="input datepicker" x-model="form.startDate" placeholder="Start date" aria-label="Start date">
                            <input type="text" class="input datepicker" x-model="form.endDate" placeholder="End date" aria-label="End date">
                        </div>
                    </div>

                    <div class="field">
                        <label class="field-label" for="ce-title">Page title</label>
                        <input id="ce-title" type="text" class="input" x-model="form.eventTitle" placeholder="We'll write this for you">
                        <p class="field-hint">We fill this in from the name and occasion — edit it if you'd rather.</p>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Continue <i class="mdi mdi-arrow-right"></i>
                    </button>
                </div>

                {{-- STEP 2 --}}
                <div x-show="step === 2" x-transition>
                    <p style="margin-bottom: 1.35rem; font-size: 0.95rem; color: var(--muted)">
                        Almost there — create your free account to publish the page.
                    </p>

                    <div class="field">
                        <label class="field-label" for="ce-email">Email address</label>
                        <input id="ce-email" type="email" class="input" x-model="auth.email" placeholder="you@example.com">
                    </div>

                    <div class="field">
                        <label class="field-label" for="ce-pass">Password</label>
                        <input id="ce-pass" type="password" class="input" x-model="auth.password" placeholder="Create a password">
                    </div>

                    <button type="button" @click="submitForm" class="btn btn-primary btn-block">
                        <i class="mdi mdi-party-popper"></i> Create my celebration
                    </button>

                    <p class="sheet-alt">
                        <button type="button" @click="step = 1">Back to details</button>
                    </p>
                    <p class="sheet-alt">
                        Already have an account?
                        <a href="{{ route('login') }}">Sign in instead</a>
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

</body>
</html>
