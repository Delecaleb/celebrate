<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CelebrationController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\Admin\AdminAuditController;
use App\Http\Controllers\Admin\AdminCelebrationController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminCurrencyController;
use App\Http\Controllers\Admin\AdminGiftController;
use App\Http\Controllers\Admin\AdminOutboxController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminImpersonationController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminStaffController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\WalletFundingController;

/*
| Public marketing site. These share one shell (layouts.marketing) and are
| navigated client-side by resources/js/modules/pageRouter.js.
*/
/*
| Crawler files. Generated rather than static so the sitemap always matches
| the stories and published celebrations that actually exist, and so the
| Sitemap: line in robots.txt points at the domain in APP_URL.
*/
/*
| Gateway webhooks. Signature-verified inside the controller, CSRF-exempt in
| bootstrap/app.php, and the only notice of a payment we can actually rely on —
| a browser redirect is optional, this is not.
*/
Route::post('/webhooks/paystack', [WebhookController::class, 'paystack'])->name('webhooks.paystack');
Route::post('/webhooks/stripe',   [WebhookController::class, 'stripe'])->name('webhooks.stripe');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt',  [SitemapController::class, 'robots'])->name('robots');

Route::get('/',             [MainController::class, 'home'])->name('home');
Route::get('/features',     [MainController::class, 'features'])->name('features');
Route::get('/how-it-works', [MainController::class, 'howItWorks'])->name('how-it-works');
Route::get('/pricing',      [MainController::class, 'pricing'])->name('pricing');
Route::get('/stories',      [MainController::class, 'stories'])->name('stories');
Route::get('/terms',        [MainController::class, 'terms'])->name('terms');
Route::get('/privacy',      [MainController::class, 'privacy'])->name('privacy');
Route::get('/stories/{slug}', [MainController::class, 'story'])->name('stories.show');

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
    Route::delete('/{id}/cover-photo', [CelebrationController::class, 'deleteCoverPhoto'])->name('celebrant.delete-cover');
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

/*
| Anything that starts a payment is rate limited. A gift page is public and
| unauthenticated by design, so without this a script could open thousands of
| gateway sessions against a celebration — noise for us, card-testing cover for
| whoever ran it. Twelve a minute is far above what a real guest needs.
*/
/*
| A live CSRF token for a page that has been open long enough for its own to
| die — a session passing its lifetime, or a sign-in on another tab
| regenerating it. Returning the token to its own session gives away nothing:
| a cross-origin script cannot read this response, which is the whole basis of
| CSRF protection in the first place.
*/
Route::get('/csrf-token', fn () => response()->json(['token' => csrf_token()]))
    ->middleware('throttle:60,1')->name('csrf.token');

// gift routes
Route::prefix('gift')->group(function () {
    Route::post('/send', [GiftController::class, 'send'])
        ->middleware('throttle:12,1')->name('gift.send');
    Route::post('/payment/initiate', [GiftController::class, 'initiatePayment'])
        ->middleware('throttle:12,1')->name('gift.payment.initiate');

    // Settles a gift paid without leaving the page. Verified against Paystack
    // on the way through, so a forged call settles nothing.
    Route::post('/payment/confirm', [GiftController::class, 'confirmPayment'])
        ->middleware('throttle:20,1')->name('gift.payment.confirm');
    Route::get('/payment/callback', [GiftController::class, 'paystackCallback'])->name('gift.payment.callback');
    Route::get('/stripe/success', [GiftController::class, 'stripeSuccess'])->name('gift.stripe.success');
});

// wish contribution routes
Route::prefix('wish')->group(function () {
    Route::post('/{wish}/contribute/wallet', [\App\Http\Controllers\WishContributionController::class, 'contributeFromWallet'])
        ->middleware('throttle:12,1')->name('wish.contribute.wallet');
    Route::post('/{wish}/contribute/pay',    [\App\Http\Controllers\WishContributionController::class, 'initiatePayment'])
        ->middleware('throttle:12,1')->name('wish.contribute.pay');
    // Settles a contribution paid without leaving the page. Verified against
    // Paystack on the way through, so a forged call settles nothing.
    Route::post('/contribute/confirm',       [\App\Http\Controllers\WishContributionController::class, 'confirmPayment'])
        ->middleware('throttle:20,1')->name('wish.contribute.confirm');
    Route::get('/contribute/callback',       [\App\Http\Controllers\WishContributionController::class, 'paystackCallback'])->name('wish.contribute.callback');
    Route::get('/stripe/success',            [\App\Http\Controllers\WishContributionController::class, 'stripeSuccess'])->name('wish.stripe.success');
});

