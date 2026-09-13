<?php

namespace App\Support;

use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

/**
 * Settings an operator can change from the panel, and the currencies the
 * platform trades in.
 *
 * The whole point is that nothing else has to know. These values are read into
 * Laravel's config at boot (see SettingsServiceProvider), so every existing
 * config('services.paystack.secret') and config('currency.currencies') call
 * keeps working — the only change is where the value came from.
 *
 * Precedence is: what the admin set here, then .env, then the framework
 * default. A field left blank in the panel is not "empty", it is "unset" —
 * which means carry on using .env.
 */
class SettingsRepository
{
    private const CACHE_KEY = 'settings.overlay';
    private const CACHE_TTL = 3600;

    /**
     * Which settings exist, what they mean, and which are secret.
     *
     * The catalogue lives here rather than in the table so that adding one is a
     * reviewed code change, and so a stray row can never inject a config key
     * nobody intended.
     *
     * @var array<string, array<string, array{label: string, config: string, secret?: bool, help?: string, type?: string}>>
     */
    public const CATALOGUE = [
        'payments' => [
            // The switches come first: they are what someone opening this page
            // in a hurry is most likely looking for.
            'paystack_enabled' => [
                'label'  => 'Paystack checkout',
                'config' => 'services.paystack.enabled',
                'type'   => 'toggle',
                'help'   => 'Off stops new naira checkouts. Payments already started still settle, and wallet gifts keep working.',
            ],
            'stripe_enabled' => [
                'label'  => 'Stripe checkout',
                'config' => 'services.stripe.enabled',
                'type'   => 'toggle',
                'help'   => 'Off stops new dollar checkouts. Payments already started still settle, and wallet gifts keep working.',
            ],
            'paystack_secret' => [
                'label'  => 'Paystack secret key',
                'config' => 'services.paystack.secret',
                'secret' => true,
                'help'   => 'Starts sk_. Without it nothing can be charged, verified or paid out, and bank verification falls back to manual entry.',
            ],
            'paystack_public' => [
                'label'  => 'Paystack public key',
                'config' => 'services.paystack.public_key',
                'help'   => 'Starts pk_. Safe to expose in a browser.',
            ],
            'stripe_secret' => [
                'label'  => 'Stripe secret key',
                'config' => 'services.stripe.secret',
                'secret' => true,
                'help'   => 'Used for checkouts in the base currency.',
            ],
            'stripe_publishable' => [
                'label'  => 'Stripe publishable key',
                'config' => 'services.stripe.publishable_key',
            ],
            'stripe_webhook_secret' => [
                'label'  => 'Stripe webhook signing secret',
                'config' => 'services.stripe.webhook_secret',
                'secret' => true,
                'help'   => 'From the endpoint page in the Stripe dashboard — not the API key. Without it every webhook is rejected.',
            ],
        ],

        'mail' => [
            'mailer' => [
                'label'  => 'Transport',
                'config' => 'mail.default',
                'help'   => '"log" delivers nothing — password resets included. Use smtp in production.',
            ],
            'host'     => ['label' => 'SMTP host',     'config' => 'mail.mailers.smtp.host'],
            'port'     => ['label' => 'SMTP port',     'config' => 'mail.mailers.smtp.port'],
            'username' => ['label' => 'SMTP username', 'config' => 'mail.mailers.smtp.username'],
            'password' => ['label' => 'SMTP password', 'config' => 'mail.mailers.smtp.password', 'secret' => true],
            'scheme'   => ['label' => 'Encryption',    'config' => 'mail.mailers.smtp.scheme', 'help' => 'tls, ssl, or blank.'],
            'from_address' => ['label' => 'From address', 'config' => 'mail.from.address', 'help' => 'Must be on a domain with SPF and DKIM set, or your mail lands in spam.'],
            'from_name'    => ['label' => 'From name',    'config' => 'mail.from.name'],
        ],

        'location' => [
            'ipinfo_token' => [
                'label'  => 'ipinfo.io token',
                'config' => 'services.ipinfo.token',
                'secret' => true,
                'help'   => 'Decides a new account\'s currency from where they sign up.',
            ],
            'dev_ip' => [
                'label'  => 'Development IP',
                'config' => 'services.ipinfo.dev_ip',
                'help'   => 'Stands in for a private address so the lookup can be tested locally. Leave empty in production.',
            ],
            'fallback_country' => [
                'label'  => 'Fallback country',
                'config' => 'currency.fallback_country',
                'help'   => 'Assumed when an address cannot be placed. Clear it to fall through to the base currency.',
            ],
        ],

        'features' => [
            'frames_enabled' => [
                'label'     => 'Photo frames',
                'config'    => 'features.frames',
                'type'      => 'toggle',
                'default'   => false,
                'on_label'  => 'On — celebrants can pick a frame',
                'off_label' => 'Off — the frame picker is hidden',
                'help'      => 'Shows the Frame section in a celebrant\'s page settings. Frames already chosen stay on their pages either way.',
            ],
        ],
    ];

