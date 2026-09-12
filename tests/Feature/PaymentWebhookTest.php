<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\Gift;
use App\Models\PlatformAvailableGift;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\PaymentSystem\PaymentFulfilmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The webhook is the only notice of a payment we can rely on — a browser
 * redirect is optional. So it has to refuse forgeries and it has to be safe to
 * deliver twice, which gateways do routinely.
 */
class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['services.paystack.secret' => 'sk_test_webhook_secret']);
    }

    /**
     * `currency` is guarded on User, so it has to be forced — and it decides
     * whether the owner has a local wallet at all (a USD user has only the
     * global one, which is what DualWalletTest covers).
     */
    private function owner(string $currency = 'NGN'): User
    {
        $user = User::factory()->create(['wallet_balance' => 0, 'global_wallet_balance' => 0]);
        $user->forceFill(['currency' => $currency])->save();

        return $user->fresh();
    }

    private function signed(array $payload): array
    {
        $body = json_encode($payload);

        return [$body, hash_hmac('sha512', $body, config('services.paystack.secret'))];
    }

    private function postPaystack(array $payload, ?string $signature = null)
    {
        [$body, $valid] = $this->signed($payload);

        return $this->call(
            'POST',
            '/webhooks/paystack',
            [],
            [],
            [],
            ['HTTP_X-PAYSTACK-SIGNATURE' => $signature ?? $valid, 'CONTENT_TYPE' => 'application/json'],
            $body
        );
    }

    private function chargeSuccess(string $reference): array
    {
        return ['event' => 'charge.success', 'data' => ['reference' => $reference, 'status' => 'success']];
    }

    private function pendingGift(User $owner, float $amount = 5000, string $currency = 'NGN'): Gift
    {
        $celebration = Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $owner->id,
            'title'            => 'Webhook celebration',
            'slug'             => 'webhook-celebration-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Someone',
            'status'           => 'published',
            'is_public'        => true,
        ]);

        $platformGift = PlatformAvailableGift::create([
            'gift_name'      => 'Cake',
            'gift_price'     => 10.00,
            'gift_image_url' => 'gifts/cake.png',
            'gift_link_url'  => 'https://example.com/cake',
            'is_active'      => true,
        ]);

        return Gift::create([
            'celebration_id'        => $celebration->id,
            'platform_gift_id'      => $platformGift->id,
            'sender_name'           => 'A Guest',
            'sender_email'          => 'guest@example.com',
            'amount'                => $amount,
            'currency'              => $currency,
            'guest_currency'        => $currency,
            'conversion_rate'       => 1.0,
            'payment_method'        => 'card',
            'transaction_reference' => 'gift-pay-' . Str::uuid(),
            'payment_status'        => 'pending',
            'is_anonymous'          => false,
        ]);
    }

    public function test_an_unsigned_webhook_is_rejected_and_changes_nothing(): void
    {
        $gift = $this->pendingGift(User::factory()->create());

        $this->postPaystack($this->chargeSuccess($gift->transaction_reference), 'not-the-signature')
            ->assertStatus(401);

        $this->assertSame('pending', $gift->fresh()->payment_status);
    }

    public function test_a_signed_charge_success_fulfils_the_gift_and_credits_the_owner(): void
    {
        $owner = $this->owner('NGN');
        $gift  = $this->pendingGift($owner, 5000, 'NGN');

        $this->postPaystack($this->chargeSuccess($gift->transaction_reference))->assertOk();

        $this->assertSame('paid', $gift->fresh()->payment_status);
        $this->assertSame(5000.0, (float) $owner->fresh()->wallet_balance);
    }

    public function test_delivering_the_same_webhook_twice_credits_once(): void
    {
        $owner = $this->owner('NGN');
        $gift  = $this->pendingGift($owner, 5000, 'NGN');

        $payload = $this->chargeSuccess($gift->transaction_reference);

        $this->postPaystack($payload)->assertOk();
        $this->postPaystack($payload)->assertOk();
        $this->postPaystack($payload)->assertOk();

        $this->assertSame(5000.0, (float) $owner->fresh()->wallet_balance);
        // The receiving leg is referenced "{payment}-in-{gift id}": a basket
        // credits per gift, and wallet_transactions.reference is unique.
        $this->assertSame(1, WalletTransaction::where('reference', 'like', $gift->transaction_reference . '-in-%')->count());
    }

    public function test_a_usd_gift_lands_in_the_global_wallet(): void
    {
        $owner = $this->owner('NGN');
        $gift  = $this->pendingGift($owner, 25, 'USD');

        $this->postPaystack($this->chargeSuccess($gift->transaction_reference))->assertOk();

        $owner->refresh();
        $this->assertSame(25.0, (float) $owner->global_wallet_balance);
        $this->assertSame(0.0, (float) $owner->wallet_balance);
    }

    public function test_a_wallet_top_up_is_credited_from_its_webhook(): void
    {
        $user = $this->owner('NGN');

        $tx = WalletTransaction::create([
            'user_id'           => $user->id,
            'type'              => 'credit',
            'wallet_type'       => 'local',
            'amount'            => 12000,
            'currency'          => 'NGN',
            'original_amount'   => 12000,
            'original_currency' => 'NGN',
            'description'       => 'Wallet top-up',
            'reference'         => 'wf-' . Str::uuid(),
            'status'            => 'pending',
        ]);

        $this->postPaystack($this->chargeSuccess($tx->reference))->assertOk();

        $this->assertSame('completed', $tx->fresh()->status);
        $this->assertSame(12000.0, (float) $user->fresh()->wallet_balance);
    }

    public function test_an_unknown_reference_is_acknowledged_without_error(): void
    {
        // Retrying will not make the record exist; the reconciliation sweep is
        // the safety net, so the gateway is told to stop.
        $this->postPaystack($this->chargeSuccess('gift-pay-does-not-exist'))
            ->assertOk()
            ->assertSee(PaymentFulfilmentService::MISSING);
    }

    public function test_events_other_than_a_successful_charge_are_ignored(): void
    {
        $gift = $this->pendingGift(User::factory()->create());

        $this->postPaystack(['event' => 'charge.failed', 'data' => ['reference' => $gift->transaction_reference]])
            ->assertOk();

        $this->assertSame('pending', $gift->fresh()->payment_status);
    }

    public function test_the_webhook_route_is_exempt_from_csrf(): void
    {
        // Without the exemption in bootstrap/app.php every delivery would 419
        // and no payment would ever be confirmed.
        $this->postPaystack($this->chargeSuccess('gift-pay-nothing'))->assertStatus(200);
    }

    public function test_a_stripe_webhook_without_a_valid_signature_is_rejected(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $this->call(
            'POST',
            '/webhooks/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE-SIGNATURE' => 't=1,v1=nonsense', 'CONTENT_TYPE' => 'application/json'],
            json_encode(['type' => 'checkout.session.completed'])
        )->assertStatus(401);
    }
}
