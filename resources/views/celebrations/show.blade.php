<x-guest-layout>

{{--
    Template CSS overrides — uses custom properties set on the root wrapper.
    Only elements with these specific classes are affected; everything else
    falls back to the default Tailwind utility values.
--}}
<style>
    .celebration-page    { background-color: var(--tpl-bg,           #F9FAFB) !important; }
    .celebration-cover   {
        border-color:  var(--tpl-border-color, #E5E7EB) !important;
        border-width:  var(--tpl-border-width, 2px)     !important;
        border-style:  var(--tpl-border-style, solid)   !important;
    }
    .celebration-card    { background-color: var(--tpl-card,         #FFFFFF) !important; }
    .celebration-title   { color: var(--tpl-text,                    #111827) !important; }
    .celebration-text-muted { color: var(--tpl-text-muted,           #6B7280) !important; }
</style>
@php
/* Server-side initial values — applied before Alpine loads to prevent FOUC */
$_tpl        = $celebration->template;
$_initBg     = $celebration->custom_bg   ?? $_tpl?->page_bg;
$_initText   = $celebration->custom_text ?? $_tpl?->text_primary;
@endphp
@if($_tpl || $_initBg || $_initText)
<style>
    .celebration-page {
        @if($_initBg)                    --tpl-bg:           {{ $_initBg }};                   @endif
        @if($_initText)                  --tpl-text:         {{ $_initText }};                 @endif
        @if($_tpl?->card_bg)             --tpl-card:         {{ $_tpl->card_bg }};             @endif
        @if($_tpl?->text_secondary)      --tpl-text-muted:   {{ $_tpl->text_secondary }};      @endif
        @if($_tpl?->accent_color)        --tpl-accent:       {{ $_tpl->accent_color }};        @endif
        @if($_tpl?->photo_border_color)  --tpl-border-color: {{ $_tpl->photo_border_color }};  @endif
        @if($_tpl?->photo_border_style)  --tpl-border-style: {{ $_tpl->photo_border_style }};  @endif
        @if($_tpl)                       --tpl-border-width: {{ $_tpl->photo_border_style === 'none' ? '0px' : $_tpl->photo_border_width . 'px' }}; @endif
    }
</style>
@endif
@php
$templateData    = $templates->map(fn($t) => $t->only([
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
@endphp
{{-- ── Root Alpine scope: applies template CSS vars + owns customizer state ── --}}
<div
    x-data="celebrationCustomizer({{ Js::from($customizerConfig) }})"
    :style="cssVars"
    class="min-h-screen celebration-page"
>

    {{-- ── TOP BAR ──────────────────────────────────────────────────────────── --}}
    <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-sm border-b border-gray-100">
        <div class="flex items-center justify-between px-4 py-3 max-w-screen-2xl mx-auto">

            {{-- Left: back + title --}}
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ url()->previous() === url()->current() ? route('home') : url()->previous() }}"
                   class="shrink-0 w-8 h-8 rounded-xl flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition">
                    <i class="mdi mdi-arrow-left text-lg"></i>
                </a>
                <span class="text-sm font-semibold text-gray-700 truncate hidden sm:block">
                    {{ $celebration->title }}
                </span>
            </div>

            {{-- Right: actions --}}
            <div class="flex items-center gap-2 shrink-0">

                {{-- Share (everyone) --}}
                <button
                    type="button"
                    onclick="navigator.clipboard.writeText(window.location.href)"
                    title="Copy link"
                    class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
                >
                    <i class="mdi mdi-share-variant-outline text-lg"></i>
                </button>

                {{-- Countdown badge (owner or guest, only while event is upcoming) --}}
                @if($countdown)
                    <div class="hidden sm:flex items-center gap-1 bg-gray-100 rounded-xl px-2.5 py-1.5 text-xs font-medium text-gray-600">
                        <i class="mdi mdi-clock-outline text-sm"></i>
                        {{ str_pad($countdown['days'], 2, '0', STR_PAD_LEFT) }}d
                        {{ str_pad($countdown['hours'], 2, '0', STR_PAD_LEFT) }}h
                        {{ str_pad($countdown['mins'], 2, '0', STR_PAD_LEFT) }}m
                    </div>
                @endif

                {{-- Customize — owner only --}}
                @if($isOwner)
                    <button
                        type="button"
                        @click="customizerOpen = true"
                        title="Customise page"
                        class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
                        :class="customizerOpen ? 'bg-gray-100 text-gray-700' : ''"
                    >
                        <i class="mdi mdi-tune-variant text-lg"></i>
                    </button>
                @endif

            </div>
        </div>
    </header>

    {{-- ── MAIN GRID ─────────────────────────────────────────────────────────── --}}
    <div class="grid lg:grid-cols-2 min-h-[calc(100vh-53px)]">

        {{-- ── LEFT: Cover photo + sidebar ──────────────────────────────────── --}}
        <div class="relative flex items-start justify-center p-5">

            <div class="celebration-cover relative w-full max-w-lg h-[500px] rounded-3xl overflow-hidden border bg-white">

                @if($celebration->cover_photo)
                    <img
                        src="{{ asset('storage/' . $celebration->cover_photo) }}"
                        class="w-full h-full object-cover"
                        alt="Celebration cover"
                    >
                    @if($isOwner)
                        <label for="coverUpload" class="absolute inset-0 flex items-center justify-center cursor-pointer bg-black/0 hover:bg-black/25 transition group">
                            <span class="flex items-center gap-2 bg-black/50 text-white text-xs font-medium px-3 py-2 rounded-xl opacity-0 group-hover:opacity-100 transition">
                                <i class="mdi mdi-camera-outline text-sm"></i> Update photo
                            </span>
                        </label>
                        <input type="file" id="coverUpload" class="hidden" accept="image/*">
                    @endif

                @elseif($isOwner)
                    <label for="coverUpload" class="absolute inset-0 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-100 transition group">
                        <i class="mdi mdi-camera-plus-outline text-4xl text-gray-300 group-hover:text-gray-400 transition"></i>
                        <p class="mt-2 text-sm text-gray-400">Add cover photo</p>
                    </label>
                    <input type="file" id="coverUpload" class="hidden" accept="image/*">

                @else
                    <div class="w-full h-full flex items-center justify-center bg-gray-50">
                        <i class="mdi mdi-image-outline text-6xl text-gray-200"></i>
                    </div>
                @endif

                {{-- Sidebar: received gifts + suggestions --}}
                {{-- Data pre-computed in CelebrationController::show() --}}
                <div class="absolute right-3 top-4 flex flex-col gap-2.5">
                    @forelse($sidebarItems as $item)
                        <div class="relative group">
                            <button
                                @click="$dispatch('open-gift-detail', {
                                    id:       {{ $item->gift->id }},
                                    name:     '{{ addslashes($item->gift->gift_name) }}',
                                    image:    '{{ asset('storage/' . $item->gift->gift_image_url) }}',
                                    price:    {{ round($item->displayTotal, 2) }},
                                    priceUsd: {{ (float) $item->gift->gift_price }},
                                })"
                                class="relative w-11 h-11 rounded-full bg-white/95 border border-white/80 flex items-center justify-center hover:scale-105 transition overflow-hidden cursor-pointer focus:outline-none focus:ring-2 focus:ring-rose-300"
                            >
                                @if($item->gift?->gift_image_url)
                                    <img src="{{ asset('storage/' . $item->gift->gift_image_url) }}" alt="{{ $item->gift->gift_name }}" width="50">
                                @else
                                    <i class="mdi mdi-gift-outline text-base text-gray-400"></i>
                                @endif
                                @if($item->received)
                                    <span class="absolute top-0.5 right-0.5 w-2 h-2 rounded-full bg-emerald-400 ring-1 ring-white"></span>
                                @endif
                            </button>

                            <div class="absolute right-12 top-1/2 -translate-y-1/2 z-20 bg-gray-900 text-white text-[10px] rounded-lg px-2.5 py-1.5 whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none min-w-[110px]">
                                <p class="font-medium truncate max-w-[130px]">{{ $item->gift?->gift_name ?? 'Gift' }}</p>
                                @if($item->received)
                                    <p class="text-emerald-400 mt-0.5">
                                        {{ $visitorSymbol }}{{ number_format($item->displayTotal, 2) }}
                                        @if($item->count > 1)<span class="text-gray-400"> ×{{ $item->count }}</span>@endif
                                    </p>
                                @else
                                    <p class="text-gray-400 mt-0.5">Not received yet</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="w-11 h-11 rounded-xl bg-white/60 border border-white/50 flex items-center justify-center">
                            <i class="mdi mdi-gift-outline text-base text-gray-300"></i>
                        </div>
                    @endforelse
                </div>

            </div>
        </div>

        {{-- ── RIGHT: Info + content ──────────────────────────────────────────── --}}
        <div class="flex flex-col lg:h-[calc(100vh-53px)]">

            {{-- Scrollable content area --}}
            <div class="flex-1 overflow-y-auto p-6 lg:p-12">

                {{-- Header --}}
                <div>
                    <h1 class="celebration-title text-4xl lg:text-5xl font-black text-gray-900 leading-tight">
                        {{ $celebration->title }}
                    </h1>
                    <p class="celebration-text-muted mt-3 text-sm text-gray-500 leading-relaxed max-w-xl">
                        {{ $celebration->description ?? 'Celebrate this special moment with love, gifts and wishes from family and friends around the world.' }}
                    </p>
                </div>

                {{-- ── Wishlist ────────────────────────────────────────────────── --}}
                <div class="mt-8">

                    <div class="flex items-center justify-between mb-1">
                        <div>
                            <h2 class="celebration-title text-sm font-semibold text-gray-800">{{ $celebration->celebrant_name }}'s Wishlist</h2>
                            <p class="celebration-text-muted text-xs text-gray-400 mt-0.5">Help make these wishes come true</p>
                        </div>
                        <span class="celebration-text-muted text-xs text-gray-400 bg-gray-100 rounded-full px-2.5 py-1">
                            {{ $wishes->count() }} {{ Str::plural('wish', $wishes->count()) }}
                        </span>
                    </div>

                    @if($wishes->isNotEmpty())
                        {{-- Layout toggles between horizontal scroll and grid based on template --}}
                        <div
                            class="mt-3 pb-2"
                            :class="wishesLayout === 'grid'
                                ? 'grid grid-cols-3 gap-3'
                                : 'flex gap-3 overflow-x-auto'"
                        >
                            @foreach($wishes as $wish)
                                {{-- Clickable wish card — dispatches 'open-wish' for the wishes-modal component --}}
                                <button
                                    type="button"
                                    @click="$dispatch('open-wish', {
                                        id:                {{ $wish->id }},
                                        name:              '{{ addslashes($wish->name) }}',
                                        description:       '{{ addslashes($wish->description ?? '') }}',
                                        image:             '{{ $wish->wish_image ? asset('storage/'.$wish->wish_image) : '' }}',
                                        target:            {{ (float) ($wish->displayTarget ?? 0) }},
                                        current:           {{ (float) ($wish->displayCurrent ?? 0) }},
                                        allowPartial:      {{ $wish->allow_partial_contribution ? 'true' : 'false' }},
                                        status:            '{{ $wish->status }}',
                                        contributionCount: {{ (int) $wish->contribution_count }},
                                    })"
                                    class="celebration-card min-w-[50px] bg-white rounded-2xl p-3 border border-gray-100 shrink-0 text-left hover:border-violet-200 hover:shadow-sm transition group focus:outline-none focus:ring-2 focus:ring-violet-300"
                                >
                                    <div class="relative w-10 h-10 mx-auto rounded-xl bg-gray-50 border border-gray-100 overflow-hidden flex items-center justify-center">
                                        @if($wish->wish_image)
                                            <img src="{{ asset('storage/' . $wish->wish_image) }}" class="w-full h-full object-cover" alt="{{ $wish->name }}">
                                        @else
                                            <i class="mdi mdi-gift-outline text-2xl text-gray-200 group-hover:text-violet-300 transition"></i>
                                        @endif
                                        {{-- Progress ring indicator --}}
                                        @if($wish->target_amount && $wish->displayTarget > 0)
                                            @php
                                                $pct = min(100, round(($wish->displayCurrent / $wish->displayTarget) * 100));
                                            @endphp
                                            @if($pct > 0)
                                                <div class="absolute bottom-0.5 right-0.5 w-3.5 h-3.5 rounded-full bg-violet-500 flex items-center justify-center">
                                                    <i class="mdi mdi-check text-white text-[8px]" style="font-size:7px"></i>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                    <p class="celebration-title mt-2 text-xs font-semibold text-gray-800 text-center line-clamp-1">{{ $wish->name }}</p>
                                    @if($wish->target_amount && $wish->displayTarget > 0)
                                        {{-- Mini progress bar --}}
                                        @php $pct = min(100, round(($wish->displayCurrent / $wish->displayTarget) * 100)); @endphp
                                        <div class="mt-1.5 w-full h-1 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full bg-gradient-to-r from-violet-400 to-rose-400" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <p class="celebration-text-muted text-[10px] text-gray-400 text-center mt-0.5">
                                            {{ $visitorSymbol }}{{ number_format($wish->displayCurrent, 0) }}
                                            <span class="text-gray-300">/</span>
                                            {{ number_format($wish->displayTarget, 0) }}
                                        </p>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @elseif(!$isOwner)
                        <p class="celebration-text-muted text-sm text-gray-400 mt-3 py-2">No wishes added yet.</p>
                    @endif

                    {{-- Owner: add/edit wishlist --}}
                    @if($isOwner)
                        <div x-data="wishlistForm()" class="mt-5 space-y-3">

                            <div x-show="successMessage" x-transition class="flex items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-100 px-4 py-3 rounded-xl">
                                <i class="mdi mdi-check-circle-outline text-base"></i>
                                <span x-text="successMessage"></span>
                            </div>

                            <div x-show="errorMessage" x-transition class="flex items-center gap-2 text-sm text-red-600 bg-red-50 border border-red-100 px-4 py-3 rounded-xl">
                                <i class="mdi mdi-alert-circle-outline text-base"></i>
                                <span x-text="errorMessage"></span>
                            </div>

                            <form @submit.prevent="submitForm" enctype="multipart/form-data" class="space-y-3">

                                <template x-for="(wish, index) in wishes" :key="index">
                                    <div class="celebration-card bg-white p-4 rounded-2xl border border-gray-100">
                                        <div class="flex gap-3">

                                            <label class="cursor-pointer w-14 h-14 rounded-xl border border-dashed border-gray-200 hover:border-rose-300 flex items-center justify-center overflow-hidden shrink-0 transition">
                                                <template x-if="wish.preview">
                                                    <img :src="wish.preview" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!wish.preview">
                                                    <i class="mdi mdi-image-plus-outline text-xl text-gray-300"></i>
                                                </template>
                                                <input type="file" hidden accept="image/*" @change="handleImage($event, index)">
                                            </label>

                                            <div class="flex-1 space-y-2">
                                                <input
                                                    type="text"
                                                    x-model="wish.name"
                                                    placeholder="e.g. Nike Air Max"
                                                    class="w-full bg-gray-50 border border-gray-100 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-rose-400 focus:border-transparent focus:bg-white"
                                                >
                                                <div class="flex gap-2">
                                                    <div class="flex items-center flex-1 bg-gray-50 border border-gray-100 rounded-xl px-3">
                                                        <span class="text-gray-400 text-sm">{{ $visitorSymbol }}</span>
                                                        <input
                                                            type="number"
                                                            x-model="wish.amount"
                                                            placeholder="Cost"
                                                            class="w-full bg-transparent border-0 focus:ring-0 py-2.5 text-sm pl-1"
                                                        >
                                                    </div>
                                                    <button
                                                        type="button"
                                                        x-show="wishes.length > 1"
                                                        @click="removeWish(index)"
                                                        class="px-3 rounded-xl border border-gray-100 text-gray-400 hover:border-red-200 hover:text-red-400 transition"
                                                    >
                                                        <i class="mdi mdi-trash-can-outline text-lg"></i>
                                                    </button>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </template>

                                <div class="flex gap-2">
                                    <button type="button" @click="addWish" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-600 font-medium hover:bg-gray-50 transition flex items-center gap-1.5">
                                        <i class="mdi mdi-plus text-base"></i> Add Another
                                    </button>
                                    <button type="submit" :disabled="loading" class="px-5 py-2.5 rounded-xl bg-rose-500 text-white text-sm font-medium hover:bg-rose-600 transition disabled:opacity-50 flex items-center gap-1.5">
                                        <span x-show="!loading" class="flex items-center gap-1.5">
                                            <i class="mdi mdi-content-save-outline text-base"></i> Save Wishlist
                                        </span>
                                        <span x-show="loading" class="flex items-center gap-1.5">
                                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                            </svg>
                                            Saving…
                                        </span>
                                    </button>
                                </div>

                            </form>

                            {{-- wishlistForm() is defined in resources/js/modules/wishlistForm.js --}}
                        </div>
                    @endif

                </div>

                {{-- ── Messages ──────────────────────────────────────────────────── --}}
                <div class="mt-10 flex-1">

                    <div class="flex items-center justify-between mb-4">
                        <h3 class="celebration-text-muted text-xs font-semibold text-gray-400 uppercase tracking-widest">Messages</h3>
                        @if($celebration->comments->count())
                        <button
                            type="button"
                            @click="$dispatch('open-photobook')"
                            class="flex items-center gap-1.5 text-xs text-violet-600 font-semibold hover:text-violet-800 transition"
                        >
                            <i class="mdi mdi-book-open-page-variant-outline text-base"></i>
                            Photobook
                        </button>
                        @endif
                    </div>

                    @php
                    $coverPhotoUrl = $celebration->cover_photo
                        ? asset('storage/'.$celebration->cover_photo)
                        : ($celebration->celebrant_photo ? asset('storage/'.$celebration->celebrant_photo) : '');
                    @endphp

                    <div class="space-y-4">
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
                            @endphp
                            <div class="flex gap-3">
                                <div class="w-9 h-9 rounded-full bg-gray-100 border border-gray-100 overflow-hidden flex items-center justify-center shrink-0">
                                    @if(optional($comment->user)->avatar)
                                        <img src="{{ $comment->user->avatar }}" class="w-full h-full object-cover" alt="">
                                    @else
                                        <i class="mdi mdi-account text-gray-400 text-lg"></i>
                                    @endif
                                </div>
                                <div class="celebration-card flex-1 bg-white border border-gray-100 rounded-2xl p-4">
                                    <div class="flex items-center justify-between gap-2 mb-1.5">
                                        <span class="celebration-title text-sm font-semibold text-gray-900">{{ $commentAuthor }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="celebration-text-muted text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                                            {{-- Share button --}}
                                            @if($comment->message)
                                            <button
                                                type="button"
                                                @click="window.shareComment($el, {...{{ Js::from($sharePayload) }}, accentColor: activeTemplate?.accent_color ?? '#F43F5E'})"
                                                title="Share this message"
                                                class="w-6 h-6 rounded-lg flex items-center justify-center text-gray-300 hover:text-rose-400 hover:bg-rose-50 transition"
                                            >
                                                <i class="mdi mdi-share-variant-outline text-sm"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                    <p class="celebration-text-muted text-sm text-gray-600 leading-relaxed">{{ $comment->message }}</p>
                                    @if($comment->media_url)
                                        <div class="mt-3 rounded-xl overflow-hidden border border-gray-100">
                                            @if($comment->media_type === 'local-image')
                                                <img src="{{ asset('storage/' . $comment->media_url) }}" class="w-full max-h-48 object-cover" alt="">
                                            @elseif($comment->media_type === 'video')
                                                <video controls class="w-full">
                                                    <source src="{{ asset('storage/' . $comment->media_url) }}" type="video/mp4">
                                                </video>
                                            @elseif($comment->media_type === 'audio')
                                                <audio controls class="w-full p-2">
                                                    <source src="{{ asset('storage/' . $comment->media_url) }}" type="audio/mpeg">
                                                </audio>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-12 flex flex-col items-center text-center">
                                <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center mb-3">
                                    <i class="mdi mdi-message-outline text-xl text-gray-300"></i>
                                </div>
                                <p class="celebration-text-muted text-sm text-gray-400 max-w-xs">
                                    {{ $isOwner
                                        ? 'Share the celebration link to receive wishes from friends and family.'
                                        : 'Be the first to send ' . $celebration->celebrant_name . ' a message.' }}
                                </p>
                            </div>
                        @endforelse
                    </div>

                </div>

            </div>{{-- end scrollable area --}}

            {{-- ── Comment input (non-owner) — always visible at bottom ──────────── --}}
            @unless($isOwner)
                <div x-data="wishForm()" class="shrink-0 bg-white border-t border-gray-100 px-6 py-4 z-40">

                    <div x-show="commentImagePreview" x-transition class="mb-3">
                        <img :src="commentImagePreview" class="h-20 rounded-xl object-cover border border-gray-100">
                    </div>

                    <form @submit.prevent="handleSubmit" class="flex items-end gap-2">

                        <textarea
                            x-model="message"
                            rows="1"
                            placeholder="Wish {{ $celebration->celebrant_name }} well…"
                            class="flex-1 bg-gray-50 border border-gray-100 rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-rose-400 focus:border-transparent resize-none"
                        ></textarea>

                        <label for="imageUpload" class="cursor-pointer w-10 h-10 rounded-xl bg-gray-100 border border-gray-100 flex items-center justify-center hover:bg-gray-200 transition shrink-0">
                            <i class="mdi mdi-image-outline text-lg text-gray-500"></i>
                        </label>
                        <input hidden type="file" id="imageUpload" accept='image/' @change="handleCommentImageUpload($event)">

                        <button
                            type="button"
                            x-on:click="$dispatch('open-modal', 'show-gifts')"
                            class="w-10 h-10 rounded-xl bg-gray-100 border border-gray-100 flex items-center justify-center hover:bg-rose-50 hover:border-rose-200 transition shrink-0"
                        >
                            <i class="mdi mdi-gift-outline text-lg text-rose-400"></i>
                        </button>

                        <button
                            type="submit"
                            :disabled="loading"
                            class="w-10 h-10 rounded-xl bg-rose-500 text-white flex items-center justify-center hover:bg-rose-600 transition disabled:opacity-50 shrink-0"
                        >
                            <i class="mdi mdi-send text-base" x-show="!loading"></i>
                            <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                        </button>

                    </form>

                    {{-- ── Guest action modal ───────────────────────────────────── --}}
                    <div x-show="showGuestModal" x-transition class="fixed inset-0 z-50 bg-black/40 flex items-end sm:items-center justify-center p-4">
                        <div @click.away="showGuestModal=false" class="bg-white rounded-3xl w-full max-w-sm p-6">

                            {{-- WELCOME --}}
                            <template x-if="tab === 'welcome'">
                                <div>
                                    <div class="w-12 h-12 rounded-2xl bg-rose-50 flex items-center justify-center mx-auto mb-4">
                                        <i class="mdi mdi-send-circle-outline text-2xl text-rose-500"></i>
                                    </div>
                                    <h3 class="font-bold text-xl text-center">Send Your Wish</h3>
                                    <p class="text-sm text-gray-500 text-center mt-2 mb-5">Choose how you'd like to continue.</p>
                                    <div class="space-y-2">
                                        <button @click="submitAnonymous" class="w-full py-3 rounded-xl bg-rose-500 text-white text-sm font-semibold hover:bg-rose-600 transition flex items-center justify-center gap-2">
                                            <i class="mdi mdi-incognito text-base"></i> Continue as Guest
                                        </button>
                                        <button @click="saveDraft(); tab='login'" class="w-full py-3 rounded-xl border border-gray-200 text-sm font-medium hover:bg-gray-50 transition">Sign In</button>
                                        <button @click="saveDraft(); tab='register'" class="w-full py-3 rounded-xl border border-gray-200 text-sm font-medium hover:bg-gray-50 transition">Create Account</button>
                                    </div>
                                </div>
                            </template>

                            {{-- LOGIN --}}
                            <template x-if="tab === 'login'">
                                <div>
                                    <button @click="tab='welcome'" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 mb-5">
                                        <i class="mdi mdi-arrow-left text-base"></i> Back
                                    </button>
                                    <h3 class="font-bold text-xl mb-4">Welcome Back</h3>
                                    <form @submit.prevent="login" class="space-y-3">
                                        <input x-model="loginForm.email" type="email" placeholder="Email" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-rose-400 focus:border-transparent">
                                        <input x-model="loginForm.password" type="password" placeholder="Password" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-rose-400 focus:border-transparent">
                                        <button type="submit" class="w-full py-3 rounded-xl bg-rose-500 text-white text-sm font-semibold hover:bg-rose-600 transition">Sign In & Send Wish</button>
                                    </form>
                                </div>
                            </template>

                            {{-- REGISTER --}}
                            <template x-if="tab === 'register'">
                                <div>
                                    <button @click="tab='welcome'" class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 mb-5">
                                        <i class="mdi mdi-arrow-left text-base"></i> Back
                                    </button>
                                    <h3 class="font-bold text-xl mb-4">Create Account</h3>
                                    <form @submit.prevent="register" class="space-y-3">
                                        <input x-model="registerForm.name" type="text" placeholder="Full Name" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-rose-400 focus:border-transparent">
                                        <input x-model="registerForm.email" type="email" placeholder="Email" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-rose-400 focus:border-transparent">
                                        <input x-model="registerForm.password" type="password" placeholder="Password" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-rose-400 focus:border-transparent">
                                        <button type="submit" class="w-full py-3 rounded-xl bg-rose-500 text-white text-sm font-semibold hover:bg-rose-600 transition">Create Account & Send Wish</button>
                                    </form>
                                </div>
                            </template>

                            {{-- SUCCESS --}}
                            <template x-if="tab === 'success'">
                                <div class="text-center">
                                    <div class="w-12 h-12 rounded-2xl bg-green-50 flex items-center justify-center mx-auto mb-4">
                                        <i class="mdi mdi-check-circle-outline text-2xl text-green-500"></i>
                                    </div>
                                    <h3 class="font-bold text-xl">Wish Delivered</h3>
                                    <p class="text-sm text-gray-500 mt-2">Your message has been added to the celebration.</p>
                                    <div class="mt-4 bg-gray-50 border border-gray-100 rounded-xl p-4 text-left">
                                        <p class="text-xs text-gray-400 mb-1 flex items-center gap-1.5">
                                            <i class="mdi mdi-message-outline text-sm"></i> Your wish
                                        </p>
                                        <p class="text-sm text-gray-700" x-text="submittedMessage"></p>
                                    </div>
                                    @guest
                                        <div class="mt-5 text-left">
                                            <p class="text-sm font-semibold text-gray-800 mb-3">Create a free account to:</p>
                                            <ul class="space-y-2 text-sm text-gray-500">
                                                <li class="flex items-center gap-2"><i class="mdi mdi-check text-green-500 text-base"></i> Track celebrations</li>
                                                <li class="flex items-center gap-2"><i class="mdi mdi-check text-green-500 text-base"></i> Save memories</li>
                                                <li class="flex items-center gap-2"><i class="mdi mdi-check text-green-500 text-base"></i> Send gifts faster</li>
                                                <li class="flex items-center gap-2"><i class="mdi mdi-check text-green-500 text-base"></i> Get celebration reminders</li>
                                                <li class="flex items-center gap-2"><i class="mdi mdi-check text-green-500 text-base"></i> Create your own celebration page</li>
                                            </ul>
                                            <div class="mt-5 space-y-2">
                                                <button @click="tab='register'" class="w-full py-3 rounded-xl bg-rose-500 text-white text-sm font-semibold hover:bg-rose-600 transition">Create Free Account</button>
                                                <button @click="showGuestModal=false" class="w-full py-3 rounded-xl border border-gray-200 text-sm hover:bg-gray-50 transition">Maybe Later</button>
                                            </div>
                                        </div>
                                    @endguest
                                </div>
                            </template>

                        </div>
                    </div>

                </div>
            @endunless

        </div>

    </div>

    {{-- ── CUSTOMIZER PANEL (owner only, slides in from right) ─────────────────── --}}
    @if($isOwner)

        {{-- Dim backdrop on mobile --}}
        <div
            x-show="customizerOpen"
            x-transition:enter="transition-opacity duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="customizerOpen = false"
            class="fixed inset-0 z-40 bg-black/20 lg:hidden"
        ></div>

        {{-- Panel --}}
        <aside
            x-show="customizerOpen"
            x-transition:enter="transition transform duration-300 ease-out"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition transform duration-200 ease-in"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 z-50 w-72 bg-white border-l border-gray-200 flex flex-col overflow-hidden"
        >

            {{-- Panel header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
                <div class="flex items-center gap-2">
                    <i class="mdi mdi-tune-variant text-gray-500 text-lg"></i>
                    <h2 class="font-semibold text-gray-900 text-sm">Customise</h2>
                </div>
                <button
                    @click="customizerOpen = false"
                    class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
                >
                    <i class="mdi mdi-close text-base"></i>
                </button>
            </div>

            {{-- Scrollable panel body --}}
            <div class="flex-1 overflow-y-auto p-5 space-y-6">

                {{-- Occasion Templates --}}
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Choose Occasion</p>
                    <p class="text-[10px] text-gray-400 mb-3">Each theme has preset colours you can personalise below.</p>

                    <div class="space-y-2">
                        @foreach($templates as $template)
                            <button
                                type="button"
                                @click="selectTemplate({{ $template->id }})"
                                class="relative w-full flex items-center gap-3 rounded-2xl px-3 py-2.5 text-left border-2 transition focus:outline-none"
                                :class="selectedTemplateId === {{ $template->id }}
                                    ? 'border-rose-400 ring-2 ring-rose-100'
                                    : 'border-transparent hover:border-gray-100'"
                                style="background-color: {{ $template->page_bg }}"
                            >
                                {{-- Occasion icon badge --}}
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                    style="background-color: {{ $template->accent_color }}1A"
                                >
                                    <i class="mdi {{ $template->icon }} text-xl" style="color: {{ $template->accent_color }}"></i>
                                </div>

                                {{-- Name + description --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold leading-tight" style="color: {{ $template->text_primary }}">
                                        {{ $template->name }}
                                    </p>
                                    <p class="text-[10px] leading-tight mt-0.5" style="color: {{ $template->text_secondary }}">
                                        {{ $template->description }}
                                    </p>
                                </div>

                                {{-- Colour palette swatches --}}
                                <div class="flex gap-1 shrink-0">
                                    <span class="w-3 h-3 rounded-full border border-white/70"
                                          style="background: {{ $template->page_bg }}; box-shadow: 0 0 0 1px {{ $template->text_secondary }}40"></span>
                                    <span class="w-3 h-3 rounded-full"
                                          style="background: {{ $template->text_primary }}"></span>
                                    <span class="w-3 h-3 rounded-full"
                                          style="background: {{ $template->accent_color }}"></span>
                                </div>

                                {{-- Active tick --}}
                                <span
                                    x-show="selectedTemplateId === {{ $template->id }}"
                                    class="absolute top-2 right-2 w-4 h-4 rounded-full bg-rose-400 flex items-center justify-center"
                                >
                                    <i class="mdi mdi-check text-white text-[10px]"></i>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Live preview strip --}}
                <template x-if="activeTemplate">
                    <div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-3">Preview</p>
                        <div class="rounded-2xl border border-gray-100 overflow-hidden">
                            <div class="h-14 flex items-center px-3 gap-3" :style="`background-color:${customBg || activeTemplate.page_bg}`">
                                <div class="w-8 h-8 rounded-xl border-2 shrink-0 flex items-center justify-center"
                                     :style="`background:${activeTemplate.card_bg};border-color:${activeTemplate.photo_border_color};border-style:${activeTemplate.photo_border_style}`">
                                    <i class="mdi text-sm" :class="activeTemplate.icon" :style="`color:${activeTemplate.accent_color}`"></i>
                                </div>
                                <div class="flex-1 space-y-1">
                                    <div class="h-1.5 rounded-full" :style="`background:${customText || activeTemplate.text_primary};width:65%;opacity:0.85`"></div>
                                    <div class="h-1 rounded-full" :style="`background:${activeTemplate.text_secondary};width:40%;opacity:0.6`"></div>
                                </div>
                                <div class="w-5 h-5 rounded-full shrink-0" :style="`background:${activeTemplate.accent_color}`"></div>
                            </div>
                            <div class="bg-white px-3 py-2 flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-700" x-text="activeTemplate.name"></span>
                                <span class="text-[10px] text-gray-400 capitalize" x-text="activeTemplate.wishes_layout + ' layout'"></span>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Custom Colours --}}
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-3">Custom Colours</p>
                    <div class="space-y-2.5">

                        {{-- Background colour --}}
                        <div class="flex items-center justify-between gap-3">
                            <label class="text-xs text-gray-600 shrink-0">Background</label>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] text-gray-400 font-mono" x-text="customBg || 'template default'"></span>
                                <label class="relative cursor-pointer">
                                    <input
                                        type="color"
                                        x-model="customBg"
                                        class="sr-only"
                                    >
                                    <span
                                        class="block w-7 h-7 rounded-lg border-2 border-gray-200 shadow-sm transition"
                                        :style="customBg ? `background:${customBg}` : 'background:#F9FAFB'"
                                    ></span>
                                </label>
                                <button
                                    type="button"
                                    x-show="customBg"
                                    @click="customBg = ''"
                                    class="text-gray-300 hover:text-gray-500 transition"
                                    title="Clear"
                                >
                                    <i class="mdi mdi-close-circle text-base"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Text colour --}}
                        <div class="flex items-center justify-between gap-3">
                            <label class="text-xs text-gray-600 shrink-0">Text</label>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] text-gray-400 font-mono" x-text="customText || 'template default'"></span>
                                <label class="relative cursor-pointer">
                                    <input
                                        type="color"
                                        x-model="customText"
                                        class="sr-only"
                                    >
                                    <span
                                        class="block w-7 h-7 rounded-lg border-2 border-gray-200 shadow-sm transition"
                                        :style="customText ? `background:${customText}` : 'background:#111827'"
                                    ></span>
                                </label>
                                <button
                                    type="button"
                                    x-show="customText"
                                    @click="customText = ''"
                                    class="text-gray-300 hover:text-gray-500 transition"
                                    title="Clear"
                                >
                                    <i class="mdi mdi-close-circle text-base"></i>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            {{-- Panel footer --}}
            <div class="shrink-0 px-5 py-4 border-t border-gray-100 space-y-2">

                {{-- Success feedback --}}
                <div x-show="successMessage" x-transition class="flex items-center gap-2 text-xs text-green-700 bg-green-50 px-3 py-2 rounded-lg">
                    <i class="mdi mdi-check-circle-outline text-sm"></i>
                    <span x-text="successMessage"></span>
                </div>

                <div class="flex gap-2">
                    <button
                        type="button"
                        @click="cancelPreview()"
                        x-show="hasUnsavedChange"
                        class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="applyTemplate()"
                        :disabled="loading || !hasUnsavedChange"
                        class="flex-1 py-2.5 rounded-xl bg-rose-500 text-white text-sm font-semibold hover:bg-rose-600 transition disabled:opacity-40 flex items-center justify-center gap-2"
                    >
                        <span x-show="!loading" class="flex items-center gap-1.5">
                            <i class="mdi mdi-check text-base"></i> Apply
                        </span>
                        <span x-show="loading" class="flex items-center gap-1.5">
                            <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            Saving…
                        </span>
                    </button>
                </div>

            </div>

        </aside>

    @endif

</div>{{-- end root Alpine scope --}}

{{-- Gifts plate modal — outside @unless so sidebar buttons work for owners too --}}
<x-gifts-plate
    :gifts="$platformGifts"
    :visitorCurrency="$visitorCurrency"
    :visitorSymbol="$visitorSymbol"
    :walletBalance="$walletBalance"
    :isAuthenticated="$isAuthenticated"
    :celebrationId="$celebration->id"
/>

{{-- Wish contribution modal — handles all wish card clicks --}}
<x-wishes-modal
    :visitorCurrency="$visitorCurrency"
    :visitorSymbol="$visitorSymbol"
    :walletBalance="$walletBalance"
    :isAuthenticated="$isAuthenticated"
    :isOwner="$isOwner"
    :celebrationId="$celebration->id"
/>

{{-- Comment photobook generator modal --}}
<x-photobook-modal />
@php
$photoBookComments = $celebration->comments
    ->filter(function ($c) {
        return $c->message && trim($c->message);
    })
    ->map(function ($c) {
        return [
            'author' => $c->user
                ? trim($c->user->first_name . ' ' . $c->user->last_name)
                : ($c->guest_name ?? 'Guest'),

            'avatar' => $c->user && $c->user->profile_photo
                ? asset('storage/' . $c->user->profile_photo)
                : null,

            'message' => $c->message,
            'time' => $c->created_at->diffForHumans(),
        ];
    })
    ->values()
    ->toArray();
@endphp
<script>
    window.CelebrationConfig = {
        isAuthenticated: @json(auth()->check()),
        celebrationId:   {{ $celebration->id }},
        commentStoreUrl: "{{ route('celebration.comment.store') }}",
        wishesUrl:       "{{ route('celebrant.create-wishes') }}",
        csrfToken:       "{{ csrf_token() }}",
        photobook: {
            title:           "{{ addslashes($celebration->title) }}",
            celebrantName:   "{{ addslashes($celebration->celebrant_name ?? '') }}",
            coverPhoto:      "{{ $celebration->cover_photo ? asset('storage/'.$celebration->cover_photo) : ($celebration->celebrant_photo ? asset('storage/'.$celebration->celebrant_photo) : '') }}",
            accentColor:     "#7C3AED",
            eventDate:       "{{ $celebration->event_date?->format('F j, Y') ?? '' }}",
            celebrationType: "{{ $celebration->celebration_type ?? 'celebration' }}",
            appName:         "{{ config('app.name') }}",
            comments: @json($photoBookComments),
        },
    };
</script>

</x-guest-layout>
