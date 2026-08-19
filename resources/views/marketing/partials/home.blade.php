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
            <p class="eyebrow">
                <span class="dot" aria-hidden="true"></span>
                Free to create · no card needed
            </p>

            <h1 class="h-display">
                Somebody you love
                <span class="t-serif">deserves a fuss.</span>
            </h1>

            <p class="lead">
                Build them a page in a minute. Everyone sends
                <strong>wishes, photos and real money</strong> to one link — and you keep
                a photobook of the whole thing afterwards.
            </p>

            <div class="hero-ctas">
                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-modal', 'create-event')"
                    class="btn btn-primary btn-lg"
                >
                    <i class="mdi mdi-party-popper"></i> Create their page
                </button>

                <a href="{{ route('how-it-works') }}" data-nav class="btn btn-secondary btn-lg">
                    <i class="mdi mdi-play-circle-outline"></i> See how it works
                </a>
            </div>

            <div class="hero-fineprint">
                <span><i class="mdi mdi-check-circle"></i> Live in 60 seconds</span>
                <span><i class="mdi mdi-check-circle"></i> Guests need no account</span>
                <span><i class="mdi mdi-check-circle"></i> Withdraw any time</span>
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

            <figure class="ph ph-tall">
                <img
                    src="https://images.unsplash.com/photo-1531123897727-8f129e1688ce?w=760&h=1140&fit=crop&q=80"
                    alt="A woman smiling to camera on her celebration day"
                    loading="eager"
                >
                <figcaption class="ph-tag">
                    <i class="mdi mdi-account-heart-outline"></i> 63 people joined in
                </figcaption>
            </figure>

            <figure class="ph ph-wide">
                <img
                    src="https://images.unsplash.com/photo-1541532713592-79a0317b6b77?w=680&h=530&fit=crop&q=80"
                    alt="Friends raising their glasses in a toast at a party"
                    loading="eager"
                >
            </figure>

            <figure class="ph ph-short">
                <img
                    src="https://images.unsplash.com/photo-1533227268428-f9ed0900fb3b?w=680&h=580&fit=crop&q=80"
                    alt="A man throwing his fists up, laughing, mid-celebration"
                    loading="lazy"
                >
            </figure>
        </div>
    </div>
</section>

{{-- ══ TRUST STRIP ══════════════════════════════════════════════════ --}}
<div class="wrap">
    <div class="trust">
        <div class="trust-people">
            <div class="avatars" aria-hidden="true">
                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=90&h=90&fit=crop&q=80" alt="" loading="lazy">
                <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=90&h=90&fit=crop&q=80" alt="" loading="lazy">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=90&h=90&fit=crop&q=80" alt="" loading="lazy">
                <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=90&h=90&fit=crop&q=80" alt="" loading="lazy">
                <span class="more">2.4k</span>
            </div>

            <p class="trust-text">
                <span class="trust-stars" aria-hidden="true">
                    <i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star"></i><i class="mdi mdi-star"></i>
                </span><br>
                <strong>2,400+ celebrations</strong> created this month
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
            <p class="eyebrow"><i class="mdi mdi-gesture-tap"></i> How it goes</p>
            <h2 class="h-section">Four small steps.<br>One <span class="t-serif t-accent">very good</span> day.</h2>
        </div>

        <div class="flow-grid">
            <article class="flow-card flow-1">
                <span class="n">1</span>
                <h3>Make the page</h3>
                <p>
                    Name the celebrant, pick the occasion, done. The page is live with its
                    own link before you've finished your coffee.
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
                    Guests leave wishes, upload photos and send cash gifts. No app, no
                    sign-up, no bank details in the group chat.
                </p>
            </article>

            <article class="flow-card flow-4">
                <span class="n">4</span>
                <h3>Keep all of it</h3>
                <p>
                    Withdraw to your bank whenever you like, and download the photobook of
                    everything people said.
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
                Not a form. Not a spreadsheet. A page that looks like the people on it.
            </p>
        </div>

        <div class="band-strip" aria-hidden="true">
            <figure class="ph">
                <img src="https://images.unsplash.com/photo-1543269865-cbf427effbad?w=520&h=690&fit=crop&q=80" alt="" loading="lazy">
            </figure>
            <figure class="ph">
                <img src="https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=520&h=690&fit=crop&q=80" alt="" loading="lazy">
            </figure>
            <figure class="ph">
                <img src="https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=520&h=690&fit=crop&q=80" alt="" loading="lazy">
            </figure>
            <figure class="ph">
                <img src="https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?w=520&h=690&fit=crop&q=80" alt="" loading="lazy">
            </figure>
            <figure class="ph">
                <img src="https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=520&h=690&fit=crop&q=80" alt="" loading="lazy">
            </figure>
        </div>
    </div>
