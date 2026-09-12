<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\User;
use App\Models\Wish;
use App\Models\WishContribution;
use App\Services\PaymentSystem\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * What a registry item shows as raised.
 *
 * The figure has to be one a contributor can check against their own bank
 * statement. It used to be stored in the item's currency at the live rate of
 * the day, then read back out through the item's frozen conversion_rate — two
 * different rates applied to the same money, which on a real page overstated
 * the total by 22%.
 */
class RegistryRaisedTotalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fix the rate so the arithmetic below is not at the mercy of a live
        // lookup: 1 USD = 1000 NGN. The service asks an exchange-rate API
        // first, so that call is blocked and the cache emptied — otherwise it
        // quietly uses the real rate and the expected figures drift daily.
        Http::fake(['*exchangerate*' => Http::response([], 500), '*er-api*' => Http::response([], 500)]);
        Cache::flush();

        config([
            'currency.fallback_country' => 'US',
            'currency.fallback_rates'   => ['USD' => 1.0, 'NGN' => 1000.0],
        ]);
    }

    private function wish(array $overrides = []): Wish
    {
        $celebration = Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => User::factory()->create()->id,
            'title'            => "Yemi's Birthday",
            'slug'             => 'yemi-raised-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Yemi',
            'status'           => 'published',
            'is_public'        => true,
        ]);

        return Wish::create(array_merge([
            'celebration_id'     => $celebration->id,
            'name'               => 'Air Max',
            'wish_type'          => 'cash',
            'target_amount'      => 5000,
            'amount_base'        => 5000,
            'base_currency'      => 'USD',
            'amount_converted'   => 8_100_000,
            'converted_currency' => 'NGN',

            // Deliberately not the live rate. This is the crux: the item was
            // priced when a dollar cost 1620 naira, and the rate has moved.
            'conversion_rate'    => 1620,
            'current_amount'     => 0,
            'currency'           => 'USD',
            'status'             => 'active',
        ], $overrides));
    }

    private function contribute(Wish $wish, float $amount, string $currency): WishContribution
    {
        return WishContribution::create([
            'celebration_id'    => $wish->celebration_id,
            'wish_id'           => $wish->id,
            'contributor_name'  => 'Ada',
            'contributor_email' => 'ada@example.com',
            'amount'            => $amount,
            'currency'          => $currency,
            'original_amount'   => $amount,
            'contribution_type' => 'cash',
            'payment_reference' => 'wish-pay-' . Str::uuid(),
            'payment_status'    => 'paid',
            'is_anonymous'      => false,
        ]);
    }

    public function test_naira_paid_is_the_naira_shown(): void
    {
        $wish = $this->wish();

        $this->contribute($wish, 81_000, 'NGN');
        $this->contribute($wish, 1_000_000, 'NGN');

        // Exactly what left their accounts — not a figure round-tripped
        // through two different rates.
        $this->assertEqualsWithDelta(
            1_081_000.0,
            $wish->raisedIn('NGN', app(CurrencyService::class)),
            0.01
        );
    }

    public function test_the_frozen_item_rate_never_inflates_the_total(): void
    {
        $wish = $this->wish();
        $this->contribute($wish, 100_000, 'NGN');

        $raised = $wish->raisedIn('NGN', app(CurrencyService::class));

        // The old behaviour converted in at the live rate and out at 1620,
        // which turned ₦100,000 into ₦162,000.
        $this->assertEqualsWithDelta(100_000.0, $raised, 0.01);
        $this->assertNotEqualsWithDelta(162_000.0, $raised, 0.01);
    }

    public function test_a_dollar_visitor_sees_the_same_money_converted_once(): void
    {
        $wish = $this->wish();
        $this->contribute($wish, 100_000, 'NGN');

        // 100,000 NGN at 1 USD = 1000 NGN.
        $this->assertEqualsWithDelta(
            100.0,
            $wish->raisedIn('USD', app(CurrencyService::class)),
            0.01
        );
    }

    public function test_mixed_currency_contributions_add_up(): void
    {
        $wish = $this->wish();

        $this->contribute($wish, 50_000, 'NGN');   // = 50 USD
        $this->contribute($wish, 25, 'USD');

        $this->assertEqualsWithDelta(75.0, $wish->raisedIn('USD', app(CurrencyService::class)), 0.01);
        $this->assertEqualsWithDelta(75_000.0, $wish->raisedIn('NGN', app(CurrencyService::class)), 0.01);
    }

    public function test_unpaid_contributions_are_not_counted(): void
    {
        $wish = $this->wish();

        $this->contribute($wish, 50_000, 'NGN');
        $this->contribute($wish, 999_999, 'NGN')->update(['payment_status' => 'pending']);

        $this->assertEqualsWithDelta(50_000.0, $wish->raisedIn('NGN', app(CurrencyService::class)), 0.01);
    }

    public function test_the_page_shows_the_corrected_figure(): void
    {
        $wish = $this->wish();
        $this->contribute($wish, 81_000, 'NGN');

        config(['currency.fallback_country' => 'NG']);

        $html = $this->get(route('celebrations.show', $wish->celebration->slug))
            ->assertOk()
            ->getContent();

        // The registry tile hands the modal its own figures.
        $this->assertMatchesRegularExpression('/current:\s*81000(\.0+)?\b/', $html);
    }
}
