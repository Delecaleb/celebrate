<?php

namespace App\Support;

use App\Models\Celebration;
use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use App\Models\UserBlock;
use App\Models\WallReply;
use Illuminate\Database\Eloquent\Model;

/**
 * Reports, blocks and hiding — the rules in one place, so the web and the app
 * enforce the same ones.
 */
class Moderation
{
    /**
     * Distinct people reporting the same post before it is hidden pending
     * review. One report can be a grudge; three independent ones rarely are,
     * and a hurtful post should not stay up overnight waiting for staff.
     */
    public const AUTO_HIDE_AFTER = 3;

    /** @var array<int, int[]> per-request cache: viewer id → ids they blocked */
    private static array $blocked = [];

    /** The people this viewer has blocked — their posts are left out for them. */
    public static function blockedBy(?User $viewer): array
    {
        if (! $viewer) {
            return [];
        }

        return self::$blocked[$viewer->id] ??= UserBlock::where('blocker_id', $viewer->id)
            ->pluck('blocked_id')
            ->all();
    }

    /** Has $blocker blocked $blocked? */
    public static function hasBlocked(?int $blocker, ?int $blocked): bool
    {
        if (! $blocker || ! $blocked) {
            return false;
        }

        return UserBlock::where('blocker_id', $blocker)->where('blocked_id', $blocked)->exists();
    }

    public static function forgetCache(): void
    {
        self::$blocked = [];
    }

    /**
     * Record a report. The same person reporting the same thing twice counts
     * once. Returns whether this report caused the thing to be hidden.
     */
    public static function report(string $type, Model $subject, string $reason, ?string $details, ?User $user, ?string $device): bool
    {
        $mine = Report::where('reportable_type', $type)
            ->where('reportable_id', $subject->getKey())
            ->when($user, fn ($q) => $q->where('reporter_user_id', $user->id))
            ->when(! $user, fn ($q) => $q->whereNull('reporter_user_id')->where('reporter_device', $device));

        if ($mine->exists()) {
            return false;
        }

        Report::create([
            'reporter_user_id' => $user?->id,
            'reporter_device'  => $user ? null : $device,
            'reportable_type'  => $type,
            'reportable_id'    => $subject->getKey(),
            'reason'           => $reason,
            'details'          => $details,
            'status'           => 'open',
        ]);

        // Only posts are hidden automatically. A whole page or an account is
        // a bigger call, and staff make it.
        if (! in_array($type, ['wish', 'reply'], true)) {
            return false;
        }

        $reporters = Report::where('reportable_type', $type)
            ->where('reportable_id', $subject->getKey())
            ->where('status', 'open')
            ->selectRaw("COUNT(DISTINCT COALESCE(CAST(reporter_user_id AS CHAR), reporter_device)) as n")
            ->value('n');

        if ($reporters >= self::AUTO_HIDE_AFTER) {
            self::hide($subject);

            return true;
        }

        return false;
    }

    /** Take a post off every page. Reversible with restore(). */
    public static function hide(Model $subject): void
    {
        if ($subject instanceof Comment && $subject->is_approved) {
            $subject->forceFill(['is_approved' => false])->save();
            Celebration::whereKey($subject->celebration_id)->where('comment_count', '>', 0)->decrement('comment_count');
        } elseif ($subject instanceof WallReply && ! $subject->is_hidden) {
            $subject->forceFill(['is_hidden' => true])->save();
            Comment::withoutGlobalScope('visible')->whereKey($subject->comment_id)->where('reply_count', '>', 0)->decrement('reply_count');
        }
    }

    public static function restore(Model $subject): void
    {
        if ($subject instanceof Comment && ! $subject->is_approved) {
            $subject->forceFill(['is_approved' => true])->save();
            Celebration::whereKey($subject->celebration_id)->increment('comment_count');
        } elseif ($subject instanceof WallReply && $subject->is_hidden) {
            $subject->forceFill(['is_hidden' => false])->save();
            Comment::withoutGlobalScope('visible')->whereKey($subject->comment_id)->increment('reply_count');
        }
    }
}
