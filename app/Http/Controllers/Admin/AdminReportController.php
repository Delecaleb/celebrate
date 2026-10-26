<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Celebration;
use App\Models\Comment;
use App\Models\Report;
use App\Models\WallReply;
use App\Support\Moderation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Reports from the app and the web: what people say should not be there.
 *
 * One row per reported thing, not per report — five people reporting the same
 * wish is one decision, and the count says how urgent it is. Open first,
 * most-reported first.
 *
 * App stores expect reported content to be looked at promptly (Apple asks for
 * a timely response), so this list is meant to be checked daily.
 */
class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open') === 'closed' ? 'closed' : 'open';

        $groups = Report::query()
            ->selectRaw('reportable_type, reportable_id, COUNT(*) as reports, MAX(created_at) as latest_at')
            ->when($status === 'open', fn ($q) => $q->where('status', 'open'))
            ->when($status === 'closed', fn ($q) => $q->whereIn('status', ['actioned', 'dismissed']))
            ->groupBy('reportable_type', 'reportable_id')
            ->orderByDesc('reports')
            ->orderByDesc('latest_at')
            ->paginate(25)
            ->withQueryString();

        // What each group is about, and why people reported it.
        $items = $groups->getCollection()->map(function ($group) use ($status) {
            $reports = Report::where('reportable_type', $group->reportable_type)
                ->where('reportable_id', $group->reportable_id)
                ->when($status === 'open', fn ($q) => $q->where('status', 'open'))
                ->with('reporter')
                ->latest()
                ->get();

            $subject = $reports->first()?->subject();

            return (object) [
                'type'      => $group->reportable_type,
                'id'        => $group->reportable_id,
                'count'     => (int) $group->reports,
                'latest_at' => $group->latest_at,
                'reasons'   => $reports->countBy('reason'),
                'details'   => $reports->pluck('details')->filter()->take(3),
                'subject'   => $subject,
                'summary'   => self::summary($group->reportable_type, $subject),
                'hidden'    => self::isHidden($subject),
                'page'      => self::page($group->reportable_type, $subject),
            ];
        });

        return view('admin.reports', [
            'items'     => $items,
            'groups'    => $groups,
            'status'    => $status,
            'reasons'   => Report::REASONS,
            'openCount' => self::openCount(),
        ]);
    }

    /**
     * hide     — off every page, kept for the record (wish / reply)
     * restore  — back on the page (wish / reply)
     * remove   — deleted for good (wish / reply)
     * unlist   — a celebration page taken out of Discover and made private
     * dismiss  — nothing wrong; close the reports
     */
    public function act(Request $request, string $type, int $id)
    {
        $data = $request->validate([
            'action' => ['required', 'in:hide,restore,remove,unlist,dismiss'],
        ]);

        abort_unless(array_key_exists($type, Report::TYPES), 404);

        $report  = Report::where('reportable_type', $type)->where('reportable_id', $id)->firstOrFail();
        $subject = $report->subject();
        $action  = $data['action'];

        if ($subject && in_array($action, ['hide', 'restore', 'remove'], true) && ! in_array($type, ['wish', 'reply'], true)) {
            return back()->with('error', 'That action is for wishes and replies.');
        }

        if ($subject) {
            match ($action) {
                'hide'    => Moderation::hide($subject),
                'restore' => Moderation::restore($subject),
                'remove'  => self::remove($subject),
                'unlist'  => $subject instanceof Celebration ? $subject->update(['is_public' => false]) : null,
                'dismiss' => null,
            };
        }

        // Restoring means the reports were wrong; everything else closes them
        // as dealt with.
        Report::where('reportable_type', $type)
            ->where('reportable_id', $id)
            ->where('status', 'open')
            ->update([
                'status'      => in_array($action, ['dismiss', 'restore'], true) ? 'dismissed' : 'actioned',
                'reviewed_by' => Auth::guard('admin')->id(),
                'reviewed_at' => now(),
            ]);

        AdminAuditLog::record(
            "admin.report.{$action}",
            sprintf('%s reported %s #%d', ucfirst($action), $type, $id),
            $subject,
            Str::limit(self::summary($type, $subject), 80),
        );

        return back()->with('success', 'Done.');
    }

    /** Reported things still waiting for a decision — the number on the nav badge. */
    public static function openCount(): int
    {
        return Report::where('status', 'open')
            ->select('reportable_type', 'reportable_id')
            ->distinct()
            ->get()
            ->count();
    }

    private static function remove($subject): void
    {
        if ($subject instanceof Comment) {
            // Hide first so the page's count comes down, then delete.
            Moderation::hide($subject);
            $subject->delete();
        } elseif ($subject instanceof WallReply) {
            Moderation::hide($subject);
            $subject->delete();
        }
    }

    private static function summary(string $type, $subject): string
    {
        return match (true) {
            ! $subject                        => '(already deleted)',
            $subject instanceof Comment       => ($subject->guest_name ?: 'Guest').': '.($subject->message ?: '['.($subject->media_type ?: 'media').']'),
            $subject instanceof WallReply     => ($subject->guest_name ?: 'Guest').': '.$subject->message,
            $subject instanceof Celebration   => $subject->title,
            default                           => trim(($subject->first_name ?? '').' '.($subject->last_name ?? '')).' <'.($subject->email ?? '').'>',
        };
    }

    private static function isHidden($subject): bool
    {
        return ($subject instanceof Comment && ! $subject->is_approved)
            || ($subject instanceof WallReply && $subject->is_hidden)
            || ($subject instanceof Celebration && ! $subject->is_public);
    }

    /** The page the thing is on, to see it in context. */
    private static function page(string $type, $subject): ?string
    {
        $slug = match (true) {
            $subject instanceof Comment     => Celebration::whereKey($subject->celebration_id)->value('slug'),
            $subject instanceof WallReply   => Celebration::whereKey(
                Comment::withoutGlobalScope('visible')->whereKey($subject->comment_id)->value('celebration_id')
            )->value('slug'),
            $subject instanceof Celebration => $subject->slug,
            default                         => null,
        };

        return $slug ? route('celebrations.show', $slug) : null;
    }
}
