<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BankAccountController;
use App\Http\Controllers\Api\CelebrationController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GiftController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WishController;
use App\Http\Controllers\Api\WithdrawalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API
|--------------------------------------------------------------------------
|
| Consumed by the React Native client in celebrateMobile/. Stateless: every
| protected route authenticates with a Sanctum bearer token, so there is no
| session and no CSRF token to carry.
|
| Three tiers:
|   public          — no token at all
|   optional token  — works signed out, but a token unlocks more (the
|                     celebration page needs this: anyone may view it, only the
|                     owner sees the Settings tab)
|   auth:sanctum    — token required
|
*/

Route::prefix('v1')->group(function () {

    // ── Public ──────────────────────────────────────────────────────────────
    Route::post('auth/register',        [AuthController::class, 'register']);
    Route::post('auth/login',           [AuthController::class, 'login']);
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);

    Route::get('meta', function () {
        $currencies = collect(config('currency.currencies', []))
            ->map(fn ($c, $code) => [
                'code'   => $code,
                'symbol' => $c['symbol'] ?? $code,
                'name'   => $c['name'] ?? $code,
            ])
            ->values();

        return response()->json([
            'base_currency' => config('currency.base'),
            'currencies'    => $currencies,
            'celebration_types' => [
                ['value' => 'birthday',    'label' => 'Birthday'],
                ['value' => 'wedding',     'label' => 'Wedding'],
                ['value' => 'memorial',    'label' => 'Memorial'],
                ['value' => 'graduation',  'label' => 'Graduation'],
                ['value' => 'anniversary', 'label' => 'Anniversary'],
                ['value' => 'baby_shower', 'label' => 'Baby Shower'],
                ['value' => 'other',       'label' => 'Celebration'],
            ],
        ]);
    });

    /*
     | Optional token.
     |
     | These are the guest-capable flows. 'auth:sanctum' is NOT applied — the
     | controllers call $request->user() and branch on null themselves, exactly
     | as the web controllers branch on Auth::check().
     */
    Route::get('celebrations/{slug}',          [CelebrationController::class, 'show']);
    Route::get('celebrations/{slug}/comments', [CommentController::class, 'index']);
    Route::post('comments',                 [CommentController::class, 'store']);
    Route::post('comments/{comment}/react', [CommentController::class, 'react']);

    // Replies hang off registry items, not wall posts — comment_replies.wish_id
    // is a foreign key to wishes(id).
    Route::get('wishes/{wish}/replies',  [WishController::class, 'replies']);
    Route::post('wishes/{wish}/replies', [WishController::class, 'reply']);

    // Paying by card never requires an account — only a name and an email.
    Route::post('gifts/pay',                       [GiftController::class, 'initiatePayment']);
    Route::post('gifts/verify',                    [GiftController::class, 'verifyPayment']);
    Route::post('wishes/{wish}/contribute/pay',    [WishController::class, 'initiatePayment']);
    Route::post('wishes/contribute/verify',        [WishController::class, 'verifyContribution']);

    // ── Token required ──────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Account
        Route::get('auth/me',        [AuthController::class, 'me']);
        Route::post('auth/logout',   [AuthController::class, 'logout']);
        Route::patch('auth/profile', [AuthController::class, 'updateProfile']);
        Route::post('auth/avatar',   [AuthController::class, 'updateAvatar']);
        Route::put('auth/password',  [AuthController::class, 'updatePassword']);
        Route::post('auth/email/resend-verification', [AuthController::class, 'resendVerification']);
        Route::delete('auth/account', [AuthController::class, 'destroy']);

        // Dashboard — one endpoint per rail item
        Route::get('dashboard/summary',       [DashboardController::class, 'summary']);
        Route::get('dashboard/celebrations',  [DashboardController::class, 'celebrations']);
        Route::get('dashboard/upcoming',      [DashboardController::class, 'upcoming']);
        Route::get('dashboard/activity',      [DashboardController::class, 'activity']);
        Route::post('dashboard/activity/read', [DashboardController::class, 'markActivityRead']);
        Route::get('dashboard/discover',      [DashboardController::class, 'discover']);

        // Celebrations the caller owns
        Route::post('celebrations',   [CelebrationController::class, 'store']);
        Route::put('celebrations/{slug}',    [CelebrationController::class, 'update']);
        Route::delete('celebrations/{slug}', [CelebrationController::class, 'destroy']);
        Route::post('celebrations/{slug}/cover-photo', [CelebrationController::class, 'updateCoverPhoto']);
        Route::post('celebrations/{slug}/frame',       [CelebrationController::class, 'updateFrame']);
        Route::post('celebrations/{slug}/slug',        [CelebrationController::class, 'updateSlug']);
        Route::get('celebrations/{slug}/slug/check',   [CelebrationController::class, 'checkSlug']);
        Route::post('celebrations/{slug}/template',    [CelebrationController::class, 'applyTemplate']);
        Route::delete('celebrations/{slug}/template',  [CelebrationController::class, 'resetTemplate']);

        // Registry
        Route::post('wishes',          [WishController::class, 'store']);
        Route::delete('wishes/{wish}', [WishController::class, 'destroy']);
        Route::post('wishes/{wish}/contribute/wallet', [WishController::class, 'contributeFromWallet']);

        // Gifts out of the wallet
        Route::post('gifts/send', [GiftController::class, 'send']);

        // Money
        Route::get('wallet',                [WalletController::class, 'show']);
        Route::get('wallet/transactions',   [WalletController::class, 'transactions']);
        Route::post('wallet/fund',          [WalletController::class, 'fund']);
        Route::post('wallet/fund/verify',   [WalletController::class, 'verifyFunding']);

        Route::get('bank-accounts',       [BankAccountController::class, 'index']);
        Route::get('bank-accounts/banks', [BankAccountController::class, 'banks']);
        Route::post('bank-accounts/resolve', [BankAccountController::class, 'resolve'])
            ->middleware('throttle:30,1');
        Route::post('bank-accounts',   [BankAccountController::class, 'store']);
        Route::put('bank-accounts/{bankAccount}',    [BankAccountController::class, 'update']);
        Route::delete('bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy']);
        Route::patch('bank-accounts/{bankAccount}/default', [BankAccountController::class, 'setDefault']);

        Route::get('withdrawals',  [WithdrawalController::class, 'index']);
        Route::post('withdrawals', [WithdrawalController::class, 'store']);
    });
});
