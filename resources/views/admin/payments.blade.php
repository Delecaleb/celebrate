@extends('admin.layout')

@section('title', 'Payments')
@section('topbar-title', 'Payments')

@section('topbar-actions')
    <span style="font-size:0.82rem;color:var(--muted)">
        {{ number_format($payments->total()) }} payment{{ $payments->total() === 1 ? '' : 's' }}
    </span>
@endsection

@section('content')

    {{-- Every payment on the platform: platform gifts, contributions towards a
         registry item, and wallet top-ups — successful, pending and failed
         together, because the ones that did not land are the ones support gets
         asked about. --}}

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-cash-multiple"></i></div>
            <p class="stat-label">Gifts received</p>
            <p class="stat-val">{{ number_format($totals['gifts'], 2) }}</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-gift-outline"></i></div>
            <p class="stat-label">Registry contributions</p>
            <p class="stat-val">{{ number_format($totals['wishes'], 2) }}</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-wallet-plus-outline"></i></div>
            <p class="stat-label">Wallet top-ups</p>
            <p class="stat-val">{{ number_format($totals['topups'], 2) }}</p>
        </div>
        <div class="stat-card accent-card">
            <div class="stat-icon"><i class="mdi mdi-alert-circle-outline"></i></div>
            <p class="stat-label">Not settled</p>
            <p class="stat-val">{{ number_format($counts['pending'] + $counts['failed']) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.payments') }}" class="filters-bar">
        <input type="text" name="q" value="{{ $search }}"
               placeholder="Reference, payer, email or celebration…" class="filter-input">
        <select name="status" class="filter-select">
            <option value="all"        @selected($status === 'all')>All ({{ number_format($counts['all']) }})</option>
            <option value="successful" @selected($status === 'successful')>Successful ({{ number_format($counts['successful']) }})</option>
            <option value="pending"    @selected($status === 'pending')>Pending ({{ number_format($counts['pending']) }})</option>
            <option value="failed"     @selected($status === 'failed')>Failed ({{ number_format($counts['failed']) }})</option>
        </select>
        <button type="submit" class="btn-filter"><i class="mdi mdi-magnify"></i> Filter</button>
        @if ($search !== '' || $status !== 'all')
            <a href="{{ route('admin.payments') }}" class="btn-filter"
               style="background:var(--off);color:var(--dark);border:1.5px solid var(--border);text-decoration:none">
                <i class="mdi mdi-close"></i> Clear
            </a>
        @endif
    </form>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Kind</th>
                    <th>Payer</th>
                    <th>For</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>When</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    @php
                        $settled = in_array($payment->status, ['paid', 'completed', 'success'], true);
                        $waiting = in_array($payment->status, ['pending', 'processing'], true);
                    @endphp
                    <tr>
                        <td style="font-family:ui-monospace,Menlo,monospace;font-size:0.78rem">
                            {{ $payment->reference }}
                        </td>
                        <td><span class="badge badge-gray">{{ $payment->kind }}</span></td>
                        <td>
                            {{ $payment->payer ?: 'Anonymous' }}
                            @if ($payment->payer_email)
                                <div style="font-size:0.75rem;color:var(--muted)">{{ $payment->payer_email }}</div>
                            @endif
                        </td>
                        <td>
                            @if ($payment->context_slug)
                                <a href="{{ route('celebrations.show', $payment->context_slug) }}" target="_blank" rel="noopener">
                                    {{ $payment->context }}
                                </a>
                            @else
                                {{ $payment->context }}
                            @endif
                        </td>
                        <td style="white-space:nowrap">
                            {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}
                        </td>
                        <td>
                            <span class="badge {{ $settled ? 'badge-green' : ($waiting ? 'badge-yellow' : 'badge-red') }}">
                                {{ $payment->status }}
                            </span>
                        </td>
                        <td style="white-space:nowrap;font-size:0.8rem;color:var(--muted)">
                            {{ \Illuminate\Support\Carbon::parse($payment->created_at)->format('j M Y, H:i') }}
                        </td>
                        <td>
                            {{-- Only worth offering where there is something to
                                 settle: asking the gateway about a payment that
                                 already landed changes nothing. --}}
                            @if (! $settled && auth('admin')->user()->hasPermission('payments.revalidate'))
                                <form method="POST" action="{{ route('admin.payments.revalidate') }}">
                                    @csrf
                                    <input type="hidden" name="reference" value="{{ $payment->reference }}">
                                    <button type="submit" class="btn-filter" style="padding:0.4rem 0.7rem;font-size:0.78rem">
                                        <i class="mdi mdi-refresh"></i> Re-check
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:3rem;color:var(--muted)">
                            No payments match that.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $payments->links() }}

@endsection
