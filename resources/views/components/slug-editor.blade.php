@props(['celebration'])

{{--
    Custom celebration URL editor (owner only).
    Backed by slugEditor() in resources/js/modules/slugEditor.js.
--}}

@php
    $base = url('/celebration') . '/';
@endphp

<div
    class="ds"
    x-data="slugEditor({
        celebrationId: {{ $celebration->id }},
        current:   @js($celebration->slug),
        base:      @js($base),
        checkUrl:  @js(route('celebrant.check-slug', $celebration->id)),
        saveUrl:   @js(route('celebrant.update-slug', $celebration->id)),
        csrfToken: @js(csrf_token())
    })"
>
    {{-- ── Read-only state ─────────────────────────────────────────── --}}
    <div x-show="!open" class="flex items-center gap-2 min-w-0">
        <i class="mdi mdi-link-variant text-base shrink-0" style="color: var(--muted)"></i>

        <span class="truncate text-sm font-medium" style="color: var(--muted)">
            <span style="color: var(--muted-2)">{{ str_replace(['https://', 'http://'], '', $base) }}</span><span
                class="font-bold" style="color: var(--ink)" x-text="current"></span>
        </span>

        <button type="button" class="ibtn ibtn-bare" style="width:30px;height:30px;font-size:1rem"
                @click="copy()" title="Copy link" aria-label="Copy link">
            <i class="mdi mdi-content-copy"></i>
        </button>

        <button type="button" class="ibtn ibtn-bare" style="width:30px;height:30px;font-size:1rem"
                @click="openEditor()" title="Edit link" aria-label="Edit link">
            <i class="mdi mdi-pencil-outline"></i>
        </button>
    </div>

    {{-- ── Editing state ───────────────────────────────────────────── --}}
    <div x-show="open" x-cloak class="w-full">
        <label class="field-label" for="slug-input-{{ $celebration->id }}">Your celebration link</label>

        <div class="input-prefix">
            <span>{{ str_replace(['https://', 'http://'], '', $base) }}</span>
            <input
                id="slug-input-{{ $celebration->id }}"
                type="text"
                class="input"
                x-model="value"
                @input="onInput()"
                @keydown.enter.prevent="save()"
                @keydown.escape="closeEditor()"
                placeholder="sandras-30th"
                autocomplete="off"
                spellcheck="false"
            >
        </div>

        {{-- status line --}}
        <p class="field-hint flex items-center gap-1.5" x-show="message"
           :style="status === 'available' ? 'color: var(--ok)'
                 : (status === 'checking' ? '' : 'color: var(--danger)')">
            <i class="mdi" :class="{
                'mdi-loading mdi-spin': status === 'checking',
                'mdi-check-circle':     status === 'available',
                'mdi-alert-circle':     ['taken','invalid','reserved'].includes(status)
            }"></i>
            <span x-text="message"></span>
        </p>

        <p class="field-hint" x-show="!message">
            Lowercase letters, numbers and hyphens. This changes where your page lives.
        </p>

        <div class="flex items-center gap-2 mt-3">
            <button type="button" class="btn btn-primary btn-sm" :disabled="!canSave" @click="save()">
                <i class="mdi" :class="saving ? 'mdi-loading mdi-spin' : 'mdi-check'"></i>
                <span x-text="saving ? 'Saving…' : 'Save link'"></span>
            </button>

            <button type="button" class="btn btn-ghost btn-sm" @click="closeEditor()">Cancel</button>
        </div>
    </div>
</div>
