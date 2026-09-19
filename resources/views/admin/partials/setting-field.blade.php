{{--
    One setting: its label, where the value in force came from, and the control
    for changing it.

    Shared by the gateway tabs and by the plain single-list groups, so a field
    looks and behaves the same wherever it is drawn.

    Expects: $key, $field, and optionally $last (whether to draw the divider).
--}}
<div style="padding:1.1rem 0;{{ ($last ?? false) ? '' : 'border-bottom:1px solid var(--border)' }}">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:0.45rem">
        <label class="filter-label" for="{{ $key }}" style="margin:0">{{ $field['label'] }}</label>

        {{-- Where the value in force actually comes from — the question you
             always end up asking at 2am. --}}
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
        {{-- The hidden 0 is what makes "off" possible: an unticked checkbox
             sends nothing at all, and nothing reads as "leave it as it was". --}}
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
        {{-- An empty secret box means "leave it alone", so removing one has to
             be deliberate. --}}
        <label style="display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem;font-size:0.78rem;cursor:pointer">
            <input type="checkbox" name="clear[{{ $key }}]" value="1">
            Clear this and fall back to .env
        </label>
    @endif
</div>
