@extends('admin.layout')

@section('title', 'Users')
@section('topbar-title', 'Users')

@section('topbar-actions')
    <span style="font-size:0.82rem;color:var(--muted)">
        {{ $users->total() }} user{{ $users->total() === 1 ? '' : 's' }} total
    </span>
@endsection

@section('content')

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.users') }}" class="filters-bar">
        <input type="text"
               name="search"
               value="{{ request('search') }}"
               placeholder="Search name, email or phone…"
               class="filter-input">
        <select name="type" class="filter-select">
            <option value="">All types</option>
            <option value="user"  {{ request('type') === 'user'  ? 'selected' : '' }}>User</option>
            <option value="admin" {{ request('type') === 'admin' ? 'selected' : '' }}>Admin</option>
        </select>
        <select name="status" class="filter-select">
            <option value="">All statuses</option>
            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            <option value="banned"   {{ request('status') === 'banned'   ? 'selected' : '' }}>Banned</option>
        </select>
        <button type="submit" class="btn-filter">
            <i class="mdi mdi-magnify"></i> Filter
        </button>
        @if (request()->hasAny(['search','type','status']))
            <a href="{{ route('admin.users') }}" class="btn-filter" style="background:var(--off);color:var(--dark);border:1.5px solid var(--border);text-decoration:none">
                <i class="mdi mdi-close"></i> Clear
            </a>
        @endif
    </form>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Type</th>
                    <th>Wallet (USD)</th>
                    <th>Events</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td>
                            <div class="user-chip">
                                <div class="chip-avatar">
                                    {{ strtoupper(substr($u->first_name ?? $u->name ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="chip-name">
                                        {{ trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: $u->name }}
                                    </div>
                                    <div class="chip-email">{{ $u->email }}</div>
                                    @if ($u->phone)
                                        <div class="chip-email" style="font-variant-numeric:tabular-nums">
                                            <i class="mdi mdi-phone-outline"></i>
                                            <a href="tel:{{ $u->phone }}" style="color:inherit">{{ \App\Support\PhoneNumbers::pretty($u->phone) }}</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($u->account_type === 'admin')
                                <span class="badge badge-blue"><i class="mdi mdi-shield-crown-outline"></i> Admin</span>
                            @else
                                <span class="badge badge-gray">User</span>
                            @endif
                        </td>
                        {{-- USD users have one wallet; everyone else has a local
                             balance in their own currency plus the USD one. --}}
                        <td>
                            @if (strtoupper($u->currency ?? 'USD') === 'USD')
                                ${{ number_format($u->global_wallet_balance ?? 0, 2) }}
                            @else
                                {{ config("currency.currencies.{$u->currency}.symbol", $u->currency) }}{{ number_format($u->wallet_balance ?? 0, 2) }}
                                <span style="color:var(--muted)">· ${{ number_format($u->global_wallet_balance ?? 0, 2) }}</span>
                            @endif
                        </td>
                        <td>
                            @if (($u->celebrations_count ?? 0) > 0)
                                <strong>{{ $u->celebrations_count }}</strong>
                            @else
                                <span style="color:var(--muted)">0</span>
                            @endif
                        </td>
                        <td>
                            @php $st = $u->status ?? 'active'; @endphp
                            @if ($st === 'active')
                                <span class="badge badge-green"><i class="mdi mdi-check"></i> Active</span>
                            @elseif ($st === 'banned')
                                <span class="badge badge-red"><i class="mdi mdi-cancel"></i> Banned</span>
                            @else
                                <span class="badge badge-yellow">{{ ucfirst($st) }}</span>
                            @endif
                        </td>
                        <td style="color:var(--muted);font-size:0.8rem;white-space:nowrap">
                            {{ $u->created_at->format('M j, Y') }}
                        </td>
                        <td>
                            {{-- Opens their account in the customer app, exactly
                                 as they see it. Logged against their account,
                                 and the money paths stay closed throughout. --}}
                            @if (auth('admin')->user()->hasPermission('users.impersonate'))
                                <form method="POST" action="{{ route('admin.users.impersonate', $u) }}"
                                      onsubmit="return confirm('Open {{ $u->email }}\'s account as them? This is recorded on their activity log.')">
                                    @csrf
                                    <button type="submit" class="btn-filter"
                                            style="padding:0.4rem 0.7rem;font-size:0.78rem;white-space:nowrap">
                                        <i class="mdi mdi-eye-outline"></i> View as
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:3rem;color:var(--muted)">
                            <i class="mdi mdi-account-off-outline" style="font-size:1.75rem;display:block;margin-bottom:0.5rem"></i>
                            No users found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($users->hasPages())
            <div class="pagination-wrap">
                {{ $users->links() }}
            </div>
        @endif
    </div>

@endsection
