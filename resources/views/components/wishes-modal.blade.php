@props([
    'visitorCurrency' => 'USD',
    'visitorSymbol'   => '$',
    'walletBalance'   => 0,
    'isAuthenticated' => false,
    'isOwner'         => false,
    'celebrationId'   => null,
])

<div
    x-data="wishContributionModal({
        walletBalance:   {{ (float) $walletBalance }},
        visitorCurrency: '{{ $visitorCurrency }}',
        visitorSymbol:   '{{ $visitorSymbol }}',
        isAuthenticated: {{ $isAuthenticated? 'true' : 'false' }},
        isOwner:         {{ $isOwner? 'true' : 'false' }},
        walletBaseUrl:   '{{ url('/wish') }}',
        payBaseUrl:      '{{ url('/wish') }}',
        csrfToken:       '{{ csrf_token() }}',
    })"
    @open-wish.window="openWish($event.detail)"
>

    {{-- ── Backdrop ─────────────────────────────────────────────────────────── --}}
 <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 bg-black/50 flex items-end sm:items-center justify-center p-4"
        @click.self="close()"
        style="display:none"
    >

        {{-- ── Modal panel ──────────────────────────────────────────────────── --}}
 <div
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
            class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl relative"
            @click.stop
        >

            {{-- ── CLOSE button (always visible) ───────────────────────────── --}}
 <button
                type="button"
                @click="close()"
                class="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-black/20 hover:bg-black/30 flex items-center justify-center text-white transition"
            >
 <i class="mdi mdi-close text-sm"></i>
 </button>

            {{-- ════════════════════════════════════════════════════════════
                 STEP: DETAIL — Wish preview + progress
                 ════════════════════════════════════════════════════════════ --}}
 <template x-if="step === 'detail' && wish">

 <div>

                    {{-- Hero image / gradient --}}
 <div class="relative h-44 overflow-hidden bg-gradient-to-br from-violet-400 via-purple-500 to-rose-400">
 <template x-if="wish.image">
 <img :src="wish.image" :alt="wish.name" class="w-full h-full object-cover opacity-60">
 </template>
 <div class="absolute inset-0 flex flex-col items-center justify-center px-5 text-center">
 <template x-if="!wish.image">
 <i class="mdi mdi-gift-outline text-5xl mb-2" style="color:#e11d63"></i>
 </template>
 <h3 class="text-white text-xl font-black drop-shadow leading-tight" x-text="wish.name"></h3>
 <template x-if="wish.description">
 <p class="text-white/80 text-xs mt-1 max-w-[220px] line-clamp-2" x-text="wish.description"></p>
 </template>
 </div>
 </div>

                    {{-- Progress section --}}
 <div class="px-5 pt-5 pb-1">

                        {{-- Amount labels --}}
 <div class="flex items-end justify-between mb-2">
 <div>
 <p class="text-xs text-gray-400 leading-none mb-0.5">Raised so far</p>
 <p class="text-2xl font-black text-gray-900 leading-none">
 <span x-text="visitorSymbol + formatNum(displayCurrent)"></span>
 </p>
 </div>
 <template x-if="displayTarget > 0">
 <div class="text-right">
 <p class="text-xs text-gray-400 leading-none mb-0.5">Goal</p>
 <p class="text-sm font-semibold text-gray-500" x-text="visitorSymbol + formatNum(displayTarget)"></p>
 </div>
 </template>
 </div>

                        {{-- Progress bar --}}
 <template x-if="displayTarget > 0">
 <div>
 <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
 <div
                                        class="h-full rounded-full transition-all duration-700"
                                        style="background: linear-gradient(90deg, #a855f7, #f43f5e)"
                                        :style="`width: ${progressPct}%`"
                                    ></div>
 </div>
 <div class="flex items-center justify-between mt-1.5">
 <span class="text-xs text-purple-600 font-semibold" x-text="progressPct + '% funded'"></span>
 <span class="text-xs text-gray-400" x-text="wish.contributionCount + ' ' + (wish.contributionCount === 1? 'person' : 'people') + ' contributed'"></span>
 </div>
 </div>
 </template>

 </div>

                    {{-- Actions --}}
 <div class="px-5 pb-5 pt-3 space-y-2">

                        {{-- Guest — no account needed to contribute --}}
 <template x-if="!isAuthenticated">
 <div class="space-y-2">
 <button
                                    type="button"
                                    @click="goContribute()"
                                    class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-violet-500 to-rose-500 text-white text-sm font-bold hover:opacity-90 transition flex items-center justify-center gap-2 shadow-lg shadow-rose-200"
                                >
 <i class="mdi mdi-gift-open-outline text-lg"></i>
                                    Make This Wish Come True
 </button>
 </div>
 </template>

                        {{-- Owner viewing their own wish --}}
 <template x-if="isAuthenticated && isOwner">
 <div class="text-center py-2">
 <p class="text-sm text-gray-400">This is your wish  Share the celebration link so others can make it come true!</p>
 </div>
 </template>

                        {{-- Logged-in guest — show contribute button --}}
 <template x-if="isAuthenticated &&!isOwner">
 <div class="space-y-2">
 <button
                                    type="button"
                                    @click="goContribute()"
                                    class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-violet-500 to-rose-500 text-white text-sm font-bold hover:opacity-90 transition flex items-center justify-center gap-2 shadow-lg shadow-rose-200"
                                >
 <i class="mdi mdi-gift-open-outline text-lg"></i>
                                    Make This Wish Come True 
 </button>
 </div>
 </template>

 </div>

 </div>

 </template>

            {{-- ════════════════════════════════════════════════════════════
                 STEP: CONTRIBUTE — Amount input + payment
                 ════════════════════════════════════════════════════════════ --}}
 <template x-if="step === 'contribute'">

 <div class="p-5">

                    {{-- Back --}}
 <button
                        type="button"
                        @click="goBack()"
                        class="flex items-center gap-1 text-sm text-gray-400 hover:text-gray-700 mb-4"
                    >
 <i class="mdi mdi-arrow-left text-base"></i> Back
 </button>

                    {{-- Wish mini card --}}
 <div class="flex items-center gap-3 bg-gradient-to-r from-violet-50 to-rose-50 border border-purple-100 rounded-2xl px-4 py-3 mb-5">
 <template x-if="wish.image">
 <img :src="wish.image" :alt="wish.name" class="w-10 h-10 rounded-xl object-cover border border-white shrink-0">
 </template>
 <template x-if="!wish.image">
 <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center shrink-0">
 <i class="mdi mdi-hand-heart-outline text-xl"></i>
 </div>
 </template>
 <div class="min-w-0">
 <p class="text-sm font-bold text-gray-900 truncate" x-text="wish.name"></p>
 <template x-if="remaining > 0">
 <p class="text-xs text-gray-400">
 <span x-text="visitorSymbol + formatNum(remaining)"></span> still needed
 </p>
 </template>
 </div>
 </div>

                    {{-- Amount input --}}
 <div class="mb-4">
 <label class="block text-xs font-semibold text-gray-500 mb-1.5">
                            How much would you like to give? 
 </label>
 <div class="flex gap-2">
 <div class="flex-1 flex items-center bg-gray-50 border-2 border-gray-100 focus-within:border-violet-400 focus-within:bg-white rounded-2xl px-3 transition">
 <span class="text-gray-400 font-semibold text-sm" x-text="visitorSymbol"></span>
 <input
                                    type="number"
                                    x-model="amount"
                                    min="0.01"
                                    step="0.01"
                                    placeholder="0.00"
                                    class="flex-1 bg-transparent border-0 focus:ring-0 py-3 text-base font-bold pl-1 text-gray-900"
                                    @input="error = ''"
                                >
 </div>
 <template x-if="remaining > 0">
 <button
                                    type="button"
                                    @click="fillRemaining()"
                                    class="px-3 rounded-2xl border-2 border-violet-200 text-violet-600 text-xs font-semibold hover:bg-violet-50 transition whitespace-nowrap"
                                >
                                    Fill it all
 </button>
 </template>
 </div>
 </div>

                    {{-- Note --}}
 <div class="mb-4">
 <label class="block text-xs font-semibold text-gray-500 mb-1.5">Add a warm note <span class="font-normal text-gray-400">(optional)</span></label>
 <textarea
                            x-model="note"
                            rows="2"
                            placeholder="e.g. Wishing you all the joy in the world…"
                            class="w-full bg-gray-50 border-2 border-gray-100 focus:border-violet-400 focus:bg-white rounded-2xl px-3 py-2.5 text-sm resize-none transition"
                        ></textarea>
 </div>

                    {{-- Guest details — no account needed, just a receipt address --}}
 <template x-if="!isAuthenticated">
 <div class="grid grid-cols-2 gap-2 mb-4">
 <input type="text" x-model="guestName" placeholder="Your name"
                                   class="bg-gray-50 border-2 border-gray-100 focus:border-violet-400 focus:bg-white rounded-2xl px-3 py-2.5 text-sm transition">
 <input type="email" x-model="guestEmail" placeholder="Your email"
                                   class="bg-gray-50 border-2 border-gray-100 focus:border-violet-400 focus:bg-white rounded-2xl px-3 py-2.5 text-sm transition">
 </div>
 </template>

                    {{-- Wallet balance chip (signed-in only) --}}
 <template x-if="isAuthenticated">
 <div class="flex items-center justify-between bg-gray-50 border border-gray-100 rounded-xl px-4 py-2.5 mb-4">
 <div class="flex items-center gap-2 text-sm text-gray-500">
 <i class="mdi mdi-heart-outline text-rose-400 text-base"></i>
 <span>Your giving balance</span>
 </div>
 <span
                            class="font-bold text-sm"
                            :class="hasSufficientBalance()? 'text-green-600' : 'text-gray-400'"
                            x-text="visitorSymbol + formatNum(walletBalance)"
                        ></span>
 </div>
 </template>

                    {{-- Error --}}
 <div x-show="error" x-transition class="flex items-center gap-2 text-sm text-red-600 bg-red-50 border border-red-100 rounded-xl px-3 py-2.5 mb-3">
 <i class="mdi mdi-alert-circle-outline text-base"></i>
 <span x-text="error"></span>
 </div>

                    {{-- Action buttons --}}
 <template x-if="hasSufficientBalance()">
 <button
                            type="button"
                            @click="contributeFromWallet()"
                            :disabled="loading"
                            class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-violet-500 to-rose-500 text-white text-sm font-bold hover:opacity-90 transition disabled:opacity-50 flex items-center justify-center gap-2 shadow-lg shadow-rose-200"
                        >
 <span x-show="!loading" class="flex items-center gap-2">
 <i class="mdi mdi-gift-open-outline text-base"></i>
                                Make It Happen! 
 </span>
 <span x-show="loading" class="flex items-center gap-2">
 <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
 <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
 <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
 </svg>
                                Sending the love…
 </span>
 </button>
 </template>

 <template x-if="!hasSufficientBalance()">
 <div class="space-y-2">
 <p class="text-xs text-gray-400 text-center"
                               x-text="isAuthenticated
                                    ? 'Your wallet is empty — pay securely to make this wish real'
                                    : 'Pay securely to make this wish real'"></p>
 <button
                                type="button"
                                @click="initiatePayment()"
                                :disabled="loading"
                                class="w-full py-3.5 rounded-2xl bg-gray-900 text-white text-sm font-bold hover:bg-gray-700 transition disabled:opacity-50 flex items-center justify-center gap-2"
                            >
 <span x-show="!loading" class="flex items-center gap-2">
 <i class="mdi mdi-credit-card-outline text-base"></i>
                                    Celebrate with Card 
 </span>
 <span x-show="loading" class="flex items-center gap-2">
 <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
 <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
 <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
 </svg>
                                    Preparing…
 </span>
 </button>
 </div>
 </template>

 </div>

 </template>

            {{-- ════════════════════════════════════════════════════════════
                 STEP: SUCCESS — Celebration!
                 ════════════════════════════════════════════════════════════ --}}
 <template x-if="step === 'success'">

 <div class="p-6 text-center">

                    {{-- Big emoji burst --}}
 <div class="mb-3"><i class="mdi mdi-check-circle-outline text-6xl" style="color:#0f7b4f"></i></div>

 <h3 class="text-xl font-black text-gray-900 mb-1">
                        You made it happen!
 </h3>
 <p class="text-sm text-gray-500 mb-5">
                        Your contribution to <strong x-text="wish.name"></strong> has been added.
                        The celebrant will love this! 
 </p>

                    {{-- Updated progress --}}
 <template x-if="updatedProgress && updatedProgress.target > 0">
 <div class="bg-gradient-to-r from-violet-50 to-rose-50 border border-purple-100 rounded-2xl px-5 py-4 mb-5 text-left">
 <div class="flex items-end justify-between mb-2">
 <div>
 <p class="text-xs text-gray-400 leading-none mb-0.5">Now raised</p>
 <p class="text-xl font-black text-gray-900" x-text="visitorSymbol + formatNum(updatedProgress.current)"></p>
 </div>
 <p class="text-sm text-gray-400" x-text="'of ' + visitorSymbol + formatNum(updatedProgress.target)"></p>
 </div>
 <div class="w-full h-3 bg-white/60 rounded-full overflow-hidden border border-purple-100">
 <div
                                    class="h-full rounded-full transition-all duration-1000"
                                    style="background: linear-gradient(90deg, #a855f7, #f43f5e)"
                                    :style="`width: ${updatedProgress.pct}%`"
                                ></div>
 </div>
 <p class="text-xs text-purple-600 font-semibold mt-1.5" x-text="updatedProgress.pct + '% funded'"></p>
 </div>
 </template>

                    {{-- Wallet balance after --}}
 <div class="flex items-center justify-between bg-gray-50 border border-gray-100 rounded-xl px-4 py-2.5 mb-5">
 <div class="flex items-center gap-2 text-sm text-gray-500">
 <i class="mdi mdi-wallet-outline text-gray-400 text-base"></i>
 <span>Remaining balance</span>
 </div>
 <span class="font-bold text-sm text-gray-700" x-text="visitorSymbol + formatNum(walletBalance)"></span>
 </div>

 <button
                        type="button"
                        @click="close()"
                        class="w-full py-3 rounded-2xl bg-gradient-to-r from-violet-500 to-rose-500 text-white text-sm font-bold hover:opacity-90 transition"
                    >
                        Back to the Celebration 
 </button>

 </div>

 </template>

 </div>
 </div>

</div>

{{-- wishContributionModal() is defined in resources/js/modules/wishContributionModal.js --}}
