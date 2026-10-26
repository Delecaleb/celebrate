<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\Gift;
use App\Models\PlatformNotification;
use App\Models\Reaction;
use App\Models\WallReply;
use App\Models\WishContribution;
use App\Support\Moderation;
use App\Support\Push;
use Illuminate\Support\Str;

/**
 * Turns what happens on a celebration page into notifications for the person
 * it happened to.
 *
 * Hung on the models rather than the controllers so the web and the app are
 * covered alike — a wish posted from a browser still buzzes the celebrant's
 * phone. Nobody is ever told about their own action.
 */
class ActivityObserver
{
    public function commentCreated(Comment $comment): void
    {
        $celebration = $comment->celebration;
        $owner       = $celebration?->user;

        if (! $owner || $comment->user_id === $owner->id || $comment->is_approved === false
            || Moderation::hasBlocked($owner->id, $comment->user_id)) {
            return;
        }

        $who  = $comment->guest_name ?: 'Someone';
        $body = $comment->message
            ? Str::limit(trim($comment->message), 120)
            : ($comment->media_type === 'video' ? 'Sent you a video wish 🎥' : 'Sent you a photo wish 📷');

        Push::notify($owner, 'wish.posted', "{$who} left you a wish", $body, [
            'url'            => "/celebration/{$celebration->slug}",
            'celebration_id' => $celebration->id,
            'comment_id'     => $comment->id,
        ], 'wishes');
    }

    public function reactionCreated(Reaction $reaction): void
    {
        if ($reaction->reactionable_type !== Comment::class) {
            return;
        }

        $comment = Comment::with('celebration')->find($reaction->reactionable_id);
        $author  = $comment?->user;

        if (! $author || $author->id === $reaction->user_id || Moderation::hasBlocked($author->id, $reaction->user_id)) {
            return;
        }

        // A wish that is going round collects reactions in a burst. One push
        // per wish per half hour says the same thing without the buzzing.
        $recent = PlatformNotification::where('user_id', $author->id)
            ->where('type', 'wish.reaction')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->get()
            ->contains(fn ($n) => ($n->data['comment_id'] ?? null) === $comment->id);

        if ($recent) {
            return;
        }

        $who   = $reaction->user ? $reaction->user->first_name : 'Someone';
        $emoji = self::emoji($reaction->reaction_type);
        $for   = $comment->celebration?->celebrant_name;

        Push::notify(
            $author,
            'wish.reaction',
            "{$who} reacted {$emoji} to your wish",
            $for ? "On {$for}'s celebration" : 'Tap to see it',
            [
                'url'            => $comment->celebration ? "/celebration/{$comment->celebration->slug}" : null,
                'celebration_id' => $comment->celebration_id,
                'comment_id'     => $comment->id,
            ],
        );
    }

    public function replyCreated(CommentReply $reply): void
    {
        $wish        = $reply->wish;
        $celebration = $wish?->celebration;
        $owner       = $celebration?->user;

        if (! $owner || $reply->user_id === $owner->id) {
            return;
        }

        $who = $reply->user?->first_name ?: ($reply->guest_name ?: 'Someone');

        Push::notify($owner, 'registry.reply', "{$who} replied about {$wish->name}", Str::limit((string) $reply->message, 120), [
            'url'            => "/celebration/{$celebration->slug}",
            'celebration_id' => $celebration->id,
            'wish_id'        => $wish->id,
        ]);
    }

    /** A reply on the wall reaches whoever posted the wish, and the celebrant. */
    public function wallReplyCreated(WallReply $reply): void
    {
        $comment     = $reply->comment()->with('celebration.user', 'user')->first();
        $celebration = $comment?->celebration;

        if (! $comment || ! $celebration) {
            return;
        }

        $who  = $reply->user?->first_name ?: ($reply->guest_name ?: 'Someone');
        $body = Str::limit((string) $reply->message, 120);
        $data = [
            'url'            => "/celebration/{$celebration->slug}",
            'celebration_id' => $celebration->id,
            'comment_id'     => $comment->id,
        ];

        $told = [];

        foreach ([[$comment->user, "{$who} replied to your wish"], [$celebration->user, "{$who} replied on your page"]] as [$person, $title]) {
            if (! $person || $person->id === $reply->user_id || in_array($person->id, $told, true)
                || Moderation::hasBlocked($person->id, $reply->user_id)) {
                continue;
            }

            Push::notify($person, 'wish.reply', $title, $body, $data, 'wishes');
            $told[] = $person->id;
        }
    }

    /** Gifts are written as pending and flipped to paid once the money lands. */
    public function giftSaved(Gift $gift): void
    {
        if (! self::becamePaid($gift)) {
            return;
        }

        $celebration = $gift->celebration;
        $owner       = $celebration?->user;

        if (! $owner || $gift->sender_user_id === $owner->id) {
            return;
        }

        $who = $gift->is_anonymous ? 'Someone' : ($gift->sender?->first_name ?: ($gift->sender_name ?: 'Someone'));

        Push::notify($owner, 'gift.received', "{$who} sent you a gift 🎁", $gift->label(), [
            'url'            => "/celebration/{$celebration->slug}",
            'celebration_id' => $celebration->id,
            'gift_id'        => $gift->id,
        ], 'gifts');
    }

    public function contributionSaved(WishContribution $contribution): void
    {
        if (! self::becamePaid($contribution)) {
            return;
        }

        $wish        = $contribution->wish;
        $celebration = $contribution->celebration ?? $wish?->celebration;
        $owner       = $celebration?->user;

        if (! $owner || ! $wish || $contribution->contributor_user_id === $owner->id) {
            return;
        }

        $who    = $contribution->is_anonymous ? 'Someone' : ($contribution->contributor?->first_name ?: ($contribution->contributor_name ?: 'Someone'));
        $amount = self::money($contribution->amount, $contribution->currency);

        $wish->refresh();
        $target  = (float) $wish->target_amount;
        $percent = $target > 0 ? min(100, (int) floor(((float) $wish->current_amount / $target) * 100)) : null;

        $body = match (true) {
            $percent === 100 => "{$wish->name} is fully covered! 🎉",
            $percent !== null => "{$wish->name} is now {$percent}% of the way there",
            default           => $wish->name,
        };

        Push::notify($owner, 'registry.contribution', "{$who} chipped in {$amount}", $body, [
            'url'            => "/celebration/{$celebration->slug}",
            'celebration_id' => $celebration->id,
            'wish_id'        => $wish->id,
        ], 'gifts');
    }

    private static function becamePaid($model): bool
    {
        return $model->payment_status === 'paid'
            && ($model->wasRecentlyCreated || $model->wasChanged('payment_status'));
    }

    private static function money($amount, ?string $currency): string
    {
        $symbol = config("currency.currencies.{$currency}.symbol", $currency ? "{$currency} " : '');

        return $symbol.number_format((float) $amount, (float) $amount == floor((float) $amount) ? 0 : 2);
    }

    private static function emoji(?string $type): string
    {
        return match ($type) {
            'celebrate' => '🎉',
            'laugh'     => '😂',
            'touched'   => '🥹',
            'fire'      => '🔥',
            default     => '❤️',
        };
    }
}
