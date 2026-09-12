{{--
    Shown across the top of the customer app while an admin is signed in as
    somebody. It has to be impossible to miss — the whole risk of this feature
    is an admin forgetting whose account they are looking at.
--}}
@if (session()->has('impersonator_admin_id'))
    @php $viewing = auth()->user(); @endphp

    <div style="position:sticky;top:0;z-index:9999;background:#7f1d1d;color:#fff;
                display:flex;flex-wrap:wrap;align-items:center;gap:0.75rem;
                padding:0.7rem 1.25rem;font:600 0.85rem/1.4 system-ui,sans-serif">
        <i class="mdi mdi-eye-outline" style="font-size:1.1rem"></i>

        <span style="flex:1;min-width:220px">
            You are viewing <strong>{{ $viewing?->email }}</strong> as an admin.
            Payments, withdrawals and bank details are disabled.
        </span>

        <form method="POST" action="{{ route('impersonation.stop') }}">
            @csrf
            <button type="submit"
                    style="background:#fff;color:#7f1d1d;border:0;border-radius:999px;
                           padding:0.4rem 1rem;font:700 0.82rem system-ui,sans-serif;cursor:pointer">
                Stop viewing
            </button>
        </form>
    </div>
@endif
