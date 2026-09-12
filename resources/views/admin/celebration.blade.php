@extends('admin.layout')

@section('title', $celebration->title)
@section('topbar-title', $celebration->title)

@section('topbar-actions')
    <a href="{{ route('celebrations.show', $celebration->slug) }}" target="_blank" rel="noopener"
       class="btn-filter" style="text-decoration:none">
        <i class="mdi mdi-open-in-new"></i> Open the page
    </a>
@endsection

@section('content')

    {{-- One celebration end to end: who owns it, what the registry asked for,
         how much of it arrived, and every payment against it — including the
         ones that never landed. --}}

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-account-heart-outline"></i></div>
            <p class="stat-label">Owner</p>
            <p class="stat-val" style="font-size:1.05rem">
                {{ trim(($celebration->user->first_name ?? '') . ' ' . ($celebration->user->last_name ?? '')) ?: '—' }}
            </p>
            <p style="font-size:0.78rem;color:var(--muted);margin-top:0.3rem">{{ $celebration->user->email ?? '' }}</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-format-list-checks"></i></div>
            <p class="stat-label">Registry items</p>
            <p class="stat-val">{{ $registryTotals['items'] }}</p>
            <p style="font-size:0.78rem;color:var(--muted);margin-top:0.3rem">
                {{ $registryTotals['funded'] }} fully funded
            </p>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-cash-check"></i></div>
            <p class="stat-label">Received</p>
            <p class="stat-val">{{ number_format($money['gifts_paid'] + $money['contributions_paid'], 2) }}</p>
        </div>
        <div class="stat-card accent-card">
            <div class="stat-icon"><i class="mdi mdi-clock-alert-outline"></i></div>
            <p class="stat-label">Not settled</p>
            <p class="stat-val">{{ number_format($money['gifts_pending'] + $money['contributions_other'], 2) }}</p>
        </div>
    </div>

    {{-- ── REGISTRY ─────────────────────────────────────────────────── --}}
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Registry</span>
            <span style="font-size:0.82rem;color:var(--muted)">
                {{ number_format($registryTotals['raised'], 2) }} of
                {{ number_format($registryTotals['target'], 2) }} asked for
            </span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Target</th>
                    <th>Raised</th>
                    <th>Backers</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($celebration->wishes as $wish)
                    @php
                        $target = (float) $wish->target_amount;
                        $raised = (float) $wish->current_amount;
                        $done   = $target > 0 && $raised >= $target;
                    @endphp
                    <tr>
                        <td style="font-weight:600">{{ $wish->name }}</td>
                        <td><span class="badge badge-gray">{{ $wish->wish_type ?? 'item' }}</span></td>
                        <td>{{ $wish->currency }} {{ number_format($target, 2) }}</td>
                        <td>{{ $wish->currency }} {{ number_format($raised, 2) }}</td>
                        <td>{{ $wish->contribution_count ?? 0 }}</td>
                        <td>
                            <span class="badge {{ $done ? 'badge-green' : 'badge-yellow' }}">
                                {{ $done ? 'Funded' : ($target > 0 ? round($raised / $target * 100) . '%' : 'Open') }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;padding:2.5rem;color:var(--muted)">
                        No registry items on this celebration.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── GIFTS ────────────────────────────────────────────────────── --}}
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Gifts</span>
            <span style="font-size:0.82rem;color:var(--muted)">{{ $celebration->gifts->count() }} in total</span>
        </div>

        <table>
            <thead>
                <tr><th>From</th><th>Gift</th><th>Amount</th><th>Status</th><th>Reference</th><th>When</th></tr>
            </thead>
            <tbody>
                @forelse ($celebration->gifts->sortByDesc('created_at') as $gift)
                    <tr>
                        <td>
                            {{ $gift->is_anonymous ? 'Anonymous' : $gift->sender_name }}
                            @if ($gift->sender_email && ! $gift->is_anonymous)
                                <div style="font-size:0.75rem;color:var(--muted)">{{ $gift->sender_email }}</div>
                            @endif
                        </td>
                        <td>{{ $gift->platformGift?->gift_name ?? 'Cash gift' }}</td>
                        <td style="white-space:nowrap">{{ $gift->currency }} {{ number_format((float) $gift->amount, 2) }}</td>
                        <td>
                            <span class="badge {{ $gift->payment_status === 'paid' ? 'badge-green' : ($gift->payment_status === 'pending' ? 'badge-yellow' : 'badge-red') }}">
                                {{ $gift->payment_status }}
                            </span>
                        </td>
                        <td style="font-family:ui-monospace,Menlo,monospace;font-size:0.75rem">{{ $gift->transaction_reference }}</td>
                        <td style="font-size:0.8rem;color:var(--muted);white-space:nowrap">{{ $gift->created_at?->format('j M Y, H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;padding:2.5rem;color:var(--muted)">No gifts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── CONTRIBUTIONS ────────────────────────────────────────────── --}}
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Registry contributions</span>
            <span style="font-size:0.82rem;color:var(--muted)">{{ $contributions->count() }} in total</span>
        </div>

        <table>
            <thead>
                <tr><th>From</th><th>Towards</th><th>Amount</th><th>Status</th><th>Reference</th><th>When</th></tr>
            </thead>
            <tbody>
                @forelse ($contributions as $contribution)
                    <tr>
                        <td>{{ $contribution->is_anonymous ? 'Anonymous' : $contribution->contributor_name }}</td>
                        <td>{{ $contribution->wish?->name ?? '—' }}</td>
                        <td style="white-space:nowrap">{{ $contribution->currency }} {{ number_format((float) $contribution->amount, 2) }}</td>
                        <td>
                            <span class="badge {{ $contribution->payment_status === 'paid' ? 'badge-green' : ($contribution->payment_status === 'pending' ? 'badge-yellow' : 'badge-red') }}">
                                {{ $contribution->payment_status }}
                            </span>
                        </td>
                        <td style="font-family:ui-monospace,Menlo,monospace;font-size:0.75rem">{{ $contribution->payment_reference }}</td>
                        <td style="font-size:0.8rem;color:var(--muted);white-space:nowrap">{{ $contribution->created_at?->format('j M Y, H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;padding:2.5rem;color:var(--muted)">No contributions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── WISHES ───────────────────────────────────────────────────── --}}
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Wishes left on the page</span>
            <span style="font-size:0.82rem;color:var(--muted)">latest 50</span>
        </div>

        <table>
            <thead><tr><th>From</th><th>Message</th><th>When</th></tr></thead>
            <tbody>
                @forelse ($celebration->comments as $comment)
                    <tr>
                        <td style="white-space:nowrap">
                            {{ $comment->user
                                ? trim($comment->user->first_name . ' ' . $comment->user->last_name)
                                : ($comment->guest_name ?? 'Anonymous') }}
                        </td>
                        <td style="max-width:520px">{{ $comment->message }}</td>
                        <td style="font-size:0.8rem;color:var(--muted);white-space:nowrap">{{ $comment->created_at?->format('j M Y, H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;padding:2.5rem;color:var(--muted)">No wishes yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endsection
