<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\Gift;
use App\Models\PlatformAvailableGift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Paying for a gift without leaving the celebration page.
 *
 * The browser hands us a reference and says the payment finished. That claim
 * is worth nothing on its own — anyone can post it — so the only thing these
 * tests really care about is that a gift settles when, and only when, Paystack
 * says the money arrived.
 */
class InlineGiftPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function celebration(): Celebration
    {
        return Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => User::factory()->create()->id,
            'title'            => "Sandra's Birthday",
            'slug'             => 'sandra-inline-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Sandra',
            'status'           => 'published',
            'is_public'        => true,
        ]);
    }

    private function pendingGift(string $reference): Gift
    {
        $platformGift = PlatformAvailableGift::create([
            'gift_name'  => 'Bottle of Wine',
            'gift_icon'  => 'mdi-glass-wine',
            'gift_price' => 10,
            'category'   => 'small',
            'status'     => 'active',
        ]);

        return Gift::create([
            'celebration_id'        => $this->celebration()->id,
            'platform_gift_id'      => $platformGift->id,
            'sender_name'           => 'Ada Guest',
            'sender_email'          => 'ada@example.com',
            'amount'                => 8000,
            'currency'              => 'NGN',
            'guest_currency'        => 'NGN',
            'conversion_rate'       => 1.0,
            'payment_method'        => 'card',
            'transaction_reference' => $reference,
            'payment_status'        => 'pending',
            'is_anonymous'          => false,
        ]);
    }

    private function fakeVerify(string $status): void
    {
        config(['services.paystack.secret' => 'sk_test_stub']);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data'   => ['status' => $status, 'amount' => 800000, 'currency' => 'NGN'],
            ], 200),
        ]);
    }

    public function test_a_verified_payment_settles_the_gift(): void
    {
        $reference = 'ps-' . Str::uuid();
        $gift      = $this->pendingGift($reference);

        $this->fakeVerify('success');

        $this->postJson(route('gift.payment.confirm'), ['reference' => $reference])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('paid', $gift->fresh()->payment_status);
    }

    public function test_a_payment_paystack_has_not_seen_settles_nothing(): void
    {
        // The whole attack: post the reference without ever paying.
        $reference = 'ps-' . Str::uuid();
        $gift      = $this->pendingGift($reference);

        $this->fakeVerify('abandoned');

        $this->postJson(route('gift.payment.confirm'), ['reference' => $reference])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertSame('pending', $gift->fresh()->payment_status);
    }

    public function test_an_unknown_reference_is_refused(): void
    {
        $this->fakeVerify('success');

        $this->postJson(route('gift.payment.confirm'), ['reference' => 'ps-never-existed'])
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_confirming_twice_charges_the_celebrant_once(): void
    {
        // The webhook and this endpoint race on every real payment, so landing
        // here twice is the normal case rather than the exceptional one.
        $reference = 'ps-' . Str::uuid();
        $gift      = $this->pendingGift($reference);

        $this->fakeVerify('success');

        $this->postJson(route('gift.payment.confirm'), ['reference' => $reference])->assertOk();

        $owner   = $gift->celebration->user;
        $balance = $owner->fresh()->wallet_balance;

        $this->postJson(route('gift.payment.confirm'), ['reference' => $reference])
            ->assertOk()
            ->assertJson(['success' => true, 'already' => true]);

        $this->assertEquals($balance, $owner->fresh()->wallet_balance);
        $this->assertSame(1, Gift::where('transaction_reference', $reference)->count());
    }

    public function test_a_reference_is_required(): void
    {
        $this->postJson(route('gift.payment.confirm'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reference');
    }
}
