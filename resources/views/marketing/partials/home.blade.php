{{--
    Home. Injected into <main id="view"> by layouts.marketing / pageRouter.js.
    Styles: marketing/styles/core.blade.php + marketing/styles/home.blade.php.

    The photographs are Unsplash placeholders — swap the URLs for real customer
    photos when they're available. Keep the aspect ratios; the collage and the
    photo band both rely on them.
--}}

{{-- ══ HERO ═════════════════════════════════════════════════════════ --}}
<section class="hero">
    <div class="wrap hero-grid">
        <div class="hero-copy">

            <h1 class="h-display">
                Your day is coming.
                <span class="t-serif">Let them make a fuss.</span>
            </h1>

            <p class="lead">
                Put up your page in half a minute. Everyone who loves you sends
                <strong>wishes, photos and real money</strong> to one link — and it all
                stays yours, long after the day is over.
            </p>

            <div class="hero-ctas">
                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-modal', 'create-event')"
                    class="btn btn-primary btn-lg"
                >
                    <i class="mdi mdi-party-popper"></i> Create my page
                </button>

                <a href="{{ route('how-it-works') }}" data-nav class="btn btn-secondary btn-lg">
                    <i class="mdi mdi-play-circle-outline"></i> See how it works
                </a>
            </div>

            <div class="hero-fineprint">
                <span><i class="mdi mdi-check-circle"></i> Live in 30 seconds</span>
                <span><i class="mdi mdi-check-circle"></i> Guests need no account</span>
                <span><i class="mdi mdi-check-circle"></i> Yours to keep afterwards</span>
            </div>
        </div>

        {{-- Photo collage: real people, pinned up like a fridge door --}}
        <div class="collage">
            <div class="sticker sticker-money">
                <i class="mdi mdi-cash-multiple"></i>
                <span>James sent <span class="amt">&#8358;5,000</span></span>
            </div>

            <div class="sticker sticker-wishes">
                <i class="mdi mdi-message-text-outline"></i>
                <span>12 new wishes</span>
            </div>

            <div class="sticker sticker-book">
                <i class="mdi mdi-book-open-page-variant-outline"></i>
                <span>Photobook ready</span>
            </div>

            <div class="sticker-hbd" aria-hidden="true">
                <i class="mdi mdi-cake-variant"></i> Happy birthday!
            </div>

            {{-- Self-hosted, so the fold does not depend on a third party being
                 up. fetchpriority on the tall one: it is the largest thing
                 above the fold and therefore the LCP element. --}}
            <figure class="ph ph-tall">
                <img
                    src="{{ asset('images/hero/hero-tall.webp') }}"
                    alt="A woman laughing on her birthday, friends behind her in a decorated room"
                    width="1536" height="2752"
                    loading="eager" fetchpriority="high" decoding="async"
                >
                <figcaption class="ph-tag">
                    <i class="mdi mdi-account-heart-outline"></i> 63 people joined in
                </figcaption>
            </figure>

            <figure class="ph ph-wide">
                <img
                    src="{{ asset('images/hero/hero-wide.webp') }}"
                    alt="Six friends raising their glasses in a toast around a table"
                    width="2752" height="1536"
                    loading="eager" decoding="async"
                >
            </figure>

            <figure class="ph ph-short">
                <img
                    src="{{ asset('images/hero/hero-short.webp') }}"
                    alt="A man throwing both fists up, laughing, confetti falling around him"
                    width="2752" height="1536"
                    loading="lazy" decoding="async"
                >
            </figure>
        </div>
    </div>
</section>

