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
            csrfToken:       '{{ csrf_token() }}',
        })"
        @open-gift-detail.window="openFromExternal($event.detail)"
        class="min-h-[420px] flex flex-col"
    >

        {{-- ── GRID VIEW ──────────────────────────────────────────────────────── --}}
        <div x-show="view === 'grid'" x-transition>

            <p class="text-xs text-gray-400 px-6 pt-3 pb-1">
                Select a gift to send to the celebrant
            </p>

            <div class="grid grid-cols-3 md:grid-cols-6 gap-3 p-5 max-h-[380px] overflow-y-auto">
                @foreach ($gifts as $gift)
                    {{-- displayPrice pre-computed in CelebrationController::show() --}}
                    <button
                        type="button"
                        @click="selectGift({
                            id:       {{ $gift->id }},
                            name:     '{{ addslashes($gift->gift_name) }}',
                            image:    '{{ asset('storage/' . $gift->gift_image_url) }}',
                            price:    {{ $gift->displayPrice }},
                            priceUsd: {{ (float) $gift->gift_price }},
                        })"
                        class="rounded-2xl border border-gray-100 bg-white flex flex-col items-center justify-center p-2.5 hover:border-rose-300 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-rose-300"
                    >
                        <div class="w-12 h-12 mx-auto rounded-xl overflow-hidden bg-gray-50 border border-gray-100">
                            <img
                                src="{{ asset('storage/' . $gift->gift_image_url) }}"
                                alt="{{ $gift->gift_name }}"
                                class="object-cover w-full h-full"
                            >
                        </div>
                        <h3 class="text-[10px] font-semibold text-center mt-1.5 line-clamp-1 text-gray-800">{{ $gift->gift_name }}</h3>
                        <p class="text-[10px] text-gray-400 text-center">
                            {{ $visitorSymbol }}{{ number_format($gift->displayPrice, 2) }}
                        </p>
                    </button>
                @endforeach
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

            {{-- Gift card --}}
            <div class="flex gap-4 items-center bg-gray-50 border border-gray-100 rounded-2xl p-4">
                <img
                    :src="selected.image"
                    :alt="selected.name"
                    class="w-18 h-18 rounded-xl object-cover border border-gray-100 shrink-0"
                    style="width:4.5rem;height:4.5rem"
                >
                <div>
                    <h3 class="font-bold text-base text-gray-900" x-text="selected.name"></h3>
                    <p class="text-2xl font-black text-rose-500 mt-1" x-text="visitorSymbol + formatNum(selected.price)"></p>
                </div>
            </div>

            {{-- Not logged in --}}
            <template x-if="!isAuthenticated">
                <div class="text-center space-y-3 py-4">
                    <p class="text-sm text-gray-500">Sign in to send this gift to the celebrant.</p>
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-rose-500 text-white text-sm font-semibold hover:bg-rose-600 transition"
                    >
                        <i class="mdi mdi-account-outline text-base"></i> Sign in to Send Gift
                    </a>
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
