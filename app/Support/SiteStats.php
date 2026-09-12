<?php

namespace App\Support;

use App\Models\Celebration;
use App\Models\Comment;
use App\Models\Gift;
use App\Models\WishContribution;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The numbers the marketing pages are allowed to state.
 *
 * They used to be invented — "2,400 celebrations", "₦92m gifted", "4.9 rating"
 * — on a site that takes people's money. These come from the database instead,
 * and until there are enough of them to be worth quoting, the pages show what
 * is true of the product rather than a number that is not.
 */
class SiteStats
{
    /** Below this, a count says more about our launch date than our product. */
    private const MEANINGFUL_FROM = 25;

    private const CACHE_KEY = 'site.stats';
    private const CACHE_TTL = 900;   // 15 minutes

    /**
     * @return array{celebrations: int, wishes: int, gifted: float, currency: string, guests: int}
     */
    public function figures(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                $gifted = (float) Gift::where('payment_status', 'paid')->sum('amount')
                    + (float) WishContribution::where('payment_status', 'paid')->sum('amount');

                return [
                    'celebrations' => Celebration::where('status', 'published')->count(),
                    'wishes'       => Comment::count(),
                    'gifted'       => $gifted,
                    'currency'     => '₦',
                    'guests'       => (int) Comment::distinct()->count(DB::raw('COALESCE(user_id, guest_email)')),
                ];
            });
        } catch (\Throwable $e) {
            // The home page is the last thing that should fall over because a
            // counting query did. Nothing to show is a fine answer here — the
            // pages render their product claims instead.
            report($e);

            return [
                'celebrations' => 0,
                'wishes'       => 0,
                'gifted'       => 0.0,
                'currency'     => '₦',
                'guests'       => 0,
            ];
        }
    }

    /**
     * Is there enough here to be worth putting on a landing page?
     */
    public function isMeaningful(): bool
    {
        return $this->figures()['celebrations'] >= self::MEANINGFUL_FROM;
    }

    /**
     * A count as a person would write it: 2.4k, 140k, 1.2m.
     */
    public static function short(int|float $number): string
    {
        $number = (float) $number;

        return match (true) {
            $number >= 1_000_000 => rtrim(rtrim(number_format($number / 1_000_000, 1), '0'), '.') . 'm',
            $number >= 1_000     => rtrim(rtrim(number_format($number / 1_000, 1), '0'), '.') . 'k',
            default              => number_format($number),
        };
    }

    /** Clear after a seeding run or an import. */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
