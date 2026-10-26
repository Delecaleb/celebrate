<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicUserResource;
use App\Models\Report;
use App\Models\User;
use App\Models\UserBlock;
use App\Support\Moderation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Reporting and blocking.
 *
 * Reporting is open to guests — anyone who can see a post can report it.
 * Blocking needs an account: it is "stop showing me this person", and only an
 * account has a "me" to remember it for.
 */
class ModerationController extends Controller
{
    public function report(Request $request)
    {
        $data = $request->validate([
            'type'    => ['required', Rule::in(array_keys(Report::TYPES))],
            'id'      => ['required', 'integer'],
            'reason'  => ['required', Rule::in(array_keys(Report::REASONS))],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $subject = (Report::TYPES[$data['type']])::find($data['id']);

        // Already hidden or gone: thank them all the same — telling a reporter
        // "that no longer exists" helps nobody.
        if ($subject) {
            Moderation::report(
                $data['type'],
                $subject,
                $data['reason'],
                $data['details'] ?? null,
                $request->user(),
                (string) $request->header('X-Device-Id', $request->ip()),
            );
        }

        return response()->json([
            'message' => 'Thanks for telling us. Our team will review it.',
        ], 201);
    }

    public function blocks(Request $request)
    {
        $blocks = UserBlock::where('blocker_id', $request->user()->id)->with('blocked')->latest()->get();

        return response()->json([
            'data' => $blocks->filter(fn ($b) => $b->blocked)->map(fn ($b) => (new PublicUserResource($b->blocked))->toArray($request))->values(),
        ]);
    }

    public function block(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        abort_if((int) $data['user_id'] === $request->user()->id, 422, 'You cannot block yourself.');

        UserBlock::firstOrCreate([
            'blocker_id' => $request->user()->id,
            'blocked_id' => $data['user_id'],
        ]);
        Moderation::forgetCache();

        return response()->json(['blocked' => true], 201);
    }

    public function unblock(Request $request, User $user)
    {
        UserBlock::where('blocker_id', $request->user()->id)->where('blocked_id', $user->id)->delete();
        Moderation::forgetCache();

        return response()->json(['blocked' => false]);
    }
}
