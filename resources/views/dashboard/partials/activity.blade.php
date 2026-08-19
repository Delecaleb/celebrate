                <div class="page-head">
                    <div>
                        <h1>Activity</h1>
                        <p class="sub">Wishes, gifts and page milestones as they happen.</p>
                    </div>
                </div>

                <div class="stats-bar cols-2">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="mdi {{ $unreadCount > 0 ? 'mdi-bell' : 'mdi-bell-check-outline' }}"></i></div>
                        <p class="stat-label">Unread</p>
                        <p class="stat-value">{{ $unreadCount }}</p>
                        <p class="stat-sub">{{ $unreadCount > 0 ? 'waiting for you' : "you're all caught up" }}</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="mdi mdi-history"></i></div>
                        <p class="stat-label">Recent</p>
                        <p class="stat-value">{{ $notifications->count() }}</p>
                        <p class="stat-sub">latest notifications</p>
                    </div>
                </div>

                <div class="tab-panel-header">
                    <div>
                        <h2 class="panel-title">Everything that happened</h2>
                        <p class="panel-subtitle">Newest first</p>
                    </div>
                    @if ($unreadCount > 0)
                        <form method="POST" action="{{ route('dashboard.activity.read') }}">
                            @csrf
                            <button type="submit" class="btn-create-lg">
                                <i class="mdi mdi-check-all"></i> Mark all as read
                            </button>
                        </form>
                    @endif
                </div>

                @if (session('success'))
                    <div class="flash-ok"><i class="mdi mdi-check-circle"></i> {{ session('success') }}</div>
                @endif

                @if ($notifications->isEmpty())
                    <div class="empty-state">
                        <i class="mdi mdi-bell-off-outline empty-icon"></i>
                        <h3>No notifications yet</h3>
                        <p>When guests view your page, send wishes, or give gifts you'll see it here.</p>
                    </div>
                @else
                    <div class="notif-list">
                        @foreach ($notifications as $notif)
                            @php
                                $nicons = [
                                    'gift'     => 'mdi-gift',
                                    'comment'  => 'mdi-message',
                                    'reaction' => 'mdi-heart',
                                    'view'     => 'mdi-eye',
                                    'payment'  => 'mdi-cash',
                                    'system'   => 'mdi-information',
                                ];
                                $nicon = $nicons[$notif->type] ?? 'mdi-bell';
                            @endphp
                            <div class="notif-item {{ $notif->is_read ? '' : 'unread' }}">
                                <div class="notif-indicator"><i class="mdi {{ $nicon }}"></i></div>
                                <div class="notif-content">
                                    <p class="notif-title">{{ $notif->title }}</p>
                                    @if ($notif->message)
                                        <p class="notif-msg">{{ $notif->message }}</p>
                                    @endif
                                    <p class="notif-time">{{ $notif->created_at->diffForHumans() }}</p>
                                </div>
                                @if (!$notif->is_read)
                                    <span class="notif-dot"></span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
