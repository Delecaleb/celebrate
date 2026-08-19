                {{-- The overview page — the only one that greets you. --}}
                <div class="page-head">
                    <div>
                        <div class="greeting-tag">
                            <i class="mdi mdi-weather-sunny" style="font-size:1rem"></i>
                            {{ $greeting }}
                        </div>
                        <h1>Welcome back, {{ auth()->user()->first_name ?? auth()->user()->name }}</h1>
                        <p class="sub">Here's what's happening with your celebrations today.</p>
                    </div>
                </div>

                <div class="stats-bar">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="mdi mdi-calendar-star"></i></div>
                        <p class="stat-label">Total events</p>
                        <p class="stat-value">{{ $stats['total'] }}</p>
                        <p class="stat-sub">{{ $stats['published'] }} live</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="mdi mdi-eye-outline"></i></div>
                        <p class="stat-label">Page views</p>
                        <p class="stat-value">{{ number_format($stats['views']) }}</p>
                        <p class="stat-sub">across all pages</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="mdi mdi-message-outline"></i></div>
                        <p class="stat-label">Messages</p>
                        <p class="stat-value">{{ number_format($stats['messages']) }}</p>
                        <p class="stat-sub">wishes received</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="mdi mdi-wallet-outline"></i></div>
                        <p class="stat-label">Wallet balance</p>
                        <p class="stat-value">{{ $currencySymbol }}{{ number_format($localDisplay, 2) }}</p>
                        <p class="stat-sub">
                            @if ($userCurrency !== 'USD')
                                Global ${{ number_format($globalDisplay, 2) }}
                            @else
                                available balance
                            @endif
                        </p>
                    </div>
                </div>

                <div class="tab-panel-header">
                    <div>
                        <h2 class="panel-title">My events</h2>
                        <p class="panel-subtitle">Every celebration page you've created</p>
                    </div>
                </div>

                <div class="events-grid">

                    <button class="event-add-card" x-on:click="$dispatch('open-modal', 'create-event')">
                        <i class="mdi mdi-plus-circle-outline add-icon"></i>
                        <span class="add-label">New celebration</span>
                    </button>

                    @forelse ($celebrations as $celebration)
                        @php
                            $types = [
                                'birthday'    => ['icon' => 'mdi-cake-variant',  'label' => 'Birthday'],
                                'wedding'     => ['icon' => 'mdi-ring',          'label' => 'Wedding'],
                                'graduation'  => ['icon' => 'mdi-school',        'label' => 'Graduation'],
                                'anniversary' => ['icon' => 'mdi-heart',         'label' => 'Anniversary'],
                                'memorial'    => ['icon' => 'mdi-dove',          'label' => 'Memorial'],
                                'other'       => ['icon' => 'mdi-party-popper',  'label' => 'Celebration'],
                            ];
                            $t = $types[$celebration->celebration_type] ?? $types['other'];

                            [$badgeClass, $badgeLabel] = match($celebration->status) {
                                'published' => ['badge-live',   'Live'],
                                'closed'    => ['badge-closed', 'Closed'],
                                default     => ['badge-draft',  'Draft'],
                            };

                            $displayDate = null;
                            if ($celebration->event_date)
                                $displayDate = \Carbon\Carbon::parse($celebration->event_date)->format('M j, Y');
                            elseif ($celebration->start_date)
                                $displayDate = \Carbon\Carbon::parse($celebration->start_date)->format('M j, Y');
                        @endphp

                        <div class="event-card">
                            <div class="event-cover">
                                {{-- cover_photo holds a JSON array once there is
                                     more than one photo — go through the accessor --}}
                                @if ($thumb = ($celebration->cover_photos[0] ?? null))
                                    <img src="{{ asset('storage/' . $thumb) }}" alt="{{ $celebration->title }}">
                                @else
                                    <div class="event-cover-gradient"></div>
                                @endif
                                <span class="status-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                <div class="event-type-icon"><i class="mdi {{ $t['icon'] }}"></i></div>
                            </div>

                            <div class="event-body">
                                <p class="event-type-tag">{{ $t['label'] }}</p>
                                <h3 class="event-title">{{ $celebration->title }}</h3>
                                @if ($displayDate)
                                    <p class="event-date">
                                        <i class="mdi mdi-calendar-outline"></i> {{ $displayDate }}
                                    </p>
                                @endif
                                <div class="event-stats">
                                    <div class="event-stat">
                                        <i class="mdi mdi-eye-outline"></i>
                                        <strong>{{ number_format($celebration->view_count) }}</strong>
                                        <span>views</span>
                                    </div>
                                    <div class="event-stat">
                                        <i class="mdi mdi-message-outline"></i>
                                        <strong>{{ number_format($celebration->comment_count) }}</strong>
                                        <span>msgs</span>
                                    </div>
                                    @if (($celebration->gifts_count ?? 0) > 0)
                                        <div class="event-stat">
                                            <i class="mdi mdi-gift-outline"></i>
                                            <strong>{{ $celebration->gifts_count }}</strong>
                                            <span>gifts</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="event-actions">
                                    <a href="{{ route('celebrations.show', $celebration->slug) }}"
                                       class="action-btn" target="_blank">
                                        <i class="mdi mdi-eye"></i>View
                                    </a>
                                    {{-- Editing happens in the Settings tab on the page itself --}}
                                    <a href="{{ route('celebrations.show', $celebration->slug) }}"
                                       class="action-btn">
                                        <i class="mdi mdi-pencil"></i>Edit
                                    </a>
                                    <form method="POST"
                                          action="{{ route('celebrant.destroy', $celebration->slug) }}"
                                          x-data
                                          @submit.prevent="confirm('Delete &quot;{{ addslashes($celebration->title) }}&quot;? This cannot be undone.') && $el.submit()">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-delete" title="Delete">
                                            <i class="mdi mdi-trash-can-outline"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <i class="mdi mdi-calendar-star empty-icon"></i>
                            <h3>No celebrations yet</h3>
                            <p>Your event pages will appear here. Create your first one — it only takes a minute and it's completely free.</p>
                            <button class="btn-empty" x-on:click="$dispatch('open-modal', 'create-event')">
                                <i class="mdi mdi-plus"></i> Create your first celebration
                            </button>
                        </div>
                    @endforelse

                </div>
