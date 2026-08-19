{{-- Stories page --}}

@php
/*
| Illustrative testimonials. Swap for real, permissioned quotes before launch —
| and replace the Unsplash portraits with the actual people's photos.
*/
$stories = [
    [
        'quote'  => "We made a page for my mum's 60th and sent it to the family group. By the end of the week she had 200 wishes and enough gifted to send her on the trip she'd been putting off for years. She still opens the photobook.",
        'name'   => 'Sarah K.',
        'meta'   => "Planned her mum's 60th birthday",
        'avatar' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100&h=100&fit=crop&q=80',
    ],
    [
        'quote'  => "Half our guests were abroad and couldn't make the wedding. The page meant they were still part of it — messages, photos, and contributions towards the honeymoon instead of a registry we didn't need.",
        'name'   => 'Chidi &amp; Amaka',
        'meta'   => 'Wedding, Lagos',
        'avatar' => 'https://images.unsplash.com/photo-1519671482749-fd09be7ccebf?w=100&h=100&fit=crop&q=80',
    ],
    [
        'quote'  => "I set it up for my brother's graduation in about a minute on my phone. What got me was the photobook at the end — all of it in one PDF instead of scattered across four group chats.",
        'name'   => 'Tunde A.',
        'meta'   => "Brother's graduation",
        'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop&q=80',
    ],
    [
        'quote'  => "Group gifting was the whole point for us. Instead of six people buying six different things for the baby, everyone put in towards the pram. It was funded in two days.",
        'name'   => 'Ngozi E.',
        'meta'   => 'Baby shower',
        'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&h=100&fit=crop&q=80',
    ],
    [
        'quote'  => "We used it for my father's memorial. It sounds like an odd fit for something called CelebrateMi, but a quiet page where people could leave tributes was exactly what the family needed.",
        'name'   => 'Daniel O.',
        'meta'   => 'Memorial page',
        'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=100&h=100&fit=crop&q=80',
    ],
    [
        'quote'  => "Money landed in the wallet as gifts came in, and the withdrawal hit my bank the next day. That was the part I was most nervous about and it was the part I thought about least.",
        'name'   => 'Blessing I.',
        'meta'   => 'Birthday, Abuja',
        'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&h=100&fit=crop&q=80',
    ],
];
@endphp

<section class="page-head">
    <div class="pattern pattern-dots pattern-fade"></div>

    <div class="wrap sec-inner">
        <h1 class="h-display">Real days.<br><span class="t-accent">Real people.</span></h1>
        <p class="lead">
            Birthdays, weddings, graduations and a few occasions we didn't plan for. Here's
            what people did with a page and a link.
        </p>
    </div>
</section>

{{-- ══ STATS ════════════════════════════════════════════════════════ --}}
<section class="sec sec-tight sec-alt">
    <div class="wrap">
        <div class="stats-grid">
            <div class="stat">
                <p class="n">2.4k</p>
                <p class="l">Celebrations</p>
            </div>
            <div class="stat">
                <p class="n">140k</p>
                <p class="l">Wishes sent</p>
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

{{-- ══ STORY GRID ═══════════════════════════════════════════════════ --}}
<section class="sec">
    <div class="wrap">
        <div class="grid-3">
            @foreach ($stories as $story)
                <div class="quote">
                    <span class="qmark" aria-hidden="true"><i class="mdi mdi-format-quote-close"></i></span>

                    <blockquote>{{ $story['quote'] }}</blockquote>

                    <div class="quote-author">
                        <img src="{{ $story['avatar'] }}" alt="" loading="lazy">
                        <div>
                            <p class="qa-name">{!! $story['name'] !!}</p>
                            <p class="qa-meta">{{ $story['meta'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ CTA ══════════════════════════════════════════════════════════ --}}
<section class="cta-band on-dark">
    <div class="pattern pattern-dots pattern-fade"></div>

    <div class="wrap sec-inner">
        <h2 class="h-section">Your turn.</h2>
        <p>Somebody in your life has something worth marking.</p>

        <button
            type="button"
            x-data
            x-on:click="$dispatch('open-modal', 'create-event')"
            class="btn btn-on-dark"
        >
            <i class="mdi mdi-plus-circle-outline"></i> Create your celebration
        </button>

        <p class="cta-note">Free &middot; Live in 60 seconds</p>
    </div>
</section>
