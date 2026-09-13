@extends('admin.layout')

@section('title', 'Outbox')
@section('topbar-title', 'Outbox')

@section('content')

    @if (session('success'))
        <div class="alert alert-success"><i class="mdi mdi-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error"><i class="mdi mdi-alert"></i> {{ session('error') }}</div>
    @endif

    {{-- Status first, because the only question anyone brings here is
         "did it go out?" --}}
    <div class="stats-bar cols-4" style="margin-bottom:1.5rem">
        @foreach ([
            'pending' => ['Waiting', 'mdi-timer-sand', 'var(--primary)'],
            'sent'    => ['Sent',    'mdi-check-circle-outline', '#15803d'],
            'failed'  => ['Failed',  'mdi-alert-circle-outline', '#b91c1c'],
            'held'    => ['Held',    'mdi-pause-circle-outline', 'var(--muted)'],
        ] as $key => [$label, $icon, $colour])
            <a href="{{ route('admin.outbox', ['status' => $key]) }}"
               class="stat-card" style="text-decoration:none;color:inherit">
                <div class="stat-icon" style="color:{{ $colour }}"><i class="mdi {{ $icon }}"></i></div>
                <p class="stat-label">{{ $label }}</p>
                <p class="stat-value">{{ $counts[$key] ?? 0 }}</p>
            </a>
        @endforeach
    </div>

    <form method="GET" class="filters-bar" style="margin-bottom:1.25rem">
        <input name="q" value="{{ request('q') }}" class="filter-input"
               placeholder="Address or subject" style="min-width:220px">

        <select name="status" class="filter-input">
            <option value="">Any status</option>
            @foreach (['pending', 'sending', 'sent', 'failed', 'held'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>

        <select name="type" class="filter-input">
            <option value="">Any type</option>
            @foreach ($types as $t)
                <option value="{{ $t }}" @selected(request('type') === $t)>{{ $t }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn-filter"><i class="mdi mdi-magnify"></i> Filter</button>

        @if (request()->hasAny(['q', 'status', 'type']))
            <a href="{{ route('admin.outbox') }}" class="btn-filter">Clear</a>
        @endif
    </form>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>To</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>When</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($emails as $email)
                    <tr>
                        <td style="max-width:200px">
                            <a href="{{ route('admin.outbox.show', $email) }}" style="font-weight:600">
                                {{ $email->to_address }}
                            </a>
                            @if ($email->to_name)
                                <p style="font-size:0.74rem;color:var(--muted)">{{ $email->to_name }}</p>
                            @endif
                        </td>

                        <td style="max-width:280px;font-size:0.86rem">{{ $email->subject }}</td>

                        <td><code style="font-size:0.76rem">{{ $email->type }}</code></td>

                        <td style="white-space:nowrap">
                            @php
                                $badge = match ($email->status) {
                                    'sent'    => ['badge-green', 'Sent'],
                                    'failed'  => ['badge-red',   'Failed'],
                                    'sending' => ['badge-blue',  'Sending'],
                                    'held'    => ['badge-grey',  'Held'],
                                    default   => ['badge-amber', 'Waiting'],
                                };
                            @endphp
                            <span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span>
                            @if ($email->attempts > 1)
                                <span style="font-size:0.72rem;color:var(--muted)">×{{ $email->attempts }}</span>
                            @endif
                        </td>

                        <td style="white-space:nowrap;font-size:0.78rem;color:var(--muted)">
                            {{ ($email->sent_at ?? $email->created_at)->diffForHumans() }}
                        </td>

                        <td style="white-space:nowrap">
                            @if ($email->status !== 'sent')
                                <form method="POST" action="{{ route('admin.outbox.retry', $email) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn-filter" style="padding:0.35rem 0.6rem;font-size:0.76rem"
                                            title="Send again on the next pass">
                                        <i class="mdi mdi-refresh"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:2.5rem;color:var(--muted)">
                            Nothing here yet. Every email the site sends will be listed on this page.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1.25rem">{{ $emails->links() }}</div>

@endsection
