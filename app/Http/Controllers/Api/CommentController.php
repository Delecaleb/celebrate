<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Celebration;
use App\Models\Comment;
use App\Models\Reaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The wishes wall.
 *
 * Naming, because it is genuinely confusing in this codebase: a post on the wall
 * is a Comment, while the Wish model is a *registry item* handled by
 * WishController.
 *
 * Replies live on WishController, not here: comment_replies.wish_id carries a
 * foreign key to wishes(id), so a reply can only ever hang off a registry item.
 * (comments.reply_count exists but nothing can legally populate it.)
 */
class CommentController extends Controller
{
    /** Used when an unauthenticated visitor posts without giving a name. */
    private const GUEST_NAMES = [
        'Anonymous Admirer',
        'Secret Well-Wisher',
        'Birthday Fan',
        'Celebration Friend',
        'Mystery Guest',
        'Joy Bringer',
        'Secret Supporter',
    ];

    public function index(Request $request, string $slug)
    {
        $celebration = Celebration::where('slug', $slug)->firstOrFail();

        return CommentResource::collection(
            $celebration->comments()->with('user')->latest()->paginate(30)
        );
    }

    /**
     * Post to the wall. Text, or one image, or one video — at least one of them.
     * Open to guests, as on the web.
     */
    public function store(Request $request)
    {
        $request->validate([
            'celebration_id' => ['required', 'exists:celebrations,id'],
            'anonymous'      => ['nullable', 'boolean'],
            'comment'        => ['nullable', 'string', 'max:1000', 'required_without_all:image,video'],
            'image'          => ['nullable', 'image', 'max:5120', 'required_without_all:comment,video'],
            'video'          => ['nullable', 'file', 'mimes:mp4,webm,ogg,quicktime', 'max:20480', 'required_without_all:comment,image'],
            'guest_name'     => ['nullable', 'string', 'max:120'],
        ]);

        $user      = $request->user();
        $anonymous = $request->boolean('anonymous');

        // media_type is NOT NULL and text-only rows store '', so it starts empty
        // rather than null — a null here makes the insert fail outright.
        $mediaType = '';
        $mediaUrl  = null;

        if ($request->hasFile('image')) {
            $mediaType = 'local-image';
            $mediaUrl  = $request->file('image')->store('comments', 'public');
        } elseif ($request->hasFile('video')) {
            $mediaType = 'video';
            $mediaUrl  = $request->file('video')->store('comments', 'public');
        }

        $name = match (true) {
            $anonymous  => self::GUEST_NAMES[array_rand(self::GUEST_NAMES)],
            (bool) $user => trim($user->first_name.' '.$user->last_name),
            default     => $request->guest_name ?: self::GUEST_NAMES[array_rand(self::GUEST_NAMES)],
        };

        $comment = Comment::create([
            'celebration_id' => $request->celebration_id,
            // An anonymous post keeps no link back to the account that made it.
            'user_id'        => $anonymous ? null : $user?->id,
            'guest_name'     => $name,
            'guest_email'    => $anonymous ? null : $user?->email,
            'message'        => $request->comment,
            'media_type'     => $mediaType,
            'media_url'      => $mediaUrl,
            'is_pinned'      => false,
            'is_approved'    => true,
            'like_count'     => 0,
            'reply_count'    => 0,
        ]);

        Celebration::whereKey($request->celebration_id)->increment('comment_count');

        return (new CommentResource($comment->load('user')))->response()->setStatusCode(201);
    }

    /**
     * Toggle a reaction on a wall post. Tapping the same reaction again removes
     * it; a different one replaces it.
     *
     * As with reply(), the web route for this points at a method that does not
     * exist on CelebrationController.
     */
    public function react(Request $request, Comment $comment)
    {
        $data = $request->validate([
            'reaction_type' => ['required', 'string', 'max:30'],
        ]);

        $user = $request->user();

        // Guests are identified by device so a reaction can still be undone.
        $guestId = $user ? null : (string) $request->header('X-Device-Id', $request->ip());

        $query = Reaction::where('reactionable_type', Comment::class)
            ->where('reactionable_id', $comment->id)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->when(! $user, fn ($q) => $q->whereNull('user_id')->where('guest_identifier', $guestId));

        $result = DB::transaction(function () use ($query, $comment, $data, $user, $guestId) {
            $existing = $query->first();

            if ($existing && $existing->reaction_type === $data['reaction_type']) {
                $existing->delete();
                $comment->decrement('like_count');

                return ['reacted' => false, 'reaction_type' => null];
            }

            if ($existing) {
                $existing->update(['reaction_type' => $data['reaction_type']]);

                return ['reacted' => true, 'reaction_type' => $data['reaction_type']];
            }

            Reaction::create([
                'user_id'           => $user?->id,
                'reactionable_type' => Comment::class,
                'reactionable_id'   => $comment->id,
                'reaction_type'     => $data['reaction_type'],
                'guest_identifier'  => $guestId,
            ]);
            $comment->increment('like_count');

            return ['reacted' => true, 'reaction_type' => $data['reaction_type']];
        });

        return response()->json($result + ['like_count' => $comment->fresh()->like_count]);
    }
}
