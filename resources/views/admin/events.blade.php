@extends('admin.layout')

@section('title', 'Events')
@section('topbar-title', 'Events')

@section('topbar-actions')
    <span style="font-size:0.82rem;color:var(--muted)">
        {{ $events->total() }} event{{ $events->total() === 1 ? '' : 's' }} total
    </span>
@endsection

@section('content')

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.events') }}" class="filters-bar">
        <input type="text"
               name="search"
               value="{{ request('search') }}"
               placeholder="Search event title…"
               class="filter-input">
        <select name="type" class="filter-select">
            <option value="">All types</option>
            <option value="birthday"    {{ request('type') === 'birthday'    ? 'selected' : '' }}>Birthday</option>
            <option value="wedding"     {{ request('type') === 'wedding'     ? 'selected' : '' }}>Wedding</option>
            <option value="graduation"  {{ request('type') === 'graduation'  ? 'selected' : '' }}>Graduation</option>
            <option value="anniversary" {{ request('type') === 'anniversary' ? 'selected' : '' }}>Anniversary</option>
            <option value="memorial"    {{ request('type') === 'memorial'    ? 'selected' : '' }}>Memorial</option>
            <option value="other"       {{ request('type') === 'other'       ? 'selected' : '' }}>Other</option>
        </select>
        <select name="status" class="filter-select">
            <option value="">All statuses</option>
            <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Live</option>
            <option value="draft"     {{ request('status') === 'draft'     ? 'selected' : '' }}>Draft</option>
            <option value="closed"    {{ request('status') === 'closed'    ? 'selected' : '' }}>Closed</option>
        </select>
        <button type="submit" class="btn-filter">
            <i class="mdi mdi-magnify"></i> Filter
        </button>
        @if (request()->hasAny(['search','type','status']))
            <a href="{{ route('admin.events') }}" class="btn-filter" style="background:var(--off);color:var(--dark);border:1.5px solid var(--border);text-decoration:none">
                <i class="mdi mdi-close"></i> Clear
            </a>
        @endif
    </form>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Owner</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Messages</th>
                    <th>Gifts</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php
                    $typeIcons = [
                        'birthday'    => 'mdi-cake-variant',
                        'wedding'     => 'mdi-ring',
                        'graduation'  => 'mdi-school',
                        'anniversary' => 'mdi-heart',
                        'memorial'    => 'mdi-dove',
                        'other'       => 'mdi-party-popper',
                    ];
                @endphp
                @forelse ($events as $event)
                    <tr>
                        <td style="max-width:220px">
                            <div style="font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                {{ $event->title }}
                            </div>
                            <div style="font-size:0.73rem;color:var(--muted);margin-top:0.1rem">
                                /{{ $event->slug }}
                            </div>
                        </td>
                        <td>
                            <div class="user-chip">
                                <div class="chip-avatar" style="width:24px;height:24px;font-size:0.65rem">
                                    {{ strtoupper(substr($event->user->first_name ?? $event->user->name ?? 'U', 0, 1)) }}
                                </div>
                                <div style="font-size:0.8rem">
                                    {{ $event->user->first_name ?? $event->user->name }}
                                </div>
                            </div>
                        </td>
                        <td>
                            @php $typeKey = $event->celebration_type ?? 'other'; @endphp
                            <span style="display:inline-flex;align-items:center;gap:0.3rem;font-size:0.8rem">
                                <i class="mdi {{ $typeIcons[$typeKey] ?? 'mdi-party-popper' }}" style="color:var(--accent)"></i>
                                {{ ucfirst($typeKey) }}
                            </span>
                        </td>
                        <td>
                            @if ($event->status === 'published')
                                <span class="badge badge-green"><i class="mdi mdi-circle" style="font-size:0.6rem"></i> Live</span>
                            @elseif ($event->status === 'closed')
                                <span class="badge badge-gray">Closed</span>
                            @else
                                <span class="badge badge-yellow">Draft</span>
                            @endif
                        </td>
                        <td>{{ number_format($event->view_count ?? 0) }}</td>
                        <td>{{ number_format($event->comment_count ?? $event->comments_count ?? 0) }}</td>
                        <td>{{ number_format($event->gifts_count ?? 0) }}</td>
                        <td style="color:var(--muted);font-size:0.78rem;white-space:nowrap">
                            @php
                                $d = $event->event_date ?? $event->start_date;
                            @endphp
                            {{ $d ? \Carbon\Carbon::parse($d)->format('M j, Y') : '—' }}
                        </td>
                        <td>
                            <a href="{{ route('celebrations.show', $event->slug) }}"
                               class="tbl-btn view"
                               target="_blank">
                                <i class="mdi mdi-eye-outline"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:3rem;color:var(--muted)">
                            <i class="mdi mdi-calendar-blank-outline" style="font-size:1.75rem;display:block;margin-bottom:0.5rem"></i>
                            No events found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($events->hasPages())
            <div class="pagination-wrap">
                {{ $events->links() }}
            </div>
        @endif
    </div>

@endsection
