<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\Gift;
use App\Models\User;
use App\Models\Wish;
use App\Models\WishContribution;
use App\Services\PaymentSystem\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegistryAndTotalsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Every figure in this file is in USD. A test request has no traceable
        // address, so without this the visitor would be placed in the
        // configured market and each total converted into its currency —
        // correct behaviour, but not what these tests are measuring.
        config(['currency.fallback_country' => 'US']);
    }

    /**
     * An owner who works in the base currency.
     *
     * Every figure below is denominated in USD, so the owner has to be too —
     * a new account now takes its currency from where the signup came from,
     * which for an undetectable test request is the configured market.
     */
    private function usdOwner(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['currency' => 'USD', 'country' => 'US'])->save();

        return $user->fresh();
    }

    private function celebration(User $owner): Celebration
    {
        return Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $owner->id,
            'title'            => "Sandra's Birthday",
            'slug'             => 'sandra-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Sandra',
            'status'           => 'published',
            'is_public'        => true,
        ]);
    }

    private function wish(Celebration $celebration, string $name = 'Nike Air Max'): Wish
    {
        return Wish::create([
            'celebration_id' => $celebration->id,
            'name'           => $name,
            'wish_type'      => 'cash',
            'target_amount'  => 150.00,
            'amount_base'    => 150.00,
            'base_currency'  => 'USD',
            'current_amount' => 0,
            'currency'       => 'USD',
            'status'         => 'active',
        ]);
    }

    private function gift(Celebration $c, float $amount, string $currency, string $status): Gift
    {
        static $n = 0;
        $n++;

        return Gift::create([
            'celebration_id'        => $c->id,
            'sender_name'           => 'Giver ' . $n,
            'sender_email'          => 'g' . $n . '@example.com',
            'amount'                => $amount,
            'currency'              => $currency,
            'payment_method'        => 'card',
            'transaction_reference' => 'gift-' . $n . '-' . Str::random(6),
            'payment_status'        => $status,
            'is_anonymous'          => false,
        ]);
    }

    private function contribution(Wish $w, float $amount, string $currency, string $status): WishContribution
    {
        static $n = 0;
        $n++;

        return WishContribution::create([
            'wish_id'           => $w->id,
            'celebration_id'    => $w->celebration_id,
            'contributor_name'  => 'Backer ' . $n,
            'contributor_email' => 'b' . $n . '@example.com',
            'amount'            => $amount,
            'currency'          => $currency,
            'original_amount'   => $amount,
            'conversion_rate'   => 1.0,
            'contribution_type' => 'cash',
            'payment_reference' => 'wish-' . $n . '-' . Str::random(6),
            'payment_status'    => $status,
            'is_anonymous'      => false,
        ]);
    }

    /**
     * The headline figure must be the money actually received: paid rows only,
     * every currency converted, and registry contributions included.
     */
    public function test_the_raised_total_counts_paid_gifts_and_paid_contributions(): void
    {
        $owner       = $this->usdOwner();
        $celebration = $this->celebration($owner);
        $wish        = $this->wish($celebration);

        $this->gift($celebration, 30, 'USD', 'paid');
        $this->gift($celebration, 20, 'USD', 'paid');
        $this->contribution($wish, 50, 'USD', 'paid');

        $response = $this->get(route('celebrations.show', $celebration->slug))->assertOk();

        // 30 + 20 + 50 — the contribution is not silently dropped
        $this->assertSame(100.0, round($response->viewData('totalGifts'), 2));
    }

    public function test_pending_and_failed_money_is_not_counted_as_raised(): void
    {
        $owner       = $this->usdOwner();
        $celebration = $this->celebration($owner);
        $wish        = $this->wish($celebration);

        $this->gift($celebration, 40, 'USD', 'paid');
        $this->gift($celebration, 500, 'USD', 'pending');
        $this->gift($celebration, 999, 'USD', 'failed');
        $this->contribution($wish, 10, 'USD', 'paid');
        $this->contribution($wish, 700, 'USD', 'pending');

        $response = $this->get(route('celebrations.show', $celebration->slug))->assertOk();

        $this->assertSame(50.0, round($response->viewData('totalGifts'), 2));
    }

    /** Amounts taken in different currencies must be converted, not added raw. */
    public function test_mixed_currency_gifts_are_converted_before_summing(): void
    {
        $owner       = $this->usdOwner();
        $celebration = $this->celebration($owner);

        $this->gift($celebration, 10, 'USD', 'paid');
        $this->gift($celebration, 5000, 'NGN', 'paid');

        $response = $this->get(route('celebrations.show', $celebration->slug))->assertOk();

        $rate     = app(CurrencyService::class)->convert(5000, 'NGN', 'USD');
        $expected = 10 + $rate;

        $total = (float) $response->viewData('totalGifts');
        $this->assertSame(round($expected, 2), round($total, 2));

        // the naive raw sum would have been 5010 — make sure that is not it
        $this->assertNotSame(5010.0, round($total, 2));
    }

    public function test_registry_contributors_appear_as_supporters(): void
    {
        $owner       = $this->usdOwner();
        $celebration = $this->celebration($owner);
        $wish        = $this->wish($celebration);

        $gift         = $this->gift($celebration, 30, 'USD', 'paid');
        $contribution = $this->contribution($wish, 50, 'USD', 'paid');

        $response = $this->get(route('celebrations.show', $celebration->slug))->assertOk();

        // read the names back off the records — the helpers' counters carry
        // across tests in a class run, so hard-coding "Giver 1" is not safe
        $names = collect($response->viewData('supporters'))->pluck('name');
        $this->assertTrue($names->contains($gift->sender_name), 'gift sender should be a supporter');
        $this->assertTrue($names->contains($contribution->contributor_name), 'registry contributor should be a supporter');
    }

    // ── registry management ────────────────────────────────────────────────

    public function test_an_owner_can_remove_a_registry_item(): void
    {
        $owner       = $this->usdOwner();
        $celebration = $this->celebration($owner);
        // deliberately not "Nike Air Max" — that is the add-item placeholder,
        // which the owner's own view renders and would defeat assertDontSee
        $wish        = $this->wish($celebration, 'Espresso Machine');

        $this->actingAs($owner)
            ->deleteJson(route('celebrant.wish.destroy', $wish))
            ->assertOk()
            ->assertJson(['success' => true]);

        // soft deleted: gone from the page, still in the table
        $this->assertNull(Wish::find($wish->id));
        $this->assertNotNull(Wish::withTrashed()->find($wish->id));
        $this->assertNotNull(Wish::withTrashed()->find($wish->id)->deleted_at);

        $this->get(route('celebrations.show', $celebration->slug))
            ->assertOk()
            ->assertDontSee('Espresso Machine');
    }

    /** Removing an item must never destroy money already given towards it. */
    public function test_removing_an_item_keeps_its_contributions(): void
    {
        $owner       = $this->usdOwner();
        $celebration = $this->celebration($owner);
        $wish        = $this->wish($celebration);

        $contribution = $this->contribution($wish, 60, 'USD', 'paid');

        $this->actingAs($owner)->deleteJson(route('celebrant.wish.destroy', $wish))->assertOk();

        $this->assertDatabaseHas('wish_contributions', [
            'id'             => $contribution->id,
            'payment_status' => 'paid',
        ]);

        // and it still counts towards the amount raised
        $response = $this->get(route('celebrations.show', $celebration->slug))->assertOk();
        $this->assertSame(60.0, round($response->viewData('totalGifts'), 2));
    }

    public function test_someone_else_cannot_remove_your_registry_item(): void
    {
        $owner       = $this->usdOwner();
        $celebration = $this->celebration($owner);
        $wish        = $this->wish($celebration);

        $this->actingAs(User::factory()->create())
            ->deleteJson(route('celebrant.wish.destroy', $wish))
            ->assertStatus(403);

        $this->assertNotNull(Wish::find($wish->id));
    }

    public function test_a_guest_cannot_remove_a_registry_item(): void
    {
        $owner       = $this->usdOwner();
        $celebration = $this->celebration($owner);
        $wish        = $this->wish($celebration);

        $this->deleteJson(route('celebrant.wish.destroy', $wish))->assertStatus(403);

        $this->assertNotNull(Wish::find($wish->id));
    }

    // ── adding items ───────────────────────────────────────────────────────

    /** An item with no cost is valid — it used to 500 on the missing keys. */
    public function test_an_owner_can_add_an_item_without_a_cost(): void
    {
        $owner       = $this->usdOwner();
        $celebration = $this->celebration($owner);

        $this->actingAs($owner)
            ->postJson(route('celebrant.create-wishes'), [
                'celebration_id' => $celebration->id,
                'wishlist'       => [['name' => 'A surprise', 'amount' => '']],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $wish = Wish::where('celebration_id', $celebration->id)->sole();
        $this->assertSame('A surprise', $wish->name);
        $this->assertNull($wish->target_amount);
    }

    public function test_an_owner_can_add_an_item_with_a_cost(): void
    {
        $owner       = $this->usdOwner();
        $celebration = $this->celebration($owner);

        $this->actingAs($owner)
            ->postJson(route('celebrant.create-wishes'), [
                'celebration_id' => $celebration->id,
                'wishlist'       => [['name' => 'Nike Air Max', 'amount' => '150']],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $wish = Wish::where('celebration_id', $celebration->id)->sole();
        $this->assertSame('150.00', (string) $wish->target_amount);
    }

    public function test_you_cannot_add_items_to_someone_elses_registry(): void
    {
        $owner       = $this->usdOwner();
        $celebration = $this->celebration($owner);

        $this->actingAs(User::factory()->create())
            ->postJson(route('celebrant.create-wishes'), [
                'celebration_id' => $celebration->id,
                'wishlist'       => [['name' => 'Sneaky item', 'amount' => '10']],
            ])
            ->assertStatus(403);

        $this->assertSame(0, Wish::where('celebration_id', $celebration->id)->count());
    }

    public function test_a_guest_cannot_add_items_to_a_registry(): void
    {
        $owner       = $this->usdOwner();
        $celebration = $this->celebration($owner);

        $this->postJson(route('celebrant.create-wishes'), [
            'celebration_id' => $celebration->id,
            'wishlist'       => [['name' => 'Sneaky item', 'amount' => '10']],
        ])->assertStatus(403);

        $this->assertSame(0, Wish::where('celebration_id', $celebration->id)->count());
    }
}
