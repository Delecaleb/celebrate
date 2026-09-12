{{--
    Stories index.

    The cards come from resources/data/stories.json via App\Support\StoryLibrary
    — illustrative celebrations, not customer records. Each one links to an
    archived, read-only version of the page it describes.

    Six are shown at a time; the rest are in the markup already and revealed six
    at a time by the button underneath, so the whole set is still crawlable.
--}}

@use('App\Support\StoryLibrary')

<section class="page-head">
    <div class="pattern pattern-dots pattern-fade"></div>

    <div class="wrap sec-inner">
        <h1 class="h-display">Real days.<br><span class="t-accent">Real people.</span></h1>
        <p class="lead">
            Birthdays, weddings, graduations and a few occasions we didn't plan for. Open any
            one of them — every wish and every gift is still exactly where it was left.
        </p>
    </div>
</section>

{{-- ══ STATS ════════════════════════════════════════════════════════ --}}
<section class="sec sec-tight sec-alt">
    <div class="wrap">
        {{-- These describe the archive on this page, which is a fact we can
             check, rather than platform totals we would have to invent. --}}
        <div class="stats-grid">
            <div class="stat">
                <p class="n">{{ count($stories) }}</p>
                <p class="l">{{ ($query ?? '') !== '' ? 'Matching this search' : 'Celebrations here' }}</p>
            </div>
            <div class="stat">
                <p class="n">11</p>
                <p class="l">Kinds of occasion</p>
            </div>
            <div class="stat">
                <p class="n">16</p>
                <p class="l">Countries</p>
            </div>
            <div class="stat">
                <p class="n">&infin;</p>
                <p class="l">How long they last</p>
            </div>
        </div>
    </div>
</section>

{{-- ══ STORY GRID ═══════════════════════════════════════════════════ --}}
<section class="sec">
    {{-- A search result is already a short list, so it is shown whole. --}}
    <div class="wrap" x-data="{ shown: {{ ($query ?? '') !== '' ? count($stories) : 6 }}, total: {{ count($stories) }} }">

        <div class="section-head">
            <h2 class="h-section">Thirty-six pages,<br><span class="t-serif t-accent">still open</span>.</h2>
            <p class="lead">
                Every one of these is closed to new wishes and gifts now — but nothing has been
                taken down. That's the point.
            </p>
        </div>

        {{-- A plain GET form: it works without JavaScript, and ?q= is the same
             address our structured data advertises to search engines. --}}
        <form method="GET" action="{{ route('stories') }}" class="story-search" role="search">
            <label class="sr-only" for="story-q">Search the stories</label>
            <i class="mdi mdi-magnify" aria-hidden="true"></i>
            <input
                type="search"
                id="story-q"
                name="q"
                value="{{ $query ?? '' }}"
                placeholder="Try a name, an occasion or a city — “wedding”, “Lagos”, “memorial”"
                autocomplete="off"
            >
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        @if (($query ?? '') !== '')
            <p class="story-result-note">
                @if (count($stories) === 0)
                    Nothing matches <strong>“{{ $query }}”</strong>.
                @else
                    <strong>{{ count($stories) }}</strong>
                    {{ Str::plural('celebration', count($stories)) }} matching
                    <strong>“{{ $query }}”</strong>.
                @endif
                <a href="{{ route('stories') }}" data-nav>Show all thirty-six</a>
            </p>
        @endif

        <div class="story-grid">
            @foreach ($stories as $i => $story)
                <a
                    href="{{ route('stories.show', $story['slug']) }}"
                    data-nav
                    class="story-card"
                    @if ($i >= 6) x-show="shown > {{ $i }}" x-cloak x-transition.opacity @endif
                >
                    <span class="story-cover">
                        <img
                            src="{{ StoryLibrary::photo($story['cover'], 640, 420) }}"
                            alt=""
                            loading="lazy"
                        >
                        <span class="story-chip">
                            <i class="mdi {{ $story['icon'] }}"></i> {{ $story['occasion_label'] }}
                        </span>
                        <span class="story-locked" title="Closed to new wishes and gifts">
                            <i class="mdi mdi-lock-outline"></i>
                        </span>
                    </span>

                    <span class="story-body">
                        <span class="story-title">{{ $story['title'] }}</span>
                        <span class="story-where">
                            <i class="mdi mdi-map-marker-outline"></i> {{ $story['location'] }}
                        </span>

                        <span class="story-quote">“{{ $story['pull_quote'] }}”</span>

                        <span class="story-by">
                            — {{ $story['quote_by'] }}, {{ $story['quote_meta'] }}
                        </span>

                        <span class="story-stats">
                            <span><i class="mdi mdi-message-text-outline"></i> {{ number_format($story['wish_count']) }} wishes</span>
                            <span><i class="mdi mdi-gift-outline"></i> {{ $story['currency'] }}{{ number_format($story['raised']) }}</span>
                        </span>

                        <span class="story-open">Open the page <i class="mdi mdi-arrow-right"></i></span>
                    </span>
                </a>
            @endforeach
        </div>

        <div class="story-more" x-show="shown < total" x-cloak>
            <button type="button" class="btn btn-secondary btn-lg" @click="shown = Math.min(shown + 6, total)">
                <i class="mdi mdi-plus"></i>
                Show 6 more
                <span class="story-more-left" x-text="'(' + (total - shown) + ' to go)'"></span>
            </button>
        </div>

        <p class="story-all" x-show="shown >= total" x-cloak>
            That's all thirty-six. Yours would sit here just as well.
        </p>
    </div>
</section>

{{-- ══ CTA ══════════════════════════════════════════════════════════ --}}
<section class="cta-band on-dark">
    <div class="pattern pattern-dots pattern-fade"></div>

    <div class="wrap sec-inner">
        <h2 class="h-section">Your turn.</h2>
        <p>Your day is coming, and the people who love you are waiting to be asked.</p>

        <button
            type="button"
            x-data
            x-on:click="$dispatch('open-modal', 'create-event')"
            class="btn btn-on-dark"
        >
            <i class="mdi mdi-party-popper"></i> Create my page
        </button>

        <p class="cta-note">Free · Live in 30 seconds</p>
    </div>
</section>
