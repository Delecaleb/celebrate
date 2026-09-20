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
        'last_report_sent_at' => 'datetime',
        'is_public' => 'boolean',
        'allow_wishes' => 'boolean',
        'allow_gifts' => 'boolean',
        'allow_media_uploads' => 'boolean',
        'allow_guest_posts' => 'boolean',
    ];

    /**
     * The day this celebration actually happens.
     *
     * event_date is what the Settings tab edits, but a page created from the
     * front-page form only ever sets start_date — so anything that counts down
     * to "the day" has to read both, or it silently never fires.
     */
    public function celebrationDate(): ?\Illuminate\Support\Carbon
    {
        return $this->event_date ?? $this->start_date;
    }

    /** Celebrations happening on one calendar day, by whichever date they carry. */
    public function scopeHappeningOn(\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder
    {
        $day = \Illuminate\Support\Carbon::parse($date)->toDateString();

        return $query->where(fn ($q) => $q
            ->whereDate('event_date', $day)
            ->orWhere(fn ($w) => $w->whereNull('event_date')->whereDate('start_date', $day)));
    }

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
    /**
     * The gifts this celebration has actually been sent.
     *
     * Only paid ones, and only ones that really arrived: the wall used to pad
     * itself out with suggestions from the catalogue, which made a page with
     * nothing received look like a page with six gifts on it — and gave every
     * one of them a price of zero.
     *
     * Most-received first, so the wall leads with what people actually chose.
     */
    public function sidebarGifts(int $limit = 24): Collection
    {
        return $this->gifts
            ->where('payment_status', 'paid')
            ->whereNotNull('platform_gift_id')
            ->groupBy('platform_gift_id')
            ->map(fn ($group) => (object) [
                // loadMissing so the wall can price a gift in the visitor's
                // currency without a query per tile.
                'gift'     => $group->first()->platformGift?->loadMissing('prices'),
                'totalUsd' => (float) $group->sum('amount'),
                // Items, not rows: one send of three cupcake boxes is three
                // cupcake boxes on the wall, not one.
                'count'    => (int) $group->sum(fn ($g) => max(1, (int) $g->quantity)),
            ])
            ->filter(fn ($item) => $item->gift !== null)
            ->sortByDesc('count')
            ->values()
            ->take($limit);
    }
}
