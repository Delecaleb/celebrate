<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Someone saying a wall post, reply, celebration page or person should not be
 * there. Staff review these under Admin → Reports.
 */
class Report extends Model
{
    /** What can be reported, as the app names it → the model behind it. */
    public const TYPES = [
        'wish'        => Comment::class,
        'reply'       => WallReply::class,
        'celebration' => Celebration::class,
        'user'        => User::class,
    ];

    /** The reasons offered in the app, in the order it lists them. */
    public const REASONS = [
        'spam'          => 'Spam or scam',
        'harassment'    => 'Bullying or harassment',
        'hate'          => 'Hate speech or symbols',
        'sexual'        => 'Nudity or sexual content',
        'violence'      => 'Violence or threats',
        'impersonation' => 'Pretending to be someone else',
        'other'         => 'Something else',
    ];

    protected $fillable = [
        'reporter_user_id',
        'reporter_device',
        'reportable_type',
        'reportable_id',
        'reason',
        'details',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    /** The reported thing, hidden or not — staff must be able to see it. */
    public function subject(): ?Model
    {
        $class = self::TYPES[$this->reportable_type] ?? null;

        if (! $class) {
            return null;
        }

        $query = $class::query();

        if (in_array($class, [Comment::class, WallReply::class], true)) {
            $query->withoutGlobalScope('visible');
        }

        return $query->find($this->reportable_id);
    }
}
