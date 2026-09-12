@extends('admin.layout')

@section('title', 'Settings')
@section('topbar-title', 'Settings')

@section('topbar-actions')
    <form method="POST" action="{{ route('admin.settings.test', $group) }}">
        @csrf
        <button type="submit" class="btn-filter">
            <i class="mdi mdi-connection"></i> Test these credentials
        </button>
    </form>
@endsection

@section('content')

    @if (session('success'))
        <div class="alert alert-success"><i class="mdi mdi-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error"><i class="mdi mdi-alert"></i> {{ session('error') }}</div>
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
