@extends('admin.layout')

@section('title', 'Settings')
@section('topbar-title', 'Settings')

@section('topbar-actions')
    {{-- Only groups that talk to an outside service have anything to test. --}}
    @if (in_array($group, ['payments', 'mail', 'location'], true))
        <form method="POST" action="{{ route('admin.settings.test', $group) }}">
            @csrf
            <button type="submit" class="btn-filter">
                <i class="mdi mdi-connection"></i> Test these credentials
            </button>
        </form>
    @endif
@endsection

@section('content')

    @if (session('success'))
        <div class="alert alert-success"><i class="mdi mdi-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error"><i class="mdi mdi-alert"></i> {{ session('error') }}</div>
    @endif

    {{-- Testing the credentials proves the mailbox works. It does not prove
         mail works: everything the site actually sends is queued, so with no
         worker running the Test button reports success while every receipt
         sits in the jobs table untouched. --}}
    @if ($queue && ! $queue['healthy'])
        <div class="alert alert-error" style="align-items:flex-start">
            <i class="mdi mdi-timer-sand-empty"></i>
            <div>
                <strong>{{ $queue['stale'] }} {{ Str::plural('email', $queue['stale']) }} waiting and not going out.</strong>
                Everything the site sends is written to the outbox and posted by the scheduler, so mail only
                moves while <code style="background:rgba(0,0,0,.06);padding:.1rem .3rem">php artisan schedule:run</code>
                is on cron. The oldest has been waiting
                {{ \Illuminate\Support\Carbon::parse($queue['oldest'])->diffForHumans(null, true) }}.
                <br>
                <a href="{{ route('admin.outbox') }}">Open the outbox</a> to read them, or send now with
                <code style="background:rgba(0,0,0,.06);padding:.1rem .3rem">php artisan emails:send</code>.
                @if ($queue['failed'] > 0)
                    <br>{{ $queue['failed'] }} {{ Str::plural('email', $queue['failed']) }} gave up after retrying —
                    the outbox shows what the server said.
                @endif
            </div>
        </div>
    @endif

    {{-- Values set here are encrypted, override .env, and take effect on the
         next request — no deploy, no config:cache. --}}
    <div class="filters-bar" style="margin-bottom:1.5rem">
        @foreach ($groups as $name)
            <a href="{{ route('admin.settings', $name) }}" class="btn-filter"
               style="text-decoration:none;{{ $group === $name ? '' : 'background:var(--off);color:var(--dark);border:1.5px solid var(--border)' }}">
                {{ ucfirst($name) }}
            </a>
        @endforeach

        <a href="{{ route('admin.currencies') }}" class="btn-filter"
           style="text-decoration:none;background:var(--off);color:var(--dark);border:1.5px solid var(--border)">
            Currencies
        </a>
    </div>

    @php
        /*
         * A gateway is a thing in its own right — its switch, its keys and the
         * URL it calls back — so each gets a tab rather than everything landing
         * in one column where Stripe's keys sit between Paystack's.
         */
        $gateways = [
            'paystack' => [
                'name'    => 'Paystack',
                'icon'    => 'mdi-credit-card-outline',
                'takes'   => 'Cards, transfers and USSD in naira. Also verifies bank accounts and sends payouts.',
                'webhook' => route('webhooks.paystack'),
            ],
            'stripe' => [
                'name'    => 'Stripe',
                'icon'    => 'mdi-currency-usd',
                'takes'   => 'Card payments in dollars, for everyone paying outside Nigeria.',
                'webhook' => route('webhooks.stripe'),
            ],
            'alatpay' => [
                'name'    => 'AlatPay',
                'icon'    => 'mdi-bank-transfer-in',
                'takes'   => 'Bank transfer in naira. Leads when Paystack is off, and catches it when Paystack cannot start.',
                'webhook' => route('webhooks.alatpay'),
            ],
        ];

        $tabs  = collect($sections)->keys()->filter(fn ($section) => $section !== '')->values();
        $loose = $sections[''] ?? [];
    @endphp

    <form method="POST" action="{{ route('admin.settings.update', $group) }}"
          @if ($tabs->isNotEmpty()) x-data="{ tab: '{{ $tabs->first() }}' }" @endif>
        @csrf @method('PUT')

        @if ($tabs->isNotEmpty())
            {{-- One tab per gateway, each saying at a glance whether it is on. --}}
            <div class="filters-bar" style="margin-bottom:1.25rem">
                @foreach ($tabs as $tab)
                    @php
                        $meta = $gateways[$tab] ?? ['name' => ucfirst($tab), 'icon' => 'mdi-cog-outline', 'takes' => null, 'webhook' => null];
                        $on   = \App\Support\PaymentGateways::isActive($tab);
                    @endphp

                    <button type="button" class="btn-filter" @click="tab = '{{ $tab }}'"
                            :style="tab === '{{ $tab }}' ? '' : 'background:var(--off);color:var(--dark);border:1.5px solid var(--border)'">
                        <i class="mdi {{ $meta['icon'] }}"></i>
                        {{ $meta['name'] }}
                        <span class="badge {{ $on ? 'badge-green' : 'badge-yellow' }}" style="font-size:0.66rem;margin-left:0.35rem">
                            {{ $on ? 'On' : 'Off' }}
                        </span>
                    </button>
                @endforeach
            </div>

            @foreach ($tabs as $tab)
                @php
                    $meta = $gateways[$tab] ?? ['name' => ucfirst($tab), 'icon' => 'mdi-cog-outline', 'takes' => null, 'webhook' => null];
                @endphp

                {{-- Every panel stays in the form, so saving from one tab never
                     blanks the settings sitting on another. --}}
                <div x-show="tab === '{{ $tab }}'" @unless($loop->first) x-cloak @endunless>
                    <div class="table-card" style="padding:1.75rem">

                        <div style="display:flex;align-items:flex-start;gap:0.85rem;padding-bottom:1.1rem;border-bottom:1px solid var(--border)">
                            <div style="display:flex;align-items:center;justify-content:center;width:42px;height:42px;flex:none;border-radius:12px;background:var(--off);color:var(--accent);font-size:1.3rem">
                                <i class="mdi {{ $meta['icon'] }}"></i>
                            </div>
                            <div style="flex:1;min-width:0">
                                <p style="margin:0;font-size:1rem;font-weight:800;color:var(--dark)">{{ $meta['name'] }}</p>
                                @if ($meta['takes'])
                                    <p style="margin:0.2rem 0 0;font-size:0.8rem;color:var(--muted);line-height:1.5">{{ $meta['takes'] }}</p>
                                @endif
                            </div>
                        </div>

                        @if ($meta['webhook'])
                            {{-- The one setting that is not ours to change: it
                                 goes in their dashboard, and getting it wrong is
                                 why payments sit unconfirmed. --}}
                            <div style="padding:1.1rem 0;border-bottom:1px solid var(--border)">
                                <label class="filter-label" style="margin:0 0 0.45rem">Webhook URL — paste this into {{ $meta['name'] }}</label>
                                <input type="text" class="filter-input" style="width:100%;font-family:ui-monospace,SFMono-Regular,Menlo,monospace"
                                       value="{{ $meta['webhook'] }}" readonly onclick="this.select()">
                                <p style="font-size:0.78rem;color:var(--muted);margin-top:0.4rem">
                                    {{ $meta['name'] }} calls this when a payment settles. Without it, payments are only
                                    confirmed when the payer comes back to the site.
                                </p>
                            </div>
                        @endif

                        @foreach ($sections[$tab] as $key => $field)
                            @include('admin.partials.setting-field', [
                                'key'   => $key,
                                'field' => $field,
                                'last'  => $loop->last,
                            ])
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif

        @if (count($loose))
            <div class="table-card" style="padding:1.75rem">
                @foreach ($loose as $key => $field)
                    @include('admin.partials.setting-field', [
                        'key'   => $key,
                        'field' => $field,
                        'last'  => $loop->last,
                    ])
                @endforeach
            </div>
        @endif

        <button type="submit" class="btn-filter" style="margin-top:1.25rem">
            <i class="mdi mdi-content-save"></i> Save settings
        </button>
    </form>

@endsection