</section>

{{-- ══ FEATURE ROWS ═════════════════════════════════════════════════ --}}
<section class="sec sec-alt">
    <div class="wrap">

        {{-- Wishes --}}
        <div class="frow">
            <div class="frow-copy">
                <p class="eyebrow"><i class="mdi mdi-message-text-outline"></i> Wishes</p>
                <h2>A wall of everything people wanted to say.</h2>
                <p class="lead">
                    Guests write a message, add a photo, react to each other. It all lands on
                    the page in real time — and none of it gets lost in a chat thread at 2am.
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
                <p class="eyebrow"><i class="mdi mdi-cash-multiple"></i> Gifts &amp; money</p>
                <h2>Real money, straight into your wallet.</h2>
                <p class="lead">
                    Guests pay by card or transfer through Paystack and Stripe. It lands in your
                    CelebrateMi wallet instantly, and you withdraw to your bank whenever you feel
                    like it.
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
                <p class="eyebrow"><i class="mdi mdi-book-open-page-variant-outline"></i> Afterwards</p>
                <h2>The day, bound into a photobook.</h2>
                <p class="lead">
                    When it's over we lay every wish and photo out as a book you can download,
                    print or send back to everyone who wrote in it. This is the part people
                    keep opening two years later.
                </p>

                <ul class="checks">
                    <li><i class="mdi mdi-check-circle-outline"></i> <span>Generated automatically — nothing to lay out yourself</span></li>
                    <li><i class="mdi mdi-check-circle-outline"></i> <span>Print-ready PDF, or share it as a link</span></li>
                    <li><i class="mdi mdi-check-circle-outline"></i> <span>Choose a frame and cover that suits the occasion</span></li>
                </ul>
            </div>

            <div class="frow-art">
                <div class="mock mock-tinted">
                    <div class="book" aria-hidden="true">
                        <figure class="ph"><img src="https://images.unsplash.com/photo-1533227268428-f9ed0900fb3b?w=340&h=340&fit=crop&q=80" alt="" loading="lazy"></figure>
                        <figure class="ph"><img src="https://images.unsplash.com/photo-1541532713592-79a0317b6b77?w=340&h=340&fit=crop&q=80" alt="" loading="lazy"></figure>
                        <figure class="ph"><img src="https://images.unsplash.com/photo-1543269865-cbf427effbad?w=340&h=340&fit=crop&q=80" alt="" loading="lazy"></figure>
                        <figure class="ph"><img src="https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=340&h=340&fit=crop&q=80" alt="" loading="lazy"></figure>
                        <figure class="ph"><img src="https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=340&h=340&fit=crop&q=80" alt="" loading="lazy"></figure>
                        <figure class="ph"><img src="https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=340&h=340&fit=crop&q=80" alt="" loading="lazy"></figure>
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
        <div class="stats-grid">
            <div class="stat">
                <p class="n">2.4k</p>
                <p class="l">Celebrations created</p>
            </div>
            <div class="stat">
                <p class="n">140k</p>
                <p class="l">Wishes written</p>
            </div>
            <div class="stat">
                <p class="n">&#8358;92m</p>
                <p class="l">Gifted &amp; withdrawn</p>
            </div>
            <div class="stat">
                <p class="n">4.9</p>
                <p class="l">Average rating</p>
            </div>
        </div>
    </div>
</section>

