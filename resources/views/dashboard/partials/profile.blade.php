                <div class="page-head">
                    <div>
                        <h1>Profile</h1>
                        <p class="sub">How you appear when you wish someone well, and how we reach you.</p>
                    </div>
                </div>

                @if (session('status') === 'profile-updated')
                    <div class="flash-ok"><i class="mdi mdi-check-circle"></i> Saved.</div>
                @endif
                @if (session('status') === 'password-updated')
                    <div class="flash-ok"><i class="mdi mdi-check-circle"></i> Your password has been changed.</div>
                @endif

                {{-- ══ DETAILS ══════════════════════════════════════════
                     The identity band is the top of this form, not a picture
                     of it: the avatar is the file control, and the name reads
                     back what is being typed below. --}}
                <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data"
                      x-data="{
                        first:   @js(old('first_name', $user->first_name)),
                        last:    @js(old('last_name', $user->last_name)),
                        email:   @js(old('email', $user->email)),
                        preview: '',
                        dropped: false,
                        pick(e) {
                            const file = e.target.files[0];
                            if (! file) return;
                            this.dropped = false;
                            this.preview = URL.createObjectURL(file);
                        },
                        drop() {
                            this.preview = '';
                            this.dropped = true;
                            this.$refs.photo.value = '';
                        },
                        get fullName() {
                            return [this.first, this.last].map(s => (s || '').trim()).filter(Boolean).join(' ');
                        },
                        get initials() {
                            const i = ((this.first || '')[0] || '') + ((this.last || '')[0] || '');
                            return (i || (this.email || 'U')[0] || 'U').toUpperCase();
                        },
                      }">
                    @csrf
                    @method('patch')

                    <div class="pf-identity">
                        <div class="pf-avatar-wrap">
                            <div class="pf-avatar">
                                <template x-if="preview">
                                    <img :src="preview" alt="">
                                </template>

                                @if ($user->profile_photo_url)
                                    <img x-show="!preview && !dropped" src="{{ $user->profile_photo_url }}"
                                         alt="Your profile photo">
                                    <span x-show="dropped" x-cloak x-text="initials"></span>
                                @else
                                    <span x-show="!preview" x-text="initials"></span>
                                @endif
                            </div>

                            <input type="file" name="photo" id="photo" x-ref="photo" accept="image/*"
                                   @change="pick($event)"
                                   style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none">
                            <label for="photo" class="pf-avatar-btn" title="Change your photo">
                                <i class="mdi mdi-camera-outline"></i>
                                <span class="sr-only">Change your photo</span>
                            </label>
                        </div>

                        <div class="pf-id-main">
                            <p class="pf-name" x-text="fullName || 'Your name'"></p>
                            <p class="pf-email" x-text="email"></p>

                            <div class="pf-chips">
                                <span class="pf-chip">
                                    <i class="mdi mdi-cash-multiple"></i> {{ $user->currency }}
                                </span>
                                <span class="pf-chip">
                                    <i class="mdi mdi-calendar-check-outline"></i>
                                    Joined {{ $user->created_at?->format('M Y') }}
                                </span>
                                @if ($user->account_type)
                                    <span class="pf-chip">
                                        <i class="mdi mdi-account-outline"></i> {{ ucfirst($user->account_type) }}
                                    </span>
                                @endif
                                @if (($stats['total'] ?? 0) > 0)
                                    <span class="pf-chip is-good">
                                        <i class="mdi mdi-party-popper"></i>
                                        {{ $stats['total'] }} {{ Str::plural('celebration', $stats['total']) }}
                                    </span>
                                @endif
                            </div>

                            {{-- Only worth offering when there is something to remove. --}}
                            @if ($user->profile_photo_url)
                                <p class="pf-drop" x-show="!preview">
                                    <span x-show="!dropped">JPEG, PNG or WebP, up to 4MB. ·
                                        <button type="button" @click="drop()">Remove photo</button>
                                    </span>
                                    <span x-show="dropped" x-cloak>Photo will be removed when you save.</span>
                                </p>
                            @else
                                <p class="pf-drop" x-show="!preview">JPEG, PNG or WebP, up to 4MB.</p>
                            @endif
                            <p class="pf-drop" x-show="preview" x-cloak>New photo ready — save to keep it.</p>

                            <input type="hidden" name="remove_photo" :value="dropped ? 1 : 0">
                        </div>
                    </div>

                    @error('photo')<p class="m-error" style="margin-top:0.6rem">{{ $message }}</p>@enderror

                    {{-- ── Your details ──────────────────────────────── --}}
                    <div class="pf-section">
                        <div class="tab-panel-header">
                            <div>
                                <h2 class="panel-title">Your details</h2>
                                <p class="panel-subtitle">This is the name that appears on gifts you send and wishes you leave</p>
                            </div>
                        </div>

                        <div class="form-card" style="max-width:640px">
                            <div class="pf-grid">
                                <div class="m-field" style="margin-top:0">
                                    <label class="m-label" for="first_name">First name</label>
                                    {{-- value= as well as x-model: Alpine seeds
                                         the field from the same data on init, and
                                         if its script never runs the form still
                                         holds the real name instead of blanking
                                         it on save. --}}
                                    <input id="first_name" name="first_name" type="text" class="m-input"
                                           value="{{ old('first_name', $user->first_name) }}"
                                           x-model="first" required autocomplete="given-name">
                                    @error('first_name')<p class="m-error">{{ $message }}</p>@enderror
                                </div>

                                <div class="m-field" style="margin-top:0">
                                    <label class="m-label" for="last_name">Last name</label>
                                    <input id="last_name" name="last_name" type="text" class="m-input"
                                           value="{{ old('last_name', $user->last_name) }}"
                                           x-model="last" required autocomplete="family-name">
                                    @error('last_name')<p class="m-error">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="m-field">
                                <label class="m-label" for="email">Email</label>
                                <input id="email" name="email" type="email" class="m-input"
                                       value="{{ old('email', $user->email) }}"
                                       x-model="email" required autocomplete="email">
                                <p class="m-hint">Where gift alerts and password resets are sent.</p>
                                @error('email')<p class="m-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- ── Email preference ──────────────────────────────
                         Lives inside this form so the page has one save, not
                         a button per row. --}}
                    <div class="pf-section">
                        <div class="tab-panel-header">
                            <div>
                                <h2 class="panel-title">Email</h2>
                                <p class="panel-subtitle">What we send you, and what we leave alone</p>
                            </div>
                        </div>

                        <div style="max-width:640px">
                            <div class="pf-toggle-row">
                                <div class="pf-toggle-copy">
                                    <p class="pf-toggle-title">Celebration emails</p>
                                    <p class="pf-toggle-sub">
                                        Reminders before a celebration, and the round-up of what came in afterwards.
                                        Gift receipts and password resets are sent either way.
                                    </p>
                                </div>
                                <label class="pf-switch">
                                    <input type="checkbox" name="email_notifications_enabled" value="1"
                                           @checked(old('email_notifications_enabled', $user->email_notifications_enabled ?? true))>
                                    <i></i>
                                    <span class="sr-only">Celebration emails</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="pf-actions">
                        <button type="submit" class="btn-create-lg">
                            <i class="mdi mdi-content-save-outline"></i> Save changes
                        </button>
                        <span class="note">Your currency is set when you join and cannot be changed here.</span>
                    </div>
                </form>

                {{-- ══ PASSWORD ═════════════════════════════════════════ --}}
                <div class="pf-section">
                    <div class="tab-panel-header">
                        <div>
                            <h2 class="panel-title">Password</h2>
                            <p class="panel-subtitle">Use a long one you do not use anywhere else</p>
                        </div>
                    </div>

                    <div class="form-card" style="max-width:640px">
                        <form method="post" action="{{ route('password.update') }}">
                            @csrf
                            @method('put')

                            <div class="m-field" style="margin-top:0">
                                <label class="m-label" for="current_password">Current password</label>
                                <input id="current_password" name="current_password" type="password" class="m-input"
                                       autocomplete="current-password">
                                @error('current_password', 'updatePassword')<p class="m-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="pf-grid" style="margin-top:1.1rem">
                                <div class="m-field" style="margin-top:0">
                                    <label class="m-label" for="password">New password</label>
                                    <input id="password" name="password" type="password" class="m-input"
                                           autocomplete="new-password">
                                    @error('password', 'updatePassword')<p class="m-error">{{ $message }}</p>@enderror
                                </div>

                                <div class="m-field" style="margin-top:0">
                                    <label class="m-label" for="password_confirmation">Confirm</label>
                                    <input id="password_confirmation" name="password_confirmation" type="password"
                                           class="m-input" autocomplete="new-password">
                                    @error('password_confirmation', 'updatePassword')<p class="m-error">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <button type="submit" class="btn-create-lg" style="margin-top:1.75rem">
                                <i class="mdi mdi-lock-reset"></i> Change password
                            </button>
                        </form>
                    </div>
                </div>
