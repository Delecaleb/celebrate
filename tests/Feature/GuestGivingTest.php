<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\Gift;
use App\Models\PlatformAvailableGift;
use App\Models\User;
use App\Models\Wish;
use App\Models\WishContribution;
use App\Services\PaymentSystem\PaystackService;
use App\Services\PaymentSystem\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Sending a gift or contributing to the registry must not require an account.
 * The card path only needs a name and an email for the receipt; signing in is
 * for paying out of a wallet.
 */
class GuestGivingTest extends TestCase
{
    use RefreshDatabase;

    private function celebration(): Celebration
    {
        return Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => User::factory()->create()->id,
            'title'            => "Sandra's Birthday",
            'slug'             => 'sandra-guest-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Sandra',
            'status'           => 'published',
            'is_public'        => true,
        ]);
    }

    private function platformGift(): PlatformAvailableGift
    {
        return PlatformAvailableGift::create([
            'gift_name'      => 'Bouquet',
            'gift_price'     => 20.00,
            'gift_image_url' => 'gifts/bouquet.png',
            'gift_link_url'  => 'https://example.com/bouquet',
            'is_active'      => true,
        ]);
    }

    private function wish(Celebration $celebration): Wish
    {
        return Wish::create([
            'celebration_id' => $celebration->id,
            'name'           => 'Nike Air Max',
            'wish_type'      => 'cash',
            'target_amount'  => 150.00,
            'amount_base'    => 150.00,
            'base_currency'  => 'USD',
            'current_amount' => 0,
            'currency'       => 'USD',
            'status'         => 'active',
        ]);
    }

    /**
     * The base currency is USD, so a gift goes down the Stripe path. Stub both
     * providers so no network call is made whichever branch is taken.
     */
    private function fakeCheckout(): void
    {
        $this->mock(StripeService::class, function ($m) {
            $m->shouldReceive('createCheckoutSession')->andReturn('https://checkout.stripe.com/stub');
        });
        $this->mock(PaystackService::class, function ($m) {
            $m->shouldReceive('initTransaction')->andReturn([
                'authorization_url' => 'https://checkout.paystack.com/stub',
                'reference'         => 'stub-ref-123',
            ]);
        });
    }

    /** Paystack is the non-USD path; stub the HTTP call so no network is hit. */
    private function fakePaystack(): void
    {
        config(['services.paystack.secret' => 'sk_test_stub']);

        Http::fake([
            'api.paystack.co/*' => Http::response([
                'status' => true,
                'data'   => [
                    'authorization_url' => 'https://checkout.paystack.com/stub',
                    'reference'         => 'stub-ref-123',
                    'access_code'       => 'stub',
                ],
            ], 200),
        ]);
    }

    public function test_a_guest_can_start_paying_for_a_gift(): void
    {
        $celebration = $this->celebration();
        $gift        = $this->platformGift();

        $this->fakeCheckout();

        $this->postJson(route('gift.payment.initiate'), [
            'platform_gift_id' => $gift->id,
            'celebration_id'   => $celebration->id,
            'message'          => 'Happy birthday!',
            'guest_name'       => 'Ada Guest',
            'guest_email'      => 'ada@example.com',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertGuest();

        $created = Gift::sole();
        $this->assertNull($created->sender_user_id);
        $this->assertSame('Ada Guest', $created->sender_name);
        $this->assertSame('ada@example.com', $created->sender_email);
        $this->assertSame('pending', $created->payment_status);
    }

    public function test_a_guest_gift_needs_a_name_and_email(): void
    {
        $celebration = $this->celebration();
        $gift        = $this->platformGift();

        $this->postJson(route('gift.payment.initiate'), [
            'platform_gift_id' => $gift->id,
            'celebration_id'   => $celebration->id,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['guest_name', 'guest_email']);

        $this->assertSame(0, Gift::count());
    }

    public function test_a_signed_in_user_still_does_not_need_guest_fields(): void
    {
        $celebration = $this->celebration();
        $gift        = $this->platformGift();
        $user        = User::factory()->create();

        $this->fakeCheckout();

        $this->actingAs($user)->postJson(route('gift.payment.initiate'), [
            'platform_gift_id' => $gift->id,
            'celebration_id'   => $celebration->id,
        ])->assertOk();

        $created = Gift::sole();
        $this->assertSame($user->id, $created->sender_user_id);
        $this->assertSame($user->email, $created->sender_email);
    }

    public function test_a_guest_can_start_contributing_to_a_registry_item(): void
    {
        $celebration = $this->celebration();
        $wish        = $this->wish($celebration);

        $this->fakePaystack();

        $this->postJson(route('wish.contribute.pay', $wish), [
            'amount'      => 25,
            'currency'    => 'NGN',
            'message'     => 'Enjoy!',
            'guest_name'  => 'Ada Guest',
            'guest_email' => 'ada@example.com',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertGuest();

        $contribution = WishContribution::sole();
        $this->assertNull($contribution->contributor_user_id);
        $this->assertSame('Ada Guest', $contribution->contributor_name);
        $this->assertSame('ada@example.com', $contribution->contributor_email);
        $this->assertSame('pending', $contribution->payment_status);
    }

    public function test_a_guest_contribution_needs_a_name_and_email(): void
    {
        $celebration = $this->celebration();
        $wish        = $this->wish($celebration);

        $this->postJson(route('wish.contribute.pay', $wish), [
            'amount'   => 25,
            'currency' => 'NGN',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['guest_name', 'guest_email']);

        $this->assertSame(0, WishContribution::count());
    }

    /** Paying out of a wallet is the one thing that still needs an account. */
    public function test_wallet_payment_still_requires_signing_in(): void
    {
        $celebration = $this->celebration();
        $wish        = $this->wish($celebration);
        $gift        = $this->platformGift();

        $this->postJson(route('gift.send'), [
            'platform_gift_id' => $gift->id,
            'celebration_id'   => $celebration->id,
        ])->assertStatus(401);

        $this->postJson(route('wish.contribute.wallet', $wish), [
            'amount'   => 25,
            'currency' => 'NGN',
        ])->assertStatus(401);
    }
}
