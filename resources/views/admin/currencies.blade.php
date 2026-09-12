@extends('admin.layout')

@section('title', 'Currencies')
@section('topbar-title', 'Currencies')

@section('content')

    @if (session('success'))
        <div class="alert alert-success"><i class="mdi mdi-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error"><i class="mdi mdi-alert"></i> {{ session('error') }}</div>
    @endif

    {{-- Adding a currency here is not cosmetic: every gift gains a price field
         in it, visitors from its countries get a wallet in it, and it joins the
         conversion table. --}}
    <div class="alert" style="background:var(--primary-50);border:1px solid var(--primary-100);color:var(--dark)">
        <i class="mdi mdi-information-outline" style="color:var(--accent)"></i>
        <div style="font-size:0.86rem;line-height:1.6">
            <strong>{{ $base }}</strong> is the base currency: every gift's default price is held in it, and
            it is what an unmapped country falls back to. Add a currency and each gift gets a price field
            for it on the gift form — leave that field blank and the default is converted instead.
        </div>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Currency</th>
                    <th>Countries</th>
                    <th>Fallback rate</th>
                    <th>In use</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($currencies as $currency)
                    <tr>
                        <form method="POST" action="{{ route('admin.currencies.update', $currency) }}" id="cur-{{ $currency->id }}">
                            @csrf @method('PUT')
                        </form>

                        <td style="white-space:nowrap">
                            <div style="display:flex;gap:0.5rem;align-items:center">
                                <input form="cur-{{ $currency->id }}" name="code" value="{{ $currency->code }}"
                                       class="filter-input" style="width:74px;text-transform:uppercase"
                                       @readonly($currency->isBase())>
                                <input form="cur-{{ $currency->id }}" name="symbol" value="{{ $currency->symbol }}"
                                       class="filter-input" style="width:60px" aria-label="Symbol">
                            </div>
                            <input form="cur-{{ $currency->id }}" name="name" value="{{ $currency->name }}"
                                   class="filter-input" style="width:100%;margin-top:0.4rem" aria-label="Name">
                            <input form="cur-{{ $currency->id }}" type="hidden" name="decimals" value="{{ $currency->decimals }}">
                            <input form="cur-{{ $currency->id }}" type="hidden" name="sort_order" value="{{ $currency->sort_order }}">
                        </td>

                        <td style="max-width:260px">
                            <input form="cur-{{ $currency->id }}" name="countries"
                                   value="{{ implode(', ', $currency->countries ?? []) }}"
                                   class="filter-input" style="width:100%"
                                   placeholder="ng, nigeria">
                            <p style="font-size:0.73rem;color:var(--muted);margin-top:0.3rem">
                                Codes or names, comma separated. Signups from these get this currency.
                            </p>
                        </td>

                        <td>
                            <input form="cur-{{ $currency->id }}" name="fallback_rate" type="number" step="0.000001"
                                   value="{{ (float) $currency->fallback_rate }}"
                                   class="filter-input" style="width:130px"
                                   @readonly($currency->isBase())>
                            <p style="font-size:0.73rem;color:var(--muted);margin-top:0.3rem">
                                Per 1 {{ $base }}, when the live rate is unreachable.
                            </p>
                        </td>

                        <td style="font-size:0.8rem;color:var(--muted);white-space:nowrap">
                            {{ $currency->usersCount() }} account{{ $currency->usersCount() === 1 ? '' : 's' }}<br>
                            {{ $currency->giftPricesCount() }} gift price{{ $currency->giftPricesCount() === 1 ? '' : 's' }}
                        </td>

                        <td>
                            @if ($currency->isBase())
                                {{-- No checkbox to untick, so the row still has
                                     to say "active" when it is saved. --}}
                                <input form="cur-{{ $currency->id }}" type="hidden" name="is_active" value="1">
                                <span class="badge badge-blue">Base</span>
                            @else
                                <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.8rem;cursor:pointer">
                                    <input form="cur-{{ $currency->id }}" type="checkbox" name="is_active" value="1"
                                           @checked($currency->is_active)>
                                    Active
                                </label>
                            @endif
                        </td>

                        <td style="white-space:nowrap">
                            <button form="cur-{{ $currency->id }}" type="submit" class="btn-filter"
                                    style="padding:0.4rem 0.7rem;font-size:0.78rem">
                                <i class="mdi mdi-content-save"></i>
                            </button>

                            @unless ($currency->isBase())
                                <form method="POST" action="{{ route('admin.currencies.destroy', $currency) }}" style="display:inline"
                                      onsubmit="return confirm('Remove {{ $currency->code }}? If any account or gift price uses it, it is switched off rather than deleted.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-filter"
                                            style="padding:0.4rem 0.7rem;font-size:0.78rem;background:#fee2e2;color:#991b1b;border:none">
                                        <i class="mdi mdi-trash-can-outline"></i>
                                    </button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── Add one ─────────────────────────────────────────────────── --}}
    <div class="table-card" style="padding:1.75rem">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:1.25rem">Add a currency</h3>

        <form method="POST" action="{{ route('admin.currencies.store') }}">
            @csrf

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem">
                <div>
                    <label class="filter-label" for="new_code">Code</label>
                    <input id="new_code" name="code" class="filter-input" style="width:100%;text-transform:uppercase"
                           placeholder="GHS" maxlength="3" required>
                </div>
                <div>
                    <label class="filter-label" for="new_name">Name</label>
                    <input id="new_name" name="name" class="filter-input" style="width:100%" placeholder="Ghanaian Cedi" required>
                </div>
                <div>
                    <label class="filter-label" for="new_symbol">Symbol</label>
                    <input id="new_symbol" name="symbol" class="filter-input" style="width:100%" placeholder="₵" required>
                </div>
                <div>
                    <label class="filter-label" for="new_decimals">Decimals</label>
                    <input id="new_decimals" name="decimals" type="number" class="filter-input" style="width:100%"
                           value="2" min="0" max="4" required>
                </div>
                <div>
                    <label class="filter-label" for="new_rate">Fallback rate per 1 {{ $base }}</label>
                    <input id="new_rate" name="fallback_rate" type="number" step="0.000001" class="filter-input"
                           style="width:100%" placeholder="15.5" required>
                </div>
                <div>
                    <label class="filter-label" for="new_countries">Countries</label>
                    <input id="new_countries" name="countries" class="filter-input" style="width:100%" placeholder="gh, ghana">
                </div>
                <div>
                    <label class="filter-label" for="new_sort">Position</label>
                    <input id="new_sort" name="sort_order" type="number" class="filter-input" style="width:100%"
                           value="{{ ($currencies->max('sort_order') ?? 0) + 10 }}" min="0" required>
                </div>
            </div>

            @if ($errors->any())
                <ul style="margin-top:1rem;color:#991b1b;font-size:0.8rem">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            @endif

            <label style="display:flex;align-items:center;gap:0.5rem;margin-top:1.1rem;font-size:0.85rem;cursor:pointer">
                <input type="checkbox" name="is_active" value="1" checked>
                Available immediately
            </label>

            <button type="submit" class="btn-filter" style="margin-top:1.25rem">
                <i class="mdi mdi-plus"></i> Add currency
            </button>
        </form>
    </div>

@endsection
