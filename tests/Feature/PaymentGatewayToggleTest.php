<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Celebration;
use App\Models\Gift;
use App\Models\PlatformAvailableGift;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Wish;
use App\Models\WishContribution;
use App\Services\PaymentSystem\PaymentFulfilmentService;
use App\Services\PaymentSystem\PaystackService;
use App\Services\PaymentSystem\StripeService;
use App\Support\PaymentGateways;
use App\Support\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pausing a payment gateway from the admin panel.
 *
 * Two promises: a paused gateway starts no new checkout anywhere, and it stops
 * nothing that is already under way — money that has moved must still be
 * recorded, and a wallet gift never touches a gateway at all.
 */
class PaymentGatewayToggleTest extends TestCase
{
    use RefreshDatabase;

    private function celebration(?User $owner = null): Celebration
    {
        return Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => ($owner ?? User::factory()->create())->id,
            'title'            => "Yemi's Birthday",
            'slug'             => 'yemi-gateway-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Yemi',
            'status'           => 'published',
            'is_public'        => true,
        ]);
    }

    private function gift(): PlatformAvailableGift
    {
        return PlatformAvailableGift::create([
            'gift_name' => 'Warm Hug', 'gift_icon' => 'mdi-hand-heart',
            'gift_price' => 5, 'category' => 'small', 'status' => 'active',
        ]);
    }

    /** Pin where a guest appears to be, so the currency — and so the gateway — is known. */
    private function visitorFrom(string $country): void
    {
        config(['currency.fallback_country' => $country]);
    }

    private function mockGateways(): void
    {
        $this->mock(StripeService::class, fn ($m) => $m->shouldReceive('createCheckoutSession')->andReturn('https://checkout.stripe.com/stub'));
        $this->mock(PaystackService::class, fn ($m) => $m->shouldReceive('initTransaction')->andReturn([
            'authorization_url' => 'https://checkout.paystack.com/stub',
            'access_code'       => 'stub',
            'reference'         => 'stub-ref',
        ]));
    }

    private function guestGift(PlatformAvailableGift $gift, Celebration $celebration)
    {
        return $this->postJson(route('gift.payment.initiate'), [
            'platform_gift_id' => $gift->id,
            'celebration_id'   => $celebration->id,
            'guest_name'       => 'Ada Guest',
            'guest_email'      => 'ada@example.com',
        ]);
    }

    /* ── The switch ─────────────────────────────────────────────────── */

    public function test_a_gateway_nobody_has_configured_is_on(): void
    {
        config(['services.paystack.enabled' => null]);

        $this->assertTrue(PaymentGateways::isActive('paystack'));
    }

    public function test_naira_goes_through_paystack_and_dollars_through_stripe(): void
    {
        $this->assertSame('paystack', PaymentGateways::forCurrency('NGN'));
        $this->assertSame('stripe', PaymentGateways::forCurrency('usd'));
    }

    public function test_an_admin_can_switch_a_gateway_off_and_on(): void
    {
        $admin = Admin::create([
            'name' => 'Ops', 'email' => 'ops@celebratemi.com',
            'password' => Hash::make('correct-horse-battery-1'),
            'is_super' => false, 'status' => 'active',
        ]);
        $admin->syncPermissions(['settings.manage']);
        $repo = app(SettingsRepository::class);

        // Off: the hidden 0 is all that arrives when the box is unticked.
        $this->actingAs($admin->fresh('permissions'), 'admin')
            ->put(route('admin.settings.update', 'payments'), ['settings' => ['paystack_enabled' => '0']])
            ->assertRedirect();

        $repo->flush();
        config($repo->overlay());
        $this->assertFalse(PaymentGateways::isActive('paystack'));

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update', 'payments'), ['settings' => ['paystack_enabled' => '1']]);

        $repo->flush();
        config($repo->overlay());
        $this->assertTrue(PaymentGateways::isActive('paystack'));
    }

    public function test_the_settings_page_shows_the_switches(): void
    {
        $admin = Admin::create([
            'name' => 'Ops', 'email' => 'ops2@celebratemi.com',
            'password' => Hash::make('correct-horse-battery-1'),
            'is_super' => false, 'status' => 'active',
        ]);
        $admin->syncPermissions(['settings.manage']);

        $this->actingAs($admin->fresh('permissions'), 'admin')
            ->get(route('admin.settings', 'payments'))
            ->assertOk()
            ->assertSee('Paystack checkout')
            ->assertSee('Stripe checkout')
            ->assertSee('name="settings[paystack_enabled]" value="0"', false);
    }

    /* ── Checkouts refuse ───────────────────────────────────────────── */

    public function test_a_paused_gateway_starts_no_gift_checkout_and_leaves_nothing_behind(): void
    {
        $this->mockGateways();
        $this->visitorFrom('NG');
        config(['services.paystack.enabled' => false]);

        $this->guestGift($this->gift(), $this->celebration())
            ->assertStatus(503)
            ->assertJson(['success' => false, 'code' => 'gateway_inactive']);

        // Refused before anything was written — no pending row to clean up.
        $this->assertSame(0, Gift::count());
    }

    public function test_pausing_one_gateway_leaves_the_other_open(): void
    {
        $this->mockGateways();
        $this->visitorFrom('US');
        config(['services.paystack.enabled' => false]);

        $this->guestGift($this->gift(), $this->celebration())
            ->assertOk()
            ->assertJson(['success' => true, 'provider' => 'stripe']);
    }

    public function test_a_paused_gateway_takes_no_registry_contribution(): void
    {
        $this->mockGateways();
        config(['services.paystack.enabled' => false]);

        $celebration = $this->celebration();
        $wish = Wish::create([
            'celebration_id' => $celebration->id, 'name' => 'Air Max',
            'wish_type' => 'cash', 'target_amount' => 100, 'amount_base' => 100,
            'base_currency' => 'USD', 'current_amount' => 0, 'currency' => 'USD',
            'status' => 'active',
        ]);

        $this->postJson(route('wish.contribute.pay', $wish), [
            'amount' => 5000, 'currency' => 'NGN',
            'guest_name' => 'Ada Guest', 'guest_email' => 'ada@example.com',
        ])->assertStatus(503)->assertJson(['code' => 'gateway_inactive']);

        $this->assertSame(0, WishContribution::count());
    }

    public function test_a_paused_gateway_takes_no_wallet_top_up(): void
    {
        $this->mockGateways();
        config(['services.stripe.enabled' => false]);

        $user = User::factory()->create();
        $user->forceFill(['currency' => 'USD'])->save();

        $this->actingAs($user)
            ->postJson(route('wallet.fund'), ['amount' => 50, 'wallet_type' => 'global'])
            ->assertStatus(503)
            ->assertJson(['code' => 'gateway_inactive']);

        $this->assertSame(0, WalletTransaction::where('status', 'pending')->count());
    }

    public function test_every_checkout_start_asks_before_charging(): void
    {
        // Six places start a checkout; a seventh added later without the guard
        // would quietly ignore the switch. This keeps the list honest.
        foreach ([
            'GiftController', 'WishContributionController', 'WalletFundingController',
            'Api/GiftController', 'Api/WalletController', 'Api/WishController',
        ] as $controller) {
            $source = file_get_contents(app_path("Http/Controllers/{$controller}.php"));

            $this->assertStringContainsString(
                'PaymentGateways::refuseCheckout',
                $source,
                "{$controller} starts a checkout without checking the gateway is switched on"
            );
        }
    }

    /* ── Nothing already under way stops ───────────────────────────── */

    public function test_a_wallet_gift_needs_no_gateway(): void
    {
        config(['services.paystack.enabled' => false, 'services.stripe.enabled' => false]);

        $sender = User::factory()->create();
        $sender->forceFill(['currency' => 'USD', 'global_wallet_balance' => 100])->save();

        $this->actingAs($sender)->postJson(route('gift.send'), [
            'platform_gift_id' => $this->gift()->id,
            'celebration_id'   => $this->celebration()->id,
        ])->assertOk()->assertJson(['success' => true]);
    }

    public function test_a_payment_that_already_happened_still_settles(): void
    {
        // Pausing stops new checkouts. Refusing to record money that has
        // already moved would be far worse than the pause itself.
        config(['services.paystack.enabled' => false]);

        $celebration = $this->celebration();
        $reference   = 'ps-' . Str::uuid();

        Gift::create([
            'celebration_id' => $celebration->id, 'platform_gift_id' => $this->gift()->id,
            'quantity' => 1, 'sender_name' => 'Ada', 'sender_email' => 'ada@example.com',
            'amount' => 8000, 'currency' => 'NGN', 'guest_currency' => 'NGN', 'conversion_rate' => 1.0,
            'payment_method' => 'card', 'transaction_reference' => $reference,
            'payment_status' => 'pending', 'is_anonymous' => false,
        ]);

        $this->assertSame(PaymentFulfilmentService::DONE, app(PaymentFulfilmentService::class)->fulfilGift($reference));
    }

    /* ── What the visitor sees ──────────────────────────────────────── */

    public function test_the_gift_picker_says_so_instead_of_offering_a_card_button(): void
    {
        $this->gift();
        $this->visitorFrom('NG');
        config(['services.paystack.enabled' => false]);

        $this->get(route('celebrations.show', $this->celebration()->slug))
            ->assertOk()
            ->assertSee('Card payments in NGN are paused right now')
            ->assertDontSee('Pay &amp; send gift', false);
    }

    public function test_the_gift_picker_offers_the_card_button_when_the_gateway_is_on(): void
    {
        $this->gift();
        $this->visitorFrom('NG');

        $this->get(route('celebrations.show', $this->celebration()->slug))
            ->assertOk()
            ->assertSee('Pay &amp; send gift', false)
            ->assertDontSee('are paused right now');
    }
}
