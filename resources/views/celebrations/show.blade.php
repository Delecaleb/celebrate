@php
    // Social card and indexing for this celebration. Computed up here because
    // the attributes on the tag below are evaluated before anything in its body.
    $seoCover = ($celebration->cover_photos[0] ?? null)
        ? asset('storage/' . $celebration->cover_photos[0])
        : ($celebration->celebrant_photo ? asset('storage/' . $celebration->celebrant_photo) : null);

    $seoOccasion = strtolower(str_replace('_', ' ', $celebration->celebration_type ?? 'celebration'));

    $seoDescription = $celebration->description
        ?: "{$celebration->celebrant_name}'s {$seoOccasion} on CelebrateMi — leave a wish, add a photo or send a gift from anywhere.";

    // A page its owner has not made public, or has not published, must never
    // reach an index — the link still works for anyone who has it.
    $seoNoindex = ! $celebration->is_public || $celebration->status !== 'published';
@endphp

<x-guest-layout
    :title="$celebration->title . ' — ' . config('seo.name')"
    :description="$seoDescription"
    :ogImage="$seoCover"
    :ogImageAlt="$celebration->title"
    ogType="article"
    :noindex="$seoNoindex"
>
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
    .msg-media { margin-top: 0.7rem; overflow: hidden; max-width: 300px; border: 1px solid var(--line); border-radius: 14px; }
    .msg-media img { width: 100%; display: block; max-height: 260px; object-fit: cover; }

    /* ── who has given ───────────────────────────────────────────────
       One line that flips to the next giver every few seconds, so a page
       with two hundred givers takes the same room as one with two. It sits
       above the wishes because it is the social proof you want read first. */
    .give-ticker {
        display: flex; align-items: center; gap: 0.65rem;
        border: 1px solid var(--line); border-radius: 999px;
        background: var(--surface-2);
        padding: 0.5rem 1rem; margin-bottom: 1.5rem;
    }
    .give-ticker > i { color: var(--primary); font-size: 1.05rem; line-height: 1; flex-shrink: 0; }
    .give-ticker-win { position: relative; flex: 1; min-width: 0; height: 1.3rem; overflow: hidden; }
    .give-ticker-track { position: absolute; inset: 0; transition: transform 0.55s cubic-bezier(.22,.8,.3,1); }
    .give-ticker-track[data-still] { transition: none; }
    .give-ticker-row {
        height: 1.3rem; display: flex; align-items: center; gap: 0.3rem;
        font-size: 0.84rem; white-space: nowrap;
        overflow: hidden; text-overflow: ellipsis;
    }
    /* Sized to the row so the strip never changes height as gifts scroll. */
    .give-ticker-img {
        width: 1.15rem; height: 1.15rem; flex-shrink: 0;
        object-fit: contain; border-radius: 4px;
    }
    .give-ticker-icon { color: var(--primary); font-size: 1rem; line-height: 1; flex-shrink: 0; }
    .give-ticker-row b { font-weight: 700; }
    .give-ticker-row span { font-weight: 800; color: var(--primary); }
    .give-ticker-row em { font-style: normal; color: var(--muted-2); }

    .composer { border: 1px solid var(--line); padding: 0.9rem; border-radius: 16px; }
    .composer textarea {
        width: 100%; border: 0; resize: none; font-family: inherit;
        font-size: 0.92rem; line-height: 1.55; background: transparent;
        padding: 0.2rem 0.1rem;
    }
    .composer textarea:focus { outline: none; }
    /* Floating label. The label starts where the text will be and rises out of
       the way once the field has something in it — so the question the field is
       asking survives being answered, which a placeholder does not. */
    .ff { position: relative; margin-bottom: 0.35rem; }
    .composer .ff-control,
    .ff-control {
        width: 100%; border: 0; border-bottom: 1px solid var(--line);
        font-family: inherit; background: transparent; color: var(--ink);
        font-size: 0.92rem; line-height: 1.55; resize: none;
        padding: 1.15rem 0.1rem 0.45rem;
    }
    .ff-control:focus { outline: none; border-bottom-color: var(--primary); }
    .ff-label {
        position: absolute; left: 0.1rem; top: 0.95rem;
        font-size: 0.92rem; color: var(--muted-2); pointer-events: none;
        transform-origin: 0 0; transition: transform 0.13s ease, color 0.13s ease;
    }
    /* Empty and unfocused: sitting where the text goes. Anything else: risen. */
    .ff-control:not(:placeholder-shown) + .ff-label,
    .ff-control:focus + .ff-label {
        transform: translateY(-0.95rem) scale(0.76);
        color: var(--muted);
    }
    .ff-control:focus + .ff-label { color: var(--primary); }

    .composer-name { font-weight: 600; font-size: 0.86rem; }
    .composer-name:read-only { color: var(--muted); cursor: default; }
    .composer-tools {
        display: flex; align-items: center; gap: 0.3rem;
        margin-top: 0.5rem; padding-top: 0.65rem; border-top: 1px solid var(--line);
    }

    /* The composer belongs where you finish reading, not above what you came
       to read — so it sits at the foot of the list and stays pinned there
       while the wishes scroll behind it. .cel-body is the scrollport on
       desktop; on a phone that is `overflow: visible`, so the viewport takes
       over and it docks to the bottom of the screen instead. Both are right. */
    .composer-dock {
        position: sticky; bottom: 0; z-index: 4;
        margin-top: 1.75rem;
        padding: 0.9rem 0 0.2rem;
        background: var(--surface);
    }
    /* so the last wish fades out under the dock instead of being sliced off */
    .composer-dock::before {
        content: ''; position: absolute; left: 0; right: 0; top: -26px; height: 26px;
        background: linear-gradient(to top, var(--surface), transparent);
        pointer-events: none;
    }

    .vid-tile { position: relative; width: 150px; aspect-ratio: 3/4; overflow: hidden; background: #000; cursor: pointer; border-radius: 14px; }
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
    /* The wall of gifts this celebration has been sent. Four across on a
       phone, six from tablet width, eight on a wide screen — the tiles stay
       square and the artwork scales with them. */
    .gift-grid {
        display: grid; grid-template-columns: repeat(4, 1fr);
        border-top: 1px solid var(--line); border-left: 1px solid var(--line);
    }
    @media (min-width: 640px)  { .gift-grid { grid-template-columns: repeat(6, 1fr); } }
    @media (min-width: 1024px) { .gift-grid { grid-template-columns: repeat(8, 1fr); } }

    .gift-cell {
        position: relative; aspect-ratio: 1; background: transparent;
        display: flex; align-items: center; justify-content: center;
        border: 0; border-right: 1px solid var(--line); border-bottom: 1px solid var(--line);
        cursor: pointer; transition: background 0.14s, transform 0.14s;
    }
    .gift-cell:hover { background: var(--surface-2); transform: scale(1.06); }

    /* Everything on this wall was actually received, so nothing is greyed out
       any more — these are the gifts people sent, and they should look it. */
    .gift-cell img { width: 74%; height: 74%; object-fit: contain; }
    .gift-cell i   { font-size: clamp(1.5rem, 4.2vw, 2.1rem); line-height: 1; }

    .gift-n {
        position: absolute; bottom: 3px; right: 4px;
        min-width: 17px; padding: 0 4px; border-radius: 999px;
        background: var(--primary); color: #fff;
        font-size: 0.62rem; font-weight: 800; line-height: 17px; text-align: center;
    }

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
    .set-hint { margin-top: 0.5rem; font-size: 0.76rem; color: var(--muted); }

    /* empty cover, for the owner — the placeholder is the add button.
       Stacked above the frame overlay (z 4) so it stays clickable. */
    .cel-cover-empty {
        position: relative; z-index: 5;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 0.45rem; width: 100%; height: 100%; padding: 1.5rem;
        background: var(--ink-800); color: #fff; text-align: center; cursor: pointer;
    }
    .cel-cover-empty-icon {
        display: flex; align-items: center; justify-content: center;
        width: 76px; height: 76px; border-radius: 999px;
        border: 2px dashed rgba(255,255,255,.35); background: rgba(255,255,255,.06);
        font-size: 2.4rem; color: rgba(255,255,255,.8);
        transition: transform .15s ease, border-color .15s ease, background .15s ease;
    }
    .cel-cover-empty-t { margin-top: 0.35rem; font-size: 1rem; font-weight: 800; letter-spacing: 0.01em; }
    .cel-cover-empty-s { font-size: 0.78rem; color: rgba(255,255,255,.6); }
    .cel-cover-empty:hover .cel-cover-empty-icon {
        transform: scale(1.05); border-color: var(--primary); background: rgba(255,255,255,.12); color: #fff;
    }

    /* settings — the add-cover card */
    .set-cover-cta {
        display: flex; align-items: center; gap: 0.85rem;
        padding: 0.9rem 1rem; border-radius: 14px; cursor: pointer;
        border: 2px dashed var(--primary); background: color-mix(in srgb, var(--primary) 8%, transparent);
        transition: background .15s ease, transform .15s ease;
    }
    .set-cover-cta:hover { background: color-mix(in srgb, var(--primary) 14%, transparent); transform: translateY(-1px); }
    .set-cover-cta-icon {
        flex: none; display: flex; align-items: center; justify-content: center;
        width: 46px; height: 46px; border-radius: 12px;
        background: var(--primary); color: #fff; font-size: 1.5rem;
    }
    .set-cover-cta-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.15rem; }
    .set-cover-cta-t { font-size: 0.95rem; font-weight: 800; color: var(--primary); }
    .set-cover-cta-s { font-size: 0.76rem; color: var(--muted); }
    .set-cover-cta-go { font-size: 1.3rem; color: var(--primary); }

    /* settings — each photo, with its remove button */
    .set-photo { border-radius: 10px; }
    .set-photo.is-removing img { opacity: .45; }
    .set-photo-badge {
        position: absolute; left: 5px; bottom: 5px;
        padding: 1px 7px; border-radius: 999px;
        background: rgba(0,0,0,.6); color: #fff; font-size: 0.6rem; font-weight: 800; letter-spacing: 0.04em;
    }
    .set-photo-del {
        position: absolute; top: 5px; right: 5px;
        display: flex; align-items: center; justify-content: center;
        width: 28px; height: 28px; padding: 0; border: 0; border-radius: 999px; cursor: pointer;
        background: rgba(0,0,0,.62); color: #fff; font-size: 0.95rem;
    }
    .set-photo-del:hover { background: var(--danger, #dc2626); }
    .set-photo-del:disabled { cursor: default; opacity: .7; }

    /* colour pickers — wheel, hex box, presets */
    .set-colour-row { display: flex; align-items: center; gap: 0.5rem; }
    .set-colour-wheel {
        position: relative; flex: none; width: 38px; height: 38px; border-radius: 10px;
        border: 1px solid var(--line); cursor: pointer; overflow: hidden;
        box-shadow: inset 0 0 0 2px rgba(255,255,255,0.6);
    }
    /* the native input stays clickable but invisible over the swatch */
    .set-colour-wheel input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; border: 0; padding: 0; }
    .set-colour-hex { flex: 1; min-width: 0; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; text-transform: lowercase; }
    .set-colour-presets { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.5rem; }
    .set-colour-chip {
        width: 24px; height: 24px; padding: 0; border-radius: 999px; cursor: pointer;
        border: 1px solid var(--line);
    }
    .set-colour-chip[aria-pressed="true"] { box-shadow: 0 0 0 2px var(--tpl-card, var(--surface)), 0 0 0 4px var(--primary); }
    .set-colour-preview {
        display: flex; align-items: baseline; gap: 0.6rem; margin-top: 0.9rem;
        padding: 0.75rem 0.9rem; border-radius: 12px; border: 1px solid var(--line); font-size: 0.82rem;
    }
    .set-colour-preview strong { font-size: 1.2rem; }

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
        @elseif($isOwner)
            {{-- An empty cover is the first thing the owner should fix, so for
                 them the placeholder is the button: it opens the same picker
                 as Settings → Photos. --}}
            <label for="coverUpload" class="cel-cover-empty" title="Add a cover image">
                <span class="cel-cover-empty-icon"><i class="mdi mdi-image-plus-outline"></i></span>
                <span class="cel-cover-empty-t">Add Cover Image</span>
                <span class="cel-cover-empty-s">Up to 4 photos, {{ \App\Support\UploadLimits::label() }} each</span>
            </label>
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
    {{-- The owner lands on Settings: it is where a new page gets built. --}}
    <div class="cel-panel celebration-card" x-data="{ tab: '{{ $isOwner ? 'settings' : 'wishes' }}' }">

        <div class="cel-tabs celebration-card" role="tablist">
            @if($isOwner)
                <button class="cel-tab" role="tab" :aria-selected="tab === 'settings'"  @click="tab = 'settings'">
                    <i class="mdi mdi-tune-variant"></i> Settings
                </button>
            @endif
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
                <button class="cel-tab" role="tab" :aria-selected="tab === 'photobook'" @click="tab = 'photobook'">
                    <i class="mdi mdi-book-open-page-variant-outline"></i> Photobook
                </button>
            @endif
        </div>

        <div class="cel-body">

            {{-- ── WISHES ──────────────────────────────────────────── --}}
            <div class="cel-pad" x-show="tab === 'wishes'" @if($isOwner) x-cloak @endif>
              {{-- wishForm() wraps the whole tab so the composer can be sticky:
                   a sticky element only travels within its own parent, so the
                   parent has to be the tall thing, not the box itself. The
                   owner has no composer, so they get the wrapper without the
                   component. --}}
              <div @unless($isOwner) x-data="wishForm()" @endunless>

                {{-- ── who has given ──────────────────────────────────── --}}
                @if ($supporters->isNotEmpty())
                    @php
                        /*
                         * Newest first, which is what the controller already
                         * sorts by — the line reads as "who just gave".
                         *
                         * What they gave, not what it cost: "Cake × 2" means
                         * something to a celebrant, and to everyone else
                         * reading the page, in a way that a naira figure on a
                         * public page does not.
                         */
                        $ticker = $supporters->take(24)->map(function ($s) {
                            $items = collect($s->items ?? []);

                            $shown = $items->take(2)
                                ->map(fn ($i) => $i['qty'] > 1 ? "{$i['name']} × {$i['qty']}" : $i['name'])
                                ->implode(', ');

                            $more = max(0, $items->count() - 2);

                            $lead = $items->first();

                            return [
                                'name'  => $s->name,
                                // The picture of the thing given, or its icon.
                                'image' => $lead['image'] ?? null,
                                'icon'  => $lead['icon'] ?? 'mdi-gift-outline',
                                // Gifts are sent; money towards a registry item is given.
                                'verb' => $items->isNotEmpty() && $items->every(fn ($i) => $i['kind'] === 'wish')
                                    ? 'gave towards'
                                    : 'sent',
                                'what' => $shown . ($more > 0 ? " +{$more} more" : ''),
                            ];
                        })->values();
                    @endphp

                    <div class="give-ticker"
                         x-data="{
                            rows: {{ Js::from($ticker) }},
                            i: 0,
                            still: false,
                            init() {
                                if (this.rows.length < 2) return;

                                // A copy of the first row at the end means the
                                // wrap is never seen as a rewind.
                                this.rows = [...this.rows, this.rows[0]];
                                setInterval(() => this.advance(), 3200);
                            },
                            advance() {
                                this.i++;

                                if (this.i < this.rows.length - 1) return;

                                // Showing the copy now. Let the slide land,
                                // then jump to the real first row with the
                                // transition switched off.
                                setTimeout(() => {
                                    this.still = true;
                                    this.i     = 0;
                                    requestAnimationFrame(() =>
                                        requestAnimationFrame(() => this.still = false)
                                    );
                                }, 600);
                            }
                         }">

                        <div class="give-ticker-win">
                            <div class="give-ticker-track"
                                 :data-still="still ? '' : null"
                                 :style="`transform: translateY(-${i * 1.3}rem)`">
                                <template x-for="(row, n) in rows" :key="n">
                                    <div class="give-ticker-row">
                                        {{-- The gift itself, where a generic
                                             parcel icon used to sit. --}}
                                        <template x-if="row.image">
                                            <img class="give-ticker-img" :src="row.image" alt="" loading="lazy">
                                        </template>
                                        <template x-if="! row.image">
                                            <i class="mdi give-ticker-icon" :class="row.icon" aria-hidden="true"></i>
                                        </template>

                                        <b x-text="row.name"></b>
                                        <em x-text="row.verb"></em>
                                        <span x-text="row.what"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                @endif

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

                @unless($isOwner)
                    {{-- Docked to the foot of the list. The modals sit outside
                         it — both are fixed and cloaked, so they take no room,
                         and keeping them clear of the sticky box keeps them out
                         of its stacking context. --}}
                    <div id="composer" class="composer-dock">
                        <div class="composer">
                            <div x-show="commentImagePreview" x-cloak style="margin-bottom:0.7rem;position:relative;display:inline-block">
                                <img :src="commentImagePreview" style="height:70px;border:1px solid var(--line);border-radius:10px">
                                <button type="button" @click="commentImage = null; commentImagePreview = ''"
                                        style="position:absolute;top:-8px;right:-8px;width:20px;height:20px;border-radius:999px;font-size:0.7rem;font-weight:800;color:#fff;background:var(--primary);border:0;cursor:pointer">×</button>
                            </div>

                            <div x-show="commentVideoPreview" x-cloak style="margin-bottom:0.7rem;position:relative;display:inline-block">
                                <video :src="commentVideoPreview" controls style="height:100px;border:1px solid var(--line);border-radius:10px"></video>
                                <button type="button" @click="clearVideo()"
                                        style="position:absolute;top:-8px;right:-8px;width:20px;height:20px;border-radius:999px;font-size:0.7rem;font-weight:800;color:#fff;background:var(--primary);border:0;cursor:pointer">×</button>
                            </div>

                            <form @submit.prevent="handleSubmit">
                                {{-- Who it is from. Filled in already for anyone signed
                                     in, and the one thing that keeps the wall from
                                     filling up with "Anonymous".

                                     Both fields label themselves: the label sits in the
                                     field until there is something in it, then rises and
                                     stays — so nobody is left looking at text they can
                                     no longer read the purpose of. --}}
                                <div class="ff">
                                    <input type="text" id="wish-name" class="ff-control composer-name"
                                           x-model="guestName"
                                           maxlength="120"
                                           placeholder=" "
                                           @if(auth()->check()) readonly title="You are posting as your account" @endif>
                                    <label for="wish-name" class="ff-label">Your name</label>
                                </div>

                                <div class="ff">
                                    <textarea id="wish-message" class="ff-control" x-model="message" rows="2"
                                              placeholder=" "></textarea>
                                    <label for="wish-message" class="ff-label">
                                        Message for {{ $celebration->celebrant_name ?? 'the celebrant' }}
                                    </label>
                                </div>
                                <div class="composer-tools">
                                    {{-- Video wishes hidden for now. Remove this comment
                                         wrapper to bring the record button back; the
                                         recorder modal and video playback are untouched.
                                    <button type="button" class="ibtn ibtn-bare" @click="openVideoRecorder()" aria-label="Record video">
                                        <i class="mdi mdi-video-outline"></i>
                                    </button>
                                    --}}
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
                    </div>

                    @include('celebrations.partials.guest-modal')
                    @include('celebrations.partials.recorder-modal')
                @endunless

              </div>

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
                                {{-- Every tile here was received, so the class
                                     is unconditional now. --}}
                                <button type="button" class="gift-cell is-got"
                                    title="{{ $item->gift?->gift_name }}{{ $item->count > 1 ? " ×{$item->count}" : '' }}"
                                    @click="$dispatch('open-gift-detail', {
                                        id:       {{ $item->gift->id }},
                                        name:     '{{ addslashes($item->gift->gift_name) }}',
                                        image:    '{{ $item->gift->gift_image_url ? asset('storage/'.$item->gift->gift_image_url) : '' }}',
                                        icon:     '{{ $item->gift->icon() }}',
                                        accent:   '{{ $item->gift->accent() }}',
                                        note:     '{{ addslashes($item->gift->gift_description ?? '') }}',
                                        {{-- What it costs to send, not what has
                                             already been received for it. The
                                             panel this opens is the send-a-gift
                                             screen, and passing the received
                                             total showed ₦0 on every gift
                                             nobody had sent yet. --}}
                                        price:    {{ $item->gift->priceIn($visitorCurrency) }},
                                        priceUsd: {{ (float) $item->gift->gift_price }},
                                    })">
                                    @if($item->gift?->gift_image_url)
                                        <img src="{{ asset('storage/'.$item->gift->gift_image_url) }}" alt="">
                                    @else
                                        {{-- The gift's own icon and colour, so a
                                             wall of received gifts reads as a
                                             wall of gifts rather than grey boxes. --}}
                                        <i class="mdi {{ $item->gift?->icon() ?? 'mdi-gift-outline' }}"
                                           style="color: {{ $item->gift?->accent() ?? 'var(--muted-2)' }}"></i>
                                    @endif
                                    @if($item->count > 1)<span class="gift-n">{{ $item->count }}</span>@endif
                                </button>
                            @endforeach
                        </div>
                    @else
                        {{-- Reached far more often now that the wall no longer
                             pads itself with suggestions. --}}
                        <div class="cel-empty">
                            <i class="mdi mdi-gift-outline"></i>
                            <p>No gifts received yet. They'll appear here as they arrive.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── SETTINGS (owner) ────────────────────────────────── --}}
            @if($isOwner)
                <div class="cel-pad" x-show="tab === 'settings'"
                     x-data="celebrationSettings({{ Js::from($settings) }})">

                    {{-- Cover photos first: a page without one is the first thing to fix. --}}
                    <div class="cel-sec"
                         x-data="{
                            removing: null,
                            async removeCover(path) {
                                if (! confirm('Remove this cover photo? It will disappear from your page.')) return;
                                this.removing = path;
                                try {
                                    const res = await fetch('{{ route('celebrant.delete-cover', $celebration->id) }}', {
                                        method: 'DELETE',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                        body: JSON.stringify({ path }),
                                    });
                                    const data = await res.json().catch(() => ({}));
                                    if (res.ok && data.success) { window.location.reload(); return; }
                                    window.showAlert?.(data.message || 'Could not remove the photo.', 'error');
                                } catch (e) {
                                    window.showAlert?.('Something went wrong', 'error');
                                }
                                this.removing = null;
                            }
                         }">
                        <p class="cel-sec-t">Cover photos</p>

                        {{-- The add button is the loudest thing in Settings until the
                             page is full — a small dashed square was easy to miss. --}}
                        @if(count($coverPhotos) < 4)
                            <label for="coverUpload" class="set-cover-cta">
                                <span class="set-cover-cta-icon"><i class="mdi mdi-image-plus-outline"></i></span>
                                <span class="set-cover-cta-body">
                                    <span class="set-cover-cta-t">{{ count($coverPhotos) ? 'Add another cover image' : 'Add Cover Image' }}</span>
                                    <span class="set-cover-cta-s">Up to {{ \App\Support\UploadLimits::label() }} each · {{ count($coverPhotos) }} of 4 used</span>
                                </span>
                                <i class="mdi mdi-chevron-right set-cover-cta-go"></i>
                            </label>
                        @else
                            <p class="set-hint" style="margin-top:0">4 of 4 cover photos — the most a page can hold. Remove one to add another.</p>
                        @endif

                        @if(count($coverPhotos))
                            <div class="set-photos" style="margin-top:0.75rem">
                                @foreach($coverPhotos as $photo)
                                    <div class="set-photo" :class="removing === '{{ $photo }}' && 'is-removing'">
                                        <img src="{{ asset('storage/'.$photo) }}" alt="Cover photo {{ $loop->iteration }}">
                                        @if($loop->first)
                                            <span class="set-photo-badge">Main</span>
                                        @endif
                                        <button type="button" class="set-photo-del"
                                                @click="removeCover('{{ $photo }}')"
                                                :disabled="removing !== null"
                                                aria-label="Remove cover photo {{ $loop->iteration }}" title="Remove photo">
                                            <i class="mdi" :class="removing === '{{ $photo }}' ? 'mdi-loading mdi-spin' : 'mdi-trash-can-outline'"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <input type="file" id="coverUpload" class="hidden" accept="image/*" multiple>
                    </div>

                    <div class="cel-sec">
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
                            <label class="set-label" for="set-event-date">Celebration date</label>
                            <input id="set-event-date" type="text" class="input datepicker" x-model="form.event_date"
                                   placeholder="Pick the day" autocomplete="off">
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
                    </div>

                    {{-- Custom URL --}}
                    <div class="cel-sec">
                        <p class="cel-sec-t">Page link</p>
                        <x-slug-editor :celebration="$celebration" />
                    </div>

                    {{-- Frame — only while the admin switch is on. --}}
                    @if($framesEnabled ?? false)
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

                    @endif

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

                        {{-- Colours: a wheel, a hex box and a few presets. Empty
                             means "use the theme's colour", so the wheel shows
                             the theme's rather than the browser's black. --}}
                        @foreach ([
                            'bg'   => ['label' => 'Background colour', 'model' => 'customBg',
                                       'presets' => ['#ffffff', '#fff7ed', '#fdf2f8', '#f0f9ff', '#f0fdf4', '#fefce8', '#1f2937', '#0f172a']],
                            'text' => ['label' => 'Text colour',       'model' => 'customText',
                                       'presets' => ['#111827', '#374151', '#ffffff', '#7c2d12', '#831843', '#1e3a8a', '#14532d', '#581c87']],
                        ] as $which => $colour)
                            <div class="set-field set-colour" style="margin-top:0.9rem">
                                <label class="set-label" for="set-colour-{{ $which }}">{{ $colour['label'] }}</label>
                                <div class="set-colour-row">
                                    <label class="set-colour-wheel" :style="`background:${pickerValue('{{ $which }}')}`" title="Open the colour picker">
                                        <input type="color" :value="pickerValue('{{ $which }}')"
                                               @input="setColour('{{ $which }}', $event.target.value)"
                                               aria-label="{{ $colour['label'] }} picker">
                                    </label>
                                    <input id="set-colour-{{ $which }}" type="text" class="input set-colour-hex"
                                           maxlength="7" spellcheck="false" autocomplete="off" placeholder="Theme default"
                                           :value="{{ $colour['model'] }}"
                                           @change="setColour('{{ $which }}', $event.target.value) || ($event.target.value = {{ $colour['model'] }})">
                                    <button type="button" class="btn btn-outline btn-sm" x-show="{{ $colour['model'] }}" x-cloak
                                            @click="setColour('{{ $which }}', '')" title="Use the theme's colour">
                                        <i class="mdi mdi-restore"></i> Theme
                                    </button>
                                </div>
                                <div class="set-colour-presets">
                                    @foreach ($colour['presets'] as $hex)
                                        <button type="button" class="set-colour-chip" style="background:{{ $hex }}"
                                                :aria-pressed="{{ $colour['model'] }} === '{{ $hex }}'"
                                                @click="setColour('{{ $which }}', '{{ $hex }}')"
                                                title="{{ $hex }}" aria-label="{{ $colour['label'] }} {{ $hex }}"></button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div class="set-colour-preview" :style="`background:${pickerValue('bg')};color:${pickerValue('text')}`">
                            <strong>Aa</strong><span>This is how wishes will read on your page.</span>
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
    {{-- Whether the gateway for this visitor's currency is switched on, so a
         paused gateway hides the card button instead of failing on click. --}}
    :cardPaymentsOpen="\App\Support\PaymentGateways::canCheckout($visitorCurrency)"