{{-- ══ TRUST STRIP ══════════════════════════════════════════════════ --}}
<div class="wrap">
    <div class="trust">
        <div class="trust-people">
            @php $trust = app(\App\Support\SiteStats::class); @endphp

            <div class="avatars" aria-hidden="true">
                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=90&h=90&fit=crop&q=80" alt="" loading="lazy">
                <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=90&h=90&fit=crop&q=80" alt="" loading="lazy">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=90&h=90&fit=crop&q=80" alt="" loading="lazy">
                <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=90&h=90&fit=crop&q=80" alt="" loading="lazy">
                @if ($trust->isMeaningful())
                    <span class="more">{{ \App\Support\SiteStats::short($trust->figures()['celebrations']) }}</span>
                @endif
            </div>

            <p class="trust-text">
                @if ($trust->isMeaningful())
                    <strong>{{ number_format($trust->figures()['celebrations']) }} celebrations</strong>
                    made on CelebrateMi — every one still online
                @else
                    {{-- Nothing to count yet, so the claim is about the product
                         rather than the traction. --}}
                    <strong>Free to create</strong>, live in 30 seconds, and it
                    never comes down
                @endif
            </p>
        </div>

        <span class="trust-sep" aria-hidden="true"></span>

        <span class="trust-note">
            <i class="mdi mdi-shield-check-outline"></i> Paystack &amp; Stripe payouts
        </span>

        <span class="trust-sep" aria-hidden="true"></span>

        <span class="trust-note">
            <i class="mdi mdi-earth"></i> Guests send from anywhere
        </span>
    </div>
</div>

{{-- ══ FLOW — FOUR STEPS ════════════════════════════════════════════ --}}
<section class="sec">
    <div class="wrap">
        <div class="section-head is-centred">
            <h2 class="h-section">Four small steps.<br>One <span class="t-serif t-accent">very good</span> day.</h2>
        </div>

        <div class="flow-grid">
            <article class="flow-card flow-1">
                <span class="n">1</span>
                <h3>Make your page</h3>
                <p>
                    Your name, your occasion, done. The page is live with its own link
                    before you've finished your coffee.
                </p>
            </article>

            <article class="flow-card flow-2">
                <span class="n">2</span>
                <h3>Share one link</h3>
                <p>
                    Drop it in the family group chat or on your story. That's the whole
                    invitation.
                </p>

                <div class="flow-link" aria-hidden="true">
                    <code>celebratemi.com/sandra-30</code>
                    <span>Copy</span>
                </div>
            </article>

            <article class="flow-card flow-3">
                <span class="n">3</span>
                <h3>Everyone piles in</h3>
                <p>
                    They leave wishes, upload photos and send cash gifts. No app, no
                    sign-up, and your account number never goes in the group chat.
                </p>
            </article>

            <article class="flow-card flow-4">
                <span class="n">4</span>
                <h3>Keep all of it</h3>
                <p>
                    Withdraw to your bank whenever you like. The wishes, the photos and the
                    photobook stay on your page for good.
                </p>
            </article>
        </div>
    </div>
</section>

{{-- ══ PHOTO BAND ═══════════════════════════════════════════════════ --}}
<section class="band">
    <div class="wrap">
        <div class="band-head">
            <h2 class="h-section">It fills up with <span class="t-serif t-accent">real people</span>.</h2>
            <p class="lead">
                Not a form. Not a spreadsheet. A page that looks like the people who show up
                for you.
            </p>
        </div>

        {{-- Texture, not content: the strip is aria-hidden, so these carry no
             alt text. Each cell is a 3:4 window onto a 16:9 frame, so only
             pictures whose subject sits dead centre survive the crop. --}}
        <div class="band-strip" aria-hidden="true">
            @foreach (['traditional-wedding', 'tunde-birthday', 'graduation', 'baby-shower', 'call-to-bar'] as $frame)
                <figure class="ph">
                    <img src="{{ asset("images/covers/{$frame}.webp") }}" alt="" loading="lazy" decoding="async">
                </figure>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ FEATURE ROWS ═════════════════════════════════════════════════ --}}
