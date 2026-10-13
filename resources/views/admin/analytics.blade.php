@extends('admin.layout')

@section('title', 'Visitors')
@section('topbar-title', 'Visitors')

@section('content')

    @php
        // Accounts carry first and last names; `name` alone is only on older rows.
        $who = fn ($u) => $u ? (trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: ($u->name ?? '—')) : '—';
    @endphp

    <style>
        /* Chart chrome. Series colours are a validated categorical set (CVD-safe
           in this order); everything else is the panel's own palette. */
        .viz {
            --series-visitors: #2a78d6;
            --series-visits:   #eb6834;
            --series-pages:    #1baf7a;
            --viz-grid:        #e9e8e3;
            --viz-axis:        #c9c8c1;
        }
        .range-row { display:flex; gap:0.5rem; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; }
        .range-row .lbl { font-size:0.82rem; color:var(--muted); margin-right:0.25rem; }
        .range-btn {
            display:inline-flex; align-items:center; gap:0.35rem; padding:0.5rem 0.9rem;
            border:1.5px solid var(--border); border-radius:6px; background:var(--white);
            color:var(--dark); font-size:0.85rem; font-weight:600; text-decoration:none;
        }
        .range-btn.on { border-color:var(--accent); background:var(--primary-50); }
        .range-btn .mdi { font-size:1rem; }
        .stat-sub { font-size:0.78rem; color:var(--muted); margin-top:0.25rem; }
        .viz-card { padding:1.5rem; }
        .viz-head { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap; margin-bottom:1rem; }
        .viz-legend { display:flex; gap:1.25rem; flex-wrap:wrap; font-size:0.82rem; color:var(--dark); }
        .viz-legend span { display:inline-flex; align-items:center; gap:0.45rem; }
        .viz-legend i { display:inline-block; width:18px; height:0; border-top:2px solid; border-radius:2px; }
        .viz-toggle {
            background:none; border:1.5px solid var(--border); border-radius:6px; padding:0.4rem 0.75rem;
            font-size:0.8rem; font-weight:600; color:var(--dark); cursor:pointer; font-family:inherit;
        }
        .viz-plot { position:relative; }
        .viz-plot svg { display:block; width:100%; height:auto; overflow:visible; }
        .viz-plot svg:focus { outline:2px solid var(--accent); outline-offset:4px; border-radius:4px; }
        .viz-tip {
            position:absolute; pointer-events:none; background:var(--dark); color:#fff; border-radius:8px;
            padding:0.6rem 0.75rem; font-size:0.8rem; min-width:170px; box-shadow:0 6px 20px rgba(0,0,0,0.18);
            display:none; z-index:2;
        }
        .viz-tip .d { color:rgba(255,255,255,0.65); margin-bottom:0.35rem; }
        .viz-tip .r { display:flex; align-items:center; gap:0.5rem; justify-content:space-between; padding:0.1rem 0; }
        .viz-tip .r b { font-weight:700; font-variant-numeric:tabular-nums; }
        .viz-tip .r i { display:inline-block; width:14px; border-top:2px solid; border-radius:2px; }
        .viz-tip .r span { display:flex; align-items:center; gap:0.45rem; color:rgba(255,255,255,0.8); }
        .viz-table { display:none; margin-top:1rem; }
        .viz-table.open { display:block; }
        .viz-table td, .viz-table th { font-variant-numeric:tabular-nums; }
        .viz-empty { text-align:center; color:var(--muted); padding:2rem 0; }
        .two-col { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; align-items:start; }
        @media (max-width: 1100px) { .two-col { grid-template-columns:1fr; } }
        .muted-sm { font-size:0.78rem; color:var(--muted); }
        .num { text-align:right; font-variant-numeric:tabular-nums; }
    </style>

    {{-- One filter row, above everything it scopes. --}}
    <div class="range-row">
        <span class="lbl">Showing the last</span>
        @foreach ($ranges as $r)
            <a href="{{ route('admin.analytics', ['range' => $r]) }}" class="range-btn {{ $days === $r ? 'on' : '' }}">
                @if ($days === $r)<i class="mdi mdi-check-bold"></i>@endif
                {{ $r }} days
            </a>
        @endforeach
        <span class="lbl" style="margin-left:auto">{{ $from->format('j M Y') }} – {{ now()->format('j M Y') }}</span>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-account-multiple-outline"></i></div>
            <p class="stat-label">Unique visitors</p>
            <p class="stat-val">{{ number_format($totals['visitors']) }}</p>
            <p class="stat-sub">Different people, counted once across the range</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-door-open"></i></div>
            <p class="stat-label">Visits</p>
            <p class="stat-val">{{ number_format($totals['visits']) }}</p>
            <p class="stat-sub">Sessions — a return later in the day is a new visit</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-file-document-outline"></i></div>
            <p class="stat-label">Page views</p>
            <p class="stat-val">{{ number_format($totals['views']) }}</p>
            <p class="stat-sub">Every page opened, website and app</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-party-popper"></i></div>
            <p class="stat-label">Celebration pages</p>
            <p class="stat-val">{{ number_format($totals['celebration_views']) }}</p>
            <p class="stat-sub">
                @if ($totals['views'] > 0)
                    {{ round($totals['celebration_views'] / $totals['views'] * 100) }}% of all page views
                @else
                    Views of a celebration page
                @endif
            </p>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="mdi mdi-account-plus-outline"></i></div>
            <p class="stat-label">Sign-ups</p>
            <p class="stat-val">{{ number_format($totals['signups']) }}</p>
            <p class="stat-sub">
                @if ($totals['signups'] > 0)
                    {{ number_format($totals['attributed']) }} came through a celebration page ({{ round($totals['attributed'] / $totals['signups'] * 100) }}%)
                @else
                    New accounts in the range
                @endif
            </p>
        </div>
    </div>

    {{-- ── Per day ── --}}
    <div class="table-card viz-card viz">
        <div class="viz-head">
            <div>
                <h2 class="table-card-title"><i class="mdi mdi-chart-line" style="color:var(--accent)"></i> Per day</h2>
                <p class="muted-sm" style="margin-top:0.25rem">Hover or use the arrow keys for a day's figures.</p>
            </div>
            <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap">
                <div class="viz-legend" aria-hidden="true">
                    <span><i style="border-color:var(--series-visitors)"></i> Unique visitors</span>
                    <span><i style="border-color:var(--series-visits)"></i> Visits</span>
                    <span><i style="border-color:var(--series-pages)"></i> Celebration page views</span>
                </div>
                <button type="button" class="viz-toggle" id="vizToggle" aria-expanded="false" aria-controls="vizTable">
                    <i class="mdi mdi-table"></i> Table
                </button>
            </div>
        </div>

        @if ($totals['views'] === 0)
            <div class="viz-empty">
                <i class="mdi mdi-chart-timeline-variant" style="font-size:1.75rem;display:block;margin-bottom:0.5rem"></i>
                Nothing recorded in this range yet. Counting starts with the first page anyone opens.
            </div>
        @endif

        <div class="viz-plot" id="vizPlot" @if ($totals['views'] === 0) style="display:none" @endif>
            <svg id="vizSvg" role="img" tabindex="0" aria-label="Unique visitors, visits and celebration page views per day"></svg>
            <div class="viz-tip" id="vizTip" role="status" aria-live="polite"></div>
        </div>

        {{-- The same figures as a table: the chart's twin, reachable without a pointer. --}}
        <div class="viz-table" id="vizTable">
            <table>
                <thead>
                    <tr>
                        <th>Day</th>
                        <th class="num">Unique visitors</th>
                        <th class="num">Visits</th>
                        <th class="num">Page views</th>
                        <th class="num">Celebration page views</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (array_reverse($series) as $day)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($day['date'])->format('D j M') }}</td>
                            <td class="num">{{ number_format($day['visitors']) }}</td>
                            <td class="num">{{ number_format($day['visits']) }}</td>
                            <td class="num">{{ number_format($day['views']) }}</td>
                            <td class="num">{{ number_format($day['celebration_views']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="two-col">

        {{-- ── Busiest celebration pages ── --}}
        <div class="table-card">
            <div class="table-card-header">
                <h2 class="table-card-title"><i class="mdi mdi-party-popper" style="color:var(--accent)"></i> Busiest celebration pages</h2>
            </div>
            @if ($topPages->isEmpty())
                <div class="viz-empty">No celebration page has been opened in this range.</div>
            @else
                <table>
                    <thead>
                        <tr><th>Celebration</th><th>Owner</th><th class="num">Views</th><th class="num">Visitors</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($topPages as $row)
                            <tr>
                                <td>
                                    @if ($row->celebration)
                                        <a href="{{ route('admin.events.show', $row->celebration) }}" style="color:var(--dark);font-weight:600;text-decoration:none">{{ $row->celebration->title }}</a>
                                    @else
                                        <span class="muted-sm">Deleted celebration</span>
                                    @endif
                                </td>
                                <td>{{ $who($row->celebration?->user) }}</td>
                                <td class="num">{{ number_format($row->views) }}</td>
                                <td class="num">{{ number_format($row->visitors) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- ── Who brought the sign-ups ── --}}
        <div class="table-card">
            <div class="table-card-header">
                <h2 class="table-card-title"><i class="mdi mdi-account-arrow-right-outline" style="color:var(--accent)"></i> Pages that brought sign-ups</h2>
            </div>
            @if ($referrers->isEmpty())
                <div class="viz-empty">
                    No sign-up in this range has been traced to a celebration page yet.
                    <div class="muted-sm" style="margin-top:0.35rem">A sign-up is traced when the same browser opened a celebration page before the account was made.</div>
                </div>
            @else
                <table>
                    <thead>
                        <tr><th>Celebration</th><th>Owner</th><th class="num">Sign-ups</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($referrers as $row)
                            <tr>
                                <td>
                                    @if ($row->celebration)
                                        <a href="{{ route('admin.events.show', $row->celebration) }}" style="color:var(--dark);font-weight:600;text-decoration:none">{{ $row->celebration->title }}</a>
                                    @else
                                        <span class="muted-sm">Deleted celebration</span>
                                    @endif
                                </td>
                                <td>{{ $who($row->celebration?->user) }}</td>
                                <td class="num">{{ number_format($row->signups) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- ── The sign-ups themselves ── --}}
    <div class="table-card">
        <div class="table-card-header">
            <div>
                <h2 class="table-card-title"><i class="mdi mdi-account-plus-outline" style="color:var(--accent)"></i> Sign-ups traced to a celebration page</h2>
                <p class="muted-sm" style="margin-top:0.25rem">Latest first. "Discovered" is the first celebration page their browser opened; "wishes as a guest" are messages they left before they had an account.</p>
            </div>
        </div>
        @if ($conversions->isEmpty())
            <div class="viz-empty">None in this range.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>New user</th>
                        <th>Signed up</th>
                        <th>Discovered via</th>
                        <th>Whose page</th>
                        <th>First opened</th>
                        <th class="num">Wishes as a guest</th>
                        <th>Came from</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($conversions as $user)
                        <tr>
                            <td style="font-weight:600;color:var(--dark)">{{ $who($user) }}<div class="muted-sm" style="font-weight:400">{{ $user->email }}</div></td>
                            <td>{{ $user->created_at?->format('j M, H:i') }}</td>
                            <td>
                                @if ($user->discoveredVia)
                                    <a href="{{ route('admin.events.show', $user->discoveredVia) }}" style="color:var(--dark);text-decoration:none">{{ $user->discoveredVia->title }}</a>
                                @else
                                    <span class="muted-sm">Deleted celebration</span>
                                @endif
                            </td>
                            <td>{{ $who($user->discoveredVia?->user) }}</td>
                            <td>
                                {{ $user->discovered_at?->format('j M, H:i') }}
                                @if ($user->discovered_at && $user->created_at)
                                    <div class="muted-sm">{{ $user->discovered_at->diffForHumans($user->created_at, ['parts' => 1]) }} before signing up</div>
                                @endif
                            </td>
                            <td class="num">{{ number_format($guestMessages[$user->visitor_id] ?? 0) }}</td>
                            <td class="muted-sm">{{ $user->first_referrer ?? 'Direct / shared link' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <script>
    (function () {
        const data = @json($series);
        const svg  = document.getElementById('vizSvg');
        const tip  = document.getElementById('vizTip');
        const plot = document.getElementById('vizPlot');
        if (!svg || !data.length) return;

        const SERIES = [
            { key: 'visitors',          name: 'Unique visitors',        color: 'var(--series-visitors)' },
            { key: 'visits',            name: 'Visits',                 color: 'var(--series-visits)' },
            { key: 'celebration_views', name: 'Celebration page views', color: 'var(--series-pages)' },
        ];
        const NS = 'http://www.w3.org/2000/svg';
        const el = (name, attrs) => {
            const n = document.createElementNS(NS, name);
            for (const k in attrs) n.setAttribute(k, attrs[k]);
            return n;
        };
        const compact = (v) => v >= 1000 ? (v / 1000).toFixed(v >= 10000 ? 0 : 1).replace(/\.0$/, '') + 'K' : String(v);
        const fmt = (v) => v.toLocaleString();

        // Clean axis steps: 1, 2, 5 × 10ⁿ, four or so ticks.
        function niceTicks(max) {
            if (max <= 0) return [0, 1, 2, 3, 4];
            const rough = max / 4;
            const pow = Math.pow(10, Math.floor(Math.log10(rough)));
            const step = [1, 2, 5, 10].map((m) => m * pow).find((s) => s >= rough);
            const ticks = [];
            for (let v = 0; v <= max + step * 0.999; v += step) ticks.push(v);
            return ticks;
        }

        let index = -1;
        let geometry = null;

        function render() {
            const width = Math.max(320, plot.clientWidth);
            const height = Math.min(360, Math.max(220, Math.round(width * 0.38)));
            const m = { top: 16, right: 150, bottom: 34, left: 44 };
            const w = width - m.left - m.right;
            const h = height - m.top - m.bottom;

            const max = Math.max(1, ...data.flatMap((d) => SERIES.map((s) => d[s.key])));
            const ticks = niceTicks(max);
            const top = ticks[ticks.length - 1];
            const x = (i) => m.left + (data.length === 1 ? w / 2 : (i / (data.length - 1)) * w);
            const y = (v) => m.top + h - (v / top) * h;

            svg.innerHTML = '';
            svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
            svg.setAttribute('width', width);
            svg.setAttribute('height', height);

            // Grid: solid hairlines, one step off the surface.
            for (const t of ticks) {
                svg.appendChild(el('line', { x1: m.left, x2: m.left + w, y1: y(t), y2: y(t), stroke: 'var(--viz-grid)', 'stroke-width': 1 }));
                const label = el('text', { x: m.left - 8, y: y(t) + 4, 'text-anchor': 'end', 'font-size': 11, fill: 'var(--muted)', style: 'font-variant-numeric:tabular-nums' });
                label.textContent = compact(t);
                svg.appendChild(label);
            }
            svg.appendChild(el('line', { x1: m.left, x2: m.left + w, y1: y(0), y2: y(0), stroke: 'var(--viz-axis)', 'stroke-width': 1 }));

            // X labels: as many as fit without touching.
            const every = Math.max(1, Math.ceil(data.length / Math.floor(w / 64)));
            data.forEach((d, i) => {
                if (i % every !== 0 && i !== data.length - 1) return;
                if (i === data.length - 1 && data.length > 1 && (data.length - 1) % every !== 0 && (data.length - 1) % every < every / 2) return;
                const label = el('text', { x: x(i), y: m.top + h + 20, 'text-anchor': 'middle', 'font-size': 11, fill: 'var(--muted)' });
                label.textContent = d.label;
                svg.appendChild(label);
            });

            // Lines, end markers, and selective labels: only the end value of each series.
            const ends = [];
            SERIES.forEach((s) => {
                const points = data.map((d, i) => `${x(i)},${y(d[s.key])}`).join(' ');
                svg.appendChild(el('polyline', { points, fill: 'none', stroke: s.color, 'stroke-width': 2, 'stroke-linejoin': 'round', 'stroke-linecap': 'round' }));
                const last = data[data.length - 1];
                const ex = x(data.length - 1), ey = y(last[s.key]);
                svg.appendChild(el('circle', { cx: ex, cy: ey, r: 5, fill: s.color, stroke: 'var(--white)', 'stroke-width': 2 }));
                ends.push({ s, ey, value: last[s.key] });
            });

            // End labels only where they will not collide; the legend and tooltip carry the rest.
            ends.sort((a, b) => a.ey - b.ey);
            let lastY = -Infinity;
            ends.forEach((e) => {
                if (e.ey - lastY < 14) return;
                lastY = e.ey;
                const label = el('text', { x: m.left + w + 10, y: e.ey + 4, 'font-size': 11, fill: 'var(--dark)', 'font-weight': 600 });
                label.textContent = `${fmt(e.value)} ${e.s.name.toLowerCase()}`;
                svg.appendChild(label);
            });

            // The hover layer.
            const cross = el('line', { x1: 0, x2: 0, y1: m.top, y2: m.top + h, stroke: 'var(--viz-axis)', 'stroke-width': 1, style: 'display:none' });
            svg.appendChild(cross);
            const dots = SERIES.map((s) => {
                const c = el('circle', { r: 4, fill: s.color, stroke: 'var(--white)', 'stroke-width': 2, style: 'display:none' });
                svg.appendChild(c);
                return c;
            });
            const hit = el('rect', { x: m.left, y: m.top, width: w, height: h, fill: 'transparent' });
            svg.appendChild(hit);

            geometry = { m, w, h, x, y, cross, dots, hit, width };
            if (index >= 0) show(index);
        }

        function show(i) {
            if (!geometry) return;
            index = Math.max(0, Math.min(data.length - 1, i));
            const { x, y, cross, dots, width } = geometry;
            const d = data[index];
            const px = x(index);

            cross.setAttribute('x1', px); cross.setAttribute('x2', px); cross.style.display = '';
            SERIES.forEach((s, k) => { dots[k].setAttribute('cx', px); dots[k].setAttribute('cy', y(d[s.key])); dots[k].style.display = ''; });

            tip.textContent = '';
            const date = document.createElement('div');
            date.className = 'd';
            date.textContent = new Date(d.date + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'short' }) + ' ' + d.label;
            tip.appendChild(date);
            SERIES.forEach((s) => {
                const row = document.createElement('div'); row.className = 'r';
                const name = document.createElement('span');
                const key = document.createElement('i'); key.style.borderColor = s.color;
                name.appendChild(key); name.appendChild(document.createTextNode(s.name));
                const val = document.createElement('b'); val.textContent = fmt(d[s.key]);
                row.appendChild(name); row.appendChild(val);
                tip.appendChild(row);
            });
            const pv = document.createElement('div'); pv.className = 'd'; pv.style.marginTop = '0.35rem'; pv.style.marginBottom = '0';
            pv.textContent = `${fmt(d.views)} page views in all`;
            tip.appendChild(pv);

            // Inside the plot, beside the crosshair; on the left of it near
            // the right edge, so it never covers the end labels or the legend.
            const scale = plot.clientWidth / width;
            tip.style.display = 'block';
            tip.style.transform = 'none';
            tip.style.top = (geometry.m.top * scale + 8) + 'px';
            const tipWidth = tip.offsetWidth || 180;
            const right = px * scale + 14;
            tip.style.left = (right + tipWidth > plot.clientWidth ? px * scale - 14 - tipWidth : right) + 'px';
        }

        function hide() {
            if (!geometry) return;
            geometry.cross.style.display = 'none';
            geometry.dots.forEach((c) => c.style.display = 'none');
            tip.style.display = 'none';
            index = -1;
        }

        svg.addEventListener('pointermove', (e) => {
            if (!geometry) return;
            const rect = svg.getBoundingClientRect();
            const px = (e.clientX - rect.left) * (geometry.width / rect.width);
            const i = data.length === 1 ? 0 : Math.round(((px - geometry.m.left) / geometry.w) * (data.length - 1));
            show(i);
        });
        svg.addEventListener('pointerleave', hide);
        svg.addEventListener('focus', () => show(index < 0 ? data.length - 1 : index));
        svg.addEventListener('blur', hide);
        svg.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') { show(index - 1); e.preventDefault(); }
            if (e.key === 'ArrowRight') { show(index + 1); e.preventDefault(); }
            if (e.key === 'Home') { show(0); e.preventDefault(); }
            if (e.key === 'End') { show(data.length - 1); e.preventDefault(); }
        });

        let frame = null;
        window.addEventListener('resize', () => { cancelAnimationFrame(frame); frame = requestAnimationFrame(render); });
        render();

        const toggle = document.getElementById('vizToggle');
        const table = document.getElementById('vizTable');
        toggle.addEventListener('click', () => {
            const open = table.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    })();
    </script>

@endsection
