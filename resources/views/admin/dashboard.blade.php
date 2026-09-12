@extends('admin.layout')

@section('title', 'Dashboard')
@section('topbar-title', 'Dashboard')

@section('topbar-actions')
    <a href="{{ route('admin.withdrawals') }}" class="topbar-btn">
        <i class="mdi mdi-bank-transfer-out"></i>
        @if ($stats['withdrawals_pending'] > 0)
            <strong style="color:var(--accent)">{{ $stats['withdrawals_pending'] }} pending</strong> withdrawals
        @else
            Withdrawals
        @endif
    </a>
@endsection

@section('content')

    {{-- Stats grid --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-account-group"></i></div>
            <p class="stat-label">Total users</p>
            <p class="stat-val">{{ number_format($stats['users']) }}</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-calendar-star"></i></div>
            <p class="stat-label">Total events</p>
            <p class="stat-val">{{ number_format($stats['events']) }}</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-calendar-check"></i></div>
            <p class="stat-label">Live events</p>
            <p class="stat-val">{{ number_format($stats['events_live']) }}</p>
        </div>
        <div class="stat-card {{ $stats['withdrawals_pending'] > 0 ? 'accent-card' : '' }}">
            <div class="stat-icon"><i class="mdi mdi-clock-alert"></i></div>
            <p class="stat-label">Pending withdrawals</p>
            <p class="stat-val">{{ $stats['withdrawals_pending'] }}</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-wallet"></i></div>
            <p class="stat-label">Total wallet (USD)</p>
            <p class="stat-val" style="font-size:1.55rem">${{ number_format($stats['total_wallet'], 2) }}</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-arrow-down-bold"></i></div>
            <p class="stat-label">Total credited</p>
            <p class="stat-val" style="font-size:1.55rem">${{ number_format($stats['total_credited'], 2) }}</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-arrow-up-bold"></i></div>
            <p class="stat-label">Total withdrawn</p>
            <p class="stat-val" style="font-size:1.55rem">${{ number_format($stats['total_withdrawn'], 2) }}</p>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start">

        {{-- Pending withdrawals --}}
        <div class="table-card">
            <div class="table-card-header">
                <h2 class="table-card-title">
                    <i class="mdi mdi-bank-transfer-out" style="margin-right:0.35rem;color:var(--accent)"></i>
                    Pending withdrawals
                </h2>
                <a href="{{ route('admin.withdrawals') }}" class="table-card-link">View all</a>
            </div>
            @if ($recentWithdrawals->isEmpty())
                <div style="padding:2.5rem;text-align:center;color:var(--muted)">
                    <i class="mdi mdi-check-circle-outline" style="font-size:1.75rem;display:block;margin-bottom:0.5rem;color:#059669"></i>
                    All caught up — no pending withdrawals.
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentWithdrawals as $wd)
                            <tr>
                                <td>
                                    <div class="user-chip">
                                        <div class="chip-avatar">
                                            {{ strtoupper(substr($wd->user->first_name ?? $wd->user->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="chip-name">{{ $wd->user->first_name ?? $wd->user->name }}</div>
                                            <div class="chip-email">{{ $wd->bank_name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong>${{ number_format($wd->amount, 2) }}</strong>
                                    @if ($wd->original_currency && $wd->original_currency !== 'USD')
                                        <br><span style="font-size:0.73rem;color:var(--muted)">
                                            {{ config("currency.currencies.{$wd->original_currency}.symbol", $wd->original_currency) }}{{ number_format($wd->original_amount, 0) }}
                                        </span>
                                    @endif
                                </td>
                                <td style="color:var(--muted);font-size:0.78rem">{{ $wd->created_at->diffForHumans() }}</td>
                                <td>
                                    <a href="{{ route('admin.withdrawals', ['status' => 'pending']) }}" class="tbl-btn">
                                        <i class="mdi mdi-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Recent signups --}}
        <div class="table-card">
            <div class="table-card-header">
                <h2 class="table-card-title">
                    <i class="mdi mdi-account-plus-outline" style="margin-right:0.35rem;color:var(--accent)"></i>
                    Recent signups
                </h2>
                <a href="{{ route('admin.users') }}" class="table-card-link">View all</a>
            </div>
            @if ($recentUsers->isEmpty())
                <div style="padding:2.5rem;text-align:center;color:var(--muted)">No users yet.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Wallet</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentUsers as $u)
                            <tr>
                                <td>
                                    <div class="user-chip">
                                        <div class="chip-avatar">
                                            {{ strtoupper(substr($u->first_name ?? $u->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="chip-name">{{ trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: $u->name }}</div>
                                            <div class="chip-email">{{ $u->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                {{-- USD users have one wallet; everyone else holds a local balance too. --}}
                                <td>
                                    @if (strtoupper($u->currency ?? 'USD') === 'USD')
                                        ${{ number_format($u->global_wallet_balance ?? 0, 2) }}
                                    @else
                                        {{ config("currency.currencies.{$u->currency}.symbol", $u->currency) }}{{ number_format($u->wallet_balance ?? 0, 2) }}
                                    @endif
                                </td>
                                <td style="color:var(--muted);font-size:0.78rem">{{ $u->created_at->format('M j, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

    </div>

@endsection
