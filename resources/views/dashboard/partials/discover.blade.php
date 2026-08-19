                <div class="page-head">
                    <div>
                        <h1>Discover</h1>
                        <p class="sub">
                            Public celebration pages other people have shared. Send a wish, send a
                            gift, or just borrow an idea for your own.
                        </p>
                    </div>
                </div>

                <div class="tab-panel-header">
                    <div>
                        <h2 class="panel-title">Happening now</h2>
                        <p class="panel-subtitle">
                            {{ $discover->count() }} public celebration{{ $discover->count() === 1 ? '' : 's' }}
                        </p>
                    </div>
                </div>

                @if ($discover->isEmpty())
                    <div class="empty-state">
                        <i class="mdi mdi-compass-off-outline empty-icon"></i>
                        <h3>Nothing to discover yet</h3>
                        <p>When people share public celebration pages they'll appear here.</p>
                        <button class="btn-empty" x-on:click="$dispatch('open-modal', 'create-event')">
                            <i class="mdi mdi-plus"></i> Create a public event
                        </button>
                    </div>
                @else
                    <div class="discover-grid">
                        @foreach ($discover as $cel)
                            @php
                                $dtypes = [
                                    'birthday'    => ['icon' => 'mdi-cake-variant', 'label' => 'Birthday'],
                                    'wedding'     => ['icon' => 'mdi-ring',          'label' => 'Wedding'],
                                    'graduation'  => ['icon' => 'mdi-school',        'label' => 'Graduation'],
                                    'anniversary' => ['icon' => 'mdi-heart',         'label' => 'Anniversary'],
                                    'memorial'    => ['icon' => 'mdi-dove',          'label' => 'Memorial'],
                                    'other'       => ['icon' => 'mdi-party-popper',  'label' => 'Celebration'],
                                ];
                                $dt = $dtypes[$cel->celebration_type] ?? $dtypes['other'];
                                $celDate = $cel->event_date ?? $cel->start_date;
                            @endphp
                            <a href="{{ route('celebrations.show', $cel->slug) }}" class="discover-card" target="_blank">
                                <div class="discover-cover">
                                    @if ($thumb = ($cel->cover_photos[0] ?? null))
                                        <img src="{{ asset('storage/' . $thumb) }}" alt="{{ $cel->title }}">
                                    @else
                                        <div class="discover-cover-grad"></div>
                                    @endif
                                    <span class="discover-type-badge">
                                        <i class="mdi {{ $dt['icon'] }}"></i> {{ $dt['label'] }}
                                    </span>
                                </div>
                                <div class="discover-body">
                                    <h3 class="discover-title">{{ $cel->title }}</h3>
                                    <p class="discover-meta">
                                        @if ($celDate)
                                            <i class="mdi mdi-calendar-outline"></i>
                                            {{ \Carbon\Carbon::parse($celDate)->format('M j, Y') }}
                                        @endif
                                    </p>
                                    <span class="discover-link">
                                        <i class="mdi mdi-party-popper"></i> Join the celebration
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