// Hand the session back to the admin who borrowed it. Deliberately on the
// customer side: the banner offering it renders on the dashboard.
Route::post('/stop-impersonating', [\App\Http\Controllers\Admin\AdminImpersonationController::class, 'stop'])
    ->name('impersonation.stop');

Route::middleware('auth')->group(function () {
    // Wallet funding
    Route::post('/wallet/fund', [WalletFundingController::class, 'initiate'])
        ->middleware(['throttle:12,1', 'not.impersonating'])->name('wallet.fund');
    Route::get('/wallet/fund/callback', [WalletFundingController::class, 'paystackCallback'])->name('wallet.fund.callback');
    Route::get('/wallet/fund/stripe/success', [WalletFundingController::class, 'stripeSuccess'])->name('wallet.fund.stripe.success');

    // Rendered by DashboardController so it sits in the same shell as the rest
    // of the signed-in app. There is deliberately no DELETE route: an account
    // holds wallet balances, celebrations other people have given to, and the
    // gift records behind them, so closing one is a support conversation
    // rather than a button.
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Bank accounts
    Route::get('/bank-accounts/banks', [BankAccountController::class, 'banks'])->name('bank-accounts.banks');
    // Called while the add/edit form is being filled in, so it is rate limited
    // separately from the save it leads to.
    Route::post('/bank-accounts/resolve', [BankAccountController::class, 'resolve'])
        ->middleware('throttle:30,1')
        ->name('bank-accounts.resolve');
    // Changing where the money goes is the other thing impersonation must
    // never be able to do.
    Route::middleware('not.impersonating')->group(function () {
        Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
        Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
        Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');
        Route::patch('/bank-accounts/{bankAccount}/default', [BankAccountController::class, 'setDefault'])->name('bank-accounts.default');
    });

    // Withdrawals
    Route::post('/withdrawals', [WithdrawalController::class, 'store'])
        ->middleware(['throttle:10,1', 'not.impersonating'])->name('withdrawals.store');
});

