<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\EmailQueue;
use App\Support\OutboxSender;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Every email the site has tried to send.
 *
 * The point of keeping mail in its own table rather than in the queue's
 * serialised payload: when somebody says they never got their receipt, this
 * answers it — whether it was written, whether it went, and what the server
 * said if it did not.
 */
class AdminOutboxController extends Controller
{
    /** How many an admin can push out in one click, so a request cannot hang. */
    private const SEND_NOW_LIMIT = 25;

    public function index(Request $request)
    {
        $emails = EmailQueue::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q') . '%';

                $q->where(fn ($w) => $w->where('to_address', 'like', $term)->orWhere('subject', 'like', $term));
            })
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.outbox.index', [
            'emails' => $emails,
            'counts' => EmailQueue::selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'types'  => EmailQueue::select('type')->distinct()->orderBy('type')->pluck('type'),
            // What "Send all waiting" would actually attempt right now.
            'due'    => EmailQueue::due()->count(),
        ]);
    }

    /** The email as the recipient would see it. */
    public function show(EmailQueue $email)
    {
        return view('admin.outbox.show', ['email' => $email]);
    }

    /**
     * The rendered body, served on its own so the preview can be sandboxed.
     *
     * This is somebody else's mail rendered back into a browser, so it goes in
     * an iframe with no scripts allowed rather than inline in the page.
     */
    public function preview(EmailQueue $email)
    {
        return response($email->body_html)->withHeaders([
            'Content-Type'            => 'text/html; charset=utf-8',
            'Content-Security-Policy' => "default-src 'none'; img-src * data:; style-src 'unsafe-inline'",
            'X-Frame-Options'         => 'SAMEORIGIN',
        ]);
    }

    public function retry(EmailQueue $email)
    {
        if ($email->status === EmailQueue::SENT) {
            return back()->with('error', 'That one already went out — retrying would send it twice.');
        }

        $email->retry();

        AdminAuditLog::record(
            'admin.email.retried',
            "Queued {$email->type} to {$email->to_address} for another attempt",
            $email,
            $email->uuid,
        );

        return back()->with('success', 'Back in the queue. It goes out on the next pass, within a minute.');
    }

    /**
     * Send one email this second, rather than waiting for the scheduler.
     *
     * Same code path as the cron pass, so it is claimed, recorded and retried
     * on failure exactly as an automatic send would be.
     */
    public function sendNow(EmailQueue $email, OutboxSender $sender)
    {
        $outcome = $sender->sendNow($email);

        AdminAuditLog::record(
            'admin.email.sent-now',
            "Sent {$email->type} to {$email->to_address} by hand: " . ($outcome['ok'] ? 'delivered' : 'failed'),
            $email,
            $email->uuid,
        );

        if (! $outcome['ok']) {
            return back()->with('error', 'Could not send it: ' . Str::limit((string) $outcome['error'], 160));
        }

        return back()->with('success', "Sent to {$email->to_address}.");
    }

    /**
     * Push out everything that is waiting, without waiting for cron.
     *
     * Capped per click: this runs inside the request, and a page that hangs
     * while a hundred emails crawl through SMTP is worse than two clicks.
     */
    public function sendPending(OutboxSender $sender)
    {
        $outcome = $sender->sendDue(self::SEND_NOW_LIMIT);

        if ($outcome['results'] === []) {
            return back()->with('success', 'Nothing was waiting — the outbox is clear.');
        }

        AdminAuditLog::record(
            'admin.email.sent-now',
            "Sent the waiting outbox by hand: {$outcome['sent']} delivered, {$outcome['failed']} failed",
            null,
            'outbox',
        );

        $remaining = EmailQueue::due()->count();

        $message = "Sent {$outcome['sent']} " . Str::plural('email', $outcome['sent'])
            . ($outcome['failed'] ? ", {$outcome['failed']} failed — see the list below" : '')
            . ($remaining ? ". {$remaining} still waiting, press again to continue." : '.');

        return back()->with($outcome['failed'] ? 'error' : 'success', $message);
    }

    /** Stop something that should never have been written. */
    public function hold(EmailQueue $email)
    {
        if ($email->status === EmailQueue::SENT) {
            return back()->with('error', 'That one already went out.');
        }

        $email->forceFill(['status' => EmailQueue::HELD, 'reserved_at' => null])->save();

        AdminAuditLog::record(
            'admin.email.held',
            "Held {$email->type} to {$email->to_address}",
            $email,
            $email->uuid,
        );

        return back()->with('success', 'Held. It will not be sent unless you release it.');
    }
}
