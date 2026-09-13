<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\EmailQueue;
use Illuminate\Http\Request;

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
