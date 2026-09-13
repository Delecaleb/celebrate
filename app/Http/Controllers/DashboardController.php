<?php

namespace App\Http\Controllers;

use App\Models\Celebration;
use App\Services\PaymentSystem\CurrencyService;
use App\Services\PaymentSystem\PaystackService;
use App\Services\PaymentSystem\WalletService;
use Illuminate\Http\Request;

/**
 * Signed-in dashboard.
 *
 * Every menu item is its own URL. They all render the same shell
 * (layouts.dashboard) with one of the partials in
 * resources/views/dashboard/partials. When the client-side router
 * (resources/js/modules/pageRouter.js) asks for a page it sends `X-Partial: 1`
 * and gets back just that partial's HTML as JSON, so moving between menu items
 * never reloads the page — the same contract MainController uses for the public
 * site.
 */
class DashboardController extends Controller
{
    /**
     * Rail menu, in order. `route` doubles as the active-state key.
     */
    private const NAV = [
        'Celebrations' => [
            ['page' => 'events',   'route' => 'dashboard',            'icon' => 'mdi-calendar-star',  'label' => 'My Events'],
            ['page' => 'upcoming', 'route' => 'dashboard.upcoming',   'icon' => 'mdi-calendar-clock', 'label' => 'Upcoming'],
            ['page' => 'activity', 'route' => 'dashboard.activity',   'icon' => 'mdi-bell-outline',   'label' => 'Activity', 'badge' => 'unread'],
        ],
        // Withdrawals used to be its own item, but it only restated what the
        // Wallet page already shows — the balances and the payout list.
        'Money' => [
            ['page' => 'wallet', 'route' => 'dashboard.wallet', 'icon' => 'mdi-wallet-outline', 'label' => 'Wallet'],
            ['page' => 'bank',   'route' => 'dashboard.bank',   'icon' => 'mdi-bank-outline',   'label' => 'Bank Account'],
        ],
        'Explore' => [
            ['page' => 'discover', 'route' => 'dashboard.discover', 'icon' => 'mdi-compass-outline', 'label' => 'Discover'],
        ],
    ];

    public function events(Request $request)
    {
        return $this->respond($request, 'events', 'My Events');
    }

    public function upcoming(Request $request)
    {
        return $this->respond($request, 'upcoming', 'Upcoming');
    }

    public function activity(Request $request)
    {
        return $this->respond($request, 'activity', 'Activity');
    }

    public function wallet(Request $request)
    {
        return $this->respond($request, 'wallet', 'Wallet');
    }

    /**
     * Profile lives in the same shell as everything else. It keeps the /profile
     * URL, because the avatar menu and the Breeze password routes point there.
     */
    public function profile(Request $request)
    {
        return $this->respond($request, 'profile', 'Profile');
    }

    public function bank(Request $request)
    {
        return $this->respond($request, 'bank', 'Bank Account');
    }

    public function discover(Request $request)
    {
        return $this->respond($request, 'discover', 'Discover');
    }

    /**
     * Clear the unread badge.
     *
     * Deliberately a plain form POST rather than a fetch: the badge is rendered
     * by the shell, outside #view, so the client-side router could not refresh
     * it. The redirect rebuilds the whole shell instead.
     */
    public function markActivityRead()
    {
        $marked = auth()->user()->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return redirect()->route('dashboard.activity')->with(
            'success',
            $marked === 1 ? '1 notification marked as read.' : "{$marked} notifications marked as read."
        );
    }

    /**
     * Render the full shell, or just the partial when the router asks for it.
     */
    private function respond(Request $request, string $page, string $heading)
    {
        $data = $this->data() + [
            'user'      => $request->user(),
            'page'      => $page,
            'nav'       => $page,
            'navGroups' => self::NAV,
            'heading'   => $heading,
            'title'     => "{$heading} — CelebrateMi",
        ];

        if ($request->header('X-Partial')) {
            return response()->json([
                'title' => $data['title'],
                'nav'   => $page,
                'html'  => view("dashboard.partials.{$page}", $data)->render(),
                // Unlike the marketing pages, every one of these renders live
                // figures — balances, unread counts, event lists. Tell the
                // router not to keep them in its in-memory cache.
                'cache' => false,
            // One URL, two representations. Without these headers a browser can
            // cache the JSON under the page's address and show it raw on Back.
            ])->header('Vary', 'X-Partial')->header('Cache-Control', 'no-store, private');
        }

        return response()->view('layouts.dashboard', $data)->header('Vary', 'X-Partial');
    }

    /**
     * Everything the shell and any one partial can need.
     *
     * Loaded for every page so a partial can never hit an undefined variable.
     * All of it is capped, so the cost is a handful of small queries.
     */
    private function data(): array
    {
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

        // Wallet ledger. Only the Wallet page uses it, behind a disclosure —
        // the page leads with balances and withdrawals, not a spending total.
        // transactionable carries the giver's name; without eager loading this
        // is one extra query per row.
        $walletTransactions = $user->walletTransactions()
            ->with('transactionable')
            ->latest()
            ->limit(50)
            ->get();

        // Discover — public celebrations from other users
        $discover = Celebration::where('is_public', true)
            ->where('user_id', '!=', $user->id)
            ->where('status', 'published')
            ->latest()
            ->limit(12)
            ->get();

        // Bank accounts & withdrawals. The bank list is what the add/edit
        // payout form offers — cached for a day inside the service, so this is
        // a cache read on all but the first page view of the day.
        $bankAccounts = $user->bankAccounts()->orderByDesc('is_default')->oldest()->get();
        $withdrawals  = $user->withdrawals()->with('bankAccount')->latest()->limit(30)->get();
        $banks        = app(PaystackService::class)->banks();

        // Wallet display
        $currencySvc    = app(CurrencyService::class);
        $walletSvc      = app(WalletService::class);
        $userCurrency   = $currencySvc->forUser($user);
        $currencySymbol = config("currency.currencies.{$userCurrency}.symbol", $userCurrency);
        // USD countries check out in USD, so there is nothing for a local
        // wallet to hold — they see the one balance, not two.
        $hasLocalWallet = $walletSvc->hasLocalWallet($user);
        $globalDisplay  = $walletSvc->balance($user, 'global');
        $localDisplay   = $hasLocalWallet ? $walletSvc->balance($user, 'local') : 0.0;
        $walletDisplay  = $hasLocalWallet ? $localDisplay : $globalDisplay; // legacy fallback

        $hour     = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

        $stats = [
            'total'     => $celebrations->count(),
            'views'     => $celebrations->sum('view_count'),
            'messages'  => $celebrations->sum('comment_count'),
            'published' => $celebrations->where('status', 'published')->count(),
        ];

        return compact(
            'celebrations', 'upcoming', 'notifications', 'unreadCount',
            'walletTransactions',
            'discover', 'greeting', 'stats',
            'bankAccounts', 'withdrawals', 'banks',
            'userCurrency', 'currencySymbol', 'walletDisplay',
            'localDisplay', 'globalDisplay', 'hasLocalWallet'
        );
    }
}
