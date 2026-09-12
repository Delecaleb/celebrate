@props([
    'gifts',
    'visitorCurrency'  => 'USD',
    'visitorSymbol'    => '$',
    'walletBalance'    => 0,
    'isAuthenticated'  => false,
    'celebrationId'    => null,
])

<x-modal name="show-gifts" title="Gifts" maxWidth="2xl" focusable class="my-auto">

    <div
        x-data="giftPlate({
            walletBalance:   {{ (float) $walletBalance }},
            visitorCurrency: '{{ $visitorCurrency }}',
            visitorSymbol:   '{{ $visitorSymbol }}',
            isAuthenticated: {{ $isAuthenticated ? 'true' : 'false' }},
            celebrationId:   {{ (int) $celebrationId }},
            sendUrl:         '{{ route('gift.send') }}',
            payUrl:          '{{ route('gift.payment.initiate') }}',
            confirmUrl:      '{{ route('gift.payment.confirm') }}',
            csrfToken:       '{{ csrf_token() }}',
        })"
        @open-gift-detail.window="openFromExternal($event.detail)"
        class="min-h-[420px] flex flex-col"
    >

        {{-- ── GRID VIEW ──────────────────────────────────────────────────────── --}}
        <div x-show="view === 'grid'" x-transition>

            <p class="text-xs text-gray-400 px-6 pt-3 pb-1">
                Tap a gift to send it — tap again to send more than one
            </p>

            <div class="grid grid-cols-3 md:grid-cols-6 gap-3 p-5 max-h-[380px] overflow-y-auto">
                @foreach ($gifts as $gift)
                    {{-- displayPrice pre-computed in CelebrationController::show() --}}
                    {{-- Drawn from the icon font unless a gift has real artwork
                         uploaded. Nothing has to be uploaded for a gift to look
                         finished, and the tile takes the gift's own colour. --}}
                    <button
                        type="button"
                        @click="tapGift({
                            id:       {{ $gift->id }},
                            name:     '{{ addslashes($gift->gift_name) }}',
                            image:    '{{ $gift->gift_image_url ? asset('storage/' . $gift->gift_image_url) : '' }}',
                            icon:     '{{ $gift->icon() }}',
                            accent:   '{{ $gift->accent() }}',
                            note:     '{{ addslashes($gift->gift_description ?? '') }}',
                            price:    {{ $gift->displayPrice }},
                            priceUsd: {{ (float) $gift->gift_price }},
                        })"
                        {{-- The border carries the picked state, so the tile
                             that is being counted up is obvious while tapping. --}}
                        :class="pickedCount({{ $gift->id }}) > 0
                            ? 'border-rose-400 ring-2 ring-rose-200'
                            : 'border-gray-100 hover:border-rose-300'"
                        class="relative rounded-2xl border bg-white flex flex-col items-center justify-center p-2.5 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-rose-300"
                        title="{{ $gift->gift_description }}"
                    >
                        <span x-show="pickedCount({{ $gift->id }}) > 0" x-cloak
                              class="absolute -top-1.5 -right-1.5 min-w-[20px] h-5 px-1 rounded-full bg-rose-500 text-white text-[10px] font-extrabold flex items-center justify-center shadow"
                              x-text="pickedCount({{ $gift->id }})"></span>
                        <div class="w-12 h-12 mx-auto rounded-xl overflow-hidden flex items-center justify-center"
                             style="background:{{ $gift->accent() }}18;color:{{ $gift->accent() }}">
                            @if ($gift->gift_image_url)
                                {{-- contain, not cover: artwork is a transparent
                                     icon, so cropping it to the tile edges would
                                     clip the object and hide the accent tint
                                     behind it. Matches the gift wall on the
                                     celebration page, which already uses it. --}}
                                <img
                                    src="{{ asset('storage/' . $gift->gift_image_url) }}"
                                    alt="{{ $gift->gift_name }}"
                                    class="object-contain w-full h-full p-1"
                                >
                            @else
                                <i class="mdi {{ $gift->icon() }}" style="font-size:1.5rem"></i>
                            @endif
                        </div>
                        <h3 class="text-[10px] font-semibold text-center mt-1.5 line-clamp-1 text-gray-800">{{ $gift->gift_name }}</h3>
                        <p class="text-[10px] text-gray-400 text-center">
                            {{ $visitorSymbol }}{{ number_format($gift->displayPrice, 2) }}
                        </p>
                    </button>
                @endforeach
            </div>

            {{-- Outside the scrolling grid, so the running total stays in view
                 while tapping. --}}
            <div x-show="cart.length" x-cloak x-transition
                 class="sticky bottom-0 border-t border-gray-100 bg-white px-5 py-3">
                <p x-show="error" x-cloak class="mb-2 text-xs font-semibold text-red-600" x-text="error"></p>

                <div class="flex items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-gray-900" x-text="cartLabel"></p>
                        <button type="button" @click="clearPick()"
                                class="text-xs text-gray-400 underline hover:text-gray-700">
                            Clear
                        </button>
                    </div>

                    <p class="shrink-0 text-lg font-black text-rose-500" x-text="cartTotalLabel"></p>

                    <button type="button" @click="review()"
                            class="shrink-0 rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-gray-800">
                        Continue
                    </button>
                </div>
            </div>

        </div>

        {{-- ── DETAIL VIEW ─────────────────────────────────────────────────────── --}}
        <div x-show="view === 'detail'" x-transition class="flex flex-col flex-1 p-6 gap-4">

            {{-- Back --}}
            <button
                type="button"
                @click="back()"
                class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 self-start"
            >
                <i class="mdi mdi-arrow-left text-base"></i> Back to gifts
            </button>

            {{-- One row per gift in the basket, each adjustable here so nobody
                 has to go back to the grid to change their mind. --}}
            <div class="divide-y divide-gray-100 rounded-2xl border border-gray-100 bg-white">
                <template x-for="line in cart" :key="line.gift.id">
                    <div class="flex items-center gap-3 p-3">
                        <template x-if="line.gift.image">
                            <img :src="line.gift.image" :alt="line.gift.name"
                                 class="shrink-0 rounded-xl border border-gray-100 object-contain p-1"
                                 style="width:3rem;height:3rem">
                        </template>

                        <template x-if="!line.gift.image">
                            <span class="flex shrink-0 items-center justify-center rounded-xl"
                                  style="width:3rem;height:3rem"
                                  :style="`background:${line.gift.accent}18;color:${line.gift.accent}`">
                                <i class="mdi" :class="line.gift.icon" style="font-size:1.5rem"></i>
                            </span>
                        </template>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-gray-900" x-text="line.gift.name"></p>
                            <p class="text-xs text-gray-500"
                               x-text="visitorSymbol + formatNum(line.gift.price) + ' each'"></p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1.5">
                            <button type="button" @click="bump(line.gift.id, -1)"
                                    :aria-label="`One fewer ${line.gift.name}`"
                                    class="h-8 w-8 rounded-full border border-gray-200 text-base font-bold text-gray-700 hover:border-gray-900">
                                &minus;
                            </button>

                            <span class="w-7 text-center text-sm font-black text-gray-900"
                                  aria-live="polite" x-text="line.quantity"></span>

                            <button type="button" @click="bump(line.gift.id, 1)"
                                    :disabled="line.quantity >= maxQuantity"
                                    :aria-label="`One more ${line.gift.name}`"
                                    class="h-8 w-8 rounded-full border border-gray-200 text-base font-bold text-gray-700 disabled:opacity-35 disabled:cursor-not-allowed hover:border-gray-900">
                                +
                            </button>
                        </div>

                        <p class="w-20 shrink-0 text-right text-sm font-black text-gray-900"
                           x-text="visitorSymbol + formatNum(line.gift.price * line.quantity)"></p>
                    </div>
                </template>

                <div class="flex items-center justify-between px-3 py-3">
                    <span class="text-sm font-semibold text-gray-700">Total</span>
                    <span class="text-2xl font-black text-rose-500" x-text="cartTotalLabel"></span>
                </div>
            </div>

            {{-- Guest: no account needed, just somewhere to send the receipt --}}
            <template x-if="!isAuthenticated">
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" x-model="guestName" placeholder="Your name"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-gray-900">
                        <input type="email" x-model="guestEmail" placeholder="Your email"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-gray-900">
                    </div>

                    <div x-show="error" x-transition class="flex items-center gap-2 text-sm text-red-600 bg-red-50 border border-red-100 rounded-xl px-4 py-3">
                        <i class="mdi mdi-alert-circle-outline text-base"></i>
                        <span x-text="error"></span>
                    </div>

                    <button
                        type="button"
                        @click="initiatePayment()"
                        :disabled="loading || !canGuestPay"
                        class="w-full py-3 rounded-xl bg-rose-500 text-white text-sm font-semibold hover:bg-rose-600 transition disabled:opacity-50 flex items-center justify-center gap-2"
                    >
                        <span x-show="!loading" class="flex items-center gap-2">
                            <i class="mdi mdi-credit-card-outline text-base"></i> Pay &amp; send gift
                        </span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            Preparing payment…
                        </span>
                    </button>

                    <p class="text-xs text-gray-400 text-center">
                        <a href="{{ route('login') }}" class="underline">Sign in</a> to pay from your wallet instead.
                    </p>
                </div>
            </template>

            {{-- Logged in --}}
            <template x-if="isAuthenticated">
                <div class="space-y-3">

                    {{-- Wallet balance --}}
                    <div class="flex items-center justify-between border border-gray-100 bg-white rounded-xl px-4 py-3">
                        <div class="flex items-center gap-2 text-gray-600 text-sm">
                            <i class="mdi mdi-wallet-outline text-base text-gray-400"></i>
                            <span>Wallet balance</span>
                        </div>
                        <span
                            class="font-semibold text-sm"
                            :class="hasSufficientBalance() ? 'text-green-600' : 'text-red-500'"
                            x-text="visitorSymbol + formatNum(walletBalance)"
                        ></span>
                    </div>

                    {{-- Feedback messages --}}
                    <div x-show="error" x-transition class="flex items-center gap-2 text-sm text-red-600 bg-red-50 border border-red-100 rounded-xl px-4 py-3">
                        <i class="mdi mdi-alert-circle-outline text-base"></i>
                        <span x-text="error"></span>
                    </div>
                    <div x-show="success" x-transition class="flex items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-100 rounded-xl px-4 py-3">
                        <i class="mdi mdi-check-circle-outline text-base"></i>
                        <span x-text="success"></span>
                    </div>

                    {{-- Send from wallet --}}
                    <template x-if="hasSufficientBalance()">
                        <button
                            type="button"
                            @click="sendFromWallet()"
                            :disabled="loading"
                            class="w-full py-3 rounded-xl bg-rose-500 text-white text-sm font-semibold hover:bg-rose-600 transition disabled:opacity-50 flex items-center justify-center gap-2"
                        >
                            <span x-show="!loading" class="flex items-center gap-2">
                                <i class="mdi mdi-gift-outline text-base"></i> Send Gift
                            </span>
                            <span x-show="loading" class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                </svg>
                                Sending…
                            </span>
                        </button>
                    </template>

                    {{-- Pay via Paystack --}}
                    <template x-if="!hasSufficientBalance()">
                        <div class="space-y-2">
                            <p class="text-xs text-gray-400 text-center">
                                Your wallet balance is insufficient. Pay securely to send this gift.
                            </p>
                            <button
                                type="button"
                                @click="initiatePayment()"
                                :disabled="loading"
                                class="w-full py-3 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-gray-700 transition disabled:opacity-50 flex items-center justify-center gap-2"
                            >
                                <span x-show="!loading" class="flex items-center gap-2">
                                    <i class="mdi mdi-credit-card-outline text-base"></i> Get This Gift
                                </span>
                                <span x-show="loading" class="flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>
                                    Preparing payment…
                                </span>
                            </button>
                        </div>
                    </template>

                    {{-- Optional message --}}
                    <div>
                        <label class="text-xs text-gray-400 mb-1 block">Add a note (optional)</label>
                        <textarea
                            x-model="giftMessage"
                            rows="2"
                            placeholder="Write a short note to the celebrant…"
                            class="w-full bg-gray-50 border border-gray-100 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-rose-400 focus:border-transparent resize-none"
                        ></textarea>
                    </div>

                </div>
            </template>

        </div>

    </div>

</x-modal>

{{-- giftPlate() is defined in resources/js/modules/giftPlate.js --}}
