<?php

namespace App\Support;

use App\Models\DeviceToken;
use App\Models\PlatformNotification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One call to tell someone something.
 *
 * The notification is written to the activity feed first — that is the record,
 * and it is there whether or not the person has the app. The push to their
 * phones goes out after the response is sent, so a slow push service never
 * holds up the request that caused it. No queue worker is involved: like the
 * mail outbox, this rides on what the app already runs.
 */
class Push
{
    /** Expo accepts at most 100 messages per request. */
    private const CHUNK = 100;

    /**
     * @param  array<string, mixed>  $data  Delivered to the app with the push.
     *                                      Give it a `url` (an app path such as
     *                                      /celebration/some-slug) and a tap
     *                                      opens that screen.
     */
    public static function notify(
        ?User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $channel = 'activity',
    ): ?PlatformNotification {
        if (! $user) {
            return null;
        }

        $notification = PlatformNotification::create([
            'user_id' => $user->id,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'data'    => $data,
            'is_read' => false,
        ]);

        $send = fn () => static::sendTo($user, $title, $message, $data + ['type' => $type], $channel);

        // Outside a web request (a console command, a test) just send now.
        app()->runningInConsole() ? $send() : dispatch($send)->afterResponse();

        return $notification;
    }

    /** Push only — nothing written to the feed. Used by the reminders. */
    public static function sendTo(User $user, string $title, string $body, array $data = [], string $channel = 'activity'): void
    {
        $tokens = $user->deviceTokens()->pluck('token')->all();

        if (! $tokens) {
            return;
        }

        $badge = $user->notifications()->where('is_read', false)->count();

        $messages = array_map(fn ($token) => [
            'to'        => $token,
            'title'     => $title,
            'body'      => $body,
            'data'      => (object) $data,
            'sound'     => 'default',
            'badge'     => $badge,
            'channelId' => $channel,
            'priority'  => 'high',
        ], $tokens);

        foreach (array_chunk($messages, self::CHUNK) as $chunk) {
            static::post($chunk);
        }
    }

    private static function post(array $messages): void
    {
        try {
            $request = Http::timeout(8)->acceptJson()->asJson();

            if ($token = config('services.expo.access_token')) {
                $request = $request->withToken($token);
            }

            $response = $request->post(config('services.expo.push_url'), $messages);

            if (! $response->successful()) {
                Log::warning('Expo push refused', ['status' => $response->status(), 'body' => $response->body()]);

                return;
            }

            // Tickets come back in the order the messages went out. A phone
            // that uninstalled the app answers DeviceNotRegistered — forget it,
            // or every later notification wastes a slot on it.
            foreach ((array) $response->json('data', []) as $i => $ticket) {
                if (($ticket['details']['error'] ?? null) === 'DeviceNotRegistered' && isset($messages[$i])) {
                    DeviceToken::where('token', $messages[$i]['to'])->delete();
                }
            }
        } catch (Throwable $e) {
            // A push is a nicety. Never let it break what triggered it.
            Log::warning('Expo push failed', ['error' => $e->getMessage()]);
        }
    }
}
