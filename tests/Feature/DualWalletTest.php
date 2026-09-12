<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PaymentSystem\WalletService;
use App\Services\PaymentSystem\CheckoutService;
use App\Services\PaymentSystem\StripeService;
use App\Services\PaymentSystem\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DualWalletTest extends TestCase
{
    use RefreshDatabase;

    /**
     * `currency` is guarded on User and set from the registration IP by
     * UserObserver, so tests have to force it after the fact.
     */
    private function user(string $currency, array $attributes = []): User
    {
        /** @var User $user */
        $user = User::factory()->create($attributes + [
            'wallet_balance'        => 0.00,
            'global_wallet_balance' => 0.00,
        ]);

        $user->forceFill(['currency' => $currency])->save();

        return $user;
    }

    public function test_wallet_service_can_credit_and_debit_local_and_global_wallets()
    {
        $user = $this->user('NGN');

        $walletService = app(WalletService::class);

        // Test Local Wallet Credit
        $walletService->credit($user, 1000.00, 'Refund local NGN', null, null, null, null, 'local');
        $user->refresh();
        $this->assertEquals(1000.00, $walletService->balance($user, 'local'));
        $this->assertEquals(0.00, $walletService->balance($user, 'global'));

        // Test Global Wallet Credit
        $walletService->credit($user, 50.00, 'Gift USD', null, null, null, null, 'global');
        $user->refresh();
        $this->assertEquals(1000.00, $walletService->balance($user, 'local'));
        $this->assertEquals(50.00, $walletService->balance($user, 'global'));

        // Test Local Wallet Debit
        $walletService->debit($user, 400.00, 'Spent NGN', null, null, null, null, 'local');
        $user->refresh();
        $this->assertEquals(600.00, $walletService->balance($user, 'local'));

        // Test Global Wallet Debit
        $walletService->debit($user, 20.00, 'Spent USD', null, null, null, null, 'global');
        $user->refresh();
        $this->assertEquals(30.00, $walletService->balance($user, 'global'));
    }

    /**
     * The local wallet is a workaround for countries that can't check out in
     * USD. Where USD works there is only the global wallet, so every write
     * lands there however the caller labels it — the split used to let a USD
     * user check the local balance and debit the global one.
     */
    public function test_a_usd_user_has_only_the_global_wallet()
    {
        $user = $this->user('USD');

        $walletService = app(WalletService::class);

        $this->assertFalse($walletService->hasLocalWallet($user));
        $this->assertSame('global', $walletService->resolveWalletType($user, 'local'));

        // Credited as "local", it still lands in the one wallet they have.
        $walletService->credit($user, 100.00, 'Top-up', null, null, null, null, 'local');
        $user->refresh();

        $this->assertEquals(0.00, (float) $user->wallet_balance);
        $this->assertEquals(100.00, (float) $user->global_wallet_balance);
        $this->assertEquals(100.00, $walletService->balance($user, 'local'));
        $this->assertEquals(100.00, $walletService->balance($user, 'global'));

        // The balance check and the debit now agree: no double-spend.
        $this->assertFalse($walletService->hasSufficientBalance($user, 150.00, 'local'));

        $walletService->debit($user, 60.00, 'Withdrawal', null, null, null, null, 'local');
        $user->refresh();

        $this->assertEquals(0.00, (float) $user->wallet_balance);
        $this->assertEquals(40.00, (float) $user->global_wallet_balance);
    }

    public function test_a_local_currency_user_keeps_both_wallets()
    {
        $user = $this->user('NGN');

        $walletService = app(WalletService::class);

        $this->assertTrue($walletService->hasLocalWallet($user));
        $this->assertSame('local', $walletService->resolveWalletType($user, 'local'));
        $this->assertSame('global', $walletService->resolveWalletType($user, 'global'));
        // USD money always lands global, whatever the caller asked for.
        $this->assertSame('global', $walletService->resolveWalletType($user, 'local', 'USD'));
    }

    public function test_a_usd_user_withdrawing_from_local_cannot_overdraw_the_global_wallet()
    {
        $user = $this->user('USD', ['global_wallet_balance' => 25.00]);

        $bank = \App\Models\BankAccount::create([
            'is_verified'    => true,
            'user_id'        => $user->id,
            'bank_name'      => 'Chase',
            'account_number' => '0123456789',
            'account_name'   => 'Test User',
            'is_default'     => true,
        ]);

        $this->actingAs($user)
            ->post(route('withdrawals.store'), [
                'amount'          => 50,
                'bank_account_id' => $bank->id,
                'wallet_type'     => 'local',
            ])
            ->assertSessionHas('error', 'Insufficient wallet balance.');

        $user->refresh();
        $this->assertEquals(25.00, (float) $user->global_wallet_balance);
        $this->assertEquals(0.00, (float) $user->wallet_balance);

        // A withdrawal within the balance is booked against the global wallet.
        $this->actingAs($user)
            ->post(route('withdrawals.store'), [
                'amount'          => 10,
                'bank_account_id' => $bank->id,
                'wallet_type'     => 'local',
            ])
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals(15.00, (float) $user->global_wallet_balance);
        $this->assertEquals(0.00, (float) $user->wallet_balance);
        $this->assertDatabaseHas('withdrawals', [
            'user_id'     => $user->id,
            'wallet_type' => 'global',
            'currency'    => 'USD',
        ]);
    }

    public function test_wallet_api_reports_that_a_usd_user_has_no_local_wallet()
    {
        $usd = $this->user('USD', ['wallet_balance' => 12.00, 'global_wallet_balance' => 30.00]);

        $this->actingAs($usd, 'sanctum')
            ->getJson('/api/v1/wallet')
            ->assertOk()
            ->assertJsonPath('has_local_wallet', false)
            ->assertJsonPath('local', 0)
            ->assertJsonPath('global', 30);

        $ngn = $this->user('NGN', ['wallet_balance' => 5000.00, 'global_wallet_balance' => 30.00]);

        $this->actingAs($ngn, 'sanctum')
            ->getJson('/api/v1/wallet')
            ->assertOk()
            ->assertJsonPath('has_local_wallet', true)
            ->assertJsonPath('local', 5000);
    }

    public function test_checkout_service_routes_payments_without_conversion()
    {
        // Mock StripeService
        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('createPaymentIntent')
                ->once()
                ->with(10.00, 'USD', ['email' => 'test@example.com'])
                ->andReturn('mock_client_secret');
        });

        // Mock PaystackService
        $this->mock(PaystackService::class, function ($mock) {
            $mock->shouldReceive('initTransaction')
                ->once()
                ->with(5000.00, 'NGN', ['email' => 'test@example.com'])
                ->andReturn([
                    'authorization_url' => 'https://checkout.paystack.com/mock',
                    'reference' => 'mock_ref',
                ]);
        });

        /** @var User $user */
        $user = User::factory()->create(['currency' => 'NGN']);
        $checkoutService = app(CheckoutService::class);

        // Test NGN routing -> Paystack (Mocked)
        $resLocal = $checkoutService->process($user, 5000.00, 'NGN', ['email' => 'test@example.com']);
        $this->assertEquals('paystack', $resLocal['provider']);
        $this->assertEquals('NGN', $resLocal['currency']);
        $this->assertEquals('https://checkout.paystack.com/mock', $resLocal['authorization_url']);
        $this->assertEquals('mock_ref', $resLocal['reference']);

        // Test USD routing -> Stripe (Mocked)
        $resGlobal = $checkoutService->process($user, 10.00, 'USD', ['email' => 'test@example.com']);
        $this->assertEquals('stripe', $resGlobal['provider']);
        $this->assertEquals('USD', $resGlobal['currency']);
        $this->assertEquals('mock_client_secret', $resGlobal['client_secret']);
    }

    public function test_user_can_post_video_wish()
    {
        $user = User::factory()->create();
        $celebration = \App\Models\Celebration::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'title' => 'Test Celebration',
            'slug' => 'test-celebration',
            'celebration_type' => 'birthday',
            'celebrant_name' => 'John Doe',
        ]);

        \Illuminate\Support\Facades\Storage::fake('public');

        $videoFile = \Illuminate\Http\UploadedFile::fake()->create('wish-video.webm', 500, 'video/webm');

        $response = $this->actingAs($user)
            ->postJson(route('celebration.comment.store'), [
                'celebration_id' => $celebration->id,
                'comment' => 'Happy Birthday!',
                'video' => $videoFile,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('comments', [
            'celebration_id' => $celebration->id,
            'media_type' => 'video',
        ]);

        $comment = \App\Models\Comment::first();
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($comment->media_url);
    }
}
