<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\Gift;
use App\Models\User;
use App\Services\PaymentSystem\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/**
 * The Stripe success URL is a plain GET anyone can visit. It must never move
 * money on its own — Stripe has to confirm the payment first.
 */
class StripeCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // These tests are about money moving, not about the notification.
        Mail::fake();
    }

    private function pendingGift(): Gift
    {
        $owner = User::factory()->create(['wallet_balance' => 0, 'global_wallet_balance' => 0]);

        $celebration = Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $owner->id,
            'title'            => "Yemi's Birthday",
            'slug'             => 'yemi-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Yemi',
            'status'           => 'published',
            'is_public'        => true,
        ]);

        return Gift::create([
            'celebration_id'        => $celebration->id,
            'sender_name'           => 'Ada',
            'sender_email'          => 'ada@example.com',
            'amount'                => 5000,
            'currency'              => 'USD',
            'payment_method'        => 'card',
            'transaction_reference' => 'gift-' . Str::random(10),
            'payment_status'        => 'pending',
            'is_anonymous'          => false,
        ]);
    }

    /** Stripe stub whose confirmPaidFor answers however the test wants. */
    private function stripeAnswers(bool $confirmed): void
    {
        $stub = Mockery::mock(StripeService::class);
        $stub->shouldReceive('confirmPaidFor')->andReturn($confirmed);
        $this->app->instance(StripeService::class, $stub);
    }

    public function test_a_callback_without_a_session_id_does_not_mark_a_gift_paid(): void
    {
        $gift  = $this->pendingGift();
        $owner = $gift->celebration->user;

        // no session_id at all — the old code skipped verification entirely
        $this->get(route('gift.stripe.success', ['reference' => $gift->transaction_reference]))
            ->assertRedirect();

        $this->assertSame('pending', $gift->fresh()->payment_status);
        $this->assertSame(0.0, (float) $owner->fresh()->global_wallet_balance);
    }

    public function test_a_gift_is_not_paid_when_stripe_cannot_confirm_it(): void
    {
        $this->stripeAnswers(false);

        $gift  = $this->pendingGift();
        $owner = $gift->celebration->user;

        $this->get(route('gift.stripe.success', [
            'reference'  => $gift->transaction_reference,
            'session_id' => 'cs_test_whatever',
        ]))->assertRedirect();

        $this->assertSame('pending', $gift->fresh()->payment_status);
        $this->assertSame(0.0, (float) $owner->fresh()->global_wallet_balance);
    }

    public function test_a_gift_is_paid_and_credited_once_stripe_confirms(): void
    {
        $this->stripeAnswers(true);

        $gift  = $this->pendingGift();
        $owner = $gift->celebration->user;

        $this->get(route('gift.stripe.success', [
            'reference'  => $gift->transaction_reference,
            'session_id' => 'cs_test_good',
        ]))->assertRedirect();

        $this->assertSame('paid', $gift->fresh()->payment_status);
        $this->assertGreaterThan(0, (float) $owner->fresh()->global_wallet_balance);
    }

    public function test_an_unknown_reference_is_rejected(): void
    {
        $this->get(route('gift.stripe.success', [
            'reference'  => 'gift-does-not-exist',
            'session_id' => 'cs_test_good',
        ]))->assertRedirect();

        $this->assertSame(0, Gift::where('payment_status', 'paid')->count());
    }

    /** Replaying the success URL must not credit the owner twice. */
    public function test_replaying_the_callback_does_not_credit_twice(): void
    {
        $this->stripeAnswers(true);

        $gift = $this->pendingGift();
        $url  = route('gift.stripe.success', [
            'reference'  => $gift->transaction_reference,
            'session_id' => 'cs_test_good',
        ]);

        $this->get($url)->assertRedirect();
        $after = (float) $gift->celebration->user->fresh()->global_wallet_balance;

        $this->get($url)->assertRedirect();
        $this->assertSame($after, (float) $gift->celebration->user->fresh()->global_wallet_balance);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
