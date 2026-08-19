<x-guest-layout :title="$celebration->title . ' — ' . config('app.name')">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

{{--
    Celebration page.

    Two equal columns, edge to edge, no page padding:
      left  — the celebrant's photos, full bleed
      right — everything else, in tabs

    Owner sees Wishes / Registry / Gifts / Settings / Photobook.
    Visitors see Wishes / Registry / Gifts.

    On small screens the photo goes full-screen and fixed, and the tab panel
    scrolls up over it.

    Template theming works through the `celebration-*` classes below — the
    customizer sets --tpl-* custom properties on the root element, so those
    class names must be preserved.
--}}
<style>
    .celebration-page      { background-color: var(--tpl-bg,        var(--surface))   !important; }
    .celebration-card      { background-color: var(--tpl-card,      var(--surface))   !important; }
    .celebration-title     { color:            var(--tpl-text,      var(--ink))       !important; }
    .celebration-text-muted{ color:            var(--tpl-text-muted,var(--muted))     !important; }
    .celebration-cover     {
        border-color: var(--tpl-border-color, transparent) !important;
        border-width: var(--tpl-border-width, 0px)         !important;
        border-style: var(--tpl-border-style, solid)       !important;
    }

    /* ══ SHELL ═══════════════════════════════════════════════════════
       Two equal columns, each its own scroll context, no outer padding. */
    .cel-shell {
        display: grid;
        grid-template-columns: 1fr 1fr;
        height: 100vh;
        height: 100dvh;
        overflow: hidden;
    }

    /* ── left: the photos ─────────────────────────────────────────── */
    .cel-visual {
        position: relative;
        overflow: hidden;
        background: var(--ink-900);
    }
    .cel-visual .swiper,
    .cel-visual .swiper-wrapper,
    .cel-visual .swiper-slide { width: 100%; height: 100%; }
    .cel-visual img { width: 100%; height: 100%; object-fit: cover; display: block; }

    /* identity sits on the photo, so the panel stays purely functional */
    .cel-veil {
        position: absolute; inset: 0; pointer-events: none; z-index: 2;
        background: linear-gradient(to top, rgba(0,0,0,0.72) 0%, rgba(0,0,0,0.28) 34%, transparent 62%);
    }
    .cel-id {
        position: absolute; left: 0; right: 0; bottom: 0; z-index: 3;
        padding: 2.5rem 2.75rem;
        color: #fff;
    }
    .cel-eyebrow {
        display: inline-flex; align-items: center; gap: 0.4rem;
        font-size: 0.66rem; font-weight: 800; letter-spacing: 0.16em;
        text-transform: uppercase; color: rgba(255,255,255,0.72);
    }
    .cel-title {
        font-family: 'Outfit', sans-serif; font-weight: 900;
        font-size: clamp(1.9rem, 3.2vw, 3.1rem); line-height: 0.98;
        letter-spacing: -0.04em; margin-top: 0.7rem;
    }
    .cel-sub {
        margin-top: 0.85rem; font-size: 0.86rem;
        color: rgba(255,255,255,0.78);
        display: flex; flex-wrap: wrap; gap: 1.1rem;
    }
    .cel-sub span { display: inline-flex; align-items: center; gap: 0.4rem; }

    /* floating controls over the photo */
    .cel-float {
        position: absolute; top: 1.5rem; z-index: 5;
        display: flex; align-items: center; gap: 0.5rem;
    }
    .cel-float.is-left  { left: 1.5rem; }
    .cel-float.is-right { right: 1.5rem; }
    .cel-fbtn {
        width: 38px; height: 38px; border-radius: 999px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(0,0,0,0.42); color: #fff;
        border: 1px solid rgba(255,255,255,0.22);
        backdrop-filter: blur(8px);
        font-size: 1.05rem; cursor: pointer; text-decoration: none;
        transition: background 0.15s;
    }
    .cel-fbtn:hover { background: rgba(0,0,0,0.68); }
    .cel-fbtn.is-accent { background: var(--primary); border-color: var(--primary); }
    .cel-fbtn.is-accent:hover { background: var(--primary-d); }

    /* the gift shortcut is mobile-only — on desktop the Gifts tab is right there */
    .cel-gift-float { display: none; }

    .cel-chip {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.3rem 0.7rem; border-radius: 999px;
        background: rgba(0,0,0,0.42); border: 1px solid rgba(255,255,255,0.22);
        backdrop-filter: blur(8px);
        font-size: 0.7rem; font-weight: 700; color: #fff;
    }

    /* ── right: the tab panel ─────────────────────────────────────── */
    .cel-panel {
        display: flex; flex-direction: column;
        min-width: 0; overflow: hidden;
        border-left: 1px solid var(--line);
    }
    .cel-tabs {
        display: flex; flex-shrink: 0;
        border-bottom: 1px solid var(--line);
        overflow-x: auto; scrollbar-width: none;
    }
    .cel-tabs::-webkit-scrollbar { display: none; }
    .cel-tab {
        flex: 1 0 auto; min-width: 0;
        display: flex; flex-direction: column; align-items: center; gap: 0.3rem;
        padding: 0.95rem 0.6rem 0.8rem;
        background: none; border: 0; border-bottom: 2px solid transparent;
        font-family: inherit; font-size: 0.68rem; font-weight: 700;
        letter-spacing: 0.02em; color: var(--muted);
        cursor: pointer; white-space: nowrap;
        transition: color 0.15s, border-color 0.15s;
    }
    .cel-tab i { font-size: 1.15rem; line-height: 1; }
    .cel-tab:hover { color: var(--ink); }
    .cel-tab[aria-selected="true"] { color: var(--primary); border-bottom-color: var(--primary); }

    .cel-body { flex: 1; overflow-y: auto; scrollbar-width: thin; }
    .cel-body::-webkit-scrollbar { width: 7px; }
    .cel-body::-webkit-scrollbar-thumb { background: var(--line); }
    .cel-pad { padding: 1.75rem 1.9rem 3rem; }

    /* ══ SHARED BITS ═════════════════════════════════════════════════ */
    .cel-sec + .cel-sec { margin-top: 2.25rem; }
    .cel-sec-t {
        font-size: 0.64rem; font-weight: 800; letter-spacing: 0.14em;
        text-transform: uppercase; color: var(--muted-2); margin-bottom: 0.9rem;
    }

    /* messages */
    .msg { display: flex; gap: 0.8rem; padding: 1.1rem 0; border-top: 1px solid var(--line); }
    .msg:first-child { border-top: 0; padding-top: 0; }
    .msg-name { font-size: 0.85rem; font-weight: 700; }
    .msg-time { font-size: 0.72rem; color: var(--muted-2); }
    .msg-text { font-size: 0.92rem; line-height: 1.6; margin-top: 0.3rem; }
    .msg-media { margin-top: 0.7rem; overflow: hidden; max-width: 300px; border: 1px solid var(--line); }
    .msg-media img { width: 100%; display: block; max-height: 260px; object-fit: cover; }

    .composer { border: 1px solid var(--line); padding: 0.9rem; }
    .composer textarea {
        width: 100%; border: 0; resize: none; font-family: inherit;
        font-size: 0.92rem; line-height: 1.55; background: transparent;
        padding: 0.2rem 0.1rem;
    }
    .composer textarea:focus { outline: none; }
    .composer-tools {
        display: flex; align-items: center; gap: 0.3rem;
        margin-top: 0.5rem; padding-top: 0.65rem; border-top: 1px solid var(--line);
    }

    .vid-tile { position: relative; width: 150px; aspect-ratio: 3/4; overflow: hidden; background: #000; cursor: pointer; }
    .vid-tile video { width: 100%; height: 100%; object-fit: cover; }

    /* registry — same 4-across grid as the gifts tab */
    .reg-grid { display: grid; grid-template-columns: repeat(4, 1fr); border-top: 1px solid var(--line); border-left: 1px solid var(--line); }
    .reg-cell {
        position: relative; display: flex; flex-direction: column;
        background: transparent; text-align: center; cursor: pointer;
        border: 0; border-right: 1px solid var(--line); border-bottom: 1px solid var(--line);
        padding: 0.75rem 0.5rem 0.7rem;
        transition: background 0.14s;
    }
    .reg-cell:hover { background: var(--surface-2); }
    .reg-cell.is-done { opacity: 0.55; }
    .reg-img {
        width: 100%; aspect-ratio: 1; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        background: var(--surface-2);
    }
    .reg-img img { width: 100%; height: 100%; object-fit: cover; }
    .reg-img i { font-size: 1.5rem; color: var(--muted-2); }
    .reg-name {
        font-size: 0.7rem; font-weight: 700; margin-top: 0.5rem;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    /* the ask, written on the item — no progress bar, no "x of y" */
    .reg-amt { font-size: 0.68rem; font-weight: 800; color: var(--primary); margin-top: 0.15rem; }

    /* supporters */
    .sup-line {
        display: inline-flex; align-items: center; gap: 0.3rem;
        background: none; border: 0; padding: 0; cursor: pointer;
        font-family: inherit; font-size: 0.78rem; color: var(--muted);
        text-align: left;
    }
    .sup-line:hover { color: var(--ink); }
    .sup-line strong { font-weight: 700; color: var(--ink); }
    .sup-list { margin-top: 1rem; border-top: 1px solid var(--line); }
    .sup-item {
        display: flex; align-items: center; gap: 0.7rem;
        padding: 0.7rem 0; border-bottom: 1px solid var(--line);
    }
    .sup-av {
        width: 30px; height: 30px; flex-shrink: 0; border-radius: 999px;
        display: flex; align-items: center; justify-content: center;
        background: var(--primary-l); color: var(--primary);
        font-size: 0.68rem; font-weight: 800; text-transform: uppercase;
    }
    .sup-name { font-size: 0.82rem; font-weight: 600; }
    .sup-amt  { margin-left: auto; font-size: 0.8rem; font-weight: 700; }
    .reg-flag {
        position: absolute; top: 0.5rem; right: 0.5rem;
        font-size: 0.55rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase;
        padding: 0.12rem 0.4rem; border-radius: 999px;
        background: var(--primary); color: #fff;
    }
    /* owner-only remove control, revealed on hover over the cell */
    .reg-wrap { position: relative; }
    .reg-del {
        position: absolute; top: 0.35rem; left: 0.35rem; z-index: 2;
        width: 22px; height: 22px; border-radius: 999px;
        display: flex; align-items: center; justify-content: center;
        background: var(--surface); color: var(--muted);
        border: 1px solid var(--line); cursor: pointer;
        font-size: 0.8rem; opacity: 0;
        transition: opacity 0.14s, color 0.14s, border-color 0.14s;
    }
    .reg-wrap:hover .reg-del, .reg-del:focus-visible { opacity: 1; }
    .reg-del:hover { color: var(--primary); border-color: var(--primary); }

    /* still used by the owner's add-an-item form */
    .wl-thumb {
        width: 44px; height: 44px; flex-shrink: 0; overflow: hidden;
        background: var(--surface-2); border: 1px solid var(--line);
        display: flex; align-items: center; justify-content: center;
    }
    .wl-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .wl-amt  { font-size: 0.72rem; color: var(--muted); margin-top: 0.25rem; }

    /* gifts */
    .cel-raised {
        font-family: 'Outfit', sans-serif; font-weight: 900;
        font-size: 2.4rem; line-height: 1; letter-spacing: -0.045em;
        color: var(--primary);
    }
    .gift-grid { display: grid; grid-template-columns: repeat(4, 1fr); border-top: 1px solid var(--line); border-left: 1px solid var(--line); }
    .gift-cell {
        position: relative; aspect-ratio: 1; background: transparent;
        display: flex; align-items: center; justify-content: center;
        border: 0; border-right: 1px solid var(--line); border-bottom: 1px solid var(--line);
        cursor: pointer; transition: background 0.14s;
    }
    .gift-cell:hover { background: var(--surface-2); }
    .gift-cell img { width: 58%; height: 58%; object-fit: contain; opacity: 0.3; filter: grayscale(1); }
    .gift-cell.is-got img { opacity: 1; filter: none; }
    .gift-n { position: absolute; bottom: 4px; right: 5px; font-size: 0.6rem; font-weight: 800; color: var(--primary); }

    /* settings */
    .set-field + .set-field { margin-top: 1rem; }
    .set-label {
        display: block; margin-bottom: 0.35rem;
        font-size: 0.64rem; font-weight: 800; letter-spacing: 0.11em;
        text-transform: uppercase; color: var(--muted);
    }
    .set-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
    .set-swatches { display: grid; grid-template-columns: repeat(auto-fill, minmax(64px, 1fr)); gap: 0.5rem; }
    .set-swatch {
        padding: 0; border: 1px solid var(--line); background: none; cursor: pointer;
        position: relative; overflow: hidden;
    }
    .set-swatch[aria-pressed="true"] { border-color: var(--primary); box-shadow: inset 0 0 0 1px var(--primary); }
    .set-swatch span { display: block; height: 34px; }
    .set-photos { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem; }
    .set-photo { position: relative; aspect-ratio: 1; overflow: hidden; border: 1px solid var(--line); }
    .set-photo img { width: 100%; height: 100%; object-fit: cover; }
    .set-add {
        display: flex; align-items: center; justify-content: center;
        aspect-ratio: 1; cursor: pointer; color: var(--muted-2);
        border: 1px dashed var(--line); font-size: 1.3rem;
    }
    .set-add:hover { border-color: var(--primary); color: var(--primary); }

    /* empty */
    .cel-empty { text-align: center; padding: 3rem 1rem; }
    .cel-empty i { font-size: 2rem; color: var(--muted-2); }
    .cel-empty p { font-size: 0.86rem; color: var(--muted); margin-top: 0.6rem; }

    /* ══ MOBILE ══════════════════════════════════════════════════════
       One column, one scroll. The photo sits in the normal flow and scrolls
       away with everything else; the panel lifts just far enough to tuck its
       rounded top over the foot of the photo. */
    @media (max-width: 900px) {
        .cel-shell { display: block; height: auto; overflow: visible; }

        .cel-visual {
            position: relative; inset: auto;
            /* the photo is the point of the page — give it most of the first
               screen and leave just enough room below to show the tabs exist */
            width: 100%; height: 74vh; min-height: 400px;
            z-index: 0;
        }
        /* clear of the panel's overlap so the name is never clipped */
        .cel-id { bottom: 0; padding: 2rem 1.5rem 3rem; }
        .cel-title { font-size: clamp(1.75rem, 8.5vw, 2.4rem); }

        .cel-panel {
            position: relative; z-index: 2;
            /* the whole overlap — a hint that there is more below, nothing more */
            margin-top: -20px;
            border-left: 0; border-top: 1px solid var(--line);
            border-radius: 20px 20px 0 0;
            box-shadow: 0 -12px 30px rgba(0,0,0,0.18);
            overflow: visible;
        }
        /* tabs pin to the top once the photo has scrolled past */
        .cel-tabs {
            position: sticky; top: 0; z-index: 3;
            border-radius: 20px 20px 0 0;
            background-color: var(--tpl-card, var(--surface));
        }
        .cel-tabs::before {
            content: ''; position: absolute; top: 7px; left: 50%;
            width: 36px; height: 3px; margin-left: -18px; border-radius: 999px;
            background: var(--line);
        }
        .cel-tab { padding-top: 1.15rem; }
        .cel-body { overflow: visible; }
        .cel-pad { padding: 1.5rem 1.25rem 4rem; }

        /* A video wish IS the message, so on a phone it spans the whole row —
           pulled back out of the 38px avatar gutter and its 0.8rem gap. */
        .vid-tile {
            width: auto;
            margin-left: calc(-38px - 0.8rem);
            aspect-ratio: 4/5;
        }
        .msg-media { max-width: none; }

        .cel-gift-float { display: inline-flex; }
    }

    @media (max-width: 420px) {
        .cel-tab { font-size: 0.62rem; padding-left: 0.4rem; padding-right: 0.4rem; }
        .set-grid { grid-template-columns: 1fr; }
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after { transition-duration: 0.001ms !important; animation-duration: 0.001ms !important; }
    }
</style>

@php
    $_tpl      = $celebration->template;
    $_initBg   = $celebration->custom_bg   ?? $_tpl?->page_bg;
    $_initText = $celebration->custom_text ?? $_tpl?->text_primary;

    // NB: $supporters is the grouped collection passed in by the controller —
    // don't shadow it here.
    $coverPhotos = $celebration->cover_photos;

    $templateData = $templates->map(fn($t) => $t->only([
        'id', 'name', 'slug', 'icon', 'description',
        'page_bg', 'card_bg', 'text_primary', 'text_secondary', 'accent_color',
        'photo_border_style', 'photo_border_color', 'photo_border_width', 'wishes_layout',
    ]))->values();

    $customizerConfig = [
        'templates'         => $templateData,
        'currentTemplateId' => $celebration->template_id,
        'customBg'          => $celebration->custom_bg  ?? '',
        'customText'        => $celebration->custom_text ?? '',
        'applyUrl'          => $isOwner ? route('celebration.template.apply', $celebration) : '',
        'csrfToken'         => csrf_token(),
    ];

    // cover_photo is a JSON array once there is more than one photo, so the
    // photobook and share cards have to read the first entry, not the column.
    $coverPhotoUrl = ($coverPhotos[0] ?? null)
        ? asset('storage/'.$coverPhotos[0])
        : ($celebration->celebrant_photo ? asset('storage/'.$celebration->celebrant_photo) : '');

    $videoWishes = $celebration->comments->where('media_type', 'video')->map(fn ($c) => [
        'id'        => $c->id,
        'author'    => $c->user ? trim($c->user->first_name.' '.$c->user->last_name) : ($c->guest_name ?? 'Anonymous'),
        'message'   => $c->message,
        'media_url' => Str::startsWith($c->media_url, ['http://','https://']) ? $c->media_url : asset('storage/'.$c->media_url),
        'time'      => $c->created_at->diffForHumans(),
    ])->values()->toArray();

    $eventDate = $celebration->event_date ?? $celebration->start_date;

    // Settings tab seeds its form from this.
    $settings = [
        'title'          => $celebration->title,
        'celebrantName'  => $celebration->celebrant_name,
        'type'           => $celebration->celebration_type,
        'description'    => $celebration->description ?? '',
        'venue'          => $celebration->venue ?? '',
        'eventDate'      => $celebration->event_date?->format('Y-m-d') ?? '',
        'startDate'      => $celebration->start_date?->format('Y-m-d') ?? '',
        'endDate'        => $celebration->end_date?->format('Y-m-d') ?? '',
        'isPublic'       => (bool) $celebration->is_public,
        'status'         => $celebration->status,
        'saveUrl'        => $isOwner ? route('celebrant.update', $celebration->slug) : '',
        'csrfToken'      => csrf_token(),
    ];
@endphp

@if($_tpl || $_initBg || $_initText)
<style>
    .celebration-page {
        @if($_initBg)                   --tpl-bg:           {{ $_initBg }};   @endif
        @if($_initText)                 --tpl-text:         {{ $_initText }}; @endif
        @if($_tpl?->card_bg)            --tpl-card:         {{ $_tpl->card_bg }}; @endif
        @if($_tpl?->text_secondary)     --tpl-text-muted:   {{ $_tpl->text_secondary }}; @endif
        @if($_tpl?->accent_color)       --tpl-accent:       {{ $_tpl->accent_color }}; @endif
        @if($_tpl?->photo_border_color) --tpl-border-color: {{ $_tpl->photo_border_color }}; @endif
        @if($_tpl?->photo_border_style) --tpl-border-style: {{ $_tpl->photo_border_style }}; @endif
        @if($_tpl) --tpl-border-width: {{ $_tpl->photo_border_style === 'none' ? '0px' : $_tpl->photo_border_width.'px' }}; @endif
    }
</style>
@endif

<div
    class="ds celebration-page"
    x-data="celebrationCustomizer({{ Js::from($customizerConfig) }})"
    :style="cssVars"
>
{{--
    @js, not @json — @json writes raw double quotes, which close this attribute
    at the first one and truncate the expression to "videoReelsPlayer([{".
    That left the whole component undefined, so no video ever opened.
--}}
<div class="cel-shell" x-data="videoReelsPlayer(@js($videoWishes))">

    {{-- ══ LEFT — the celebrant's photos ══════════════════════════════ --}}
    <div class="cel-visual celebration-cover"
         x-data="{
            currentFrame: {{ $celebration->frame ? Js::from($celebration->frame->only(['id','type','css_content','svg_content'])) : 'null' }},
         }"
         :style="currentFrame && currentFrame.type === 'css' ? currentFrame.css_content : ''">

        @if(count($coverPhotos) > 1)
            <div class="swiper cover-swiper">
                <div class="swiper-wrapper">
                    @foreach($coverPhotos as $photo)
                        <div class="swiper-slide">
                            <img src="{{ asset('storage/'.$photo) }}" alt="">
                        </div>
                    @endforeach
                </div>
                <div class="swiper-pagination"></div>
            </div>
        @elseif(count($coverPhotos) === 1)
            <img src="{{ asset('storage/'.$coverPhotos[0]) }}" alt="">
        @else
            <div class="w-full h-full flex items-center justify-center" style="background: var(--ink-800)">
                <i class="mdi mdi-image-outline" style="font-size:3rem;color:rgba(255,255,255,.22)"></i>
            </div>
        @endif

        {{-- SVG frame overlay --}}
        <template x-if="currentFrame && currentFrame.type === 'svg'">
            <div x-html="currentFrame.svg_content" class="absolute inset-0 w-full h-full pointer-events-none" style="z-index:4"></div>
        </template>

        <div class="cel-veil"></div>

        {{-- Controls --}}
        <div class="cel-float is-left">
            <a href="{{ route('home') }}" class="cel-fbtn" aria-label="Home">
                <i class="mdi mdi-arrow-left"></i>
            </a>
        </div>

        <div class="cel-float is-right">
            @if($countdown)
                <span class="cel-chip">
                    <i class="mdi mdi-clock-outline"></i>
                    {{ $countdown['days'] }}d {{ $countdown['hours'] }}h
                </span>
            @endif
            <button type="button" class="cel-fbtn" aria-label="Copy link"
                    onclick="navigator.clipboard.writeText(window.location.href); window.showAlert?.('Link copied','success')">
                <i class="mdi mdi-share-variant-outline"></i>
            </button>
            {{-- mobile only: gifting is a tab away on desktop --}}
            <button type="button" class="cel-fbtn is-accent cel-gift-float" aria-label="Send a gift"
                    x-on:click="$dispatch('open-modal','show-gifts')">
                <i class="mdi mdi-gift-outline"></i>
            </button>
        </div>

        {{-- Identity --}}
        <div class="cel-id">
            <span class="cel-eyebrow">
                <i class="mdi mdi-party-popper"></i>
                {{ Str::headline($celebration->celebration_type ?? 'Celebration') }}
            </span>
            <h1 class="cel-title">{{ $celebration->title }}</h1>
            <div class="cel-sub">
                @if($celebration->celebrant_name)
                    <span><i class="mdi mdi-account-heart-outline"></i>{{ $celebration->celebrant_name }}</span>
                @endif
                @if($eventDate)
                    <span><i class="mdi mdi-calendar-blank-outline"></i>{{ $eventDate->format('j M Y') }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ══ RIGHT — tabs ═══════════════════════════════════════════════ --}}
    <div class="cel-panel celebration-card" x-data="{ tab: 'wishes' }">

        <div class="cel-tabs celebration-card" role="tablist">
            <button class="cel-tab" role="tab" :aria-selected="tab === 'wishes'"  @click="tab = 'wishes'">
                <i class="mdi mdi-message-text-outline"></i> Wishes
            </button>
            <button class="cel-tab" role="tab" :aria-selected="tab === 'registry'" @click="tab = 'registry'">
                <i class="mdi mdi-format-list-checks"></i> Registry
            </button>
            <button class="cel-tab" role="tab" :aria-selected="tab === 'gifts'"   @click="tab = 'gifts'">
                <i class="mdi mdi-gift-outline"></i> Gifts
            </button>
            @if($isOwner)
                <button class="cel-tab" role="tab" :aria-selected="tab === 'settings'"  @click="tab = 'settings'">
                    <i class="mdi mdi-tune-variant"></i> Settings
                </button>
                <button class="cel-tab" role="tab" :aria-selected="tab === 'photobook'" @click="tab = 'photobook'">
                    <i class="mdi mdi-book-open-page-variant-outline"></i> Photobook
                </button>
            @endif
        </div>

        <div class="cel-body">

            {{-- ── WISHES ──────────────────────────────────────────── --}}
            <div class="cel-pad" x-show="tab === 'wishes'">
                @unless($isOwner)
                    <div id="composer" x-data="wishForm()" style="margin-bottom:1.5rem">
                        <div class="composer">
                            <div x-show="commentImagePreview" x-cloak style="margin-bottom:0.7rem;position:relative;display:inline-block">
                                <img :src="commentImagePreview" style="height:70px;border:1px solid var(--line)">
                                <button type="button" @click="commentImage = null; commentImagePreview = ''"
                                        style="position:absolute;top:-8px;right:-8px;width:20px;height:20px;font-size:0.7rem;font-weight:800;color:#fff;background:var(--primary);border:0;cursor:pointer">×</button>
                            </div>

                            <div x-show="commentVideoPreview" x-cloak style="margin-bottom:0.7rem;position:relative;display:inline-block">
                                <video :src="commentVideoPreview" controls style="height:100px;border:1px solid var(--line)"></video>
                                <button type="button" @click="clearVideo()"
                                        style="position:absolute;top:-8px;right:-8px;width:20px;height:20px;font-size:0.7rem;font-weight:800;color:#fff;background:var(--primary);border:0;cursor:pointer">×</button>
                            </div>

                            <form @submit.prevent="handleSubmit">
                                <textarea x-model="message" rows="2"
                                          placeholder="Write {{ $celebration->celebrant_name }} a message…"></textarea>
                                <div class="composer-tools">
                                    <button type="button" class="ibtn ibtn-bare" @click="openVideoRecorder()" aria-label="Record video">
                                        <i class="mdi mdi-video-outline"></i>
                                    </button>
                                    <label for="imageUpload" class="ibtn ibtn-bare cursor-pointer" aria-label="Add photo">
                                        <i class="mdi mdi-image-outline"></i>
                                    </label>
                                    <input hidden type="file" id="imageUpload" accept="image/*" @change="handleCommentImageUpload($event)">
                                    <button type="submit" :disabled="loading" class="btn btn-primary btn-sm" style="margin-left:auto">
                                        <i class="mdi" :class="loading ? 'mdi-loading mdi-spin' : 'mdi-send'"></i>
                                        <span x-text="loading ? 'Sending…' : 'Send'"></span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        @include('celebrations.partials.guest-modal')
                        @include('celebrations.partials.recorder-modal')
                    </div>
                @endunless

                @forelse($celebration->comments as $comment)
                    @php
                        $commentAuthor = $comment->user
                            ? ($comment->user->name ?? $comment->user->first_name.' '.$comment->user->last_name)
                            : ($comment->guest_name ?? 'Anonymous');
                        $sharePayload = [
                            'celebrantPhoto'   => $coverPhotoUrl,
                            'commentText'      => $comment->message ?? '',
                            'authorName'       => $commentAuthor,
                            'celebrationTitle' => $celebration->title,
                        ];
                        $mediaSrc = $comment->media_url
                            ? (Str::startsWith($comment->media_url, ['http://','https://']) ? $comment->media_url : asset('storage/'.$comment->media_url))
                            : null;
                    @endphp
                    <article class="msg">
                        <div class="avatar">
                            @if(optional($comment->user)->avatar)
                                <img src="{{ $comment->user->avatar }}" alt="">
                            @else
                                {{ Str::substr($commentAuthor, 0, 2) }}
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="msg-name celebration-title">{{ $commentAuthor }}</span>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="msg-time">{{ $comment->created_at->diffForHumans() }}</span>
                                    @if($comment->message)
                                        <button type="button" class="ibtn ibtn-bare" style="width:24px;height:24px;font-size:.85rem"
                                                @click="window.shareComment($el, {...{{ Js::from($sharePayload) }}, accentColor: activeTemplate?.accent_color ?? '#7c3aed'})"
                                                aria-label="Share this message">
                                            <i class="mdi mdi-share-variant-outline"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if($comment->message)
                                <p class="msg-text celebration-title">{{ $comment->message }}</p>
                            @endif

                            @if($mediaSrc)
                                @if($comment->media_type === 'local-image')
                                    <div class="msg-media"><img src="{{ $mediaSrc }}" alt=""></div>
                                @elseif($comment->media_type === 'video')
                                    <div class="vid-tile mt-3 group"
                                         x-data="{ playing: false }"
                                         x-init="
                                            const io = new IntersectionObserver((es) => es.forEach(e => {
                                                const v = e.target.querySelector('video');
                                                if (!v) return;
                                                if (e.isIntersecting) v.play().then(() => playing = true).catch(() => {});
                                                else { v.pause(); playing = false; }
                                            }), { threshold: 0.5 });
                                            io.observe($el);
                                         "
                                         @click="openReel({{ $comment->id }})">
                                        {{--
                                            No type= — recordings are webm and
                                            uploads are mp4; declaring the wrong
                                            one makes strict browsers skip the
                                            source and show a black tile.
                                            Muted is deliberate: this is a silent
                                            preview, and browsers only autoplay
                                            muted video. Sound comes from the
                                            reel player when you tap it.
                                        --}}
                                        <video muted loop playsinline @playing="playing = true" @pause="playing = false">
                                            <source src="{{ $mediaSrc }}">
                                        </video>
                                        <div class="absolute inset-0 flex items-end p-2 text-white"
                                             style="background: linear-gradient(to top, rgba(0,0,0,.65), transparent 55%)">
                                            <i class="mdi mdi-play-circle" style="font-size:1.5rem"></i>
                                        </div>
                                    </div>
                                @elseif($comment->media_type === 'audio')
                                    <audio controls class="w-full mt-3"><source src="{{ $mediaSrc }}" type="audio/mpeg"></audio>
                                @endif
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="cel-empty">
                        <i class="mdi mdi-message-text-outline"></i>
                        <p>{{ $isOwner ? 'Share your link to start receiving wishes.' : 'Be the first to leave a message.' }}</p>
                    </div>
                @endforelse

                @include('celebrations.partials.reels-modal')
            </div>

            {{-- ── REGISTRY ────────────────────────────────────────── --}}
            <div class="cel-pad" x-show="tab === 'registry'" x-cloak>
                @if($wishes->isNotEmpty())
                    <div class="reg-grid" x-data="registryManager()">
                        @foreach($wishes as $wish)
                            @php
                                $target  = (float) ($wish->displayTarget ?? 0);
                                $current = (float) ($wish->displayCurrent ?? 0);
                                $funded  = $target > 0 && $current >= $target;
                            @endphp
                            <div class="reg-wrap" x-show="!removed.includes({{ $wish->id }})">
                            @if($isOwner)
                                <button type="button" class="reg-del" aria-label="Remove {{ $wish->name }}"
                                        @click.stop="remove({{ $wish->id }}, '{{ addslashes($wish->name) }}')">
                                    <i class="mdi mdi-close"></i>
                                </button>
                            @endif
                            <button type="button" class="reg-cell @if($funded) is-done @endif"
                                style="width:100%"
                                title="{{ $wish->name }}"
                                @click="$dispatch('open-wish', {
                                    id:                {{ $wish->id }},
                                    name:              '{{ addslashes($wish->name) }}',
                                    description:       '{{ addslashes($wish->description ?? '') }}',
                                    image:             '{{ $wish->wish_image ? asset('storage/'.$wish->wish_image) : '' }}',
                                    target:            {{ $target }},
                                    current:           {{ $current }},
                                    allowPartial:      {{ $wish->allow_partial_contribution ? 'true' : 'false' }},
                                    status:            '{{ $wish->status }}',
                                    contributionCount: {{ (int) $wish->contribution_count }},
                                })">
                                @if($funded)<span class="reg-flag">Got it</span>@endif
                                <span class="reg-img">
                                    @if($wish->wish_image)
                                        <img src="{{ asset('storage/'.$wish->wish_image) }}" alt="">
                                    @else
                                        <i class="mdi mdi-gift-outline"></i>
                                    @endif
                                </span>
                                <span class="reg-name celebration-title">{{ $wish->name }}</span>
                                <span class="reg-amt">
                                    @if($target > 0)
                                        {{ $visitorSymbol }}{{ number_format($target, 0) }}
                                    @else
                                        Any amount
                                    @endif
                                </span>
                            </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="cel-empty">
                        <i class="mdi mdi-format-list-checks"></i>
                        <p>{{ $isOwner ? 'Add the things you would love to receive.' : 'Nothing on the registry yet.' }}</p>
                    </div>
                @endif

                @if($isOwner)
                    <div x-data="wishlistForm()" class="cel-sec" style="border-top:1px solid var(--line);padding-top:1.5rem">
                        <p class="cel-sec-t">Add an item</p>

                        <div x-show="successMessage" x-cloak class="badge badge-ok" style="margin-bottom:0.75rem">
                            <i class="mdi mdi-check-circle-outline"></i> <span x-text="successMessage"></span>
                        </div>
                        <div x-show="errorMessage" x-cloak class="badge badge-danger" style="margin-bottom:0.75rem">
                            <i class="mdi mdi-alert-circle-outline"></i> <span x-text="errorMessage"></span>
                        </div>

                        <form @submit.prevent="submitForm" enctype="multipart/form-data" class="space-y-3">
                            <template x-for="(wish, index) in wishes" :key="index">
                                <div class="flex gap-2.5">
                                    <label class="wl-thumb cursor-pointer" style="border-style:dashed">
                                        <template x-if="wish.preview"><img :src="wish.preview" alt=""></template>
                                        <template x-if="!wish.preview"><i class="mdi mdi-image-plus-outline" style="color:var(--muted-2)"></i></template>
                                        <input type="file" hidden accept="image/*" @change="handleImage($event, index)">
                                    </label>
                                    <div class="flex-1 space-y-2 min-w-0">
                                        <input type="text" class="input" x-model="wish.name" placeholder="e.g. Nike Air Max">
                                        <div class="flex gap-2">
                                            <div class="input-prefix flex-1">
                                                <span>{{ $visitorSymbol }}</span>
                                                <input type="number" class="input" x-model="wish.amount" placeholder="Cost">
                                            </div>
                                            <button type="button" class="ibtn" x-show="wishes.length > 1" @click="removeWish(index)" aria-label="Remove">
                                                <i class="mdi mdi-trash-can-outline"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div class="flex gap-2 pt-1">
                                <button type="button" class="btn btn-outline btn-sm" @click="addWish">
                                    <i class="mdi mdi-plus"></i> Add
                                </button>
                                <button type="submit" class="btn btn-primary btn-sm" :disabled="loading">
                                    <i class="mdi" :class="loading ? 'mdi-loading mdi-spin' : 'mdi-content-save-outline'"></i>
                                    <span x-text="loading ? 'Saving…' : 'Save'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

            {{-- ── GIFTS ───────────────────────────────────────────── --}}
            <div class="cel-pad" x-show="tab === 'gifts'" x-cloak
                 x-data="{ showSupporters: false }">
                <p class="cel-sec-t">Raised so far</p>
                <p class="cel-raised">{{ $visitorSymbol }}{{ number_format($totalGifts, 0) }}</p>

                @if ($supporters->isEmpty())
                    <p class="wl-amt">Be the first to give</p>
                @else
                    @php $lead = $supporters->first(); $others = $supporters->count() - 1; @endphp
                    <button type="button" class="sup-line" style="margin-top:0.4rem"
                            @click="showSupporters = !showSupporters" :aria-expanded="showSupporters">
                        <span>
                            From <strong>{{ Str::before($lead->name, ' ') }}</strong>@if ($others > 0)
                                and {{ $others }} other {{ Str::plural('supporter', $others) }}@endif
                        </span>
                        <i class="mdi" :class="showSupporters ? 'mdi-chevron-up' : 'mdi-chevron-down'"></i>
                    </button>

                    <div class="sup-list" x-show="showSupporters" x-cloak x-transition.opacity>
                        @foreach ($supporters as $supporter)
                            <div class="sup-item">
                                <span class="sup-av">{{ Str::substr($supporter->name, 0, 2) }}</span>
                                <span class="min-w-0">
                                    <span class="sup-name celebration-title block truncate">{{ $supporter->name }}</span>
                                    @if ($supporter->count > 1)
                                        <span class="wl-amt" style="margin-top:0">{{ $supporter->count }} gifts</span>
                                    @endif
                                </span>
                                <span class="sup-amt celebration-title">
                                    {{ $visitorSymbol }}{{ number_format($supporter->total, 0) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <button type="button" class="btn btn-primary btn-block" style="margin-top:1.25rem"
                        x-on:click="$dispatch('open-modal','show-gifts')">
                    <i class="mdi mdi-gift-outline"></i> Send a gift
                </button>

                <div class="cel-sec">
                    <p class="cel-sec-t">Received</p>
                    @if($sidebarItems->isNotEmpty())
                        <div class="gift-grid">
                            @foreach($sidebarItems as $item)
                                <button type="button" class="gift-cell @if($item->received) is-got @endif"
                                    title="{{ $item->gift?->gift_name ?? 'Gift' }}"
                                    @click="$dispatch('open-gift-detail', {
                                        id:       {{ $item->gift->id }},
                                        name:     '{{ addslashes($item->gift->gift_name) }}',
                                        image:    '{{ asset('storage/'.$item->gift->gift_image_url) }}',
                                        price:    {{ round($item->displayTotal, 2) }},
                                        priceUsd: {{ (float) $item->gift->gift_price }},
                                    })">
                                    @if($item->gift?->gift_image_url)
                                        <img src="{{ asset('storage/'.$item->gift->gift_image_url) }}" alt="">
                                    @else
                                        <i class="mdi mdi-gift-outline" style="color: var(--muted-2)"></i>
                                    @endif
                                    @if($item->count > 1)<span class="gift-n">{{ $item->count }}</span>@endif
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div class="cel-empty"><i class="mdi mdi-gift-outline"></i><p>No gifts yet.</p></div>
                    @endif
                </div>
            </div>

            {{-- ── SETTINGS (owner) ────────────────────────────────── --}}
            @if($isOwner)
                <div class="cel-pad" x-show="tab === 'settings'" x-cloak
                     x-data="celebrationSettings({{ Js::from($settings) }})">

                    <p class="cel-sec-t">Page details</p>

                    <div class="set-field">
                        <label class="set-label">Page title</label>
                        <input type="text" class="input" x-model="form.title">
                    </div>
                    <div class="set-field">
                        <label class="set-label">Celebrant</label>
                        <input type="text" class="input" x-model="form.celebrant_name">
                    </div>
                    <div class="set-field set-grid">
                        <div>
                            <label class="set-label">Occasion</label>
                            <select class="input" x-model="form.celebration_type">
                                @foreach ([
                                    'birthday' => 'Birthday', 'wedding' => 'Wedding', 'memorial' => 'Memorial',
                                    'graduation' => 'Graduation', 'anniversary' => 'Anniversary',
                                    'baby_shower' => 'Baby Shower', 'other' => 'Other',
                                ] as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="set-label">Date</label>
                            <input type="date" class="input" x-model="form.event_date">
                        </div>
                    </div>
                    <div class="set-field">
                        <label class="set-label">About</label>
                        <textarea class="input" rows="3" x-model="form.description"
                                  placeholder="A short line about this celebration"></textarea>
                    </div>
                    <div class="set-field set-grid">
                        <div>
                            <label class="set-label">Status</label>
                            <select class="input" x-model="form.status">
                                <option value="draft">Draft</option>
                                <option value="published">Live</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div>
                            <label class="set-label">Visibility</label>
                            <select class="input" x-model="form.is_public">
                                <option :value="true">Public</option>
                                <option :value="false">Unlisted</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;gap:0.75rem;margin-top:1.25rem">
                        <button type="button" class="btn btn-primary btn-sm" :disabled="saving" @click="save()">
                            <i class="mdi" :class="saving ? 'mdi-loading mdi-spin' : 'mdi-check'"></i>
                            <span x-text="saving ? 'Saving…' : 'Save details'"></span>
                        </button>
                        <span x-show="saved" x-cloak class="badge badge-ok">
                            <i class="mdi mdi-check-circle-outline"></i> Saved
                        </span>
                        <span x-show="error" x-cloak class="badge badge-danger" x-text="error"></span>
                    </div>

                    {{-- Custom URL --}}
                    <div class="cel-sec">
                        <p class="cel-sec-t">Page link</p>
                        <x-slug-editor :celebration="$celebration" />
                    </div>

                    {{-- Photos --}}
                    <div class="cel-sec">
                        <p class="cel-sec-t">Photos</p>
                        <div class="set-photos">
                            <label for="coverUpload" class="set-add" title="Add photos">
                                <i class="mdi mdi-camera-plus-outline"></i>
                            </label>
                            @foreach($coverPhotos as $photo)
                                <div class="set-photo"><img src="{{ asset('storage/'.$photo) }}" alt=""></div>
                            @endforeach
                        </div>
                        <input type="file" id="coverUpload" class="hidden" accept="image/*" multiple>
                    </div>

                    {{-- Frame --}}
                    <div class="cel-sec"
                         x-data="{
                            frameId: {{ $celebration->frame_id ?? 'null' }},
                            async pick(id) {
                                this.frameId = id;
                                try {
                                    const res = await fetch('{{ route('celebrant.update-frame', $celebration->id) }}', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                        body: JSON.stringify({ frame_id: id }),
                                    });
                                    const data = await res.json();
                                    if (data.success) window.location.reload();
                                    else window.showAlert?.(data.message || 'Could not set the frame', 'error');
                                } catch (e) { window.showAlert?.('Something went wrong', 'error'); }
                            }
                         }">
                        <p class="cel-sec-t">Frame</p>
                        <div class="set-swatches">
                            <button type="button" class="set-swatch" :aria-pressed="frameId === null" @click="pick(null)" title="None">
                                <span style="display:flex;align-items:center;justify-content:center;color:var(--muted-2)">
                                    <i class="mdi mdi-close"></i>
                                </span>
                            </button>
                            @foreach($frames as $frame)
                                <button type="button" class="set-swatch" :aria-pressed="frameId === {{ $frame->id }}"
                                        @click="pick({{ $frame->id }})" title="{{ $frame->name }}">
                                    <span style="{{ $frame->type === 'css' ? $frame->css_content : '' }};background:var(--surface-2);display:flex;align-items:center;justify-content:center;overflow:hidden">
                                        @if($frame->type === 'svg'){!! $frame->svg_content !!}@endif
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Theme --}}
                    <div class="cel-sec">
                        <p class="cel-sec-t">Theme</p>
                        <div class="set-swatches">
                            @foreach($templates as $template)
                                <button type="button" class="set-swatch"
                                        :aria-pressed="selectedTemplateId === {{ $template->id }}"
                                        @click="selectTemplate({{ $template->id }})" title="{{ $template->name }}">
                                    <span style="background: {{ $template->page_bg }}">
                                        <span style="display:block;width:50%;height:100%;background: {{ $template->card_bg }}"></span>
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        <div class="set-field set-grid" style="margin-top:0.9rem">
                            <div>
                                <label class="set-label">Background</label>
                                <input type="color" x-model="customBg" class="input" style="padding:0.2rem;height:38px">
                            </div>
                            <div>
                                <label class="set-label">Text</label>
                                <input type="color" x-model="customText" class="input" style="padding:0.2rem;height:38px">
                            </div>
                        </div>

                        <div style="display:flex;align-items:center;gap:0.6rem;margin-top:0.9rem">
                            <button type="button" class="btn btn-primary btn-sm"
                                    :disabled="!hasUnsavedChange || loading" @click="applyTemplate()">
                                <i class="mdi" :class="loading ? 'mdi-loading mdi-spin' : 'mdi-check'"></i>
                                <span x-text="loading ? 'Saving…' : 'Save theme'"></span>
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" x-show="hasUnsavedChange" @click="cancelPreview()">
                                Reset
                            </button>
                            <span x-show="successMessage" x-cloak class="badge badge-ok" x-text="successMessage"></span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ── PHOTOBOOK (owner) ───────────────────────────────── --}}
            @if($isOwner)
                <div class="cel-pad" x-show="tab === 'photobook'" x-cloak>
                    @if($celebration->comments->count())
                        <p class="cel-sec-t">Photobook</p>
                        <p class="msg-text celebration-text-muted" style="margin-top:0">
                            {{ $celebration->comments->count() }} {{ Str::plural('message', $celebration->comments->count()) }} ready to print.
                        </p>
                        <button type="button" class="btn btn-primary btn-block" style="margin-top:1.25rem"
                                @click="$dispatch('open-photobook')">
                            <i class="mdi mdi-book-open-page-variant-outline"></i> Build photobook
                        </button>
                    @else
                        <div class="cel-empty">
                            <i class="mdi mdi-book-open-page-variant-outline"></i>
                            <p>Once wishes come in, you can turn them into a photobook here.</p>
                        </div>
                    @endif
                </div>
            @endif

        </div>{{-- /cel-body --}}
    </div>{{-- /cel-panel --}}

