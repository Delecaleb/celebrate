@extends('admin.layout')

@section('title', 'Audit log')
@section('topbar-title', 'Audit log')

@section('topbar-actions')
    <span style="font-size:0.82rem;color:var(--muted)">
        {{ number_format($entries->total()) }} entr{{ $entries->total() === 1 ? 'y' : 'ies' }}
    </span>
@endsection

@section('content')

    {{-- Super admins only, and never shown to customers: an admin opening
         somebody's account is a matter for whoever runs the platform. --}}

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-history"></i></div>
            <p class="stat-label">Recorded actions</p>
            <p class="stat-val">{{ number_format($summary['total']) }}</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-eye-outline"></i></div>
            <p class="stat-label">Account views</p>
            <p class="stat-val">{{ number_format($summary['impersonation']) }}</p>
        </div>
        <div class="stat-card accent-card">
            <div class="stat-icon"><i class="mdi mdi-clock-outline"></i></div>
            <p class="stat-label">In the last 24 hours</p>
            <p class="stat-val">{{ number_format($summary['last24h']) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.audit') }}" class="filters-bar">
        <input type="text" name="q" value="{{ $search }}"
               placeholder="Who, what, or an IP…" class="filter-input">

        <select name="action" class="filter-select">
            <option value="">Every action</option>
            @foreach ($actions as $key => $label)
                <option value="{{ $key }}" @selected($action === $key)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="admin" class="filter-select">
            <option value="">Every admin</option>
            @foreach ($admins as $staff)
                <option value="{{ $staff->id }}" @selected($adminId === (string) $staff->id)>
                    {{ $staff->name }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="btn-filter"><i class="mdi mdi-magnify"></i> Filter</button>

        @if ($search !== '' || $action !== '' || $adminId !== '')
            <a href="{{ route('admin.audit') }}" class="btn-filter"
               style="background:var(--off);color:var(--dark);border:1.5px solid var(--border);text-decoration:none">
                <i class="mdi mdi-close"></i> Clear
            </a>
        @endif
    </form>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>When</th>
                    <th>Admin</th>
                    <th>Action</th>
                    <th>What</th>
                    <th>From</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    @php $isView = str_starts_with($entry->action, 'admin.impersonation.'); @endphp
                    <tr>
                        <td style="white-space:nowrap;font-size:0.8rem;color:var(--muted)">
                            {{ $entry->created_at?->format('j M Y, H:i:s') }}
                        </td>
                        <td>
                            {{-- The email is copied onto the row, so an entry
                                 still names who did it after that admin is
                                 deleted. --}}
                            <div style="font-weight:600">{{ $entry->admin?->name ?? '—' }}</div>
                            <div style="font-size:0.75rem;color:var(--muted)">{{ $entry->admin_email }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $isView ? 'badge-yellow' : 'badge-gray' }}">
                                {{ $entry->actionLabel() }}
                            </span>
                        </td>
                        <td style="max-width:420px">{{ $entry->description }}</td>
                        <td style="font-size:0.78rem;color:var(--muted);white-space:nowrap">
                            {{ $entry->ip_address ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:3rem;color:var(--muted)">
                            <i class="mdi mdi-history" style="font-size:1.75rem;display:block;margin-bottom:0.5rem"></i>
                            Nothing recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $entries->links() }}

@endsection
