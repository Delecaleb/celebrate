{{--
    The withdrawal list, shared by the Wallet and Withdrawals pages.

    Expects $withdrawals and $currencySymbol. Renders the rows only — each page
    brings its own heading and its own empty state, since they word it
    differently.
--}}
<div class="tx-list">
    @foreach ($withdrawals as $wd)
        @php
            /* status colours stay inside the two-hue palette:
               teal = done, primary = failed, neutral = in-flight */
            $wdBadge = match($wd->status) {
                'completed'  => ['bg' => 'var(--teal-l)',    'color' => 'var(--teal)',    'label' => 'Completed'],
                'processing' => ['bg' => 'var(--line-2)',    'color' => 'var(--ink)',     'label' => 'Processing'],
                'failed'     => ['bg' => 'var(--primary-l)', 'color' => 'var(--primary)', 'label' => 'Failed'],
                'rejected'   => ['bg' => 'var(--primary-l)', 'color' => 'var(--primary)', 'label' => 'Rejected'],
                default      => ['bg' => 'var(--line-2)',    'color' => 'var(--muted)',   'label' => 'Pending'],
            };
            $wdSymbol = $wd->original_currency
                ? config("currency.currencies.{$wd->original_currency}.symbol", $wd->original_currency)
                : $currencySymbol;
        @endphp
        <div class="tx-item">
            <div class="tx-icon debit"><i class="mdi mdi-arrow-up"></i></div>
            <div class="tx-info">
                <p class="tx-desc">
                    {{ $wd->bank_name ?? $wd->bankAccount->bank_name }} —
                    ••{{ substr($wd->bank_account_number ?? $wd->bankAccount->account_number ?? '0000', -4) }}
                </p>
                <p class="tx-meta">
                    {{ $wd->bank_account_name ?? $wd->bankAccount->account_name ?? '' }} ·
                    {{ $wd->created_at->format('M j, Y · g:ia') }}
                </p>
                @if ($wd->note)
                    <p class="tx-meta accent" style="margin-top:0.2rem">
                        <i class="mdi mdi-alert-circle-outline" style="font-size:0.8rem"></i>
                        {{ $wd->note }}
                    </p>
                @endif
            </div>
            <div class="tx-right">
                <p class="tx-amount debit">−{{ $wdSymbol }}{{ number_format($wd->original_amount ?? $wd->amount, 2) }}</p>
                <span class="wd-badge" style="background:{{ $wdBadge['bg'] }};color:{{ $wdBadge['color'] }};margin-top:0.35rem">
                    {{ $wdBadge['label'] }}
                </span>
            </div>
        </div>
    @endforeach
</div>
