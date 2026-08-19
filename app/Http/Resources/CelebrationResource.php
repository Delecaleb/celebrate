<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The card shape — what My Events, Upcoming and Discover render.
 *
 * Deliberately does not load wishes/gifts/comments; the detail resource does
 * that. Everything the card shows is either a column or a *_count.
 */
class CelebrationResource extends JsonResource
{
    public function toArray($request): array
    {
        $covers = Media::urls($this->cover_photos);

        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'title'            => $this->title,
            'slug'             => $this->slug,
            'celebration_type' => $this->celebration_type,
            'celebrant_name'   => $this->celebrant_name,
            'description'      => $this->description,
            'venue'            => $this->venue,

            'event_date' => $this->event_date?->toIso8601String(),
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date'   => $this->end_date?->toIso8601String(),
            // What the card prints under the title: event_date, else start_date.
            'display_date' => ($this->event_date ?? $this->start_date)?->toIso8601String(),

            'status'       => $this->status,
            'is_public'    => (bool) $this->is_public,
            'published_at' => $this->published_at?->toIso8601String(),

            'allow_wishes'        => (bool) $this->allow_wishes,
            'allow_gifts'         => (bool) $this->allow_gifts,
            'allow_media_uploads' => (bool) $this->allow_media_uploads,
            'allow_guest_posts'   => (bool) $this->allow_guest_posts,

            'cover_photos' => $covers,
            'cover_photo'  => $covers[0] ?? null,
            'theme_color'  => $this->theme_color,
            'font_style'   => $this->font_style,
            'custom_bg'    => $this->custom_bg,
            'custom_text'  => $this->custom_text,

            'view_count'    => (int) $this->view_count,
            'share_count'   => (int) $this->share_count,
            'comment_count' => (int) $this->comment_count,
            'gifts_count'   => $this->whenCounted('gifts'),
            'wishes_count'  => $this->whenCounted('wishes'),

            'owner' => new UserResource($this->whenLoaded('user')),

            'web_url'    => route('celebrations.show', $this->slug),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
