@extends('admin.layout')

@section('title', 'Gifts')
@section('topbar-title', 'Gift catalogue')

@section('topbar-actions')
    <a href="{{ route('admin.gifts.create') }}" class="btn-filter" style="text-decoration:none">
        <i class="mdi mdi-plus"></i> Add a gift
    </a>
@endsection

@section('content')

    {{-- What a guest can send from any celebration page. Prices are held in USD
         and converted to the visitor's currency when the page renders, so one
         catalogue serves every market. --}}

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-gift-outline"></i></div>
            <p class="stat-label">In the catalogue</p>
            <p class="stat-val">{{ $counts['all'] }}</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-check-decagram"></i></div>
            <p class="stat-label">Live to guests</p>
            <p class="stat-val">{{ $counts['active'] }}</p>
        </div>
        <div class="stat-card accent-card">
            <div class="stat-icon"><i class="mdi mdi-clock-outline"></i></div>
            <p class="stat-label">Pending</p>
            <p class="stat-val">{{ $counts['pending'] }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.gifts') }}" class="filters-bar">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search gifts…" class="filter-input">

        <select name="status" class="filter-select">
            <option value="">Any status</option>
            <option value="active"  @selected($status === 'active')>Active</option>
            <option value="pending" @selected($status === 'pending')>Pending</option>
        </select>

        <select name="category" class="filter-select">
            <option value="">Every category</option>
            @foreach ($categories as $key => $label)
                <option value="{{ $key }}" @selected($category === $key)>{{ $label }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn-filter"><i class="mdi mdi-magnify"></i> Filter</button>

        @if ($search !== '' || $status !== '' || $category !== '')
            <a href="{{ route('admin.gifts') }}" class="btn-filter"
               style="background:var(--off);color:var(--dark);border:1.5px solid var(--border);text-decoration:none">
                <i class="mdi mdi-close"></i> Clear
            </a>
        @endif
    </form>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th style="width:64px"></th>
                    <th>Gift</th>
                    <th>Category</th>
                    <th>Price (USD)</th>
                    <th>Tier</th>
                    <th>Sent</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gifts as $gift)
                    <tr>
                        <td>
                            {{-- The uploaded artwork where there is any; the
                                 fallback icon keeps a new gift from rendering
                                 as an empty square before one is added. --}}
                            @if ($gift->gift_image_url)
                                <span style="display:flex;align-items:center;justify-content:center;
                                             width:44px;height:44px;border-radius:12px;overflow:hidden;
                                             background:var(--off)">
                                    <img src="{{ asset('storage/' . $gift->gift_image_url) }}"
                                         alt="{{ $gift->gift_name }}"
                                         style="width:100%;height:100%;object-fit:contain">
                                </span>
                            @else
                                <span title="No image uploaded — showing the fallback icon"
                                      style="display:flex;align-items:center;justify-content:center;
                                             width:44px;height:44px;border-radius:12px;
                                             background:{{ $gift->accent() }}18;color:{{ $gift->accent() }};
                                             font-size:1.35rem">
                                    <i class="mdi {{ $gift->icon() }}"></i>
                                </span>
                            @endif
                        </td>
                        <td style="max-width:320px">
                            <div style="font-weight:600">{{ $gift->gift_name }}</div>
                            @if ($gift->gift_description)
                                <div style="font-size:0.78rem;color:var(--muted);margin-top:0.15rem">
                                    {{ $gift->gift_description }}
                                </div>
                            @endif
                        </td>
                        <td><span class="badge badge-gray">{{ $gift->categoryLabel() }}</span></td>
                        <td style="white-space:nowrap">
                            {{-- The default first, then anything priced by hand
                                 for a specific market. --}}
                            <div style="font-weight:700">
                                {{ $currencies[$baseCurrency]['symbol'] ?? '$' }}{{ number_format((float) $gift->gift_price, 2) }}
                            </div>

                            @foreach ($currencies as $code => $meta)
                                @continue($code === $baseCurrency)
                                @php $explicit = $gift->explicitPrice($code); @endphp
                                @if ($explicit !== null)
                                    <div style="font-size:0.76rem;color:var(--muted)">
                                        {{ $meta['symbol'] }}{{ number_format($explicit, 2) }} {{ $code }}
                                    </div>
                                @endif
                            @endforeach
                        </td>
                        <td style="font-size:0.8rem;color:var(--muted)">{{ $gift->tier() }}</td>
                        <td>
                            @php $sent = $sentCounts[$gift->id] ?? 0; @endphp
                            @if ($sent > 0)
                                <strong>{{ number_format($sent) }}</strong>
                            @else
                                <span style="color:var(--muted)">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $gift->isActive() ? 'badge-green' : 'badge-yellow' }}">
                                {{ $gift->isActive() ? 'Active' : 'Pending' }}
                            </span>
                        </td>
                        <td style="white-space:nowrap">
                            {{-- One click, because this is the thing staff do
                                 most often. --}}
                            <form method="POST" action="{{ route('admin.gifts.toggle', $gift) }}" style="display:inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn-filter"
                                        style="padding:0.4rem 0.7rem;font-size:0.78rem"
                                        title="{{ $gift->isActive() ? 'Take it off celebration pages' : 'Make it available to guests' }}">
                                    <i class="mdi {{ $gift->isActive() ? 'mdi-pause' : 'mdi-play' }}"></i>
                                    {{ $gift->isActive() ? 'Pause' : 'Publish' }}
                                </button>
                            </form>

                            <a href="{{ route('admin.gifts.edit', $gift) }}" class="btn-filter"
                               style="padding:0.4rem 0.7rem;font-size:0.78rem;text-decoration:none">
                                <i class="mdi mdi-pencil"></i>
                            </a>

                            <form method="POST" action="{{ route('admin.gifts.destroy', $gift) }}" style="display:inline"
                                  onsubmit="return confirm('Delete “{{ $gift->gift_name }}”? If it has ever been sent it will be set to pending instead.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-filter"
                                        style="padding:0.4rem 0.7rem;font-size:0.78rem;background:#fee2e2;color:#991b1b;border:none">
                                    <i class="mdi mdi-trash-can-outline"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:3rem;color:var(--muted)">
                            <i class="mdi mdi-gift-off-outline" style="font-size:1.75rem;display:block;margin-bottom:0.5rem"></i>
                            No gifts match that.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endsection
