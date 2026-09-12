<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LocationModule\LocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Which currency a new account gets.
 *
 * This decides whether someone has a local wallet at all, which gateway their
 * guests pay through, and what every amount on their page is denominated in —
 * and it is set once, at signup, from an IP lookup that can fail. So the
 * failure paths matter as much as the happy one.
 */
class CurrencyDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();   // country lookups are cached for a day
    }

    private function fakeIpinfo(?string $country): void
    {
        Http::fake([
            'ipinfo.io/*'  => $country
                ? Http::response(['country' => $country])
                : Http::response([], 404),
            'ip-api.com/*' => Http::response([], 404),
        ]);
    }

    public function test_a_nigerian_ip_gets_naira(): void
    {
        $this->fakeIpinfo('NG');

        $this->assertSame('NGN', app(LocationService::class)->getCurrencyFromIp('102.89.1.1'));
    }

    public function test_a_country_that_is_not_mapped_gets_the_base_currency(): void
    {
        // Only countries in currency.country_map have their own currency;
        // everywhere else checks out in USD, which is the point of the base.
        $this->fakeIpinfo('DE');

        $this->assertSame('USD', app(LocationService::class)->getCurrencyFromIp('91.0.0.1'));
    }

    public function test_an_undetectable_address_falls_back_to_the_configured_country(): void
    {
        // The bug this fixes: a signup from localhost, or from behind a lookup
        // that failed, used to become a USD account by default.
        config(['currency.fallback_country' => 'NG']);
        $this->fakeIpinfo(null);

        $this->assertSame('NGN', app(LocationService::class)->getCurrencyFromIp('127.0.0.1'));
    }

    public function test_clearing_the_fallback_returns_to_the_base_currency(): void
    {
        config(['currency.fallback_country' => '']);
        $this->fakeIpinfo(null);

        $this->assertSame('USD', app(LocationService::class)->getCurrencyFromIp('127.0.0.1'));
    }

    public function test_a_new_signup_from_nigeria_is_created_in_naira(): void
    {
        $this->fakeIpinfo('NG');

        $this->post('/register', [
            'first_name'            => 'Ada',
            'last_name'             => 'Okafor',
            'name'                  => 'Ada Okafor',
            'email'                 => 'ada@example.com',
            'password'              => 'correct-horse-battery-1',
            'password_confirmation' => 'correct-horse-battery-1',
        ], ['REMOTE_ADDR' => '102.89.1.1']);

        $user = User::where('email', 'ada@example.com')->first();

        $this->assertNotNull($user, 'registration did not create the account');
        $this->assertSame('NGN', $user->currency);
        $this->assertSame('NG', $user->country);
    }

    public function test_a_signup_we_cannot_place_still_lands_in_the_market_currency(): void
    {
        config(['currency.fallback_country' => 'NG']);
        $this->fakeIpinfo(null);

        $this->post('/register', [
            'first_name'            => 'Local',
            'last_name'             => 'Developer',
            'name'                  => 'Local Developer',
            'email'                 => 'local@example.com',
            'password'              => 'correct-horse-battery-1',
            'password_confirmation' => 'correct-horse-battery-1',
        ]);

        $user = User::where('email', 'local@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('NGN', $user->currency);
    }

    public function test_the_currency_column_cannot_be_set_through_a_form(): void
    {
        $this->fakeIpinfo('NG');

        // It decides which wallet the account has, so it is guarded.
        $user = User::create([
            'uuid'       => (string) \Illuminate\Support\Str::uuid(),
            'first_name' => 'Ada',
            'last_name'  => 'Okafor',
            'email'      => 'guarded@example.com',
            'password'   => 'irrelevant',
            'currency'   => 'USD',
        ]);

        $this->assertSame('NGN', $user->fresh()->currency);
    }

    public function test_a_public_address_on_172_is_not_treated_as_private(): void
    {
        // Only 172.16–31 is private. The old check swallowed the whole /8 and
        // gave every visitor on it the fallback instead of a real lookup.
        $this->fakeIpinfo('GB');

        $this->assertSame('GB', app(LocationService::class)->getCountryFromIp('172.217.16.1'));
        $this->assertNull(app(LocationService::class)->getCountryFromIp('172.16.0.1'));
    }

    public function test_the_development_ip_stands_in_for_a_local_address(): void
    {
        // So the real lookup can be exercised before deploying.
        config(['services.ipinfo.dev_ip' => '102.89.1.1']);
        $this->fakeIpinfo('NG');

        $this->assertSame('NG', app(LocationService::class)->getCountryFromIp('127.0.0.1'));
    }

    public function test_the_sync_command_leaves_accounts_holding_money_alone(): void
    {
        config(['currency.fallback_country' => 'NG']);
        $this->fakeIpinfo(null);

        $empty = User::factory()->create();
        $empty->forceFill(['currency' => 'USD', 'country' => null, 'wallet_balance' => 0, 'global_wallet_balance' => 0])->save();

        $funded = User::factory()->create();
        $funded->forceFill(['currency' => 'USD', 'country' => null, 'global_wallet_balance' => 250])->save();

        $this->artisan('users:sync-currency')->assertSuccessful();

        $this->assertSame('NGN', $empty->fresh()->currency);

        // Changing this one's currency would move its balance to a wallet it
        // does not have.
        $this->assertSame('USD', $funded->fresh()->currency);
    }
}
