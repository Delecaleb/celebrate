<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;
use App\Http\Controllers\CelebrationController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\WalletFundingController;

Route::get('/', [MainController::class, 'home'])->name('home');
Route::post('/create-celebration', [CelebrationController::class, 'store'])->name('celebrations.store');
Route::get('/celebration/{slug}', [CelebrationController::class, 'show'])->name('celebrations.show');    
Route::get('/dashboard', function () {
    $user = auth()->user();

    // My events
    $celebrations = $user->celebrations()->withCount('gifts')->latest()->get();

    // Upcoming (published + future date)
    $upcoming = $user->celebrations()
        ->where('status', 'published')
        ->where(function ($q) {
            $q->whereDate('event_date', '>=', now()->toDateString())
              ->orWhereDate('start_date', '>=', now()->toDateString());
        })
        ->orderByRaw('COALESCE(event_date, start_date) ASC')
        ->limit(10)
        ->get();

    // Notifications
    $notifications = $user->notifications()->latest()->limit(40)->get();
    $unreadCount   = $user->notifications()->where('is_read', false)->count();

    // Wallet
    $walletTransactions = $user->walletTransactions()->latest()->limit(50)->get();
    $totalCredited = $walletTransactions->where('type', 'credit')->where('status', 'completed')->sum('amount');
    $totalDebited  = $walletTransactions->where('type', 'debit')->where('status', 'completed')->sum('amount');

    // Discover — public celebrations from other users
    $discover = \App\Models\Celebration::where('is_public', true)
        ->where('user_id', '!=', $user->id)
        ->where('status', 'published')
        ->latest()
        ->limit(12)
        ->get();

    // Bank accounts & withdrawals
    $bankAccounts = $user->bankAccounts()->orderByDesc('is_default')->oldest()->get();
    $withdrawals  = $user->withdrawals()->with('bankAccount')->latest()->limit(30)->get();

    // Wallet display in user's local currency
    $currencySvc      = app(\App\Services\PaymentSystem\CurrencyService::class);
    $walletSvc        = app(\App\Services\PaymentSystem\WalletService::class);
    $userCurrency     = $currencySvc->forUser($user);
    $currencySymbol   = config("currency.currencies.{$userCurrency}.symbol", $userCurrency);
    $walletDisplay    = $walletSvc->balance($user, $userCurrency);

    $hour     = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

    $stats = [
        'total'     => $celebrations->count(),
        'views'     => $celebrations->sum('view_count'),
        'messages'  => $celebrations->sum('comment_count'),
        'published' => $celebrations->where('status', 'published')->count(),
    ];

    return view('dashboard', compact(
        'celebrations', 'upcoming', 'notifications', 'unreadCount',
        'walletTransactions', 'totalCredited', 'totalDebited',
        'discover', 'greeting', 'stats',
        'bankAccounts', 'withdrawals',
        'userCurrency', 'currencySymbol', 'walletDisplay'
    ));
})->middleware(['auth', 'verified'])->name('dashboard');
Route::middleware(['auth'])->group(function () {
    Route::get('/bulk-upload-celebrants/form',    [\App\Http\Controllers\CelebrantController::class, 'showBulkUploadForm'])->name('celebrant.bulkUploadForm');
    Route::post('/bulk-upload-celebrants/preview', [\App\Http\Controllers\CelebrantController::class, 'previewBulkUpload'])->name('celebrant.bulkUpload.preview');
    Route::post('/bulk-upload-celebrants',         [\App\Http\Controllers\CelebrantController::class, 'bulkUpload'])->name('celebrant.bulkUpload');
});

// celebrant routes
Route::prefix('celebrant')->group(function () {
    Route::post('/create-wishes', [CelebrationController::class, 'createWishes'])->name('celebrant.create-wishes');
    Route::get('/{slug}/edit', [CelebrationController::class, 'edit'])->name('celebrant.edit');
    Route::put('/{slug}', [CelebrationController::class, 'update'])->name('celebrant.update');
    Route::delete('/{slug}', [CelebrationController::class, 'destroy'])->name('celebrant.destroy');
    Route::post('/{id}/cover-photo', [CelebrationController::class, 'updateCoverPhoto'])->name('celebrant.update-cover');
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
});

// wish contribution routes
Route::prefix('wish')->group(function () {
    Route::post('/{wish}/contribute/wallet', [\App\Http\Controllers\WishContributionController::class, 'contributeFromWallet'])->name('wish.contribute.wallet');
    Route::post('/{wish}/contribute/pay',    [\App\Http\Controllers\WishContributionController::class, 'initiatePayment'])->name('wish.contribute.pay');
    Route::get('/contribute/callback',       [\App\Http\Controllers\WishContributionController::class, 'paystackCallback'])->name('wish.contribute.callback');
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
    });
});

require __DIR__.'/auth.php';
