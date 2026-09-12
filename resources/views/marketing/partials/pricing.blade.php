{{-- Pricing page --}}

@php
/*
|--------------------------------------------------------------------------
| PLACEHOLDER PRICING — replace before launch
|--------------------------------------------------------------------------
| There is no fee/commission logic in the codebase yet (nothing in
| app/Services/payment_system or config/), so these numbers are NOT wired to
| anything real. Edit this one array once the commercial model is decided, and
| move it to config/ or the database if it needs to change without a deploy.
|
| Money is written as a NUMBER, not as a string with a currency glyph in it.
| $symbol, $decimals and $currencyName come from the controller, which places
| the visitor — Nigeria sees naira, everywhere else sees the base currency —
| so the figures below are already currency-aware for whatever replaces them.
| Anything that is not an amount goes in 'display' instead.
*/
$money = function ($amount) use ($symbol, $decimals) {
    // Whole figures read better without trailing zeros on a pricing page.
    $places = fmod((float) $amount, 1) === 0.0 ? 0 : $decimals;

    return $symbol . number_format((float) $amount, $places);
};

$plans = [
    [
        'name'     => 'Free',
        'amount'   => 0,
        'unit'     => 'to create a page',
        'note'     => 'Everything you need to run one celebration end to end.',
        'featured' => false,
        'cta'      => 'Create your celebration',
        'features' => [
            'Unlimited wishes and photos',
            'Cash gifts by card or transfer',
            'Group gifting towards wishlist items',
            'One shareable link',
            'Downloadable photobook',
        ],
    ],
    [
        'name'     => 'Gift fee',
        'display'  => 'TBC%',
        'unit'     => 'per cash gift received',
        'note'     => 'Deducted automatically before the gift reaches your wallet. Covers the card and transfer charges from Paystack and Stripe.',
        'featured' => true,
        'cta'      => 'Start collecting gifts',
        'features' => [
            'No monthly subscription',
            'No charge if nobody sends money',
            'Withdraw to any saved bank account',
            'Itemised history of every gift',
        ],
    ],
    [
        'name'     => 'Organisations',
        'display'  => 'Custom',
        'unit'     => 'talk to us',
        'note'     => 'For teams and communities running celebrations in bulk.',
        'featured' => false,
        'cta'      => 'Contact us',
        'features' => [
            'Bulk celebrant upload',
            'Multiple administrators',
            'Consolidated reporting',
            'Priority support',
        ],
    ],
];
@endphp

<section class="page-head">
    <div class="pattern pattern-dots pattern-fade"></div>

    <div class="wrap sec-inner">
        <h1 class="h-display">Free to start.<br><span class="t-accent">Always.</span></h1>
        <p class="lead">
            Creating a celebration page costs nothing. We only take a cut when money actually
            changes hands — so if nobody sends a gift, you pay nothing.
        </p>
    </div>
</section>

{{-- ══ PLANS ════════════════════════════════════════════════════════ --}}
<section class="sec">
    <div class="wrap">
        <div class="price-grid">
            @foreach ($plans as $plan)
                <div class="price-card @if ($plan['featured']) is-featured @endif">
                    @if ($plan['featured'])
                        <span class="price-tag">Most relevant</span>
                    @endif

                    <p class="price-name">{{ $plan['name'] }}</p>
                    <p class="price-amount">
                        {{ $plan['display'] ?? $money($plan['amount']) }}
                        <small>{{ $plan['unit'] }}</small>
                    </p>
                    <p class="price-note">{{ $plan['note'] }}</p>

                    <ul class="checks">
                        @foreach ($plan['features'] as $feature)
                            <li><i class="mdi mdi-check-circle"></i> <span>{{ $feature }}</span></li>
                        @endforeach
                    </ul>

                    @if ($plan['name'] === 'Organisations')
                        <a href="mailto:hello@celebratemi.com" class="btn btn-secondary">
                            <i class="mdi mdi-email-outline"></i> {{ $plan['cta'] }}
                        </a>
                    @else
                        <button
                            type="button"
                            x-data
                            x-on:click="$dispatch('open-modal', 'create-event')"
                            class="btn {{ $plan['featured'] ? 'btn-primary' : 'btn-secondary' }}"
                        >
                            <i class="mdi mdi-plus-circle-outline"></i> {{ $plan['cta'] }}
                        </button>
                    @endif
                </div>
            @endforeach
        </div>

        
    </div>
</section>

{{-- ══ WHAT'S INCLUDED ══════════════════════════════════════════════ --}}
<section class="sec sec-alt">
    <div class="pattern pattern-grid pattern-fade"></div>

    <div class="wrap sec-inner">
        <div class="section-head">
            <h2 class="h-section">No tiers. No<br><span class="t-accent">locked features</span>.</h2>
            <p class="lead">
                Every celebration gets the full product. We don't hide the photobook or the
                themes behind an upgrade.
            </p>
        </div>

        <div class="grid-3 mt-grid">
            <article class="card">
                <div class="itile"><i class="mdi mdi-infinity"></i></div>
                <h3 class="h-card">Unlimited guests</h3>
                <p>No cap on wishes, photos or the number of people who can send a gift.</p>
            </article>

            <article class="card">
                <div class="itile"><i class="mdi mdi-shield-check-outline"></i></div>
                <h3 class="h-card">Secure payments</h3>
                <p>Card details never touch our servers — Paystack and Stripe handle the checkout.</p>
            </article>

            <article class="card">
                <div class="itile"><i class="mdi mdi-clock-outline"></i></div>
                <h3 class="h-card">Pages don't expire</h3>
                <p>Your celebration stays online after the event, with the wishes still readable.</p>
            </article>
        </div>
    </div>
</section>

{{-- ══ FAQ ══════════════════════════════════════════════════════════ --}}
<section class="sec">
    <div class="wrap">
        <div class="section-head">
            <h2 class="h-section">Billing questions.</h2>
        </div>

        <div class="grid-2 mt-grid">
            <article class="card">
                <h3 class="h-card">When am I charged?</h3>
                <p>Never up front. The gift fee comes out of each cash gift as it arrives, so you always see the net amount in your wallet.</p>
            </article>

            <article class="card">
                <h3 class="h-card">Is there a subscription?</h3>
                <p>No. There's nothing to cancel and no monthly bill — you can leave a page dormant for years at no cost.</p>
            </article>

            <article class="card">
                <h3 class="h-card">What does a withdrawal cost?</h3>
                <p>Withdrawals to your saved bank account are processed at cost. Your wallet history shows the exact figure each time.</p>
            </article>

            <article class="card">
                <h3 class="h-card">Can I use it without collecting money?</h3>
                <p>Absolutely. Plenty of people use CelebrateMi purely for the wishes wall and the photobook, and never turn gifting on.</p>
            </article>
        </div>
    </div>
</section>

{{-- ══ CTA ══════════════════════════════════════════════════════════ --}}
<section class="cta-band on-dark">
    <div class="pattern pattern-dots pattern-fade"></div>

    <div class="wrap sec-inner">
        <h2 class="h-section">Nothing to lose.</h2>
        <p>Create the page, share it, and see what happens.</p>

        <button
            type="button"
            x-data
            x-on:click="$dispatch('open-modal', 'create-event')"
            class="btn btn-on-dark"
        >
            <i class="mdi mdi-plus-circle-outline"></i> Create your celebration
        </button>

        <p class="cta-note">Free &middot; No card needed</p>
    </div>
</section>
