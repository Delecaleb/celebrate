@extends('admin.layout')

@section('title', 'Staff')
@section('topbar-title', 'Admin staff')

@section('topbar-actions')
    <a href="{{ route('admin.staff.create') }}" class="btn-filter" style="text-decoration:none">
        <i class="mdi mdi-account-plus"></i> Add admin
    </a>
@endsection

@section('content')

    {{-- Who can open the panel, and how much of it. A super admin holds every
         permission implicitly, including ones added after their account. --}}

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Admin</th>
                    <th>Access</th>
                    <th>Status</th>
                    <th>Last signed in</th>
                    <th>Added by</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($admins as $staff)
                    <tr>
                        <td>
                            <div class="user-chip">
                                <div class="chip-avatar">{{ strtoupper(substr($staff->name, 0, 1)) }}</div>
                                <div>
                                    <div style="font-weight:600">{{ $staff->name }}</div>
                                    <div style="font-size:0.75rem;color:var(--muted)">{{ $staff->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($staff->is_super)
                                <span class="badge badge-blue"><i class="mdi mdi-shield-crown"></i> Super admin</span>
                            @elseif ($staff->permissions->isEmpty())
                                <span class="badge badge-gray">Sign-in only</span>
                            @else
                                <div style="display:flex;flex-wrap:wrap;gap:0.3rem;max-width:340px">
                                    @foreach ($staff->permissions as $permission)
                                        <span class="badge badge-gray" style="font-size:0.72rem">
                                            {{ $permission->label() }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $staff->isActive() ? 'badge-green' : 'badge-red' }}">
                                {{ $staff->status }}
                            </span>
                        </td>
                        <td style="font-size:0.8rem;color:var(--muted);white-space:nowrap">
                            {{ $staff->last_login_at?->format('j M Y, H:i') ?? 'Never' }}
                            @if ($staff->last_login_ip)
                                <div style="font-size:0.72rem">{{ $staff->last_login_ip }}</div>
                            @endif
                        </td>
                        <td style="font-size:0.8rem;color:var(--muted)">
                            {{ $staff->creator?->name ?? 'Console' }}
                        </td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('admin.staff.edit', $staff) }}" class="btn-filter"
                               style="padding:0.4rem 0.7rem;font-size:0.78rem;text-decoration:none">
                                <i class="mdi mdi-pencil"></i> Edit
                            </a>

                            @if ($staff->id !== auth('admin')->id())
                                <form method="POST" action="{{ route('admin.staff.destroy', $staff) }}"
                                      style="display:inline"
                                      onsubmit="return confirm('Remove {{ $staff->email }}? They lose access immediately.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-filter"
                                            style="padding:0.4rem 0.7rem;font-size:0.78rem;background:#fee2e2;color:#991b1b;border:none">
                                        <i class="mdi mdi-trash-can-outline"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@endsection
