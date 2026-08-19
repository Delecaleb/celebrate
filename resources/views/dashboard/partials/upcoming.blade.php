@php
    $nextUp   = $upcoming->first();
    $nextDate = $nextUp ? ($nextUp->event_date ?? $nextUp->start_date) : null;
@endphp

                <div class="page-head">
                    <div>
                        <h1>Upcoming</h1>
                        <p class="sub">Published celebrations with a date still ahead of them.</p>
                    </div>
                </div>

                <div class="stats-bar cols-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="mdi mdi-calendar-clock"></i></div>
                        <p class="stat-label">Coming up</p>
                        <p class="stat-value">{{ $upcoming->count() }}</p>
                        <p class="stat-sub">celebration{{ $upcoming->count() === 1 ? '' : 's' }} scheduled</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="mdi mdi-calendar-star"></i></div>
                        <p class="stat-label">Next one</p>
                        <p class="stat-value" style="font-size:1.35rem">
                            {{ $nextDate ? \Carbon\Carbon::parse($nextDate)->format('M j') : '—' }}
                        </p>
                        <p class="stat-sub">
                            {{ $nextDate ? \Carbon\Carbon::parse($nextDate)->diffForHumans() : 'nothing scheduled' }}
                        </p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="mdi mdi-check-decagram-outline"></i></div>
                        <p class="stat-label">Live pages</p>
                        <p class="stat-value">{{ $stats['published'] }}</p>
                        <p class="stat-sub">published and collecting</p>
                    </div>
                </div>

                <div class="tab-panel-header">
                    <div>
                        <h2 class="panel-title">The schedule</h2>
                        <p class="panel-subtitle">Soonest first</p>
                    </div>
                </div>

                @if ($upcoming->isEmpty())
                    <div class="empty-state">
                        <i class="mdi mdi-calendar-blank empty-icon"></i>
                        <h3>Nothing upcoming</h3>
                        <p>Publish a celebration with a future date and it will appear here.</p>
                        <a href="{{ route('dashboard') }}" data-nav class="btn-empty">
                            <i class="mdi mdi-arrow-left"></i> Go to My Events
                        </a>
                    </div>
                @else
                    <div class="upcoming-list">
                        @foreach ($upcoming as $cel)
                            @php
                                $targetDate = $cel->event_date ?? $cel->start_date;
                                $carbon     = $targetDate ? \Carbon\Carbon::parse($targetDate) : null;
                                $daysAway   = $carbon ? max(0, (int) $carbon->startOfDay()->diffInDays(now()->startOfDay(), false)) : null;
                                $utypes = [
                                    'birthday'    => ['icon' => 'mdi-cake-variant', 'label' => 'Birthday'],
                                    'wedding'     => ['icon' => 'mdi-ring',          'label' => 'Wedding'],
                                    'graduation'  => ['icon' => 'mdi-school',        'label' => 'Graduation'],
                                    'anniversary' => ['icon' => 'mdi-heart',         'label' => 'Anniversary'],
                                    'memorial'    => ['icon' => 'mdi-dove',          'label' => 'Memorial'],
                                    'other'       => ['icon' => 'mdi-party-popper',  'label' => 'Celebration'],
                                ];
                                $ut = $utypes[$cel->celebration_type] ?? $utypes['other'];
                            @endphp
                            <div class="upcoming-item">
                                @if ($carbon)
                                    <div class="date-box">
                                        <span class="date-day">{{ $carbon->format('j') }}</span>
                                        <span class="date-month">{{ $carbon->format('M') }}</span>
                                    </div>
                                @else
                                    <div class="date-box" style="background:var(--surface-2)">
                                        <span class="date-day">?</span>
                                        <span class="date-month">TBD</span>
                                    </div>
                                @endif
                                <div>
                                    <p class="upcoming-info-title">{{ $cel->title }}</p>
                                    <p class="upcoming-info-meta">
                                        <i class="mdi {{ $ut['icon'] }}" style="margin-right:0.2rem"></i>{{ $ut['label'] }}
                                    </p>
                                </div>
                                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.6rem">
                                    @if ($daysAway !== null)
                                        <span class="upcoming-countdown">
                                            @if ($daysAway === 0) Today!
                                            @elseif ($daysAway === 1) Tomorrow
                                            @else In {{ $daysAway }} days
                                            @endif
                                        </span>
                                    @endif
                                    <a href="{{ route('celebrations.show', $cel->slug) }}"
                                       class="action-btn" style="white-space:nowrap"
                                       target="_blank">View <i class="mdi mdi-arrow-right"></i></a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
