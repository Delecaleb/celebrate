@extends('admin.layout')

@section('title', $gift->exists ? 'Edit gift' : 'Add a gift')
@section('topbar-title', $gift->exists ? "Edit “{$gift->gift_name}”" : 'Add a gift')

@section('content')

    <form method="POST" enctype="multipart/form-data"
          x-data="giftForm({
              icon:   '{{ old('gift_icon', $gift->gift_icon ?: 'mdi-gift-outline') }}',
              accent: '{{ old('accent_color', $gift->accent_color ?: '#7c3aed') }}',
              name:   @js(old('gift_name', $gift->gift_name)),
              price:  {{ (float) old('gift_price', $gift->gift_price ?: 0) }},
              image:  '{{ $gift->gift_image_url ? asset('storage/' . $gift->gift_image_url) : '' }}'
          })"
          action="{{ $gift->exists ? route('admin.gifts.update', $gift) : route('admin.gifts.store') }}">
        @csrf
        @if ($gift->exists) @method('PUT') @endif

        <div style="display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1fr);gap:1.5rem;align-items:start">

            {{-- ── The gift ─────────────────────────────────────────────── --}}
            <div class="table-card" style="padding:1.75rem">
                <h3 style="font-size:1rem;font-weight:700;margin-bottom:1.25rem">The gift</h3>

                <div style="margin-bottom:1.25rem">
                    <label class="filter-label" for="gift_name">Name</label>
                    <input id="gift_name" type="text" name="gift_name" class="filter-input" style="width:100%"
                           value="{{ old('gift_name', $gift->gift_name) }}" x-model="name"
                           placeholder="Birthday Cake" required>
                    @error('gift_name')<p style="color:#991b1b;font-size:0.78rem;margin-top:0.3rem">{{ $message }}</p>@enderror
                </div>

                <div style="margin-bottom:1.25rem">
                    <label class="filter-label" for="gift_description">
                        Description
                        <span style="color:var(--muted);font-weight:400">— one line, and it is what sells it</span>
                    </label>
                    <input id="gift_description" type="text" name="gift_description" class="filter-input" style="width:100%"
                           value="{{ old('gift_description', $gift->gift_description) }}"
                           maxlength="300"
                           placeholder="The one thing the day genuinely cannot happen without.">
                    @error('gift_description')<p style="color:#991b1b;font-size:0.78rem;margin-top:0.3rem">{{ $message }}</p>@enderror
                </div>

                <div style="margin-bottom:1.25rem">
                    <label class="filter-label" for="category">Category</label>
                    <select id="category" name="category" class="filter-select" style="width:100%">
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}" @selected(old('category', $gift->category) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- ── Prices ───────────────────────────────────────────────
                     One field per currency in config/currency.php, so adding a
                     market grows this form on its own — no migration, no
                     deploy. The base currency is the default: any currency left
                     blank is converted from it at the live rate. --}}
                <h3 style="font-size:1rem;font-weight:700;margin:1.75rem 0 0.35rem">Prices</h3>
                <p style="font-size:0.82rem;color:var(--muted);margin-bottom:1.25rem">
                    Set the figure that belongs in each market. Leave one blank and it is
                    converted from the default — right to the penny, but ₦651.37 is not a price
                    anybody puts on a gift.
                </p>

                <div style="display:grid;gap:0.85rem">
                    @foreach ($currencies as $code => $meta)
                        @php
                            $isBase    = $code === $baseCurrency;
                            $explicit  = $gift->exists ? $gift->explicitPrice($code) : null;
                            $converted = $gift->exists ? $gift->priceIn($code) : null;
                        @endphp

                        <div style="display:flex;align-items:center;gap:0.85rem">
                            <span style="width:112px;flex-shrink:0;font-size:0.85rem;font-weight:600">
                                {{ $meta['symbol'] }} {{ $code }}
                                @if ($isBase)
                                    <span style="display:block;font-size:0.7rem;font-weight:600;color:var(--accent)">Default</span>
                                @endif
                            </span>

                            @if ($isBase)
                                {{-- The one price that always exists. Everything
                                     else falls back to it. --}}
                                <input type="number" name="gift_price" class="filter-input" style="flex:1"
                                       value="{{ old('gift_price', $gift->gift_price) }}" x-model.number="price"
                                       step="0.01" min="0.01" required
                                       aria-label="Price in {{ $meta['name'] }}">
                            @else
                                <input type="number" name="prices[{{ $code }}]" class="filter-input" style="flex:1"
                                       value="{{ old("prices.{$code}", $explicit) }}"
                                       step="0.01" min="0"
                                       placeholder="{{ $converted !== null ? number_format($converted, 2, '.', '') : '' }}"
                                       aria-label="Price in {{ $meta['name'] }}">
                            @endif

                            <span style="width:150px;flex-shrink:0;font-size:0.75rem;color:var(--muted)">
                                @if ($isBase)
                                    {{ $meta['name'] }}
                                @elseif ($explicit !== null)
                                    Set by hand
                                @else
                                    Converted automatically
                                @endif
                            </span>
                        </div>

                        @error("prices.{$code}")
                            <p style="color:#991b1b;font-size:0.78rem;margin:-0.4rem 0 0 7rem">{{ $message }}</p>
                        @enderror
                    @endforeach
                </div>

                @error('gift_price')<p style="color:#991b1b;font-size:0.78rem;margin-top:0.5rem">{{ $message }}</p>@enderror

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem">
                    <div>
                        <label class="filter-label" for="status">Status</label>
                        <select id="status" name="status" class="filter-select" style="width:100%">
                            <option value="active"  @selected(old('status', $gift->status) === 'active')>Active — guests can send it</option>
                            <option value="pending" @selected(old('status', $gift->status) === 'pending')>Pending — hidden from guests</option>
                        </select>
                    </div>

                    <div>
                        <label class="filter-label" for="sort_order">
                            Position <span style="color:var(--muted);font-weight:400">— low numbers first</span>
                        </label>
                        <input id="sort_order" type="number" name="sort_order" class="filter-input" style="width:100%"
                               value="{{ old('sort_order', $gift->sort_order ?? 0) }}" min="0" max="9999" required>
                    </div>
                </div>

                <div style="margin-top:1.25rem">
                    <label class="filter-label" for="gift_link_url">
                        Link <span style="color:var(--muted);font-weight:400">— optional, where the celebrant can buy it</span>
                    </label>
                    <input id="gift_link_url" type="url" name="gift_link_url" class="filter-input" style="width:100%"
                           value="{{ old('gift_link_url', $gift->gift_link_url) }}" placeholder="https://">
                    @error('gift_link_url')<p style="color:#991b1b;font-size:0.78rem;margin-top:0.3rem">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- ── How it looks ─────────────────────────────────────────── --}}
            <div class="table-card" style="padding:1.75rem">
                <h3 style="font-size:1rem;font-weight:700;margin-bottom:0.35rem">How it looks</h3>
                <p style="font-size:0.82rem;color:var(--muted);margin-bottom:1.25rem">
                    Exactly as a guest sees it on the gift plate.
                </p>

                {{-- Live preview: the tile from the celebration page, showing
                     the uploaded artwork the moment it is chosen. --}}
                <div style="display:flex;justify-content:center;margin-bottom:1.5rem">
                    <div style="width:130px;padding:1rem 0.75rem;border:1px solid var(--border);border-radius:16px;text-align:center;background:var(--white)">
                        <span style="display:flex;align-items:center;justify-content:center;width:56px;height:56px;margin:0 auto;border-radius:16px;overflow:hidden"
                              :style="image ? '' : `background:${accent}18;color:${accent}`">
                            <template x-if="image">
                                <img :src="image" alt="" style="width:100%;height:100%;object-fit:contain">
                            </template>
                            <template x-if="!image">
                                <i class="mdi" :class="icon" style="font-size:1.75rem"></i>
                            </template>
                        </span>
                        <p style="font-size:0.78rem;font-weight:700;margin-top:0.6rem" x-text="name || 'Gift name'"></p>
                        <p style="font-size:0.75rem;color:var(--muted)"
                           x-text="'{{ $currencies[$baseCurrency]['symbol'] ?? '$' }}' + (price || 0).toFixed(2)"></p>
                    </div>
                </div>

                {{-- ── Artwork ──────────────────────────────────────────── --}}
                <div style="margin-bottom:1.5rem">
                    <label class="filter-label" for="gift_image">Gift image</label>

                    <label for="gift_image"
                           style="display:flex;flex-direction:column;align-items:center;gap:0.4rem;
                                  padding:1.4rem 1rem;border:1.5px dashed var(--border);border-radius:12px;
                                  cursor:pointer;text-align:center;background:var(--off)">
                        <i class="mdi mdi-tray-arrow-up" style="font-size:1.6rem;color:var(--accent)"></i>
                        <span style="font-size:0.85rem;font-weight:600" x-text="fileName || 'Choose a PNG or SVG'"></span>
                        <span style="font-size:0.75rem;color:var(--muted)">
                            SVG stays sharp at any size · 2MB max
                        </span>
                    </label>

                    <input id="gift_image" type="file" name="gift_image" accept=".png,.svg,image/png,image/svg+xml"
                           style="position:absolute;width:1px;height:1px;opacity:0;overflow:hidden"
                           @change="pickFile($event)">

                    @error('gift_image')<p style="color:#991b1b;font-size:0.78rem;margin-top:0.5rem">{{ $message }}</p>@enderror

                    @if ($gift->gift_image_url)
                        <label style="display:flex;align-items:center;gap:0.5rem;margin-top:0.85rem;font-size:0.82rem;cursor:pointer">
                            <input type="checkbox" name="remove_image" value="1" @change="if ($event.target.checked) image = ''">
                            Remove the current image and fall back to the icon
                        </label>
                    @endif
                </div>

                {{-- ── Fallback icon ───────────────────────────────────── --}}
                <details style="margin-bottom:1.25rem" @if (! $gift->gift_image_url) open @endif>
                    <summary style="cursor:pointer;font-size:0.85rem;font-weight:600;margin-bottom:0.75rem">
                        Fallback icon
                        <span style="color:var(--muted);font-weight:400">— used until an image is uploaded</span>
                    </summary>

                    <input id="gift_icon" type="text" name="gift_icon" class="filter-input" style="width:100%"
                           x-model="icon" value="{{ old('gift_icon', $gift->gift_icon) }}"
                           placeholder="mdi-cake-variant">
                    @error('gift_icon')<p style="color:#991b1b;font-size:0.78rem;margin-top:0.3rem">{{ $message }}</p>@enderror

                    <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:0.4rem;margin-top:0.85rem">
                        @foreach ($icons as $suggestion)
                            <button type="button" @click="icon = '{{ $suggestion }}'"
                                    title="{{ $suggestion }}"
                                    style="aspect-ratio:1;display:flex;align-items:center;justify-content:center;
                                           border:1px solid var(--border);border-radius:10px;background:var(--white);
                                           cursor:pointer;font-size:1.05rem;color:var(--dark)"
                                    :style="icon === '{{ $suggestion }}' ? 'border-color:var(--accent);color:var(--accent)' : ''">
                                <i class="mdi {{ $suggestion }}"></i>
                            </button>
                        @endforeach
                    </div>
                </details>

                <div>
                    <label class="filter-label" for="accent_color">Accent colour</label>
                    <div style="display:flex;gap:0.6rem;align-items:center">
                        <input id="accent_color" type="color" name="accent_color" x-model="accent"
                               value="{{ old('accent_color', $gift->accent_color ?: '#7c3aed') }}"
                               style="width:52px;height:40px;border:1px solid var(--border);border-radius:10px;background:none;cursor:pointer">
                        <input type="text" class="filter-input" style="flex:1" x-model="accent" readonly>
                    </div>
                    @error('accent_color')<p style="color:#991b1b;font-size:0.78rem;margin-top:0.3rem">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div style="display:flex;gap:0.75rem;margin-top:1.5rem">
            <button type="submit" class="btn-filter">
                <i class="mdi mdi-content-save"></i>
                {{ $gift->exists ? 'Save changes' : 'Add the gift' }}
            </button>
            <a href="{{ route('admin.gifts') }}" class="btn-filter"
               style="background:var(--off);color:var(--dark);border:1.5px solid var(--border);text-decoration:none">
                Cancel
            </a>
        </div>
    </form>

    <script>
        function giftForm(initial) {
            return {
                icon:     initial.icon,
                accent:   initial.accent,
                name:     initial.name || '',
                price:    initial.price || 0,
                image:    initial.image || '',
                fileName: '',

                /**
                 * Show the chosen file in the preview before it is uploaded —
                 * an SVG that turns out to be the wrong size is much cheaper to
                 * notice here than after saving.
                 */
                pickFile(event) {
                    const file = event.target.files[0];

                    if (!file) return;

                    this.fileName = file.name;
                    this.image    = URL.createObjectURL(file);
                },
            };
        }
    </script>

@endsection
