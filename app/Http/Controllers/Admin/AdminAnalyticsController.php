<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Celebration;
use App\Models\Comment;
use App\Models\PageView;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Who is visiting, and who brought them.
 *
 * One range scopes everything on the page: the daily chart, the tiles, the
 * busiest celebration pages, and the sign-ups traced back to the page their
 * visitor opened first.
 */
class AdminAnalyticsController extends Controller
{
    private const RANGES = [7, 30, 90];

    public function visitors(Request $request)
    {
        $days = in_array((int) $request->range, self::RANGES, true) ? (int) $request->range : 30;
        $from = CarbonImmutable::today()->subDays($days - 1);

        $inRange = fn ($query) => $query->where('viewed_at', '>=', $from);

        // ── By day ──
        // SUM over a CASE rather than a filtered COUNT, so it reads the same
        // on MySQL and on the SQLite the tests run against.
        $byDay = $inRange(PageView::query())
            ->selectRaw(
                'DATE(viewed_at) as day,
                 COUNT(*) as views,
                 COUNT(DISTINCT visitor_id) as visitors,
                 COUNT(DISTINCT session_id) as visits,
                 SUM(CASE WHEN celebration_id IS NULL THEN 0 ELSE 1 END) as celebration_views'
            )
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $series = [];
        for ($d = $from; $d->lte(CarbonImmutable::today()); $d = $d->addDay()) {
            $row = $byDay->get($d->toDateString());

            $series[] = [
                'date'              => $d->toDateString(),
                'label'             => $d->format('j M'),
                'visitors'          => (int) ($row->visitors ?? 0),
                'visits'            => (int) ($row->visits ?? 0),
                'views'             => (int) ($row->views ?? 0),
                'celebration_views' => (int) ($row->celebration_views ?? 0),
            ];
        }

        // ── The range as a whole ──
        // Counted across the range, not summed from the days: a visitor who
        // came on three days is one visitor.
        $totals = $inRange(PageView::query())
            ->selectRaw(
                'COUNT(*) as views,
                 COUNT(DISTINCT visitor_id) as visitors,
                 COUNT(DISTINCT session_id) as visits,
                 SUM(CASE WHEN celebration_id IS NULL THEN 0 ELSE 1 END) as celebration_views'
            )
            ->first();

        $signups    = User::where('created_at', '>=', $from)->count();
        $attributed = User::where('created_at', '>=', $from)->whereNotNull('discovered_via_celebration_id')->count();

        // ── Busiest celebration pages ──
        $topPages = $inRange(PageView::query())
            ->whereNotNull('celebration_id')
            ->selectRaw('celebration_id, COUNT(*) as views, COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('celebration_id')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $celebrations = Celebration::with('user')->whereIn('id', $topPages->pluck('celebration_id'))->get()->keyBy('id');
        $topPages->each(fn ($row) => $row->celebration = $celebrations->get($row->celebration_id));

        // ── Who brought the sign-ups ──
        $referrers = User::where('created_at', '>=', $from)
            ->whereNotNull('discovered_via_celebration_id')
            ->selectRaw('discovered_via_celebration_id as celebration_id, COUNT(*) as signups')
            ->groupBy('discovered_via_celebration_id')
            ->orderByDesc('signups')
            ->limit(10)
            ->get();

        $referring = Celebration::with('user')->whereIn('id', $referrers->pluck('celebration_id'))->get()->keyBy('id');
        $referrers->each(fn ($row) => $row->celebration = $referring->get($row->celebration_id));

        // ── The sign-ups themselves ──
        $conversions = User::where('created_at', '>=', $from)
            ->whereNotNull('discovered_via_celebration_id')
            ->with('discoveredVia.user')
            ->latest()
            ->limit(25)
            ->get();

        // Wishes each of them left as a guest before they had an account.
        $guestMessages = Comment::whereIn('visitor_id', $conversions->pluck('visitor_id')->filter())
            ->whereNull('user_id')
            ->selectRaw('visitor_id, COUNT(*) as total')
            ->groupBy('visitor_id')
            ->pluck('total', 'visitor_id');

        return view('admin.analytics', [
            'days'          => $days,
            'ranges'        => self::RANGES,
            'from'          => $from,
            'series'        => $series,
            'totals'        => [
                'visitors'          => (int) ($totals->visitors ?? 0),
                'visits'            => (int) ($totals->visits ?? 0),
                'views'             => (int) ($totals->views ?? 0),
                'celebration_views' => (int) ($totals->celebration_views ?? 0),
                'signups'           => $signups,
                'attributed'        => $attributed,
            ],
            'topPages'      => $topPages,
            'referrers'     => $referrers,
            'conversions'   => $conversions,
            'guestMessages' => $guestMessages,
        ]);
    }
}
