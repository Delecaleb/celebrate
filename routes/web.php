<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CelebrationController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\WalletFundingController;

/*
| Public marketing site. These share one shell (layouts.marketing) and are
| navigated client-side by resources/js/modules/pageRouter.js.
*/
Route::get('/',             [MainController::class, 'home'])->name('home');
Route::get('/features',     [MainController::class, 'features'])->name('features');
Route::get('/how-it-works', [MainController::class, 'howItWorks'])->name('how-it-works');
Route::get('/pricing',      [MainController::class, 'pricing'])->name('pricing');
Route::get('/stories',      [MainController::class, 'stories'])->name('stories');

Route::post('/create-celebration', [CelebrationController::class, 'store'])->name('celebrations.store');
Route::get('/celebration/{slug}', [CelebrationController::class, 'show'])->name('celebrations.show');

/*
| Hands a payment gateway's redirect back to the mobile app. Paystack and Stripe
| both refuse to redirect to a custom scheme, so they are pointed here and this
| 302s to celebratemi://. Verifies nothing — see PaymentBridgeController.
*/
Route::get('/payments/bridge', \App\Http\Controllers\PaymentBridgeController::class)
    ->name('payments.bridge');
/*
| Signed-in dashboard. Every rail menu item is its own URL; they share one shell
| (layouts.dashboard) and are navigated client-side by the same pageRouter.js
| the marketing site uses.
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard',             [DashboardController::class, 'events'])->name('dashboard');
    Route::get('/dashboard/upcoming',    [DashboardController::class, 'upcoming'])->name('dashboard.upcoming');
    Route::get('/dashboard/activity',    [DashboardController::class, 'activity'])->name('dashboard.activity');
    Route::get('/dashboard/wallet',      [DashboardController::class, 'wallet'])->name('dashboard.wallet');
    Route::get('/dashboard/bank',        [DashboardController::class, 'bank'])->name('dashboard.bank');
    Route::get('/dashboard/discover',    [DashboardController::class, 'discover'])->name('dashboard.discover');

    // Clears the unread badge in the rail. A plain POST, so the shell (which
    // renders the badge outside #view) is rebuilt by the redirect.
    Route::post('/dashboard/activity/read', [DashboardController::class, 'markActivityRead'])->name('dashboard.activity.read');
});
Route::middleware(['auth'])->group(function () {
    Route::get('/bulk-upload-celebrants/form',    [\App\Http\Controllers\CelebrantController::class, 'showBulkUploadForm'])->name('celebrant.bulkUploadForm');
    Route::post('/bulk-upload-celebrants/preview', [\App\Http\Controllers\CelebrantController::class, 'previewBulkUpload'])->name('celebrant.bulkUpload.preview');
    Route::post('/bulk-upload-celebrants',         [\App\Http\Controllers\CelebrantController::class, 'bulkUpload'])->name('celebrant.bulkUpload');
});

// celebrant routes
Route::prefix('celebrant')->group(function () {
    Route::post('/create-wishes', [CelebrationController::class, 'createWishes'])->name('celebrant.create-wishes');
    Route::delete('/wishes/{wish}', [CelebrationController::class, 'destroyWish'])->name('celebrant.wish.destroy');
    // No separate edit screen — page details live in the Settings tab on the
    // celebration page itself, saved through celebrant.update below.
    Route::put('/{slug}', [CelebrationController::class, 'update'])->name('celebrant.update');
    Route::delete('/{slug}', [CelebrationController::class, 'destroy'])->name('celebrant.destroy');
    Route::post('/{id}/cover-photo', [CelebrationController::class, 'updateCoverPhoto'])->name('celebrant.update-cover');
    Route::post('/{id}/frame', [CelebrationController::class, 'updateFrame'])->name('celebrant.update-frame');
    Route::post('/{id}/slug',  [CelebrationController::class, 'updateSlug'])->name('celebrant.update-slug');
    Route::get('/{id}/slug/check', [CelebrationController::class, 'checkSlug'])->name('celebrant.check-slug');
});

//comment routes
Route::prefix('comment')->group(function () {
    Route::post('/store', [CelebrationController::class, 'storeComment'])->name('celebration.comment.store');
    Route::post('/reply', [CelebrationController::class, 'storeReply'])->name('celebration.reply.store');
    Route::post('/react', [CelebrationController::class, 'toggleReaction'])->name('celebration.react');
});

// template routes (auth required — only owner can apply)
Route::middleware('auth')->group(function () {
    Route::post('/celebrations/{celebration}/template', [TemplateController::class, 'apply'])->name('celebration.template.apply');
    Route::delete('/celebrations/{celebration}/template', [TemplateController::class, 'reset'])->name('celebration.template.reset');
});

// gift routes
Route::prefix('gift')->group(function () {
    Route::post('/send', [GiftController::class, 'send'])->name('gift.send');
    Route::post('/payment/initiate', [GiftController::class, 'initiatePayment'])->name('gift.payment.initiate');
    Route::get('/payment/callback', [GiftController::class, 'paystackCallback'])->name('gift.payment.callback');
    Route::get('/stripe/success', [GiftController::class, 'stripeSuccess'])->name('gift.stripe.success');
});

// wish contribution routes
Route::prefix('wish')->group(function () {
    Route::post('/{wish}/contribute/wallet', [\App\Http\Controllers\WishContributionController::class, 'contributeFromWallet'])->name('wish.contribute.wallet');
    Route::post('/{wish}/contribute/pay',    [\App\Http\Controllers\WishContributionController::class, 'initiatePayment'])->name('wish.contribute.pay');
    Route::get('/contribute/callback',       [\App\Http\Controllers\WishContributionController::class, 'paystackCallback'])->name('wish.contribute.callback');
    Route::get('/stripe/success',            [\App\Http\Controllers\WishContributionController::class, 'stripeSuccess'])->name('wish.stripe.success');
});

Route::middleware('auth')->group(function () {
    // Wallet funding
    Route::post('/wallet/fund', [WalletFundingController::class, 'initiate'])->name('wallet.fund');
    Route::get('/wallet/fund/callback', [WalletFundingController::class, 'paystackCallback'])->name('wallet.fund.callback');
    Route::get('/wallet/fund/stripe/success', [WalletFundingController::class, 'stripeSuccess'])->name('wallet.fund.stripe.success');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Bank accounts
    Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
    Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');
    Route::patch('/bank-accounts/{bankAccount}/default', [BankAccountController::class, 'setDefault'])->name('bank-accounts.default');

    // Withdrawals
    Route::post('/withdrawals', [WithdrawalController::class, 'store'])->name('withdrawals.store');
});

// Admin module
Route::prefix('admin')->name('admin.')->group(function () {
    // Auth (guest only)
    Route::get('/login',  [AdminLoginController::class, 'showLogin'])->name('login')->middleware('guest');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('login.post');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    // Protected panel
    Route::middleware('admin')->group(function () {
        Route::get('/',            [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users',       [AdminController::class, 'users'])->name('users');
        Route::get('/events',      [AdminController::class, 'events'])->name('events');
        Route::get('/withdrawals', [AdminController::class, 'withdrawals'])->name('withdrawals');
        Route::patch('/withdrawals/{withdrawal}/approve', [AdminController::class, 'approveWithdrawal'])->name('withdrawals.approve');
        Route::patch('/withdrawals/{withdrawal}/reject',  [AdminController::class, 'rejectWithdrawal'])->name('withdrawals.reject');

        // Frame management
        Route::get('/frames',         [AdminController::class, 'frames'])->name('frames');
        Route::post('/frames',        [AdminController::class, 'storeFrame'])->name('frames.store');
        Route::delete('/frames/{frame}', [AdminController::class, 'deleteFrame'])->name('frames.delete');
    });
});

require __DIR__.'/auth.php';
