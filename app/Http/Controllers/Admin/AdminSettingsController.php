<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Support\QueueHealth;
use App\Support\SettingsRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Gateway keys, mail credentials and the location token.
 *
 * Values set here are encrypted, overlay the config at boot, and take
 * precedence over .env — clearing a field hands that key back to .env rather
 * than forcing it empty.
 *
 * Secrets are never rendered back to the browser. The form shows a masked
 * preview and only writes when somebody types a new value, so opening the page
 * and pressing save cannot silently blank a live key.
 */
class AdminSettingsController extends Controller
{
    public function __construct(private SettingsRepository $settings) {}

    public function edit(string $group = 'payments')
    {
        abort_unless(isset(SettingsRepository::CATALOGUE[$group]), 404);

        $fields = [];

        foreach (SettingsRepository::CATALOGUE[$group] as $key => $meta) {
            $state = $this->settings->describe($group, $key);

            $fields[$key] = $meta + [
                'source'  => $state['source'],
                // A secret is shown masked; everything else round-trips.
                'display' => ($meta['secret'] ?? false)
                    ? SettingsRepository::mask($state['value'])
                    : (string) ($state['value'] ?? ''),
            ];
        }

        return view('admin.settings', [
            'group'  => $group,
            'groups' => array_keys(SettingsRepository::CATALOGUE),
            'fields' => $fields,
            // Credentials being right is only half of mail working; the other
            // half is something draining the queue.
            'queue'  => $group === 'mail' ? QueueHealth::check() : null,
        ]);
    }

    public function update(Request $request, string $group)
    {
        abort_unless(isset(SettingsRepository::CATALOGUE[$group]), 404);

        $catalogue = SettingsRepository::CATALOGUE[$group];

        $data = $request->validate([
            'settings'   => ['sometimes', 'array'],
            'settings.*' => ['nullable', 'string', 'max:2000'],
            // Ticked per field to blank one deliberately, since an empty box
            // means "leave it alone" for a secret.
            'clear'      => ['sometimes', 'array'],
        ]);

        $values  = [];
        $changed = [];

        foreach ($catalogue as $key => $meta) {
            $isSecret = (bool) ($meta['secret'] ?? false);
            $typed    = $data['settings'][$key] ?? null;
            $clearing = isset($data['clear'][$key]);

            if ($clearing) {
                $values[$key] = '';
                $changed[]    = "{$key} (cleared)";

                continue;
            }

            // An untouched secret field arrives empty — that must not wipe it.
            if ($isSecret && ($typed === null || trim($typed) === '')) {
                continue;
            }

            if ($typed === null) {
                continue;
            }

            $current = $this->settings->describe($group, $key)['value'];

            if ((string) $typed !== (string) $current) {
                $changed[] = $key;
            }

            $values[$key] = $typed;
        }

        $this->settings->put($group, $values);

        // The names of what changed, never the values.
        AdminAuditLog::record(
            'admin.settings.updated',
            $changed === []
                ? "Saved {$group} settings with no changes"
                : "Changed {$group} settings: " . implode(', ', $changed),
            null,
            $group,
        );

        return back()->with('success', 'Settings saved. They take effect immediately.');
    }

    /**
     * Prove the credentials actually work, before a customer finds out they
     * do not.
     */
    public function test(Request $request, string $group)
    {
        $admin = Auth::guard('admin')->user();

        $result = match ($group) {
            'payments' => $this->testGateways(),
            'mail'     => $this->testMail($admin->email),
            'location' => $this->testLocation(),
            default    => ['ok' => false, 'message' => 'Nothing to test in that group.'],
        };

        AdminAuditLog::record(
            'admin.settings.tested',
            "Tested {$group} settings: " . ($result['ok'] ? 'passed' : 'failed'),
            null,
            $group,
        );

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private function testGateways(): array
    {
        $lines = [];

        $paystack = (string) config('services.paystack.secret');

        if ($paystack === '') {
            $lines[] = 'Paystack: no secret key set';
        } else {
            try {
                $response = Http::withToken($paystack)->timeout(10)->get('https://api.paystack.co/bank', ['perPage' => 1]);
                $lines[]  = $response->successful()
                    ? 'Paystack: key accepted'
                    : 'Paystack: rejected (' . $response->status() . ')';
            } catch (\Throwable $e) {
                $lines[] = 'Paystack: could not reach the API';
            }
        }

        $stripe = (string) config('services.stripe.secret');

        if ($stripe === '') {
            $lines[] = 'Stripe: no secret key set';
        } else {
            try {
                $response = Http::withToken($stripe)->timeout(10)->get('https://api.stripe.com/v1/balance');
                $lines[]  = $response->successful()
                    ? 'Stripe: key accepted'
                    : 'Stripe: rejected (' . $response->status() . ')';
            } catch (\Throwable $e) {
                $lines[] = 'Stripe: could not reach the API';
            }
        }

        if (config('services.stripe.webhook_secret') === null || config('services.stripe.webhook_secret') === '') {
            $lines[] = 'Stripe webhook secret: not set, so every webhook will be rejected';
        }

        return [
            'ok'      => ! str_contains(implode(' ', $lines), 'rejected') && ! str_contains(implode(' ', $lines), 'could not reach'),
            'message' => implode(' · ', $lines),
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private function testMail(string $to): array
    {
        if (config('mail.default') === 'log') {
            return ['ok' => false, 'message' => 'The transport is "log", so nothing is delivered — including password resets. Set it to smtp.'];
        }

        try {
            Mail::raw(
                "This is a test from the CelebrateMi admin panel.\n\nIf you are reading it, the mail credentials work.",
                fn ($message) => $message->to($to)->subject('CelebrateMi test email')
            );

            $queue   = QueueHealth::check();
            $message = "Test email sent to {$to}. If it does not arrive, check SPF and DKIM on the from-domain.";

            // This test sends inside the request. Everything the site actually
            // sends is queued, so a passing test with a stalled queue is the
            // most misleading result this button can give.
            if (! $queue['healthy']) {
                $message .= " Note that {$queue['stale']} real " . Str::plural('email', $queue['stale'])
                    . ' are queued and not going out — start a worker (php artisan queue:work).';
            }

            return ['ok' => true, 'message' => $message];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Could not send: ' . $e->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private function testLocation(): array
    {
        // A Nigerian address, so the answer is checkable at a glance.
        $ip = '102.89.1.1';

        try {
            $token   = (string) config('services.ipinfo.token');
            $headers = $token !== '' ? ['Authorization' => "Bearer {$token}"] : [];

            $response = Http::withHeaders($headers)->timeout(10)->get("https://ipinfo.io/{$ip}/json");

            if (! $response->successful()) {
                return ['ok' => false, 'message' => 'ipinfo.io rejected the request (' . $response->status() . ').'];
            }

            $country  = $response->json('country');
            $currency = app(\App\Services\LocationModule\LocationService::class)->getCurrencyForCountry($country);

            return [
                'ok'      => (bool) $country,
                'message' => $country
                    ? "Lookup worked: {$ip} resolves to {$country}, which maps to {$currency}."
                    : 'ipinfo.io answered but returned no country.',
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Could not reach ipinfo.io.'];
        }
    }
}
