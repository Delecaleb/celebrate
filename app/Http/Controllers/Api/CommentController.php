<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Celebration;
use App\Models\Comment;
use App\Models\Reaction;
use App\Models\WallReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The wishes wall.
 *
 * Naming, because it is genuinely confusing in this codebase: a post on the wall
 * is a Comment, while the Wish model is a *registry item* handled by
 * WishController.
 *
 * Registry replies live on WishController: comment_replies.wish_id carries a
 * foreign key to wishes(id), so a reply can only ever hang off a registry item.
 * Replies to wall posts live in wall_replies and are handled here.
 */
class CommentController extends Controller
{
    /** Shown when nobody has told us who posted. */
    private const GUEST_NAME = 'Anonymous';

    public function index(Request $request, string $slug)
    {
        $celebration = Celebration::where('slug', $slug)->firstOrFail();

        return CommentResource::collection(
            $celebration->comments()->with(['user', 'reactions'])->latest()->paginate(30)
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
            $mediaUrl  = \App\Support\Images::store($request->file('image'), 'comments');
        } elseif ($request->hasFile('video')) {
            $mediaType = 'video';
            $mediaUrl  = $request->file('video')->store('comments', 'public');
        }

        $name = match (true) {
            $anonymous   => self::GUEST_NAME,
            (bool) $user => trim($user->first_name.' '.$user->last_name),
            default      => $request->guest_name ?: self::GUEST_NAME,
        };

        $comment = Comment::create([
            'celebration_id' => $request->celebration_id,
            // An anonymous post keeps no link back to the account that made it.
            'user_id'        => $anonymous ? null : $user?->id,
            // The device it came from, so a guest's wish can be matched to an
            // account made later. Not for anonymous posts: those stay unlinked.
            'visitor_id'     => $anonymous ? null : \App\Support\Visitors::id($request),
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
            'reaction_type' => ['required', 'in:love,celebrate,laugh,touched,fire,clap,like'],
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

        return response()->json($result + [
            'like_count' => $comment->fresh()->like_count,
            'reactions'  => (object) $comment->reactions()->pluck('reaction_type')->countBy()->all(),
        ]);
    }

    /** The thread under a wall post, oldest first so it reads as a conversation. */
    public function replies(Comment $comment)
    {
        $replies = $comment->wallReplies()->with('user')->oldest()->get()->map(fn ($r) => self::reply($r));

        return response()->json(['data' => $replies]);
    }

    /** Open to guests, like the post it answers. */
    public function storeReply(Request $request, Comment $comment)
    {
        $data = $request->validate([
            'message'    => ['required', 'string', 'max:500'],
            'guest_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $request->user();

        $reply = DB::transaction(function () use ($comment, $data, $user) {
            $reply = WallReply::create([
                'comment_id' => $comment->id,
                'user_id'    => $user?->id,
                'guest_name' => $user
                    ? trim($user->first_name.' '.$user->last_name)
                    : (($data['guest_name'] ?? null) ?: self::GUEST_NAME),
                'message'    => $data['message'],
            ]);

            $comment->increment('reply_count');

            return $reply;
        });

        return response()->json(self::reply($reply->load('user')), 201);
    }

    private static function reply(WallReply $r): array
    {
        return [
            'id'           => $r->id,
            'message'      => $r->message,
            'author'       => $r->user ? trim($r->user->first_name.' '.$r->user->last_name) : ($r->guest_name ?: 'Guest'),
            'author_photo' => \App\Support\Media::url($r->user?->profile_photo),
            'created_at'   => $r->created_at?->toIso8601String(),
        ];
    }
}