<section class="sec sec-alt">
    <div class="wrap">

        {{-- Wishes --}}
        <div class="frow">
            <div class="frow-copy">
                <h2>A wall of everything people wanted to tell you.</h2>
                <p class="lead">
                    They write a message, add a photo, react to each other. It lands on your
                    page in real time and it stays there — not buried in a chat thread at 2am,
                    not gone from your story in a day.
                </p>

                <ul class="checks">
                    <li><i class="mdi mdi-check-circle-outline"></i> <span><strong>No account needed</strong> — guests just open the link and type</span></li>
                    <li><i class="mdi mdi-check-circle-outline"></i> <span>Photos, videos and voice notes all welcome</span></li>
                    <li><i class="mdi mdi-check-circle-outline"></i> <span>You approve anything before it shows, if you'd rather</span></li>
                </ul>
            </div>

            <div class="frow-art">
                <div class="mock">
                    <div class="pv-top">
                        <div>
                            <p class="pv-title">Sandra's 30th</p>
                            <p class="pv-url">celebratemi.com/sandra-30</p>
                        </div>
                        <span class="pv-live"><span class="dot" aria-hidden="true"></span> Live</span>
                    </div>

                    <div style="margin-top: 0.9rem">
                        <div class="wish-row">
                            <span class="pv-av"><img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=80&h=80&fit=crop&q=80" alt="" loading="lazy"></span>
                            <div>
                                <p class="wish-name">James A.</p>
                                <p class="wish-body">Thirty looks unbothered on you. Wishing you a year with no stress and plenty of jollof.</p>
                                <span class="wish-react">❤️ 24</span>
                            </div>
                        </div>

                        <div class="wish-row">
                            <span class="pv-av"><img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&h=80&fit=crop&q=80" alt="" loading="lazy"></span>
                            <div>
                                <p class="wish-name">Aunty Bisi</p>
                                <p class="wish-body">I have known you since you were this small. Look at you now. God bless you my dear.</p>
                                <span class="wish-react">🎉 41</span>
                            </div>
                        </div>

                        <div class="wish-row">
                            <span class="pv-av">TO</span>
                            <div>
                                <p class="wish-name">Tolu O.</p>
                                <p class="wish-body">Found the photo from Lagos 2019 — attaching it before you delete it 😂</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Money --}}
        <div class="frow frow-flip">
            <div class="frow-copy">
                <h2>Real money, straight into your wallet.</h2>
                <p class="lead">
                    Nobody has to ask you for account details. Guests pay by card or transfer
                    through Paystack and Stripe, it lands in your CelebrateMi wallet instantly,
                    and you withdraw to your bank whenever you feel like it.
                </p>

                <ul class="checks">
                    <li><i class="mdi mdi-check-circle-outline"></i> <span><strong>Group gifting</strong> — everyone chips in until a wish is funded</span></li>
                    <li><i class="mdi mdi-check-circle-outline"></i> <span>Watch the total climb live, or keep amounts private</span></li>
                    <li><i class="mdi mdi-check-circle-outline"></i> <span>Withdraw to any Nigerian bank, or hold it in your wallet</span></li>
                </ul>
            </div>

            <div class="frow-art">
                <div class="mock">
                    <div class="pv-raise">
                        <p class="pv-label">Gifted so far</p>
                        <p class="pv-amount">&#8358;850,400</p>
                        <div class="pv-bar" role="img" aria-label="68 percent of the goal reached">
                            <span style="width: 68%"></span>
                        </div>
                        <div class="pv-bar-meta">
                            <span>68% of &#8358;1.25m goal</span>
                            <span>4 days left</span>
                        </div>
                    </div>

                    <div style="margin-top: 1rem">
                        <div class="gift-row">
                            <span class="itile itile-warm"><i class="mdi mdi-airplane"></i></span>
                            <div class="gift-meta">
                                <p class="gift-name">Trip to Zanzibar</p>
                                <p class="gift-sub">14 people chipped in</p>
                            </div>
                            <p class="gift-amt">&#8358;420k</p>
                        </div>

                        <div class="gift-row">
                            <span class="itile"><i class="mdi mdi-camera-outline"></i></span>
                            <div class="gift-meta">
                                <p class="gift-name">The camera she keeps mentioning</p>
                                <p class="gift-sub">Fully funded 🎉</p>
                            </div>
                            <p class="gift-amt">&#8358;310k</p>
                        </div>

                        <div class="gift-row">
                            <span class="itile itile-warm"><i class="mdi mdi-cash-multiple"></i></span>
                            <div class="gift-meta">
                                <p class="gift-name">Just cash, no strings</p>
                                <p class="gift-sub">31 gifts</p>
                            </div>
                            <p class="gift-amt">&#8358;120k</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Photobook --}}
        <div class="frow">
            <div class="frow-copy">
                <h2>Your day, bound into a photobook.</h2>
                <p class="lead">
                    When it's over we lay every wish and photo out as a book you can download,
                    print or send back to everyone who wrote in it. This is the part you'll
                    still be opening two years later.
                </p>

                <ul class="checks">
                    <li><i class="mdi mdi-check-circle-outline"></i> <span>Generated automatically — nothing to lay out yourself</span></li>
                    <li><i class="mdi mdi-check-circle-outline"></i> <span>Print-ready PDF, or share it as a link</span></li>
                    <li><i class="mdi mdi-check-circle-outline"></i> <span>Choose a frame and cover that suits the occasion</span></li>
                </ul>
            </div>

            <div class="frow-art">
                <div class="mock mock-tinted">
                    {{-- Six square cells standing in for a finished photobook. --}}
                    <div class="book" aria-hidden="true">
                        @foreach (['tunde-birthday', 'wedding', 'grandma-birthday', 'baby-shower', 'house-warming', 'graduation'] as $page)
                            <figure class="ph">
                                <img src="{{ asset("images/covers/{$page}.webp") }}" alt="" loading="lazy" decoding="async">
                            </figure>
                        @endforeach
                    </div>

                    <p class="book-cap">Sandra's 30th — 245 wishes, 180 photos</p>
                </div>
            </div>
        </div>

    </div>
