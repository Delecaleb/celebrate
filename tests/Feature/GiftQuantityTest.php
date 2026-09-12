<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\Gift;
use App\Models\PlatformAvailableGift;
use App\Models\User;
use App\Services\PaymentSystem\PaystackService;
use App\Services\PaymentSystem\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Sending more than one of the same gift.
 *
 * The count comes from the browser but the money never does: the server
 * multiplies its own unit price by the quantity, so a hand-edited request can
 * change how many are sent and never what each one costs.
 */
class GiftQuantityTest extends TestCase
{
    use RefreshDatabase;

    private function celebration(?User $owner = null): Celebration
    {
        return Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => ($owner ?? User::factory()->create())->id,
            'title'            => "Yemi's Birthday",
            'slug'             => 'yemi-qty-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Yemi',
            'status'           => 'published',
            'is_public'        => true,
        ]);
    }

    private function platformGift(float $price = 10): PlatformAvailableGift
    {
        return PlatformAvailableGift::create([
            'gift_name' => 'Box of Cupcakes', 'gift_icon' => 'mdi-cupcake',
            'gift_price' => $price, 'category' => 'treats', 'status' => 'active',
        ]);
    }

    private function fakeCheckout(): void
    {
        $this->mock(StripeService::class, function ($m) {
            $m->shouldReceive('createCheckoutSession')->andReturn('https://checkout.stripe.com/stub');
        });
        $this->mock(PaystackService::class, function ($m) {
            $m->shouldReceive('initTransaction')->andReturn([
                'authorization_url' => 'https://checkout.paystack.com/stub',
                'access_code'       => 'stub-access-code',
                'reference'         => 'stub-ref-123',
            ]);
        });
    }

    public function test_the_charge_is_the_unit_price_times_the_quantity(): void
    {
        $this->fakeCheckout();
        $celebration = $this->celebration();
        $gift        = $this->platformGift(10);

        $this->postJson(route('gift.payment.initiate'), [
            'platform_gift_id' => $gift->id,
            'celebration_id'   => $celebration->id,
            'quantity'         => 4,
            'guest_name'       => 'Ada Guest',
            'guest_email'      => 'ada@example.com',
        ])->assertOk();

        $created = Gift::sole();
        $unit    = $gift->priceIn($created->currency);

        $this->assertSame(4, $created->quantity);
        $this->assertEqualsWithDelta($unit * 4, (float) $created->amount, 0.01);
    }

    public function test_no_quantity_means_one(): void
    {
        $this->fakeCheckout();
        $celebration = $this->celebration();
        $gift        = $this->platformGift();

        $this->postJson(route('gift.payment.initiate'), [
            'platform_gift_id' => $gift->id,
            'celebration_id'   => $celebration->id,
            'guest_name'       => 'Ada Guest',
            'guest_email'      => 'ada@example.com',
        ])->assertOk();

        $this->assertSame(1, Gift::sole()->quantity);
    }

    public function test_a_nonsense_quantity_is_refused(): void
    {
        $this->fakeCheckout();
        $celebration = $this->celebration();
        $gift        = $this->platformGift();

        foreach ([0, -3, 500] as $bad) {
            $this->postJson(route('gift.payment.initiate'), [
                'platform_gift_id' => $gift->id,
                'celebration_id'   => $celebration->id,
                'quantity'         => $bad,
                'guest_name'       => 'Ada Guest',
                'guest_email'      => 'ada@example.com',
            ])->assertStatus(422)->assertJsonValidationErrors('quantity');
        }

        $this->assertSame(0, Gift::count());
    }

    public function test_a_forged_amount_is_ignored(): void
    {
        // The only thing the browser gets to decide is how many.
        $this->fakeCheckout();
        $celebration = $this->celebration();
        $gift        = $this->platformGift(10);

        $this->postJson(route('gift.payment.initiate'), [
            'platform_gift_id' => $gift->id,
            'celebration_id'   => $celebration->id,
            'quantity'         => 2,
            'amount'           => 0.01,
            'price'            => 0.01,
            'guest_name'       => 'Ada Guest',
            'guest_email'      => 'ada@example.com',
        ])->assertOk();

        $created = Gift::sole();
        $this->assertEqualsWithDelta($gift->priceIn($created->currency) * 2, (float) $created->amount, 0.01);
    }

    public function test_a_wallet_send_charges_for_every_one(): void
    {
        $celebration = $this->celebration();
        $gift        = $this->platformGift(10);

        $sender = User::factory()->create();
        $sender->forceFill(['currency' => 'USD', 'global_wallet_balance' => 500])->save();

        $this->actingAs($sender)->postJson(route('gift.send'), [
            'platform_gift_id' => $gift->id,
            'celebration_id'   => $celebration->id,
            'quantity'         => 3,
        ])->assertOk()->assertJson(['success' => true]);

        $created = Gift::sole();
        $this->assertSame(3, $created->quantity);
        $this->assertEqualsWithDelta(30.0, (float) $created->amount, 0.01);

        // Debited for all three, not for one.
        $this->assertEqualsWithDelta(470.0, (float) $sender->fresh()->global_wallet_balance, 0.01);
    }

    public function test_the_wall_counts_items_not_sends(): void
    {
        $owner       = User::factory()->create();
        $celebration = $this->celebration($owner);
        $platform    = $this->platformGift();

        // One send of three, and one send of two: five cupcake boxes.
        foreach ([3, 2] as $n) {
            Gift::create([
                'celebration_id'        => $celebration->id,
                'platform_gift_id'      => $platform->id,
                'quantity'              => $n,
                'sender_name'           => 'Ada',
                'sender_email'          => 'ada@example.com',
                'amount'                => 10 * $n,
                'currency'              => 'USD',
                'guest_currency'        => 'USD',
                'conversion_rate'       => 1.0,
                'payment_method'        => 'card',
                'transaction_reference' => 'ps-' . Str::uuid(),
                'payment_status'        => 'paid',
                'is_anonymous'          => false,
            ]);
        }

        $item = $celebration->fresh()->load('gifts.platformGift')->sidebarGifts()->first();

        $this->assertSame(5, $item->count);
    }

    public function test_a_row_from_before_quantity_existed_counts_as_one(): void
    {
        $celebration = $this->celebration();
        $platform    = $this->platformGift();

        $gift = Gift::create([
            'celebration_id'        => $celebration->id,
            'platform_gift_id'      => $platform->id,
            'sender_name'           => 'Ada',
            'sender_email'          => 'ada@example.com',
            'amount'                => 10,
            'currency'              => 'USD',
            'guest_currency'        => 'USD',
            'conversion_rate'       => 1.0,
            'payment_method'        => 'card',
            'transaction_reference' => 'ps-' . Str::uuid(),
            'payment_status'        => 'paid',
            'is_anonymous'          => false,
        ]);

        // Simulates a legacy row: the column exists but holds nothing.
        Gift::withoutEvents(fn () => \DB::table('gifts')->where('id', $gift->id)->update(['quantity' => 0]));

        $this->assertSame(1, $celebration->fresh()->load('gifts.platformGift')->sidebarGifts()->first()->count);
        $this->assertSame('Box of Cupcakes', $gift->fresh()->load('platformGift')->label());
    }

    public function test_a_wallet_transfer_writes_both_legs(): void
    {
        // wallet_transactions.reference is unique, and both legs of a wallet
        // gift used to be written with the same one — so the credit always
        // collided and no wallet gift could ever be sent.
        $owner       = User::factory()->create();
        $celebration = $this->celebration($owner);
        $platform    = $this->platformGift(10);

        $sender = User::factory()->create();
        $sender->forceFill(['currency' => 'USD', 'global_wallet_balance' => 100])->save();

        $this->actingAs($sender)->postJson(route('gift.send'), [
            'platform_gift_id' => $platform->id,
            'celebration_id'   => $celebration->id,
        ])->assertOk();

        $legs = \App\Models\WalletTransaction::where('reference', 'like', 'wallet-%')->get();

        $this->assertCount(2, $legs);
        $this->assertEqualsCanonicalizing(['debit', 'credit'], $legs->pluck('type')->all());
        $this->assertEqualsWithDelta(10.0, (float) $owner->fresh()->global_wallet_balance, 0.01);
        $this->assertEqualsWithDelta(90.0, (float) $sender->fresh()->global_wallet_balance, 0.01);
    }

    public function test_the_label_says_how_many(): void
    {
        $celebration = $this->celebration();
        $platform    = $this->platformGift();

        $gift = Gift::create([
            'celebration_id'        => $celebration->id,
            'platform_gift_id'      => $platform->id,
            'quantity'              => 3,
            'sender_name'           => 'Ada',
            'sender_email'          => 'ada@example.com',
            'amount'                => 30,
            'currency'              => 'USD',
            'guest_currency'        => 'USD',
            'conversion_rate'       => 1.0,
            'payment_method'        => 'card',
            'transaction_reference' => 'ps-' . Str::uuid(),
            'payment_status'        => 'paid',
            'is_anonymous'          => false,
        ]);

        $this->assertSame('Box of Cupcakes × 3', $gift->load('platformGift')->label());
    }
}
