<?php

namespace App\Console\Commands;

use App\Models\Celebration;
use App\Models\Comment;
use App\Models\Gift;
use App\Models\User;
use App\Models\WishContribution;
use App\Support\Push;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The reasons to open the app that nobody has to remember:
 *
 *   countdown  — the celebrant, 3 days out, 1 day out and on the day
 *   guests     — people who have celebrated this person before, the day before
 *                and on the day (there is no follow feature; having left a
 *                wish or a gift on one of their pages is the relationship)
 *   birthday   — your own birthday is two weeks away and there is no page yet
 *   memory     — a year ago today was your celebration
 *
 * Each goes once: push_reminders remembers what was sent, so running the
 * command twice in a day is harmless.
 */
class SendPushReminders extends Command
{
    protected $signature   = 'push:reminders';
    protected $description = 'Send push reminders: countdowns, guest nudges, birthday prompts and memories';

    private int $sent = 0;

    public function handle(): int
    {
        $this->countdowns();
        $this->guests();
        $this->birthdays();
        $this->memories();

        $this->info("Push reminders sent: {$this->sent}");

        return self::SUCCESS;
    }

    private function countdowns(): void
    {
        $copy = [
            3 => ['3 days to go! 🎈', 'Share your page now so the wishes start rolling in.'],
            1 => ['Tomorrow is the day! 🎉', 'Everything set? Take one last look at your page.'],
            0 => ['Today is the day! 🥳', 'Wishes are coming in — come and see them.'],
        ];

        foreach ($copy as $days => [$title, $body]) {
            foreach ($this->celebrationsOn(today()->addDays($days)) as $celebration) {
                $this->once($celebration->user, "countdown.{$days}", $celebration->id, $title, $body, $celebration);
            }
        }
    }

    private function guests(): void
    {
        foreach ([1 => 'tomorrow', 0 => 'today'] as $days => $when) {
            foreach ($this->celebrationsOn(today()->addDays($days)) as $celebration) {
                if ($celebration->allow_wishes === false) {
                    continue;
                }

                $name  = $celebration->celebrant_name ?: $celebration->user?->first_name ?: 'Someone';
                $what  = $celebration->celebration_type === 'birthday' ? 'birthday' : 'celebration';
                $title = $days ? "{$name}'s {$what} is tomorrow" : "It's {$name}'s {$what} today! 🎉";
                $body  = $days ? 'Get your wish in early — it takes a minute.' : 'Leave a wish and make their day.';

                foreach ($this->peopleWhoCelebrated($celebration) as $guest) {
                    $this->once($guest, "guest.{$days}", $celebration->id, $title, $body, $celebration);
                }
            }
        }
    }

    private function birthdays(): void
    {
        $day = today()->addDays(14);

        User::whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', $day->month)
            ->whereDay('date_of_birth', $day->day)
            ->whereHas('deviceTokens')
            ->each(function (User $user) use ($day) {
                $planned = Celebration::where('user_id', $user->id)
                    ->whereBetween('event_date', [today(), $day->copy()->addDays(14)])
                    ->exists();

                if ($planned) {
                    return;
                }

                $this->once(
                    $user,
                    'birthday.prompt',
                    (int) $day->format('Y'),
                    'Your birthday is in 2 weeks 🎂',
                    'Set up your page now so friends can start sending wishes.',
                    null,
                    '/modal/create-celebration',
                );
            });
    }

    private function memories(): void
    {
        foreach ($this->celebrationsOn(today()->subYear()) as $celebration) {
            if (! $celebration->comments()->exists()) {
                continue;
            }

            $this->once(
                $celebration->user,
                'memory.year',
                $celebration->id,
                'One year ago today 💜',
                "Relive the wishes from {$celebration->title}.",
                $celebration,
            );
        }
    }

    /** @return Collection<int, Celebration> */
    private function celebrationsOn(Carbon $date): Collection
    {
        return Celebration::with('user')
            ->whereDate('event_date', $date->toDateString())
            ->where('status', 'published')
            ->get();
    }

    /**
     * Signed-in people who left a wish, a gift or a contribution on any of
     * this owner's other pages — and have not already posted on this one.
     *
     * @return Collection<int, User>
     */
    private function peopleWhoCelebrated(Celebration $celebration): Collection
    {
        $others = Celebration::where('user_id', $celebration->user_id)
            ->where('id', '!=', $celebration->id)
            ->pluck('id');

        if ($others->isEmpty()) {
            return collect();
        }

        $ids = collect()
            ->merge(Comment::whereIn('celebration_id', $others)->whereNotNull('user_id')->pluck('user_id'))
            ->merge(Gift::whereIn('celebration_id', $others)->where('payment_status', 'paid')->whereNotNull('sender_user_id')->pluck('sender_user_id'))
            ->merge(WishContribution::whereIn('celebration_id', $others)->where('payment_status', 'paid')->whereNotNull('contributor_user_id')->pluck('contributor_user_id'))
            ->unique()
            ->reject(fn ($id) => $id === $celebration->user_id);

        $already = Comment::where('celebration_id', $celebration->id)->whereNotNull('user_id')->pluck('user_id');

        return User::whereIn('id', $ids->diff($already))->whereHas('deviceTokens')->get();
    }

    private function once(?User $user, string $kind, int $subjectId, string $title, string $body, ?Celebration $celebration, ?string $url = null): void
    {
        if (! $user) {
            return;
        }

        $fresh = DB::table('push_reminders')->insertOrIgnore([
            'user_id'    => $user->id,
            'kind'       => $kind,
            'subject_id' => $subjectId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! $fresh) {
            return;
        }

        Push::sendTo($user, $title, $body, [
            'type'           => "reminder.{$kind}",
            'url'            => $url ?? ($celebration ? "/celebration/{$celebration->slug}" : null),
            'celebration_id' => $celebration?->id,
        ], 'reminders');

        $this->sent++;
    }
}
