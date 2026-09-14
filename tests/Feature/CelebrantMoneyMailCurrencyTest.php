<?php

namespace Tests\Feature;

use App\Mail\GiftReceivedMail;
use App\Models\Celebration;
use App\Models\EmailQueue;
use App\Models\Gift;
use App\Models\PlatformAvailableGift;
use App\Models\User;
use App\Models\Wish;
use App\Models\WishContribution;
use App\Services\PaymentSystem\PaymentFulfilmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The celebrant's money emails name the currency the money is actually in.
 *
 * The gift email printed its running total as "$… USD" whatever the gifts were
 * paid in, and a registry contribution sent the celebrant no email at all.
 */
class CelebrantMoneyMailCurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Celebration $celebration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'email'    => 'celebrant@example.com',
            'currency' => 'NGN',
            'country'  => 'Nigeria',
        ]);

        $this->celebration = Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $this->owner->id,
            'title'            => "Yemi's Birthday",
            'slug'             => 'yemi-naira-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Yemi',
            'status'           => 'published',
            'is_public'        => true,
        ]);
    }

    private function nairaGift(string $status = 'paid', float $amount = 8000): Gift
    {
        $platformGift = PlatformAvailableGift::create([
            'gift_name' => 'Bottle of Wine', 'gift_icon' => 'mdi-glass-wine',
            'gift_price' => 10, 'category' => 'small', 'status' => 'active',
        ]);

        return Gift::create([
            'celebration_id'        => $this->celebration->id,
            'platform_gift_id'      => $platformGift->id,
            'sender_name'           => 'Ada Guest',
            'sender_email'          => 'ada@example.com',
            'amount'                => $amount,
            'currency'              => 'NGN',
            'guest_currency'        => 'NGN',
            'conversion_rate'       => 1.0,
            'payment_method'        => 'card',
            'transaction_reference' => 'ps-' . Str::uuid(),
            'payment_status'        => $status,
            'is_anonymous'          => false,
        ]);
    }

    private function wish(): Wish
    {
        return Wish::create([
            'celebration_id' => $this->celebration->id,
            'name'           => 'Standing Mixer',
            'wish_type'      => 'cash',
            'target_amount'  => 50000,
            'current_amount' => 0,
            'currency'       => 'NGN',
            'status'         => 'active',
        ]);
    }

    private function nairaContribution(Wish $wish, string $status = 'pending', float $amount = 5000): WishContribution
    {
        return WishContribution::create([
            'wish_id'           => $wish->id,
            'celebration_id'    => $this->celebration->id,
            'contributor_name'  => 'Bola Guest',
            'contributor_email' => 'bola@example.com',
            'amount'            => $amount,
            'currency'          => 'NGN',
            'conversion_rate'   => 1.0,
            'original_amount'   => $amount,
            'contribution_type' => 'cash',
            'payment_reference' => 'wish-pay-' . Str::uuid(),
            'payment_status'    => $status,
            'is_anonymous'      => false,
        ]);
    }

    /* ── Gift email ─────────────────────────────────────────────────── */

    public function test_a_naira_gift_email_is_in_naira_throughout(): void
    {
        $gift = $this->nairaGift();

        $html = (new GiftReceivedMail($gift, $this->celebration))->render();

        $this->assertStringContainsString('8,000.00 NGN', $html);
        $this->assertStringNotContainsString('USD', $html);
    }

    public function test_the_gift_email_total_includes_registry_contributions(): void
    {
        $gift = $this->nairaGift();
        $this->nairaContribution($this->wish(), 'paid', 2000);

        $html = (new GiftReceivedMail($gift, $this->celebration))->render();

        $this->assertStringContainsString('10,000.00 NGN', $html);
        $this->assertStringContainsString('1 registry contribution', $html);
    }

    /* ── Contribution email ─────────────────────────────────────────── */

    public function test_a_card_contribution_emails_the_celebrant_in_naira(): void
    {
        $contribution = $this->nairaContribution($this->wish());

        app(PaymentFulfilmentService::class)->fulfilWishContribution($contribution->payment_reference);

        $mail = EmailQueue::where('type', 'contribution.received')->sole();

        $this->assertSame('celebrant@example.com', $mail->to_address);
        $this->assertStringContainsString('Standing Mixer', $mail->subject);
        $this->assertStringContainsString('5,000.00 NGN', $mail->body_html);
        $this->assertStringContainsString('Bola Guest', $mail->body_html);
        $this->assertStringNotContainsString('USD', $mail->body_html);
    }

    public function test_settling_a_contribution_twice_sends_one_email(): void
    {
        $contribution = $this->nairaContribution($this->wish());
        $service      = app(PaymentFulfilmentService::class);

        $service->fulfilWishContribution($contribution->payment_reference);
        $service->fulfilWishContribution($contribution->payment_reference);

        $this->assertSame(1, EmailQueue::where('type', 'contribution.received')->count());
    }

    public function test_a_wallet_contribution_in_the_app_emails_the_celebrant(): void
    {
        $wish        = $this->wish();
        $contributor = User::factory()->create([
            'currency'       => 'NGN',
            'country'        => 'Nigeria',
            'wallet_balance' => 20000,
        ]);

        Sanctum::actingAs($contributor);

        $this->postJson("/api/v1/wishes/{$wish->id}/contribute/wallet", [
            'amount'   => 3000,
            'currency' => 'NGN',
        ])->assertOk();

        $mail = EmailQueue::where('type', 'contribution.received')->sole();

        $this->assertSame('celebrant@example.com', $mail->to_address);
        $this->assertStringContainsString('3,000.00 NGN', $mail->body_html);
        $this->assertStringNotContainsString('USD', $mail->body_html);
    }
}
