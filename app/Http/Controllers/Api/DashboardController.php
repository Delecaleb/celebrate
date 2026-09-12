<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CelebrationResource;
use App\Http\Resources\NotificationResource;
use App\Models\Celebration;
use App\Services\PaymentSystem\CurrencyService;
use App\Services\PaymentSystem\WalletService;
use Illuminate\Http\Request;

/**
 * The signed-in dashboard, one endpoint per rail item.
 *
 * The web controller loads everything for every page because a Blade partial
 * must never hit an undefined variable. The app has no such constraint, so each
 * screen fetches only its own data and the tab bar stays cheap.
 */
class DashboardController extends Controller
{
    public function __construct(
        private CurrencyService $currency,
        private WalletService $wallet,
    ) {}

    /**
     * Everything the home screen header needs: the greeting, the four stat
     * tiles and the unread badge.
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        // Aggregate in SQL rather than pulling every row down to sum it.
        $stats = $user->celebrations()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COALESCE(SUM(view_count), 0) as views')
            ->selectRaw('COALESCE(SUM(comment_count), 0) as messages')
            ->selectRaw("COALESCE(SUM(status = 'published'), 0) as published")
            ->first();

        $userCurrency = $this->currency->forUser($user);
        $hour = now()->hour;

        return response()->json([
            'greeting' => $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening'),
            'user'     => [
                'first_name' => $user->first_name,
                'name'       => trim($user->first_name.' '.$user->last_name),
            ],
            'stats' => [
                'total'     => (int) $stats->total,
                'views'     => (int) $stats->views,
                'messages'  => (int) $stats->messages,
                'published' => (int) $stats->published,
            ],
            'wallet' => $this->balances($user, $userCurrency),
            'unread_count' => $user->notifications()->where('is_read', false)->count(),
        ]);
    }

    /**
     * My Events — every page you have made, newest first.
     */
    public function celebrations(Request $request)
    {
        $celebrations = $request->user()->celebrations()
            ->withCount(['gifts', 'wishes'])
            ->latest()
            ->paginate(20);

        return CelebrationResource::collection($celebrations);
    }

    /**
     * Upcoming — published pages whose date has not passed. Same ordering rule
     * as the web page: whichever of event_date/start_date is set.
     */
    public function upcoming(Request $request)
    {
        $upcoming = $request->user()->celebrations()
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereDate('event_date', '>=', now()->toDateString())
                  ->orWhereDate('start_date', '>=', now()->toDateString());
            })
            ->orderByRaw('COALESCE(event_date, start_date) ASC')
            ->withCount(['gifts', 'wishes'])
            ->limit(20)
            ->get();

        return CelebrationResource::collection($upcoming);
    }

    public function activity(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->paginate(40);

        return NotificationResource::collection($notifications)->additional([
            'meta' => [
                'unread_count' => $request->user()->notifications()->where('is_read', false)->count(),
            ],
        ]);
    }

    public function markActivityRead(Request $request)
    {
        $marked = $request->user()->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'marked'       => $marked,
            'unread_count' => 0,
        ]);
    }

    /**
     * Discover — public, published pages belonging to other people.
     */
    public function discover(Request $request)
    {
        $discover = Celebration::where('is_public', true)
            ->where('user_id', '!=', $request->user()->id)
            ->where('status', 'published')
            ->with('user')
            ->withCount(['gifts', 'wishes'])
            ->latest()
            ->paginate(12);

        return CelebrationResource::collection($discover);
    }

    /**
     * The balances, plus the symbol the client formats with.
     *
     * A USD user has one wallet — the global one — so `local` comes back 0 and
     * `has_local_wallet` tells the client not to show it.
     */
    private function balances($user, string $userCurrency): array
    {
        $hasLocalWallet = $this->wallet->hasLocalWallet($user);

        return [
            'currency'         => $userCurrency,
            'symbol'           => config("currency.currencies.{$userCurrency}.symbol", $userCurrency),
            'has_local_wallet' => $hasLocalWallet,
            'local'            => $hasLocalWallet ? round($this->wallet->balance($user, 'local'), 2) : 0.0,
            'global'           => round($this->wallet->balance($user, 'global'), 2),
        ];
    }
}
