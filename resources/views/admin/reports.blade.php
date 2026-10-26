@extends('admin.layout')

@section('title', 'Reports')
@section('topbar-title', 'Reports')

@section('topbar-actions')
    <span style="font-size:0.82rem;color:var(--muted)">
        {{ number_format($openCount) }} waiting for review
    </span>
@endsection

@section('content')

    {{-- What people have reported from the app and the web, one row per
         reported thing. A wish or reply reported by three different people is
         already hidden ("Hidden" below) and waits here for a decision. Check
         this daily: app stores expect reports to be dealt with promptly. --}}

    <form method="GET" action="{{ route('admin.reports') }}" class="filters-bar">
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="open"   @selected($status === 'open')>Waiting for review</option>
            <option value="closed" @selected($status === 'closed')>Dealt with</option>
        </select>
    </form>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>What</th>
                    <th>Reported content</th>
                    <th>Why</th>
                    <th>Reports</th>
                    <th>Latest</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>
                            <span class="badge badge-gray">{{ $item->type }}</span>
                            @if ($item->hidden)
                                <div><span class="badge badge-yellow" style="margin-top:4px">Hidden</span></div>
                            @endif
                        </td>
                        <td style="max-width:360px">
                            <div style="white-space:pre-wrap;word-break:break-word">{{ \Illuminate\Support\Str::limit($item->summary, 220) }}</div>
                            @if ($item->subject instanceof \App\Models\Comment && $item->subject->media_url)
                                <a href="{{ \App\Support\Media::url($item->subject->media_url) }}" target="_blank" rel="noopener" style="font-size:0.78rem">
                                    <i class="mdi mdi-paperclip"></i> View attached {{ $item->subject->media_type === 'video' ? 'video' : 'photo' }}
                                </a>
                            @endif
                            @if ($item->page)
                                <div><a href="{{ $item->page }}" target="_blank" rel="noopener" style="font-size:0.78rem">
                                    <i class="mdi mdi-open-in-new"></i> Open the page
                                </a></div>
                            @endif
                            @foreach ($item->details as $detail)
                                <div style="font-size:0.75rem;color:var(--muted);margin-top:4px">“{{ \Illuminate\Support\Str::limit($detail, 160) }}”</div>
                            @endforeach
                        </td>
                        <td style="font-size:0.8rem">
                            @foreach ($item->reasons as $reason => $n)
                                <div>{{ $reasons[$reason] ?? $reason }} @if ($n > 1)<strong>× {{ $n }}</strong>@endif</div>
                            @endforeach
                        </td>
                        <td><strong>{{ $item->count }}</strong></td>
                        <td style="white-space:nowrap;font-size:0.8rem;color:var(--muted)">
                            {{ \Illuminate\Support\Carbon::parse($item->latest_at)->diffForHumans() }}
                        </td>
                        <td style="white-space:nowrap">
                            @if ($status === 'open' && $item->subject)
                                @php $post = in_array($item->type, ['wish', 'reply'], true); @endphp
                                <div style="display:flex;gap:6px;flex-wrap:wrap">
                                    @if ($post && ! $item->hidden)
                                        @include('admin.partials.report-action', ['item' => $item, 'action' => 'hide', 'label' => 'Hide', 'icon' => 'eye-off-outline'])
                                    @endif
                                    @if ($post && $item->hidden)
                                        @include('admin.partials.report-action', ['item' => $item, 'action' => 'restore', 'label' => 'Restore', 'icon' => 'eye-outline'])
                                    @endif
                                    @if ($post)
                                        @include('admin.partials.report-action', ['item' => $item, 'action' => 'remove', 'label' => 'Delete', 'icon' => 'delete-outline', 'confirm' => 'Delete this for good?'])
                                    @endif
                                    @if ($item->type === 'celebration' && ! $item->hidden)
                                        @include('admin.partials.report-action', ['item' => $item, 'action' => 'unlist', 'label' => 'Make private', 'icon' => 'lock-outline'])
                                    @endif
                                    @include('admin.partials.report-action', ['item' => $item, 'action' => 'dismiss', 'label' => 'Nothing wrong', 'icon' => 'check'])
                                </div>
                            @elseif ($status === 'open')
                                @include('admin.partials.report-action', ['item' => $item, 'action' => 'dismiss', 'label' => 'Close', 'icon' => 'check'])
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:3rem;color:var(--muted)">
                            {{ $status === 'open' ? 'Nothing waiting — all clear.' : 'Nothing dealt with yet.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1rem">{{ $groups->links() }}</div>

@endsection
