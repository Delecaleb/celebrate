<?php

namespace Tests\Feature;

use App\Mail\GiftReceivedMail;
use App\Mail\GiftSentMail;
use App\Models\Celebration;
use App\Models\Comment;
use App\Models\Gift;
use App\Models\PlatformAvailableGift;
use App\Models\User;
use App\Services\PaymentSystem\PaymentFulfilmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Both sides of a gift hear about it: the celebrant that money arrived, and
 * the giver that their payment went through. Most givers have no account, so
 * their receipt is the only record they keep of it.
 */
class GiftNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function celebration(): Celebration
    {
        return Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => User::factory()->create(['email' => 'celebrant@example.com'])->id,
            'title'            => "Yemi's Birthday",
            'slug'             => 'yemi-notify-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Yemi',
            'status'           => 'published',
            'is_public'        => true,
        ]);
    }

    private function paidGift(Celebration $celebration, string $reference, ?string $senderEmail = 'ada@example.com'): Gift
    {
        $platformGift = PlatformAvailableGift::create([
            'gift_name' => 'Bottle of Wine', 'gift_icon' => 'mdi-glass-wine',
            'gift_price' => 10, 'category' => 'small', 'status' => 'active',
        ]);

        return Gift::create([
            'celebration_id'        => $celebration->id,
            'platform_gift_id'      => $platformGift->id,
            'sender_name'           => 'Ada Guest',
            'sender_email'          => $senderEmail,
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

    public function test_both_sides_are_told_when_a_gift_settles(): void
    {
        Mail::fake();

        $celebration = $this->celebration();
        $reference   = 'ps-' . Str::uuid();
        $this->paidGift($celebration, $reference);

        app(PaymentFulfilmentService::class)->fulfilGift($reference);

        Mail::assertQueued(GiftReceivedMail::class, fn ($mail) => $mail->hasTo('celebrant@example.com'));
        Mail::assertQueued(GiftSentMail::class, fn ($mail) => $mail->hasTo('ada@example.com'));
    }

    public function test_settling_twice_does_not_send_the_mail_twice(): void
    {
        Mail::fake();

        $celebration = $this->celebration();
        $reference   = 'ps-' . Str::uuid();
        $this->paidGift($celebration, $reference);

        $service = app(PaymentFulfilmentService::class);
        $service->fulfilGift($reference);
        $service->fulfilGift($reference);

        Mail::assertQueuedCount(2);   // one each, not two each
    }

    public function test_a_giver_without_an_email_still_settles(): void
    {
        Mail::fake();

        $celebration = $this->celebration();
        $reference   = 'ps-' . Str::uuid();
        $gift        = $this->paidGift($celebration, $reference, null);

        $status = app(PaymentFulfilmentService::class)->fulfilGift($reference);

        $this->assertSame(PaymentFulfilmentService::DONE, $status);
        $this->assertSame('paid', $gift->fresh()->payment_status);
        Mail::assertNotQueued(GiftSentMail::class);
        Mail::assertQueued(GiftReceivedMail::class);
    }

    public function test_the_receipt_shows_the_currency_actually_charged(): void
    {
        // A naira gift used to be emailed as dollars.
        $celebration = $this->celebration();
        $gift        = $this->paidGift($celebration, 'ps-' . Str::uuid());

        $html = (new GiftSentMail($gift, $celebration))->render();

        $this->assertStringContainsString('NGN', $html);
        $this->assertStringNotContainsString('$8,000.00 USD', $html);
        $this->assertStringContainsString($gift->transaction_reference, $html);
    }

    public function test_a_wish_from_someone_not_signed_in_is_posted_as_anonymous(): void
    {
        $celebration = $this->celebration();

        $this->postJson(route('celebration.comment.store'), [
            'celebration_id' => $celebration->id,
            'comment'        => 'Happy birthday Yemi!',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame('Anonymous', Comment::sole()->guest_name);
    }
}