// Admin module
Route::prefix('admin')->name('admin.')->group(function () {
    // Auth (guest only)
    Route::get('/login',  [AdminLoginController::class, 'showLogin'])->name('login')->middleware('guest');
    // Belt and braces: a per-IP ceiling in front of the per-email lockout in
    // the controller, so a spray across many addresses is capped too.
    Route::post('/login', [AdminLoginController::class, 'login'])
        ->middleware('throttle:20,1')
        ->name('login.post');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    // Protected panel. Every route past this point states the permission it
    // needs — a super admin holds all of them implicitly.
    Route::middleware('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

        // People
        Route::middleware('admin.can:users.view')->group(function () {
            Route::get('/users', [AdminController::class, 'users'])->name('users');
        });

        // Seeing an account as its owner sees it.
        Route::post('/users/{user}/impersonate', [AdminImpersonationController::class, 'start'])
            ->middleware('admin.can:users.impersonate')
            ->name('users.impersonate');

        // Celebrations and their registries
        Route::middleware('admin.can:events.view')->group(function () {
            Route::get('/events', [AdminController::class, 'events'])->name('events');
            Route::get('/events/{celebration}', [AdminCelebrationController::class, 'show'])->name('events.show');
        });

        // Money in
        Route::middleware('admin.can:payments.view')->group(function () {
            Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments');
        });
        Route::post('/payments/revalidate', [AdminPaymentController::class, 'revalidate'])
            ->middleware('admin.can:payments.revalidate')
            ->name('payments.revalidate');

        // Money out
        Route::middleware('admin.can:withdrawals.view')->group(function () {
            Route::get('/withdrawals', [AdminController::class, 'withdrawals'])->name('withdrawals');
        });
        Route::middleware('admin.can:withdrawals.process')->group(function () {
            Route::patch('/withdrawals/{withdrawal}/approve', [AdminController::class, 'approveWithdrawal'])->name('withdrawals.approve');
            Route::patch('/withdrawals/{withdrawal}/reject',  [AdminController::class, 'rejectWithdrawal'])->name('withdrawals.reject');
        });

        // The gift catalogue
        Route::middleware('admin.can:gifts.manage')->group(function () {
            Route::get('/gifts',                [AdminGiftController::class, 'index'])->name('gifts');
            Route::get('/gifts/new',            [AdminGiftController::class, 'create'])->name('gifts.create');
            Route::post('/gifts',               [AdminGiftController::class, 'store'])->name('gifts.store');
            Route::get('/gifts/{gift}',         [AdminGiftController::class, 'edit'])->name('gifts.edit');
            Route::put('/gifts/{gift}',         [AdminGiftController::class, 'update'])->name('gifts.update');
            Route::patch('/gifts/{gift}/toggle',[AdminGiftController::class, 'toggle'])->name('gifts.toggle');
            Route::delete('/gifts/{gift}',      [AdminGiftController::class, 'destroy'])->name('gifts.destroy');
        });

        // Frame management
        Route::middleware('admin.can:frames.manage')->group(function () {
            Route::get('/frames',            [AdminController::class, 'frames'])->name('frames');
            Route::post('/frames',           [AdminController::class, 'storeFrame'])->name('frames.store');
            Route::delete('/frames/{frame}', [AdminController::class, 'deleteFrame'])->name('frames.delete');
        });

        // What staff have been doing. Super admins only — this records the
        // person reading it too, so it is not something to delegate.
        Route::get('/audit', [AdminAuditController::class, 'index'])
            ->middleware('admin.super')
            ->name('audit');

        // Credentials and currencies. These decide whether money can move at
        // all, so the permission is deliberately narrow.
        Route::middleware('admin.can:settings.manage')->group(function () {
            Route::get('/settings/{group}',       [AdminSettingsController::class, 'edit'])->name('settings')->where('group', 'payments|mail|location|features');
            Route::put('/settings/{group}',       [AdminSettingsController::class, 'update'])->name('settings.update')->where('group', 'payments|mail|location|features');
            Route::post('/settings/{group}/test', [AdminSettingsController::class, 'test'])->name('settings.test')->where('group', 'payments|mail|location|features');

            /*
            | The outbox. Sits behind settings.manage because it is mail
            | configuration's other half — and because a rendered email can
            | carry personal detail, so it is not for every staff member.
            */
            Route::get('/outbox',                  [AdminOutboxController::class, 'index'])->name('outbox');
            Route::get('/outbox/{email}',          [AdminOutboxController::class, 'show'])->name('outbox.show');
            Route::get('/outbox/{email}/preview',  [AdminOutboxController::class, 'preview'])->name('outbox.preview');
            Route::post('/outbox/{email}/retry',   [AdminOutboxController::class, 'retry'])->name('outbox.retry');
            Route::post('/outbox/{email}/hold',    [AdminOutboxController::class, 'hold'])->name('outbox.hold');
            Route::post('/outbox/{email}/send-now', [AdminOutboxController::class, 'sendNow'])->name('outbox.send-now');
            // Not /outbox/{email}/... — this one acts on the whole queue.
            Route::post('/outbox-send-pending',     [AdminOutboxController::class, 'sendPending'])->name('outbox.send-pending');

            Route::get('/currencies',               [AdminCurrencyController::class, 'index'])->name('currencies');
            Route::post('/currencies',              [AdminCurrencyController::class, 'store'])->name('currencies.store');
            Route::put('/currencies/{currency}',    [AdminCurrencyController::class, 'update'])->name('currencies.update');
            Route::delete('/currencies/{currency}', [AdminCurrencyController::class, 'destroy'])->name('currencies.destroy');
        });

        // Staff accounts and their access levels
        Route::middleware('admin.can:admins.manage')->group(function () {
            Route::get('/staff',              [AdminStaffController::class, 'index'])->name('staff');
            Route::get('/staff/new',          [AdminStaffController::class, 'create'])->name('staff.create');
            Route::post('/staff',             [AdminStaffController::class, 'store'])->name('staff.store');
            Route::get('/staff/{admin}',      [AdminStaffController::class, 'edit'])->name('staff.edit');
            Route::put('/staff/{admin}',      [AdminStaffController::class, 'update'])->name('staff.update');
            Route::delete('/staff/{admin}',   [AdminStaffController::class, 'destroy'])->name('staff.destroy');
        });
    });
});

require __DIR__.'/auth.php';