/>

<x-wishes-modal
    :visitorCurrency="$visitorCurrency"
    :visitorSymbol="$visitorSymbol"
    :walletBalance="$walletBalance"
    :isAuthenticated="$isAuthenticated"
    :isOwner="$isOwner"
    :celebrationId="$celebration->id"
    :cardPaymentsOpen="\App\Support\PaymentGateways::canCheckout($visitorCurrency)"
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

@if (request()->boolean('gifts'))
    {{-- Sent here by the gift prompt on the wish-posted screen. Going through
         a reload rather than opening the plate in place is deliberate: the new
         wish is only drawn on a fresh load, so a visitor who opens gifts and
         then changes their mind still ends up looking at their own message
         instead of a wall that appears not to have taken it. --}}
    <script>
        document.addEventListener('alpine:initialized', () => {
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'show-gifts' }));
        });

        if (window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete('gifts');
            window.history.replaceState({}, '', url);
        }
    </script>
@endif

@if (request()->boolean('gifted'))
    {{-- Landed back here from a gateway that took the payer away (Stripe, or
         Paystack when the inline script could not load). The inline flow
         throws its confetti before it reloads, so it does not set this flag
         and nobody gets the show twice. --}}
    <script>
        window.addEventListener('load', () => window.giftConfetti?.());

        // Drop the flag so a refresh, or a shared link, does not re-celebrate.
        if (window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete('gifted');
            window.history.replaceState({}, '', url);
        }
    </script>
@endif

<script>
    window.CelebrationConfig = {
        isAuthenticated: @json(auth()->check()),
        // Prefills the composer's name box, so a signed-in person never types
        // their own name to leave a wish.
        viewerName:      @json(auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : ''),
        celebrationId:   {{ $celebration->id }},
        commentStoreUrl: "{{ route('celebration.comment.store') }}",
        wishesUrl:       "{{ route('celebrant.create-wishes') }}",
        csrfToken:       "{{ csrf_token() }}",
        // Cover photo and registry image limit, checked in the browser before
        // uploading. Same number the server validates against.
        imageMaxBytes:   {{ \App\Support\UploadLimits::bytes() }},
        imageMaxLabel:   "{{ \App\Support\UploadLimits::label() }}",
        // New cover photos join the existing ones, up to the page's limit.
        coverCount:      {{ count($coverPhotos) }},
        coverMax:        4,
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