</section>

{{-- ══ OCCASIONS STRIP ══════════════════════════════════════════════ --}}
<div class="marquee" aria-hidden="true">
    <div class="marquee-track">
        {{-- duplicated so the -50% translate loops seamlessly --}}
        @for ($i = 0; $i < 2; $i++)
            <div class="marquee-group">
                <span class="marquee-item"><i class="mdi mdi-cake-variant-outline"></i> Birthdays</span>
                <span class="marquee-item"><i class="mdi mdi-ring"></i> Weddings</span>
                <span class="marquee-item"><i class="mdi mdi-school-outline"></i> Graduations</span>
                <span class="marquee-item"><i class="mdi mdi-baby-carriage"></i> Baby showers</span>
                <span class="marquee-item"><i class="mdi mdi-heart-outline"></i> Anniversaries</span>
                <span class="marquee-item"><i class="mdi mdi-trophy-outline"></i> Promotions</span>
                <span class="marquee-item"><i class="mdi mdi-home-heart"></i> Housewarmings</span>
                <span class="marquee-item"><i class="mdi mdi-candle"></i> Memorials</span>
            </div>
        @endfor
    </div>
</div>

{{-- ══ STAT BAND ════════════════════════════════════════════════════ --}}
<section class="stat-band on-dark">
    <div class="pattern pattern-dots pattern-fade"></div>

    <div class="wrap sec-inner">
        {{-- Real figures, or none at all. A count of celebrations that does not
             exist yet is worth less than saying nothing — and on a site that
             takes people's money, a good deal less than that. --}}
        @php $stats = app(\App\Support\SiteStats::class); @endphp

        @if ($stats->isMeaningful())
            @php $figures = $stats->figures(); @endphp
            <div class="stats-grid">
                <div class="stat">
                    <p class="n">{{ \App\Support\SiteStats::short($figures['celebrations']) }}</p>
                    <p class="l">Celebrations kept</p>
                </div>
                <div class="stat">
                    <p class="n">{{ \App\Support\SiteStats::short($figures['wishes']) }}</p>
                    <p class="l">Wishes still online</p>
                </div>
                <div class="stat">
                    <p class="n">{{ $figures['currency'] }}{{ \App\Support\SiteStats::short($figures['gifted']) }}</p>
                    <p class="l">Gifted &amp; withdrawn</p>
                </div>
                <div class="stat">
                    <p class="n">&infin;</p>
                    <p class="l">How long pages last</p>
                </div>
            </div>
        @else
            <div class="stats-grid">
                <div class="stat">
                    <p class="n">30s</p>
                    <p class="l">To publish a page</p>
                </div>
                <div class="stat">
                    <p class="n">&#8358;0</p>
                    <p class="l">To create one</p>
                </div>
                <div class="stat">
                    <p class="n">2</p>
                    <p class="l">Ways to be paid</p>
                </div>
                <div class="stat">
                    <p class="n">&infin;</p>
                    <p class="l">How long it lasts</p>
                </div>
            </div>
        @endif
    </div>
</section>

{{-- ══ VOICES ═══════════════════════════════════════════════════════ --}}
<section class="sec">
    <div class="wrap">
        <div class="section-head is-centred">
            <h2 class="h-section">What actually happened.</h2>
        </div>

        {{-- Three of the pages on /stories, quoted in the celebrant's own
             words. The card links straight to the page it is talking about. --}}
        <div class="voices-grid">
            @foreach ($voices as $voice)
                <figure class="quote @if ($loop->index === 1) voice-tint @endif">
                    <span class="qmark" aria-hidden="true"><i class="mdi mdi-format-quote-close"></i></span>

                    <blockquote>{{ $voice['pull_quote'] }}</blockquote>

                    <figcaption class="quote-author">
                        <img src="{{ \App\Support\StoryLibrary::photo($voice['avatar'], 110, 110) }}" alt="" loading="lazy">
                        <div>
                            <p class="qa-name">{{ $voice['quote_by'] }}</p>
                            <p class="qa-meta">{{ $voice['quote_meta'] }}</p>
                        </div>
                    </figcaption>

                    <a href="{{ route('stories.show', $voice['slug']) }}" data-nav class="link-arrow quote-link">
                        Open their page <i class="mdi mdi-arrow-right"></i>
                    </a>
                </figure>
            @endforeach
        </div>
        <p style="margin-top: 2.5rem; text-align: center">
            <a href="{{ route('stories') }}" data-nav class="link-arrow">
                Read more stories <i class="mdi mdi-arrow-right"></i>
            </a>
        </p>
    </div>
</section>

{{-- ══ FAQ ══════════════════════════════════════════════════════════ --}}
<section class="sec sec-alt">
    <div class="wrap">
        <div class="section-head is-centred">
            <h2 class="h-section">The questions we get most.</h2>
        </div>

        {{-- One list, two audiences: rendered here for readers, and published
             as FAQPage structured data by MainController. Editing the questions
             in one place keeps the markup and the schema identical, which is
             what search engines check for. --}}
        <div class="faq">
            @foreach (\App\Http\Controllers\MainController::FAQ as $item)
                <details name="faq" @if ($loop->first) open @endif>
                    <summary>
                        {{ $item['q'] }}
                        <i class="mdi mdi-plus" aria-hidden="true"></i>
                    </summary>
                    <p class="answer">
                        {{ $item['a'] }}

                        @if ($loop->first)
                            Full breakdown on the
                            <a href="{{ route('pricing') }}" data-nav>pricing page</a>.
                        @endif
                    </p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ FINALE ═══════════════════════════════════════════════════════ --}}
<section class="finale">
    <div class="confetti" aria-hidden="true">
        <span></span><span></span><span></span><span></span><span></span><span></span><span></span>
    </div>

    <div class="wrap finale-inner">
        <div class="icon-row icon-row-light" aria-hidden="true">
            <span><i class="mdi mdi-cake-variant-outline"></i></span>
            <span><i class="mdi mdi-gift-outline"></i></span>
            <span><i class="mdi mdi-cash-multiple"></i></span>
            <span><i class="mdi mdi-message-text-outline"></i></span>
            <span><i class="mdi mdi-book-open-page-variant-outline"></i></span>
        </div>

        <h2>
            Go on then.<br>
            <span class="t-serif">Let them spoil you.</span>
        </h2>

        <p>Free to create, live in 30 seconds, and yours to keep afterwards.</p>

        <button
            type="button"
            x-data
            x-on:click="$dispatch('open-modal', 'create-event')"
            class="btn btn-primary btn-lg"
        >
            <i class="mdi mdi-party-popper"></i> Create my page
        </button>

        <p class="cta-note">No card needed · Cancel any time · Takes 30 seconds</p>
    </div>
</section>
