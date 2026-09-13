@extends('admin.layout')

@section('title', 'Settings')
@section('topbar-title', 'Settings')

@section('topbar-actions')
    {{-- Only groups that talk to an outside service have anything to test. --}}
    @if (in_array($group, ['payments', 'mail', 'location'], true))
        <form method="POST" action="{{ route('admin.settings.test', $group) }}">
            @csrf
            <button type="submit" class="btn-filter">
                <i class="mdi mdi-connection"></i> Test these credentials
            </button>
        </form>
    @endif
@endsection

@section('content')

    @if (session('success'))
        <div class="alert alert-success"><i class="mdi mdi-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error"><i class="mdi mdi-alert"></i> {{ session('error') }}</div>
    @endif

    {{-- Testing the credentials proves the mailbox works. It does not prove
         mail works: everything the site actually sends is queued, so with no
         worker running the Test button reports success while every receipt
         sits in the jobs table untouched. --}}
    @if ($queue && ! $queue['healthy'])
        <div class="alert alert-error" style="align-items:flex-start">
            <i class="mdi mdi-timer-sand-empty"></i>
            <div>
                <strong>{{ $queue['stale'] }} {{ Str::plural('email', $queue['stale']) }} waiting and not going out.</strong>
                Everything the site sends is written to the outbox and posted by the scheduler, so mail only
                moves while <code style="background:rgba(0,0,0,.06);padding:.1rem .3rem">php artisan schedule:run</code>
                is on cron. The oldest has been waiting
                {{ \Illuminate\Support\Carbon::parse($queue['oldest'])->diffForHumans(null, true) }}.
                <br>
                <a href="{{ route('admin.outbox') }}">Open the outbox</a> to read them, or send now with
                <code style="background:rgba(0,0,0,.06);padding:.1rem .3rem">php artisan emails:send</code>.
                @if ($queue['failed'] > 0)
                    <br>{{ $queue['failed'] }} {{ Str::plural('email', $queue['failed']) }} gave up after retrying —
                    the outbox shows what the server said.
                @endif
            </div>
        </div>
    @elseif ($queue && $queue['driver'] === 'database' && $queue['pending'] > 0)
        <div class="alert alert-success">
            <i class="mdi mdi-check-circle"></i>
            The outbox is moving — {{ $queue['pending'] }} {{ Str::plural('email', $queue['pending']) }} waiting to go.
        </div>
    @endif

    {{-- Values set here are encrypted, override .env, and take effect on the
         next request — no deploy, no config:cache. --}}
    <div class="filters-bar" style="margin-bottom:1.5rem">
        @foreach ($groups as $name)
            <a href="{{ route('admin.settings', $name) }}" class="btn-filter"
               style="text-decoration:none;{{ $group === $name ? '' : 'background:var(--off);color:var(--dark);border:1.5px solid var(--border)' }}">
                {{ ucfirst($name) }}
            </a>
        @endforeach

        <a href="{{ route('admin.currencies') }}" class="btn-filter"
           style="text-decoration:none;background:var(--off);color:var(--dark);border:1.5px solid var(--border)">
            Currencies
        </a>
    </div>

    <form method="POST" action="{{ route('admin.settings.update', $group) }}">
        @csrf @method('PUT')

        <div class="table-card" style="padding:1.75rem">
            @foreach ($fields as $key => $field)
                <div style="padding:1.1rem 0;{{ ! $loop->last ? 'border-bottom:1px solid var(--border)' : '' }}">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:0.45rem">
                        <label class="filter-label" for="{{ $key }}" style="margin:0">{{ $field['label'] }}</label>

                        {{-- Where the value in force actually comes from —
                             the question you always end up asking at 2am. --}}
                        <span class="badge {{ ['panel' => 'badge-green', 'env' => 'badge-blue', 'unset' => 'badge-yellow'][$field['source']] }}"
                              style="font-size:0.7rem">
                            @switch($field['source'])
                                @case('panel') Set here @break
                                @case('env')   From .env @break
                                @default       Not set
                            @endswitch
                        </span>
                    </div>

                    @if (($field['type'] ?? null) === 'toggle')
                        {{-- The hidden 0 is what makes "off" possible: an unticked
                             checkbox sends nothing at all, and nothing reads as
                             "leave it as it was". --}}
                        @php $on = filter_var($field['display'] === '' ? ($field['default'] ?? true) : $field['display'], FILTER_VALIDATE_BOOLEAN); @endphp
                        <input type="hidden" name="settings[{{ $key }}]" value="0">
                        <label style="display:inline-flex;align-items:center;gap:0.6rem;cursor:pointer;font-size:0.88rem;font-weight:600">
                            <input id="{{ $key }}" type="checkbox" name="settings[{{ $key }}]" value="1" @checked($on)
                                   style="width:1.1rem;height:1.1rem;accent-color:var(--accent)">
                            <span>{{ $on ? ($field['on_label'] ?? 'Active — taking checkouts') : ($field['off_label'] ?? 'Inactive — checkouts paused') }}</span>
                        </label>
                    @else
                    <input id="{{ $key }}"
                           type="{{ ($field['secret'] ?? false) ? 'password' : 'text' }}"
                           name="settings[{{ $key }}]"
                           class="filter-input" style="width:100%"
                           autocomplete="off"
                           @if ($field['secret'] ?? false)
                               placeholder="{{ $field['display'] !== '' ? $field['display'] . '  —  leave blank to keep' : 'Not set' }}"
                           @else
                               value="{{ old("settings.{$key}", $field['display']) }}"
                           @endif
                    >
                    @endif

                    @if (! empty($field['help']))
                        <p style="font-size:0.78rem;color:var(--muted);margin-top:0.4rem">{{ $field['help'] }}</p>
                    @endif

                    @if (($field['secret'] ?? false) && $field['source'] === 'panel')
                        {{-- An empty secret box means "leave it alone", so
                             removing one has to be deliberate. --}}
                        <label style="display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem;font-size:0.78rem;cursor:pointer">
                            <input type="checkbox" name="clear[{{ $key }}]" value="1">
                            Clear this and fall back to .env
                        </label>
                    @endif
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn-filter" style="margin-top:1.25rem">
            <i class="mdi mdi-content-save"></i> Save settings
        </button>
    </form>

@endsection
