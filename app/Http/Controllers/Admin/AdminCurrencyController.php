<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Currency;
use App\Support\SettingsRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The currencies the platform trades in.
 *
 * Adding one here is not cosmetic: it gives every gift a price field in that
 * currency, gives users from its countries a local wallet, and adds it to the
 * conversion table. Removing one is therefore the dangerous direction, and the
 * guards below are the point of this controller.
 */
class AdminCurrencyController extends Controller
{
    public function __construct(private SettingsRepository $settings) {}

    public function index()
    {
        return view('admin.currencies', [
            'currencies' => Currency::ordered()->get(),
            'base'       => strtoupper((string) config('currency.base')),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        // An unticked checkbox is absent from the request, so "missing" means
        // off — never on.
        $currency = Currency::create($data + ['is_active' => $request->boolean('is_active')]);

        $this->settings->flush();

        AdminAuditLog::record(
            'admin.currency.created',
            sprintf('Added %s (%s) at a fallback rate of %s', $currency->code, $currency->name, $currency->fallback_rate),
            $currency,
            $currency->code,
        );

        return back()->with('success', "{$currency->code} is now available. Every gift has a price field for it, and visitors from its countries get a wallet in it.");
    }

    public function update(Request $request, Currency $currency)
    {
        $data = $this->validated($request, $currency);

        // The base currency is what every stored price is denominated in and
        // what an unmapped country falls back to. It cannot be switched off.
        if ($currency->isBase() && ! $request->boolean('is_active')) {
            return back()->with('error', "{$currency->code} is the base currency — change the base before deactivating it.");
        }

        $currency->update($data + ['is_active' => $request->boolean('is_active')]);

        $this->settings->flush();

        AdminAuditLog::record(
            'admin.currency.updated',
            sprintf('Changed %s — rate %s, %s', $currency->code, $currency->fallback_rate, $currency->is_active ? 'active' : 'inactive'),
            $currency,
            $currency->code,
        );

        return back()->with('success', "{$currency->code} has been updated.");
    }

    public function destroy(Currency $currency)
    {
        if ($currency->isBase()) {
            return back()->with('error', "{$currency->code} is the base currency and cannot be removed.");
        }

        $users  = $currency->usersCount();
        $prices = $currency->giftPricesCount();

        // Wallet balances and gift prices reference the code. Deleting the row
        // would leave both unreadable, so it is retired instead — exactly how a
        // gift that has been sent is handled.
        if ($users > 0 || $prices > 0) {
            $currency->update(['is_active' => false]);
            $this->settings->flush();

            AdminAuditLog::record(
                'admin.currency.updated',
                sprintf('Deactivated %s — kept because %d account(s) and %d gift price(s) reference it', $currency->code, $users, $prices),
                $currency,
                $currency->code,
            );

            return back()->with('success', sprintf(
                '%s is in use by %d account(s) and %d gift price(s), so it has been switched off rather than deleted. Nothing already priced or held in it is lost.',
                $currency->code,
                $users,
                $prices
            ));
        }

        $code = $currency->code;

        AdminAuditLog::record('admin.currency.deleted', "Deleted the currency {$code}", $currency, $code);

        $currency->delete();
        $this->settings->flush();

        return back()->with('success', "{$code} has been removed.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Currency $currency = null): array
    {
        $data = $request->validate([
            'code'          => ['required', 'string', 'size:3', 'alpha', Rule::unique('currencies', 'code')->ignore($currency?->id)],
            'name'          => ['required', 'string', 'max:60'],
            'symbol'        => ['required', 'string', 'max:8'],
            'decimals'      => ['required', 'integer', 'min:0', 'max:4'],
            'fallback_rate' => ['required', 'numeric', 'min:0.000001'],
            'sort_order'    => ['required', 'integer', 'min:0', 'max:9999'],

            // Typed as a comma-separated list, stored as an array — the column
            // is JSON and the overlay iterates it.
            'countries'     => ['nullable', 'string', 'max:500'],
        ], [
            'code.alpha' => 'A currency code is three letters, like NGN or GHS.',
        ]);

        $data['countries'] = $this->parseCountries($request->input('countries'));

        return $data;
    }

    /**
     * @return array<int, string>
     */
    private function parseCountries(?string $raw): array
    {
        if (! $raw) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(fn ($part) => strtolower(trim($part)), explode(',', $raw))
        )));
    }
}