</div>{{-- /cel-shell --}}
</div>{{-- end root Alpine scope --}}

{{-- ══ MODALS ════════════════════════════════════════════════════════ --}}
<x-gifts-plate
    :gifts="$platformGifts"
    :visitorCurrency="$visitorCurrency"
    :visitorSymbol="$visitorSymbol"
    :walletBalance="$walletBalance"
    :isAuthenticated="$isAuthenticated"
    :celebrationId="$celebration->id"
/>

<x-wishes-modal
    :visitorCurrency="$visitorCurrency"
    :visitorSymbol="$visitorSymbol"
    :walletBalance="$walletBalance"
    :isAuthenticated="$isAuthenticated"
    :isOwner="$isOwner"
    :celebrationId="$celebration->id"
/>

@include('celebrations.partials.share-fallback')

<x-photobook-modal />

@php
$photoBookComments = $celebration->comments
    ->filter(fn ($c) => ($c->message && trim($c->message)) || ($c->media_url && $c->media_type === 'local-image'))
    ->map(fn ($c) => [
        'author'    => $c->user ? trim($c->user->first_name.' '.$c->user->last_name) : ($c->guest_name ?? 'Guest'),
        'avatar'    => $c->user && $c->user->profile_photo ? asset('storage/'.$c->user->profile_photo) : null,
        'message'   => $c->message,
        'media_url' => ($c->media_url && $c->media_type === 'local-image') ? asset('storage/'.$c->media_url) : null,
        'time'      => $c->created_at->diffForHumans(),
    ])->values()->toArray();