{{-- ══ VOICES ═══════════════════════════════════════════════════════ --}}
<section class="sec">
    <div class="wrap">
        <div class="section-head is-centred">
            <p class="eyebrow"><i class="mdi mdi-heart-outline"></i> From the people who did it</p>
            <h2 class="h-section">What actually happened.</h2>
        </div>

        <div class="voices-grid">
            <figure class="quote">
                <span class="qmark" aria-hidden="true"><i class="mdi mdi-format-quote-close"></i></span>
                <blockquote>
                    We made a page for my mum's 60th and sent it to the family group. By the end
                    of the week she had 200 wishes and enough gifted to finally send her on that
                    trip. She still opens the photobook.
                </blockquote>
                <figcaption class="quote-author">
                    <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=110&h=110&fit=crop&q=80" alt="" loading="lazy">
                    <div>
                        <p class="qa-name">Sarah K.</p>
                        <p class="qa-meta">Planned her mum's 60th</p>
                    </div>
                </figcaption>
            </figure>

            <figure class="quote voice-tint">
                <span class="qmark" aria-hidden="true"><i class="mdi mdi-format-quote-close"></i></span>
                <blockquote>
                    The bit I didn't expect: cousins abroad who never make it to anything were
                    the loudest people on the page. Nobody had to ask anyone for account details.
                </blockquote>
                <figcaption class="quote-author">
                    <img src="https://images.unsplash.com/photo-1552058544-f2b08422138a?w=110&h=110&fit=crop&q=80" alt="" loading="lazy">
                    <div>
                        <p class="qa-name">Emmanuel D.</p>
                        <p class="qa-meta">Graduation, 88 guests</p>
                    </div>
                </figcaption>
            </figure>

            <figure class="quote">
                <span class="qmark" aria-hidden="true"><i class="mdi mdi-format-quote-close"></i></span>
                <blockquote>
                    I set it up on the bus. Genuinely. Then spent the next three days watching
                    the wishes come in instead of chasing people for gift money.
                </blockquote>
                <figcaption class="quote-author">
                    <img src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=110&h=110&fit=crop&q=80" alt="" loading="lazy">
                    <div>
                        <p class="qa-name">Daniela A.</p>
                        <p class="qa-meta">Surprise party for her partner</p>
                    </div>
                </figcaption>
            </figure>
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
            <p class="eyebrow"><i class="mdi mdi-help-circle-outline"></i> Before you ask</p>
            <h2 class="h-section">The questions we get most.</h2>
        </div>

        <div class="faq">
            <details name="faq" open>
                <summary>
                    What does it cost?
                    <i class="mdi mdi-plus" aria-hidden="true"></i>
                </summary>
                <p class="answer">
                    Creating a page is free, and you don't need a card to start. We take a small
                    fee on cash gifts you receive — nothing else. Full breakdown on the
                    <a href="{{ route('pricing') }}" data-nav>pricing page</a>.
                </p>
            </details>

            <details name="faq">
                <summary>
                    Do my guests need to download anything?
                    <i class="mdi mdi-plus" aria-hidden="true"></i>
                </summary>
                <p class="answer">
                    No. They open the link in whatever browser they already have, write their
                    wish and — if they want to — send a gift. No app, no account, no password.
                </p>
            </details>

            <details name="faq">
                <summary>
                    How do I get the money out?
                    <i class="mdi mdi-plus" aria-hidden="true"></i>
                </summary>
                <p class="answer">
                    Gifts land in your CelebrateMi wallet as they arrive. Withdraw to your bank
                    account whenever you like, in full or in parts. You can also leave it sitting
                    in the wallet — there's no deadline.
                </p>
            </details>

            <details name="faq">
                <summary>
                    Can guests send from another country?
                    <i class="mdi mdi-plus" aria-hidden="true"></i>
                </summary>
                <p class="answer">
                    Yes. Local cards and transfers go through Paystack, international cards
                    through Stripe. Your guest picks whichever suits them; you receive it the
                    same way either way.
                </p>
            </details>

            <details name="faq">
                <summary>
                    Is it only for birthdays?
                    <i class="mdi mdi-plus" aria-hidden="true"></i>
                </summary>
                <p class="answer">
                    Weddings, graduations, baby showers, anniversaries, new jobs, housewarmings,
                    memorials — anything where people want to say something and send something.
                    You pick the occasion when you create the page.
                </p>
            </details>

            <details name="faq">
                <summary>
                    Can I keep the amounts private?
                    <i class="mdi mdi-plus" aria-hidden="true"></i>
                </summary>
                <p class="answer">
                    You choose. Show a running total to build momentum, show individual gifts,
                    or hide the numbers entirely and let the wishes be the visible part.
                </p>
            </details>
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
            <span class="t-serif">Make the fuss.</span>
        </h2>

        <p>Free to create, live in under a minute, and they'll never forget it.</p>

        <button
            type="button"
            x-data
            x-on:click="$dispatch('open-modal', 'create-event')"
            class="btn btn-primary btn-lg"
        >
            <i class="mdi mdi-party-popper"></i> Create their page
        </button>

        <p class="cta-note">No card needed · Cancel any time · Takes about a minute</p>
    </div>
</section>
