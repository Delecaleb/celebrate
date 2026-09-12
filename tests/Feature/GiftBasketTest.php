<?php

namespace Tests\Feature;

use App\Mail\GiftReceivedMail;
use App\Mail\GiftSentMail;
use App\Models\Celebration;
use App\Models\Gift;
use App\Models\PlatformAvailableGift;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\PaymentSystem\PaymentFulfilmentService;
use App\Services\PaymentSystem\PaystackService;
use App\Services\PaymentSystem\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Sending several different gifts in one payment.
 *
 * A basket is one row per gift sharing one transaction reference. That is what
 * lets the wall count each gift separately while the giver is charged, mailed
 * and settled exactly once.
 */
class GiftBasketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A guest's currency comes from where they are, and the app falls back
        // to Nigeria. These assertions are about arithmetic, not conversion,
        // so pin the visitor to the base currency.
        config(['currency.fallback_country' => 'US']);
    }

    private function celebration(?User $owner = null): Celebration
    {
        return Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => ($owner ?? User::factory()->create(['email' => 'owner@example.com']))->id,
            'title'            => "Yemi's Birthday",
            'slug'             => 'yemi-basket-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Yemi',
            'status'           => 'published',
            'is_public'        => true,
        ]);
    }

    /** @return array<int, PlatformAvailableGift> */
    private function catalogue(): array
    {
        return [
            PlatformAvailableGift::create(['gift_name' => 'A Wink', 'gift_icon' => 'mdi-emoticon-wink',
                'gift_price' => 1, 'category' => 'small', 'status' => 'active']),
            PlatformAvailableGift::create(['gift_name' => 'Warm Hug', 'gift_icon' => 'mdi-hand-heart',
                'gift_price' => 2, 'category' => 'small', 'status' => 'active']),
            PlatformAvailableGift::create(['gift_name' => 'Birthday Cake', 'gift_icon' => 'mdi-cake',
                'gift_price' => 5, 'category' => 'party', 'status' => 'active']),
        ];
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

    public function test_several_gifts_become_one_payment(): void
    {
        $this->fakeCheckout();
        $celebration           = $this->celebration();
        [$wink, $hug, $cake]   = $this->catalogue();

        $this->postJson(route('gift.payment.initiate'), [
            'celebration_id' => $celebration->id,
            'guest_name'     => 'Ada Guest',
            'guest_email'    => 'ada@example.com',
            'items'          => [
                ['platform_gift_id' => $wink->id, 'quantity' => 2],
                ['platform_gift_id' => $hug->id,  'quantity' => 1],
                ['platform_gift_id' => $cake->id, 'quantity' => 3],
            ],
        ])->assertOk()->assertJson(['success' => true]);

        $gifts = Gift::all();

        $this->assertCount(3, $gifts);
        // One reference is what makes it one basket.
        $this->assertCount(1, $gifts->pluck('transaction_reference')->unique());
        // 1×2 + 2×1 + 5×3 = 19
        $this->assertEqualsWithDelta(19.0, (float) $gifts->sum('amount'), 0.01);
    }

    public function test_the_same_gift_twice_in_one_basket_is_folded_together(): void
    {
        $this->fakeCheckout();
        $celebration = $this->celebration();
        [$wink]      = $this->catalogue();

        $this->postJson(route('gift.payment.initiate'), [
            'celebration_id' => $celebration->id,
            'guest_name'     => 'Ada Guest',
            'guest_email'    => 'ada@example.com',
            'items'          => [
                ['platform_gift_id' => $wink->id, 'quantity' => 2],
                ['platform_gift_id' => $wink->id, 'quantity' => 3],
            ],
        ])->assertOk();

        // One gift, one row, five of them — not two rows for the same gift.
        $this->assertCount(1, Gift::all());
        $this->assertSame(5, Gift::sole()->quantity);
    }

    public function test_a_basket_settles_every_line_at_once(): void
    {
        Mail::fake();

        $owner       = User::factory()->create(['email' => 'owner@example.com']);
        $celebration = $this->celebration($owner);
        [$wink, $hug] = $this->catalogue();
        $reference   = 'ps-' . Str::uuid();

        foreach ([[$wink, 2, 2.0], [$hug, 1, 2.0]] as [$platform, $qty, $amount]) {
            Gift::create([
                'celebration_id'        => $celebration->id,
                'platform_gift_id'      => $platform->id,
                'quantity'              => $qty,
                'sender_name'           => 'Ada Guest',
                'sender_email'          => 'ada@example.com',
                'amount'                => $amount,
                'currency'              => 'USD',
                'guest_currency'        => 'USD',
                'conversion_rate'       => 1.0,
                'payment_method'        => 'card',
                'transaction_reference' => $reference,
                'payment_status'        => 'pending',
                'is_anonymous'          => false,
            ]);
        }

        $status = app(PaymentFulfilmentService::class)->fulfilGift($reference);

        $this->assertSame(PaymentFulfilmentService::DONE, $status);
        $this->assertSame(0, Gift::where('payment_status', 'pending')->count());

        // Credited per line, so the ledger names each gift.
        $this->assertSame(2, WalletTransaction::where('type', 'credit')->count());
        $this->assertEqualsWithDelta(4.0, (float) $owner->fresh()->global_wallet_balance, 0.01);

        // One mail each way for the basket, not one per line.
        Mail::assertQueuedCount(2);
        Mail::assertQueued(GiftReceivedMail::class);
        Mail::assertQueued(GiftSentMail::class);
    }

    public function test_settling_a_basket_twice_pays_out_once(): void
    {
        Mail::fake();

        $owner       = User::factory()->create();
        $celebration = $this->celebration($owner);
        [$wink, $hug] = $this->catalogue();
        $reference   = 'ps-' . Str::uuid();

        foreach ([$wink, $hug] as $platform) {
            Gift::create([
                'celebration_id'        => $celebration->id,
                'platform_gift_id'      => $platform->id,
                'quantity'              => 1,
                'sender_name'           => 'Ada Guest',
                'sender_email'          => 'ada@example.com',
                'amount'                => 5,
                'currency'              => 'USD',
                'guest_currency'        => 'USD',
                'conversion_rate'       => 1.0,
                'payment_method'        => 'card',
                'transaction_reference' => $reference,
                'payment_status'        => 'pending',
                'is_anonymous'          => false,
            ]);
        }

        $service = app(PaymentFulfilmentService::class);

        $this->assertSame(PaymentFulfilmentService::DONE, $service->fulfilGift($reference));
        // The webhook racing the browser is the normal case, not the odd one.
        $this->assertSame(PaymentFulfilmentService::ALREADY, $service->fulfilGift($reference));

        $this->assertSame(2, WalletTransaction::where('type', 'credit')->count());
        $this->assertEqualsWithDelta(10.0, (float) $owner->fresh()->global_wallet_balance, 0.01);
    }

    public function test_a_wallet_basket_debits_the_total_once(): void
    {
        $owner       = User::factory()->create();
        $celebration = $this->celebration($owner);
        [$wink, $hug, $cake] = $this->catalogue();

        $sender = User::factory()->create();
        $sender->forceFill(['currency' => 'USD', 'global_wallet_balance' => 100])->save();

        $this->actingAs($sender)->postJson(route('gift.send'), [
            'celebration_id' => $celebration->id,
            'items'          => [
                ['platform_gift_id' => $wink->id, 'quantity' => 2],
                ['platform_gift_id' => $cake->id, 'quantity' => 1],
            ],
        ])->assertOk()->assertJson(['success' => true]);

        // 1×2 + 5×1 = 7
        $this->assertEqualsWithDelta(93.0, (float) $sender->fresh()->global_wallet_balance, 0.01);
        $this->assertEqualsWithDelta(7.0, (float) $owner->fresh()->global_wallet_balance, 0.01);
        $this->assertSame(1, WalletTransaction::where('type', 'debit')->count());
    }

    public function test_the_wall_lists_each_gift_in_the_basket(): void
    {
        $celebration         = $this->celebration();
        [$wink, $hug, $cake] = $this->catalogue();
        $reference           = 'ps-' . Str::uuid();

        foreach ([[$wink, 2], [$hug, 1], [$cake, 3]] as [$platform, $qty]) {
            Gift::create([
                'celebration_id'        => $celebration->id,
                'platform_gift_id'      => $platform->id,
                'quantity'              => $qty,
                'sender_name'           => 'Ada',
                'sender_email'          => 'ada@example.com',
                'amount'                => 5,
                'currency'              => 'USD',
                'guest_currency'        => 'USD',
                'conversion_rate'       => 1.0,
                'payment_method'        => 'card',
                'transaction_reference' => $reference,
                'payment_status'        => 'paid',
                'is_anonymous'          => false,
            ]);
        }

        $wall = $celebration->fresh()->load('gifts.platformGift')->sidebarGifts();

        $this->assertCount(3, $wall);
        $this->assertSame(
            ['Birthday Cake' => 3, 'A Wink' => 2, 'Warm Hug' => 1],
            $wall->mapWithKeys(fn ($i) => [$i->gift->gift_name => $i->count])->all()
        );
    }

    public function test_a_basket_naming_no_gift_is_refused(): void
    {
        $celebration = $this->celebration();

        $this->postJson(route('gift.payment.initiate'), [
            'celebration_id' => $celebration->id,
            'guest_name'     => 'Ada Guest',
            'guest_email'    => 'ada@example.com',
        ])->assertStatus(422);

        $this->assertSame(0, Gift::count());
    }

    public function test_the_older_single_gift_shape_still_works(): void
    {
        // The mobile client and every existing link still send this.
        $this->fakeCheckout();
        $celebration = $this->celebration();
        [$wink]      = $this->catalogue();

        $this->postJson(route('gift.payment.initiate'), [
            'platform_gift_id' => $wink->id,
            'celebration_id'   => $celebration->id,
            'quantity'         => 4,
            'guest_name'       => 'Ada Guest',
            'guest_email'      => 'ada@example.com',
        ])->assertOk();

        $this->assertSame(4, Gift::sole()->quantity);
    }
}
