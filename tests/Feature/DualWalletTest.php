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

    public function test_wallet_service_can_credit_and_debit_local_and_global_wallets()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'currency' => 'NGN',
            'wallet_balance' => 0.00,
            'global_wallet_balance' => 0.00,
        ]);

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
