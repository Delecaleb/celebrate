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
               placeholder="Search name or email…"
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
                        <td>${{ number_format($u->wallet_balance ?? 0, 2) }}</td>
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
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:3rem;color:var(--muted)">
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
