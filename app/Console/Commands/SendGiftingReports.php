<?php

namespace App\Console\Commands;

use App\Support\Outbox;
use App\Mail\GiftingReportMail;
use App\Models\Gift;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendGiftingReports extends Command
{
    protected $signature   = 'mail:gifting-reports {--period=weekly : Period label (weekly or monthly)}';
    protected $description = 'Send gifting summary reports to users who received gifts in the past period';

    public function handle(): int
    {
        $period      = $this->option('period');
        $periodLabel = $period === 'monthly' ? 'Monthly' : 'Weekly';
        $since       = $period === 'monthly' ? now()->subMonth() : now()->subWeek();
        $sent        = 0;

        // Find users who received at least one paid gift in the period
        $userIds = Gift::where('payment_status', 'paid')
            ->where('created_at', '>=', $since)
            ->join('celebrations', 'gifts.celebration_id', '=', 'celebrations.id')
            ->pluck('celebrations.user_id')
            ->unique();

        User::whereIn('id', $userIds)->each(function (User $user) use ($since, $periodLabel, &$sent) {
            $celebrations = $user->celebrations()
                ->with(['gifts' => fn ($q) => $q->where('payment_status', 'paid')->where('created_at', '>=', $since)])
                ->get()
                ->filter(fn ($c) => $c->gifts->isNotEmpty());

            if ($celebrations->isEmpty()) {
                return;
            }

            $breakdown = $celebrations->map(fn ($c) => [
                'title' => $c->title,
                'type'  => $c->celebration_type,
                'count' => $c->gifts->count(),
                'total' => (float) $c->gifts->sum('amount'),
            ])->values();

            $allGifts      = $celebrations->flatMap->gifts;
            $totalReceived = (float) $allGifts->sum('amount');
            $totalCount    = $allGifts->count();

            $topGifters = $allGifts
                ->where('is_anonymous', false)
                ->groupBy('sender_name')
                ->map(fn ($gifts, $name) => [
                    'name'  => $name,
                    'total' => (float) $gifts->sum('amount'),
                ])
                ->sortByDesc('total')
                ->take(5)
                ->values();

            Outbox::queue(new GiftingReportMail(
                user:                 $user,
                celebrationBreakdown: $breakdown,
                topGifters:           $topGifters,
                totalReceived:        $totalReceived,
                totalGiftCount:       $totalCount,
                celebrationCount:     $celebrations->count(),
                periodLabel:          $periodLabel,
            ),
                $user->email,
                'gifting.report',
                ['period' => $periodLabel, 'celebrations' => $celebrations->count()],
                $user->first_name,
            );

            $sent++;
            $this->line("  Report queued for: {$user->email}");
        });

        $this->info("{$periodLabel} gifting reports sent: {$sent}");

        return self::SUCCESS;
    }
}
