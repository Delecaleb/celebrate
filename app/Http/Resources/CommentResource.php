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

            'created_at'      => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
