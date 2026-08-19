<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Celebration extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'title',
        'slug',
        'celebration_type',
        'celebrant_name',
        'celebrant_photo',
        'description',
        'event_date',
        'start_date',
        'end_date',
        'venue',
        'is_public',
        'allow_wishes',
        'allow_gifts',
        'allow_media_uploads',
        'allow_guest_posts',
        'theme_color',
        'font_style',
        'cover_photo',
        'intro_video',
        'background_music',
        'view_count',
        'share_count',
        'comment_count',
        'status',
        'published_at',
        'template_id',
        'custom_bg',
        'custom_text',
        'frame_id',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'published_at' => 'datetime',
        'is_public' => 'boolean',
        'allow_wishes' => 'boolean',
        'allow_gifts' => 'boolean',
        'allow_media_uploads' => 'boolean',
        'allow_guest_posts' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCoverPhotosAttribute()
    {
        $value = $this->cover_photo;
        if (empty($value)) {
            return [];
        }
        if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
            return json_decode($value, true) ?: [];
        }
        return [$value];
    }

    public function frame()
    {
        return $this->belongsTo(Frame::class);
    }

    public function guests()
    {
        return $this->hasMany(CelebrationGuest::class);
    }

    public function wishes()
    {
        return $this->hasMany(Wish::class);
    }

    public function gifts()
    {
        return $this->hasMany(Gift::class);
    }

    /**
     * Money given through the registry. Counts towards the amount raised just
     * as much as a platform gift does.
     */
    public function contributions()
    {
        return $this->hasMany(WishContribution::class);
    }

    public function giftGoals()
    {
        return $this->hasMany(GiftGoal::class);
    }

    public function mediaUploads()
    {
        return $this->hasMany(MediaUpload::class);
    }

    public function timelinePosts()
    {
        return $this->hasMany(TimelinePost::class);
    }

    public function tickets()
    {
        return $this->hasMany(EventTicket::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function template()
    {
        return $this->belongsTo(CelebrationTemplate::class, 'template_id');
    }

    /**
     * Build the list of gift items shown on the cover-photo sidebar.
     *
     * Returns up to $limit items:
     *  - Paid gifts first, grouped by platform gift type with summed amounts.
     *  - Remaining slots filled with un-received platform gifts (totalUsd = 0).
     *
     * Assumes $this->gifts has already been eager-loaded with platformGift.
     */
    public function sidebarGifts(Collection $platformGifts, int $limit = 6): Collection
    {
        // Group confirmed gifts by the platform gift they represent
        $received = $this->gifts
            ->where('payment_status', 'paid')
            ->whereNotNull('platform_gift_id')
            ->groupBy('platform_gift_id')
            ->map(fn ($group) => (object) [
                'gift'     => $group->first()->platformGift,
                'totalUsd' => (float) $group->sum('amount'),
                'count'    => $group->count(),
            ])
            ->values();

        // Fill the remaining slots with suggested gifts that haven't been received yet
        $usedIds     = $received->pluck('gift.id')->filter();
        $suggestions = $platformGifts
            ->whereNotIn('id', $usedIds)
            ->take(max(0, $limit - $received->count()))
            ->map(fn ($g) => (object) [
                'gift'     => $g,
                'totalUsd' => 0.0,
                'count'    => 0,
            ]);

        return $received->concat($suggestions)->take($limit);
    }
}
