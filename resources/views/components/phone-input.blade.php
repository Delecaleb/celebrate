@props([
    'name'     => 'phone',
    'label'    => 'Phone number',
    'value'    => null,          // an E.164 number to edit, when there is one
    'country'  => null,          // ISO 3166-1 alpha-2 the picker opens on
    // Set false inside a multi-step form: the browser refuses to submit a
    // form holding a required field it cannot focus, which is what a field on
    // a hidden step is. The server still requires the number.
    'required' => true,
    'hint'     => null,
])

@php
    use App\Support\PhoneNumbers;

    $countries = PhoneNumbers::countries();
    $iso       = PhoneNumbers::defaultCountry($country);

    // Editing an existing number: open on its country and show the rest.
    $national = '';

    if (PhoneNumbers::isValid($value)) {
        $digits = substr((string) $value, 1);

        foreach (collect($countries)->sortByDesc(fn ($c) => strlen($c['dial'])) as $candidate) {
            if (str_starts_with($digits, $candidate['dial'])) {
                $iso      = $candidate['iso'];
                $national = substr($digits, strlen($candidate['dial']));
                break;
            }
        }
    }

    $id = $name . '-' . Str::random(4);
@endphp

@once
    <style>
        .phone-field { position: relative; }

        .phone-box {
            display: flex; align-items: stretch;
            border: 1.5px solid var(--border, #e5e2ee);
            border-radius: 12px;
            background: var(--white, #fff);
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .phone-box:focus-within {
            border-color: var(--accent, #7c3aed);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
        .phone-box.is-invalid { border-color: #dc2626; }

        /* The country button: flag, dialling code, chevron. */
        .phone-country {
            display: flex; align-items: center; gap: 0.4rem;
            padding: 0 0.7rem 0 0.85rem;
            border: 0; border-right: 1.5px solid var(--border, #e5e2ee);
            border-radius: 12px 0 0 12px;
            background: transparent; cursor: pointer;
            font-family: inherit; font-size: 0.9rem; font-weight: 600;
            color: var(--dark, #16121f); white-space: nowrap;
        }
        .phone-country:hover { background: var(--off, #faf8fe); }
        .phone-flag { font-size: 1.15rem; line-height: 1; }
        .phone-caret { font-size: 0.7rem; opacity: 0.55; }

        /* The number itself — no border of its own, the box owns that. */
        .phone-number {
            flex: 1; min-width: 0;
            border: 0 !important; border-radius: 0 12px 12px 0 !important;
            padding: 0.82rem 1rem; font-size: 0.9rem; font-family: inherit;
            color: var(--dark, #16121f); background: transparent; outline: none;
            box-shadow: none !important;
        }

        /* The list. Fixed height so it never pushes the form around. */
        .phone-menu {
            position: absolute; z-index: 60; top: calc(100% + 6px); left: 0;
            width: min(360px, 100%);
            background: var(--white, #fff);
            border: 1.5px solid var(--border, #e5e2ee);
            border-radius: 14px; overflow: hidden;
            box-shadow: 0 18px 40px rgba(22, 18, 31, 0.16);
        }
        .phone-search {
            width: 100%; border: 0; border-bottom: 1.5px solid var(--border, #e5e2ee);
            padding: 0.7rem 0.9rem; font-size: 0.86rem; font-family: inherit;
            outline: none; background: var(--off, #faf8fe); color: inherit;
        }
        .phone-list { max-height: 260px; overflow-y: auto; }

        .phone-option {
            display: flex; align-items: center; gap: 0.6rem; width: 100%;
            padding: 0.6rem 0.9rem; border: 0; background: none; cursor: pointer;
            font-family: inherit; font-size: 0.86rem; text-align: left;
            color: var(--dark, #16121f);
        }
        .phone-option:hover, .phone-option.is-active { background: var(--off, #faf8fe); }
        .phone-option.is-chosen { font-weight: 700; }
        .phone-option-name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .phone-option-dial { color: var(--muted, #6d6683); font-variant-numeric: tabular-nums; }

        /* The pinned markets, separated from the alphabet below them. */
        .phone-option.is-last-preferred { border-bottom: 1.5px solid var(--border, #e5e2ee); }

        .phone-empty { padding: 1rem 0.9rem; font-size: 0.84rem; color: var(--muted, #6d6683); }
        .phone-hint  { font-size: 0.78rem; color: var(--muted, #6d6683); margin-top: 0.35rem; }
    </style>
@endonce

<div {{ $attributes->class(['field', 'phone-field']) }}
     x-data="phoneInput({ countries: @js($countries), country: @js($iso), national: @js(old($name . '_national', $national)) })"
     x-modelable="e164"
     @click.outside="open = false"
     @keydown.escape.window="open = false">

    <label for="{{ $id }}">{{ $label }}</label>

    <div class="phone-box" :class="{ 'is-invalid': {{ $errors->has($name) ? 'true' : 'false' }} }">
        <button type="button" class="phone-country"
                @click="toggle()"
                :aria-expanded="open"
                aria-haspopup="listbox"
                :aria-label="`Country code: ${country.name} +${country.dial}`">
            <span class="phone-flag" x-text="flag(iso)"></span>
            <span x-text="'+' + country.dial"></span>
            <span class="phone-caret">▼</span>
        </button>

        <input type="tel"
               id="{{ $id }}"
               class="phone-number"
               x-ref="number"
               x-model="national"
               @input="onNumberInput()"
               placeholder="803 123 4567"
               autocomplete="tel-national"
               inputmode="tel"
               @if ($required) required @endif>
    </div>

    {{-- One number, one spelling: this is what the server stores. --}}
    <input type="hidden" name="{{ $name }}" :value="e164">
    {{-- Kept so a rejected form comes back with what they typed. --}}
    <input type="hidden" name="{{ $name }}_national" :value="national">

    <div class="phone-menu" x-show="open" x-cloak x-transition.opacity role="listbox">
        <input type="text" class="phone-search"
               x-ref="search"
               x-model="search"
               @input="highlight = 0"
               @keydown.down.prevent="move(1)"
               @keydown.up.prevent="move(-1)"
               @keydown.enter.prevent="chooseHighlighted()"
               placeholder="Search country or code"
               aria-label="Search countries">

        <div class="phone-list" x-ref="list">
            <template x-for="(option, index) in matches" :key="option.iso">
                <button type="button"
                        class="phone-option"
                        role="option"
                        :aria-selected="option.iso === iso"
                        :class="{
                            'is-active': index === highlight,
                            'is-chosen': option.iso === iso,
                            'is-last-preferred': option.preferred && ! (matches[index + 1]?.preferred),
                        }"
                        @click="choose(option.iso)"
                        @mouseenter="highlight = index">
                    <span class="phone-flag" x-text="flag(option.iso)"></span>
                    <span class="phone-option-name" x-text="option.name"></span>
                    <span class="phone-option-dial" x-text="'+' + option.dial"></span>
                </button>
            </template>

            <p class="phone-empty" x-show="matches.length === 0">No country matches that.</p>
        </div>
    </div>

    @error($name)
        <p class="error-text">{{ $message }}</p>
    @else
        @if ($hint)
            <p class="phone-hint">{{ $hint }}</p>
        @endif
    @enderror
</div>