    /**
     * Everything the provider needs to overlay, as config key => value.
     *
     * Cached, because this runs on every request. Any write clears it.
     *
     * @return array<string, mixed>
     */
    public function overlay(): array
    {
        if (! $this->tablesReady()) {
            return [];
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return array_merge($this->credentialOverlay(), $this->currencyOverlay());
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function credentialOverlay(): array
    {
        $overlay = [];

        foreach (Setting::all() as $setting) {
            $meta = self::CATALOGUE[$setting->group][$setting->key] ?? null;

            // A row naming something not in the catalogue sets nothing.
            if (! $meta) {
                continue;
            }

            $value = $setting->plainValue();

            // Unset means "keep using .env", which is not the same as an
            // operator deliberately blanking a field.
            if ($value === null) {
                continue;
            }

            $overlay[$meta['config']] = $value;
        }

        return $overlay;
    }

    /**
     * Currencies, rebuilt into the shapes the rest of the app already reads.
     *
     * @return array<string, mixed>
     */
    private function currencyOverlay(): array
    {
        $currencies = Currency::active()->ordered()->get();

        if ($currencies->isEmpty()) {
            return [];
        }

        $meta      = [];
        $countries = [];
        $rates     = [];

        foreach ($currencies as $currency) {
            $meta[$currency->code] = [
                'symbol'   => $currency->symbol,
                'name'     => $currency->name,
                'decimals' => $currency->decimals,
            ];

            $rates[$currency->code] = (float) $currency->fallback_rate;

            // Cast to array: a hand-edited row could hold a string, and the
            // whole site reads this at boot.
            foreach ((array) ($currency->countries ?? []) as $country) {
                // Keyed the way LocationService looks them up: lowercased code
                // or country name.
                $countries[strtolower(trim((string) $country))] = $currency->code;
            }
        }

        return [
            'currency.currencies'     => $meta,
            'currency.country_map'    => $countries,
            'currency.fallback_rates' => $rates,
        ];
    }

    /**
     * Write a group of values.
     *
     * A blank string clears the setting — which hands that key back to .env
     * rather than forcing it empty.
     *
     * @param  array<string, string|null>  $values
     */
    public function put(string $group, array $values): void
    {
        $catalogue = self::CATALOGUE[$group] ?? [];

        foreach ($values as $key => $value) {
            $meta = $catalogue[$key] ?? null;

            if (! $meta) {
                continue;
            }

            $value    = is_string($value) ? trim($value) : $value;
            $isSecret = (bool) ($meta['secret'] ?? false);

            if ($value === null || $value === '') {
                Setting::where('group', $group)->where('key', $key)->delete();

                continue;
            }

            Setting::updateOrCreate(
                ['group' => $group, 'key' => $key],
                [
                    'value'        => $isSecret ? Crypt::encryptString($value) : $value,
                    'is_encrypted' => $isSecret,
                    'updated_by'   => Auth::guard('admin')->id(),
                ]
            );
        }

        $this->flush();
    }

    /**
     * The current value of one setting, and where it came from.
     *
     * @return array{value: ?string, source: string}
     */
    public function describe(string $group, string $key): array
    {
        $meta = self::CATALOGUE[$group][$key] ?? null;

        if (! $meta) {
            return ['value' => null, 'source' => 'unknown'];
        }

        $stored = $this->tablesReady()
            ? Setting::where('group', $group)->where('key', $key)->first()?->plainValue()
            : null;

        if ($stored !== null) {
            return ['value' => $stored, 'source' => 'panel'];
        }

        $fromConfig = config($meta['config']);

        return [
            'value'  => $fromConfig === null ? null : (string) $fromConfig,
            'source' => $fromConfig === null || $fromConfig === '' ? 'unset' : 'env',
        ];
    }

    /**
     * A secret, shown the only way a secret should be: enough to recognise,
     * not enough to use.
     */
    public static function mask(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return strlen($value) <= 8
            ? str_repeat('•', strlen($value))
            : substr($value, 0, 4) . str_repeat('•', 8) . substr($value, -4);
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * False before the migration has run — during install, and during the
     * migration itself, where a config read must not hit a missing table.
     */
    private function tablesReady(): bool
    {
        try {
            return Schema::hasTable('settings') && Schema::hasTable('currencies');
        } catch (\Throwable) {
            return false;
        }
    }
}
