@extends('admin.layout')

@section('title', $admin->exists ? 'Edit admin' : 'Add admin')
@section('topbar-title', $admin->exists ? "Edit {$admin->name}" : 'Add an admin')

@section('content')

    @if (session('error'))
        <div class="alert alert-error"><i class="mdi mdi-alert"></i> {{ session('error') }}</div>
    @endif

    <form method="POST"
          action="{{ $admin->exists ? route('admin.staff.update', $admin) : route('admin.staff.store') }}">
        @csrf
        @if ($admin->exists) @method('PUT') @endif

        <div class="table-card" style="padding:1.75rem">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:1.25rem">Account</h3>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem">
                <div>
                    <label class="filter-label" for="name">Name</label>
                    <input id="name" type="text" name="name" class="filter-input" style="width:100%"
                           value="{{ old('name', $admin->name) }}" required>
                    @error('name')<p style="color:#991b1b;font-size:0.78rem;margin-top:0.3rem">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="filter-label" for="email">Email</label>
                    <input id="email" type="email" name="email" class="filter-input" style="width:100%"
                           value="{{ old('email', $admin->email) }}" required>
                    @error('email')<p style="color:#991b1b;font-size:0.78rem;margin-top:0.3rem">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="filter-label" for="password">
                        Password @if ($admin->exists)<span style="color:var(--muted);font-weight:400"> — leave blank to keep</span>@endif
                    </label>
                    <input id="password" type="password" name="password" class="filter-input" style="width:100%"
                           autocomplete="new-password" @required(! $admin->exists)>
                    @error('password')<p style="color:#991b1b;font-size:0.78rem;margin-top:0.3rem">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="filter-label" for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation"
                           class="filter-input" style="width:100%" autocomplete="new-password"
                           @required(! $admin->exists)>
                </div>

                <div>
                    <label class="filter-label" for="status">Status</label>
                    <select id="status" name="status" class="filter-select" style="width:100%">
                        <option value="active"    @selected(old('status', $admin->status) === 'active')>Active</option>
                        <option value="suspended" @selected(old('status', $admin->status) === 'suspended')>Suspended</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-card" style="padding:1.75rem">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:0.4rem">Access level</h3>
            <p style="font-size:0.85rem;color:var(--muted);margin-bottom:1.5rem">
                Tick only what this person needs. Anything not ticked is not just hidden — the
                route refuses it.
            </p>

            {{-- Super admin overrides the lot, so the checkboxes below are
                 disabled while it is on rather than quietly ignored. --}}
            <label style="display:flex;gap:0.75rem;align-items:flex-start;padding:1rem;border:1.5px solid var(--border);border-radius:8px;margin-bottom:1.5rem;cursor:pointer">
                <input type="checkbox" name="is_super" value="1" id="is_super"
                       @checked(old('is_super', $admin->is_super))
                       onchange="document.querySelectorAll('.perm-check').forEach(c => c.disabled = this.checked)">
                <span>
                    <strong>Super admin</strong>
                    <span style="display:block;font-size:0.82rem;color:var(--muted);margin-top:0.2rem">
                        Everything, including managing other admins and any capability added later.
                        Give this to as few people as possible.
                    </span>
                </span>
            </label>

            @foreach ($permissions as $group => $items)
                <h4 style="font-size:0.78rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:var(--muted);margin:1.5rem 0 0.75rem">
                    {{ $group }}
                </h4>

                <div style="display:grid;gap:0.6rem">
                    @foreach ($items as $key => $meta)
                        <label style="display:flex;gap:0.75rem;align-items:flex-start;padding:0.85rem 1rem;border:1px solid var(--border);border-radius:8px;cursor:pointer">
                            <input type="checkbox" class="perm-check" name="permissions[]" value="{{ $key }}"
                                   @checked(in_array($key, old('permissions', $granted), true))
                                   @disabled(old('is_super', $admin->is_super))>
                            <span>
                                <strong style="font-size:0.9rem">{{ $meta['label'] }}</strong>
                                <span style="display:block;font-size:0.8rem;color:var(--muted);margin-top:0.2rem">
                                    {{ $meta['note'] }}
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div style="display:flex;gap:0.75rem">
            <button type="submit" class="btn-filter">
                <i class="mdi mdi-content-save"></i>
                {{ $admin->exists ? 'Save changes' : 'Create admin' }}
            </button>
            <a href="{{ route('admin.staff') }}" class="btn-filter"
               style="background:var(--off);color:var(--dark);border:1.5px solid var(--border);text-decoration:none">
                Cancel
            </a>
        </div>
    </form>

@endsection
