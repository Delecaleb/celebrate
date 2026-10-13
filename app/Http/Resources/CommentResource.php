<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A wish on the wall — text, and optionally one image or one video.
 *
 * media_type comes out of the DB as '' for text-only rows (the column is NOT
 * NULL), so it is normalised to null here rather than making the client test
 * for an empty string.
 */
class CommentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'message'    => $this->message,
            'author'     => $this->user
                ? trim(($this->user->first_name ?? '').' '.($this->user->last_name ?? ''))
                : ($this->guest_name ?: 'Guest'),
            'author_photo' => Media::url($this->user?->profile_photo),
            'is_guest'     => $this->user_id === null,

            'media_type' => $this->media_type ?: null,
            'media_url'  => Media::url($this->media_url),

            'is_pinned'   => (bool) $this->is_pinned,
            'like_count'  => (int) $this->like_count,
            'reply_count' => (int) $this->reply_count,

            // Only when the reactions were loaded with the comment — counting
            // them per row here would be a query per wish on the wall.
            'reactions'   => $this->whenLoaded('reactions', fn () => (object) $this->reactions
                ->countBy('reaction_type')
                ->all()),
            'my_reaction' => $this->whenLoaded('reactions', fn () => $this->viewerReaction($request)),

            'created_at'      => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }

    /** The viewer's own reaction — a guest is known by device, as in react(). */
    private function viewerReaction($request): ?string
    {
        $user = $request->user();

        if ($user) {
            return $this->reactions->firstWhere('user_id', $user->id)?->reaction_type;
        }

        $guestId = (string) $request->header('X-Device-Id', $request->ip());

        return $this->reactions
            ->first(fn ($r) => $r->user_id === null && $r->guest_identifier === $guestId)
            ?->reaction_type;
    }
}