@endphp

<script>
    window.CelebrationConfig = {
        isAuthenticated: @json(auth()->check()),
        celebrationId:   {{ $celebration->id }},
        commentStoreUrl: "{{ route('celebration.comment.store') }}",
        wishesUrl:       "{{ route('celebrant.create-wishes') }}",
        csrfToken:       "{{ csrf_token() }}",
        photobook: {
            title:           @json($celebration->title),
            celebrantName:   @json($celebration->celebrant_name ?? ''),
            coverPhoto:      @json($coverPhotoUrl),
            accentColor:     "#7c3aed",
            eventDate:       @json($celebration->event_date?->format('F j, Y') ?? ''),
            celebrationType: @json($celebration->celebration_type ?? 'celebration'),
            appName:         @json(config('app.name')),
            comments:        @json($photoBookComments),
        },
    };

    /**
     * Registry tab — lets the celebrant take an item off the page.
     * The server soft-deletes, so contributions already made survive.
     */
    function registryManager() {
        return {
            removed: [],
            async remove(id, name) {
                if (!confirm(`Remove "${name}" from your registry?`)) return;

                try {
                    const res = await fetch(`/celebrant/wishes/${id}`, {
                        method:  'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': window.CelebrationConfig.csrfToken,
                            'Accept':       'application/json',
                        },
                    });
                    const data = await res.json();

                    if (data.success) {
                        this.removed.push(id);
                        window.showAlert?.(data.message, 'success');
                    } else {
                        window.showAlert?.(data.message || 'Could not remove it.', 'error');
                    }
                } catch (e) {
                    window.showAlert?.('Network error.', 'error');
                }
            },
        };
    }
    window.registryManager = registryManager;

    /**
     * Settings tab — saves the page details through celebrant.update.
     */
    function celebrationSettings(config) {
        return {
            saving: false,
            saved:  false,
            error:  '',
            form: {
                title:            config.title,
                celebrant_name:   config.celebrantName,
                celebration_type: config.type,
                description:      config.description,
                venue:            config.venue,
                event_date:       config.eventDate,
                start_date:       config.startDate,
                end_date:         config.endDate,
                is_public:        config.isPublic,
                status:           config.status,
            },

            async save() {
                this.saving = true;
                this.error  = '';
                this.saved  = false;

                try {
                    // A real PUT — `_method` spoofing is not read out of a JSON
                    // body, only out of form-encoded input.
                    const res = await fetch(config.saveUrl, {
                        method:  'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken,
                            'Accept':       'application/json',
                        },
                        body: JSON.stringify(this.form),
                    });
                    const data = await res.json();

                    if (res.ok && data.success) {
                        this.saved = true;
                        setTimeout(() => { this.saved = false; }, 2500);
                    } else {
                        this.error = data.message
                            || Object.values(data.errors ?? {})[0]?.[0]
                            || 'Could not save.';
                    }
                } catch (e) {
                    this.error = 'Network error.';
                } finally {
                    this.saving = false;
                }
            },
        };
    }
    window.celebrationSettings = celebrationSettings;

    function videoReelsPlayer(videoList) {
        return {
            videos: videoList,
            activeIndex: -1,
            isOpen: false,
            isPlaying: false,
            isMuted: false,
            progress: 0,
            duration: 0,
            currentTime: 0,

            get activeVideo() {
                return this.activeIndex >= 0 ? this.videos[this.activeIndex] : null;
            },

            openReel(videoId) {
                const idx = this.videos.findIndex(v => v.id === videoId);
                if (idx === -1) return;
                this.activeIndex = idx;
                this.isOpen      = true;
                this.isPlaying   = true;
                this.isMuted     = false;
                this.progress    = 0;
                this.$nextTick(() => this.initVideo());
            },

            close() {
                this.isOpen = false;
                this.pauseVideo();
                this.activeIndex = -1;
            },

            initVideo() {
                const el = document.getElementById('reelVideoPlayer');
                if (!el) return;
                el.load();
                el.muted = this.isMuted;
                el.play().then(() => { this.isPlaying = true; })
                         .catch(() => { this.isPlaying = false; });
            },

            togglePlay() {
                const el = document.getElementById('reelVideoPlayer');
                if (!el) return;
                if (this.isPlaying) { el.pause(); this.isPlaying = false; }
                else { el.play().catch(() => {}); this.isPlaying = true; }
            },

            /*
             * Space toggles playback, but the listener is on the window, so it
             * also fires while the player is closed and while someone is typing.
             * Guarding here rather than in the template keeps it a plain
             * expression — Alpine cannot parse an `if` statement in an attribute.
             */
            onSpaceKey(event) {
                if (! this.isOpen) return;

                const el = event.target;
                if (el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.isContentEditable)) return;

                event.preventDefault();
                this.togglePlay();
            },

            pauseVideo() {
                const el = document.getElementById('reelVideoPlayer');
                if (el) el.pause();
                this.isPlaying = false;
            },

            toggleMute() {
                const el = document.getElementById('reelVideoPlayer');
                if (!el) return;
                el.muted = !el.muted;
                this.isMuted = el.muted;
            },

            onTimeUpdate() {
                const el = document.getElementById('reelVideoPlayer');
                if (!el) return;
                this.currentTime = el.currentTime;
                this.duration    = el.duration || 1;
                this.progress    = (this.currentTime / this.duration) * 100;
            },

            onEnded() { this.next(); },

            next() {
                if (this.activeIndex < this.videos.length - 1) {
                    this.activeIndex++;
                    this.progress = 0;
                    this.$nextTick(() => this.initVideo());
                } else {
                    this.close();
                }
            },

            prev() {
                if (this.activeIndex > 0) {
                    this.activeIndex--;
                    this.progress = 0;
                    this.$nextTick(() => this.initVideo());
                }
            }
        };
    }
    window.videoReelsPlayer = videoReelsPlayer;

    // Cover carousel
    document.addEventListener('DOMContentLoaded', () => {
        if (document.querySelector('.cover-swiper') && window.Swiper) {
            new Swiper('.cover-swiper', {
                loop: true,
                autoplay: { delay: 5200, disableOnInteraction: false },
                effect: 'fade',
                fadeEffect: { crossFade: true },
                pagination: { el: '.swiper-pagination', clickable: true },
            });
        }
    });
</script>

</x-guest-layout>
