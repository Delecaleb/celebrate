@extends('admin.layout')

@section('title', 'Withdrawals')
@section('topbar-title', 'Withdrawals')

@section('topbar-actions')
    @if ($counts['pending'] > 0)
        <span style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.45rem 0.9rem;background:#fff0eb;border:1.5px solid #f9c5a9;border-radius:99px;font-size:0.8rem;font-weight:700;color:var(--accent)">
            <i class="mdi mdi-clock-alert"></i> {{ $counts['pending'] }} pending
        </span>
    @endif
@endsection

@section('content')

    {{-- Status tabs --}}
    <div class="status-tabs">
        @foreach ([
            'pending'    => ['label' => 'Pending',    'icon' => 'mdi-clock-outline'],
            'processing' => ['label' => 'Processing', 'icon' => 'mdi-progress-clock'],
            'completed'  => ['label' => 'Completed',  'icon' => 'mdi-check-circle-outline'],
            'failed'     => ['label' => 'Failed',     'icon' => 'mdi-alert-circle-outline'],
            'rejected'   => ['label' => 'Rejected',   'icon' => 'mdi-cancel'],
            'all'        => ['label' => 'All',         'icon' => 'mdi-format-list-bulleted'],
        ] as $key => $tab)
            <a href="{{ route('admin.withdrawals', ['status' => $key]) }}"
               class="status-tab {{ $status === $key ? 'active' : '' }}">
                <i class="mdi {{ $tab['icon'] }}"></i>
                {{ $tab['label'] }}
                @if ($counts[$key] > 0)
                    <span style="background:{{ $status === $key ? 'rgba(255,255,255,0.35)' : 'var(--border)' }};color:{{ $status === $key ? '#fff' : 'var(--dark)' }};border-radius:99px;padding:0.06rem 0.45rem;font-size:0.68rem;font-weight:700">
                        {{ $counts[$key] }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Bank account</th>
                    <th>Amount (USD)</th>
                    <th>Local</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($withdrawals as $wd)
                    <tr>
                        <td style="color:var(--muted);font-size:0.78rem">#{{ $wd->id }}</td>
                        <td>
                            <div class="user-chip">
                                <div class="chip-avatar">
                                    {{ strtoupper(substr($wd->user->first_name ?? $wd->user->name ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="chip-name">{{ trim(($wd->user->first_name ?? '') . ' ' . ($wd->user->last_name ?? '')) ?: $wd->user->name }}</div>
                                    <div class="chip-email">{{ $wd->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size:0.85rem;font-weight:600">{{ $wd->bank_name ?? '—' }}</div>
                            <div style="font-size:0.75rem;color:var(--muted)">
                                {{ $wd->bank_account_number ?? '—' }} · {{ $wd->bank_account_name ?? '—' }}
                            </div>
                        </td>
                        <td>
                            <strong>${{ number_format($wd->amount, 2) }}</strong>
                        </td>
                        <td style="color:var(--muted);font-size:0.82rem">
                            @if ($wd->original_currency && $wd->original_currency !== 'USD')
                                {{ config("currency.currencies.{$wd->original_currency}.symbol", $wd->original_currency) }}{{ number_format($wd->original_amount, 0) }}
                                <span style="font-size:0.7rem">({{ $wd->original_currency }})</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @php
                                $badgeMap = [
                                    'pending'    => 'badge-yellow',
                                    'processing' => 'badge-blue',
                                    'completed'  => 'badge-green',
                                    'failed'     => 'badge-red',
                                    'rejected'   => 'badge-red',
                                ];
                            @endphp
                            <span class="badge {{ $badgeMap[$wd->status] ?? 'badge-gray' }}">
                                {{ ucfirst($wd->status) }}
                            </span>
                            @if ($wd->note)
                                <div style="font-size:0.72rem;color:#dc2626;margin-top:0.2rem;max-width:160px">
                                    {{ Str::limit($wd->note, 60) }}
                                </div>
                            @endif
                        </td>
                        <td style="color:var(--muted);font-size:0.78rem;white-space:nowrap">
                            {{ $wd->created_at->format('M j, Y') }}<br>
                            <span style="font-size:0.72rem">{{ $wd->created_at->format('g:ia') }}</span>
                        </td>
                        <td>
                            <div class="action-row">
                                @if (in_array($wd->status, ['pending', 'processing']))
                                    {{-- Approve --}}
                                    <form method="POST"
                                          action="{{ route('admin.withdrawals.approve', $wd) }}"
                                          x-data
                                          @submit.prevent="confirm('Approve withdrawal #{{ $wd->id }} for ${{ number_format($wd->amount, 2) }}?') && $el.submit()">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="tbl-btn approve">
                                            <i class="mdi mdi-check"></i> Approve
                                        </button>
                                    </form>

                                    {{-- Reject --}}
                                    <button class="tbl-btn reject"
                                            @click="$dispatch('open-reject', {{ $wd->id }})">
                                        <i class="mdi mdi-close"></i> Reject
                                    </button>
                                @elseif ($wd->status === 'completed')
                                    <span style="font-size:0.78rem;color:#059669;display:flex;align-items:center;gap:0.3rem">
                                        <i class="mdi mdi-check-circle"></i>
                                        {{ $wd->processed_at?->format('M j') }}
                                    </span>
                                @elseif ($wd->status === 'rejected')
                                    <span style="font-size:0.78rem;color:#dc2626;display:flex;align-items:center;gap:0.3rem">
                                        <i class="mdi mdi-cancel"></i> Refunded
                                    </span>
                                @else
                                    <span style="font-size:0.78rem;color:var(--muted)">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:3rem;color:var(--muted)">
                            <i class="mdi mdi-bank-transfer-out" style="font-size:1.75rem;display:block;margin-bottom:0.5rem;opacity:0.4"></i>
                            No withdrawals found for this status.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($withdrawals->hasPages())
            <div class="pagination-wrap">
                {{ $withdrawals->links() }}
            </div>
        @endif
    </div>

@endsection

@push('modals')
{{-- Reject modal (Alpine.js-driven) --}}
<div x-data="{
    open: false,
    wdId: null,
    reason: '',
    formAction: ''
}"
x-on:open-reject.window="open = true; wdId = $event.detail; formAction = '/admin/withdrawals/' + wdId + '/reject'"
x-show="open"
x-cloak
style="position:fixed;inset:0;z-index:60">

    <div class="modal-overlay" @click.self="open = false">
        <div class="modal-box">
            <h2 class="modal-title"><i class="mdi mdi-alert-circle-outline" style="color:#dc2626;margin-right:0.3rem"></i> Reject withdrawal</h2>
            <p class="modal-sub">The user's wallet will be refunded automatically. Provide a reason.</p>

            <form :action="formAction" method="POST">
                @csrf @method('PATCH')
                <div class="modal-field">
                    <label>Rejection reason <span style="color:#dc2626">*</span></label>
                    <textarea name="reason"
                              x-model="reason"
                              placeholder="e.g. Account details mismatch — please update your bank account."
                              required
                              maxlength="300"></textarea>
                    <p style="font-size:0.72rem;color:var(--muted);margin-top:0.3rem">
                        Max 300 characters. This note is shown to the user.
                    </p>
                </div>
                <div class="modal-actions">
                    <button type="button" class="modal-cancel" @click="open = false">Cancel</button>
                    <button type="submit" class="modal-submit">
                        <i class="mdi mdi-close-circle-outline"></i> Reject &amp; refund
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush
