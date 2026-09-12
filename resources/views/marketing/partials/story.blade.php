{{--
    One archived celebration page.

    Rendered from resources/data/stories.json — no database rows behind it, and
    nothing on it can be added to. The lock notice below is not decoration: the
    composer is disabled, the reaction counts are plain text, and there is no
    gift button anywhere on the page.
--}}

@use('App\Support\StoryLibrary')

<article class="story-page">

    {{-- ══ COVER ════════════════════════════════════════════════════ --}}
    <header class="sp-hero">
        <img class="sp-hero-img" src="{{ StoryLibrary::photo($story['cover'], 1600, 900) }}" alt="" loading="eager">
        <span class="sp-hero-veil" aria-hidden="true"></span>

        <a href="{{ route('stories') }}" data-nav class="sp-back">
            <i class="mdi mdi-arrow-left"></i> All stories
        </a>

        <div class="wrap sp-hero-inner">
            <p class="sp-chip">
                <i class="mdi {{ $story['icon'] }}"></i> {{ $story['occasion_label'] }}
            </p>

            <h1 class="sp-title">{{ $story['title'] }}</h1>

            <p class="sp-sub">
                {{-- On a memorial the page is named after the person, so this
                     would only say it twice. --}}
                @if ($story['celebrant'] !== $story['title'])
                    <span><i class="mdi mdi-account-heart-outline"></i> {{ $story['celebrant'] }}</span>
                @endif
                <span><i class="mdi mdi-calendar-blank-outline"></i> {{ $story['date'] }}</span>
                <span><i class="mdi mdi-map-marker-outline"></i> {{ $story['location'] }}</span>
            </p>
        </div>
    </header>

    <div class="wrap">

        {{-- ══ THE LOCK ═════════════════════════════════════════════ --}}
        <div class="sp-lock" role="status">
            <i class="mdi mdi-lock-outline" aria-hidden="true"></i>
            <div>
                <p class="sp-lock-title">This celebration is closed.</p>
                <p class="sp-lock-body">
                    It stays here exactly as it was left — every wish, photo and gift is still
                    readable. New wishes, reactions and gifts are switched off, so nothing on
                    this page can be added to or changed.
                </p>
            </div>
        </div>

        {{-- ══ WHAT HAPPENED ════════════════════════════════════════ --}}
        <div class="sp-stats">
            <div>
                <p class="n">{{ number_format($story['wish_count']) }}</p>
                <p class="l">Wishes left</p>
            </div>
            <div>
                <p class="n">{{ number_format($story['guests']) }}</p>
                <p class="l">People who came</p>
            </div>
            <div>
                <p class="n">{{ $story['currency'] }}{{ number_format($story['raised']) }}</p>
                <p class="l">Gifted</p>
            </div>
            <div>
                <p class="n">{{ $story['progress'] }}%</p>
                <p class="l">Of the goal</p>
            </div>
        </div>

        <div class="sp-grid">

            {{-- ── WISHES ──────────────────────────────────────────── --}}
            <section class="sp-col">
                <h2 class="sp-h2"><i class="mdi mdi-message-text-outline"></i> Wishes</h2>

                {{-- The composer, kept visible and inert, so it is obvious what
                     the page used to do and obvious that it no longer does. --}}
                <div class="sp-composer" aria-hidden="true">
                    <span class="sp-av is-muted"><i class="mdi mdi-lock-outline"></i></span>
                    <input type="text" class="input" value="Wishes are closed on this celebration" disabled>
                </div>

                @foreach ($story['wishes'] as $wish)
                    <article class="sp-wish">
                        <span class="sp-av">{{ StoryLibrary::initials($wish['name']) }}</span>

                        <div class="sp-wish-body">
                            <p class="sp-wish-head">
                                <span class="sp-wish-name">{{ $wish['name'] }}</span>
                                <span class="sp-wish-time">{{ $wish['when'] }}</span>
                            </p>

                            <p class="sp-wish-text">{{ $wish['message'] }}</p>

                            @isset($wish['photo'])
                                <span class="sp-wish-photo">
                                    <img src="{{ StoryLibrary::photo($wish['photo'], 560, 380) }}" alt="" loading="lazy">
                                </span>
                            @endisset

                            {{-- A count, not a button. Nothing here is clickable. --}}
                            <span class="sp-hearts"><i class="mdi mdi-heart"></i> {{ $wish['hearts'] }}</span>
                        </div>
                    </article>
                @endforeach

                <p class="sp-rest">
                    {{ number_format($story['wish_count'] - count($story['wishes'])) }} more wishes
                    were left on this page. They're in the photobook the family took home.
                </p>
            </section>

            {{-- ── GIFTS ───────────────────────────────────────────── --}}
            <aside class="sp-col sp-side">
                <h2 class="sp-h2"><i class="mdi mdi-gift-outline"></i> Gifts</h2>

                <div class="sp-raise">
                    <p class="sp-raise-label">Gifted in total</p>
                    <p class="sp-raise-amount">{{ $story['currency'] }}{{ number_format($story['raised']) }}</p>

                    <div class="sp-bar" role="img"
                         aria-label="{{ $story['progress'] }} percent of the goal reached">
                        <span style="width: {{ $story['progress'] }}%"></span>
                    </div>

                    <p class="sp-raise-meta">
                        @if (($story['goal'] ?? 0) > 0)
                            {{ $story['progress'] }}% of a {{ $story['currency'] }}{{ number_format($story['goal']) }} goal
                        @else
                            No goal set
                        @endif
                        · closed
                    </p>
                </div>

                @foreach ($story['registry'] as $item)
                    <div class="sp-item">
                        <span class="sp-item-icon"><i class="mdi mdi-{{ $item['icon'] }}"></i></span>

                        <div class="sp-item-body">
                            <p class="sp-item-name">
                                {{ $item['name'] }}
                                @if ($item['funded'])<span class="sp-flag">Funded</span>@endif
                            </p>

                            @if (($item['goal'] ?? 0) > 0)
                                <div class="sp-bar sp-bar-sm">
                                    <span style="width: {{ $item['progress'] }}%"></span>
                                </div>
                            @endif

                            <p class="sp-item-meta">
                                {{ $story['currency'] }}{{ number_format($item['raised']) }}
                                @if (($item['goal'] ?? 0) > 0)
                                    of {{ $story['currency'] }}{{ number_format($item['goal']) }}
                                @endif
                                · {{ $item['backers'] }} people
                            </p>
                        </div>
                    </div>
                @endforeach

                <h3 class="sp-h3">Who sent something</h3>

                <ul class="sp-gifts">
                    @foreach ($story['gifts'] as $gift)
                        <li>
                            <span class="sp-av sp-av-sm">{{ StoryLibrary::initials($gift['name']) }}</span>
                            <span class="sp-gift-name">{{ $gift['name'] }}</span>
                            <span class="sp-gift-when">{{ $gift['when'] }}</span>
                            <span class="sp-gift-amt">{{ $story['currency'] }}{{ number_format($gift['amount']) }}</span>
                        </li>
                    @endforeach
                </ul>

                <p class="sp-side-note">
                    <i class="mdi mdi-lock-outline"></i>
                    Gifting is closed. The money was withdrawn to the celebrant's bank when the
                    page was still open.
                </p>
            </aside>
        </div>

        {{-- ══ MORE ═════════════════════════════════════════════════ --}}
        <section class="sp-more">
            <h2 class="sp-h2">More stories</h2>

            <div class="sp-more-grid">
                @foreach ($more as $other)
                    <a href="{{ route('stories.show', $other['slug']) }}" data-nav class="sp-more-card">
                        <img src="{{ StoryLibrary::photo($other['cover'], 400, 260) }}" alt="" loading="lazy">
                        <span>
                            <strong>{{ $other['title'] }}</strong>
                            {{ $other['occasion_label'] }} · {{ $other['location'] }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>

    {{-- ══ CTA ══════════════════════════════════════════════════════ --}}
    <section class="cta-band on-dark">
        <div class="pattern pattern-dots pattern-fade"></div>

        <div class="wrap sec-inner">
            <h2 class="h-section">Want one of these?</h2>
            <p>Yours would look like this, and it would still be here years from now.</p>

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
</article>
