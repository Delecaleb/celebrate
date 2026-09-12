<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The pricing page speaks the visitor's currency.
 *
 * Nigeria sees naira because that is what Paystack will charge them; everyone
 * unmapped sees the base currency. A signed-in account keeps the currency it
 * was opened with, since that is the one its wallet is held in.
 */
class PricingCurrencyTest extends TestCase
{
    use RefreshDatabase;

    /** Stand in for the ipinfo lookup, which caches by address for a day. */
    private function visitingFrom(string $ip, string $country): array
    {
        Cache::put("location_country_{$ip}", $country, 60);

        return ['REMOTE_ADDR' => $ip];
    }

    /** The figure on the first plan card, which is the page's only real price. */
    private function amountShown(string $html): string
    {
        preg_match('/class="price-amount">\s*([^\s<]+)/', $html, $m);

        return $m[1] ?? '';
    }

    public function test_a_nigerian_visitor_sees_naira(): void
    {
        $html = $this->get('/pricing', $this->visitingFrom('197.210.70.1', 'nigeria'))
            ->assertOk()
            ->getContent();

        $this->assertSame('₦0', $this->amountShown($html));
    }

    public function test_a_visitor_from_abroad_sees_the_base_currency(): void
    {
        $html = $this->get('/pricing', $this->visitingFrom('8.8.8.8', 'united states'))
            ->assertOk()
            ->getContent();

        $this->assertSame('$0', $this->amountShown($html));
    }

    public function test_an_unrecognised_country_falls_back_rather_than_breaking(): void
    {
        $html = $this->get('/pricing', $this->visitingFrom('81.2.69.142', 'united kingdom'))
            ->assertOk()
            ->getContent();

        $this->assertSame('$0', $this->amountShown($html));
    }

    public function test_a_signed_in_account_keeps_its_own_currency_abroad(): void
    {
        // Their wallet is in naira, so quoting them dollars because they are
        // travelling would be a lie about what they will be paid in.
        $user = User::factory()->create();
        $user->forceFill(['currency' => 'NGN'])->save();

        $html = $this->actingAs($user)
            ->get('/pricing', $this->visitingFrom('8.8.8.8', 'united states'))
            ->assertOk()
            ->getContent();

        $this->assertSame('₦0', $this->amountShown($html));
    }

    public function test_the_page_carries_no_hardcoded_currency_glyph(): void
    {
        // The naira sign used to be written into the plan itself, which is how
        // an American visitor ended up being quoted ₦0.
        $html = file_get_contents(resource_path('views/marketing/partials/pricing.blade.php'));

        $this->assertStringNotContainsString('&#8358;', $html);
        $this->assertStringNotContainsString('₦', $html);
    }
}
