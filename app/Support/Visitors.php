<?php

namespace App\Support;

use App\Models\Celebration;
use App\Models\PageView;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Who is looking, without asking them.
 *
 * A visitor is told apart by an id that costs them nothing: on the website a
 * cookie set on their first page and kept for a year, in the app the device id
 * it already sends with every request. Each page they open is recorded against
 * it (TrackVisit middleware). When they sign up, attribute() finds the pages
 * that id opened before the account existed, and the first celebration among
 * them is written on the account as the one that brought them — so "how did
 * this person find us?" is answered by whose page they opened first.
 *
 * Nothing identifying is kept about someone who never signs up: no IP, no
 * name, a hashed session key. The id itself is a random string.
 */
final class Visitors
{
    public const COOKIE = 'cm_vid';

    /** Chrome caps a cookie at 400 days; this is that. */
    private const COOKIE_MINUTES = 400 * 24 * 60;

    /** Where the id is kept for the rest of the request, once worked out. */
    private const ATTRIBUTE = 'visitor_id';

    /**
     * Link previews, crawlers and scripts open pages too; they are not
     * visitors. Applied to the website only — see TrackVisit.
     */
    private const BOTS = '/bot|crawl|spider|slurp|facebookexternalhit|facebot|whatsapp|telegram|twitterbot|linkedinbot|discordbot|skypeuripreview|preview|embedly|quora link|pinterest|headless|lighthouse|pagespeed|curl\/|wget\/|python-requests|go-http-client|java\/|axios\/|node-fetch|uptime|monitor/i';

    /**
     * The visitor id this request carries, or null when it carries none.
     *
     * The website's cookie first; failing that, the app's device header.
     */
    public static function id(Request $request): ?string
    {
        if ($known = $request->attributes->get(self::ATTRIBUTE)) {
            return $known;
        }

        $id = self::valid($request->cookie(self::COOKIE))
            ?? self::valid($request->header('X-Device-Id'));

        if ($id) {
            $request->attributes->set(self::ATTRIBUTE, $id);
        }

        return $id;
    }

    /**
     * The visitor id for a website request, minting and setting the cookie
     * when there is none yet. Null for an app request with no device header.
     */
    public static function resolve(Request $request): ?string
    {
        if ($id = self::id($request)) {
            return $id;
        }

        // The app has no cookie jar; without its header there is no visitor.
        if ($request->is('api/*')) {
            return null;
        }

        $id = (string) Str::uuid();
        $request->attributes->set(self::ATTRIBUTE, $id);

        Cookie::queue(cookie(
            self::COOKIE,
            $id,
            self::COOKIE_MINUTES,
            '/',
            null,
            $request->isSecure(),
            true,   // not readable from page scripts
            false,
            'lax',
        ));

        return $id;
    }

    public static function isBot(?string $userAgent): bool
    {
        return $userAgent === null || trim($userAgent) === '' || preg_match(self::BOTS, $userAgent) === 1;
    }

    /**
     * Record one page opened. Never throws: a page is not worth less because
     * the count of it failed.
     */
    public static function record(Request $request, string $visitorId, ?int $celebrationId, string $source): void
    {
        try {
            PageView::create([
                'visitor_id'     => $visitorId,
                'session_id'     => self::sessionKey($request, $visitorId),
                'user_id'        => $request->user()?->id,
                'celebration_id' => $celebrationId,
                'path'           => Str::limit('/' . ltrim($request->path(), '/'), 250, ''),
                'referrer'       => self::externalReferrer($request),
                'source'         => $source,
                'viewed_at'      => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Page view not recorded', ['path' => $request->path(), 'error' => $e->getMessage()]);
        }
    }

    /**
     * Which celebration a request is looking at, if it is a celebration page.
     *
     * Read off the route rather than the controller, so the page itself has
     * no idea it is being counted.
     */
    public static function celebrationId(Request $request): ?int
    {
        $route = $request->route();
        $slug  = $route?->parameter('slug');

        if (! is_string($slug) || $slug === '' || ! str_ends_with((string) $route->getActionName(), 'CelebrationController@show')) {
            return null;
        }

        return Celebration::where('slug', $slug)->value('id');
    }

    /**
     * A new account: write on it how its visitor found the site.
     *
     * The first celebration page the visitor ever opened is the one that
     * brought them. Their earlier page views are then tied to the account,
     * so what they looked at before signing up stays readable.
     */
    public static function attribute(User $user, ?string $visitorId): void
    {
        if (! $visitorId) {
            return;
        }

        try {
            $first = PageView::where('visitor_id', $visitorId)
                ->whereNotNull('celebration_id')
                ->orderBy('viewed_at')
                ->first();

            $earliest = $first
                ? PageView::where('visitor_id', $visitorId)->orderBy('viewed_at')->first()
                : null;

            $user->forceFill([
                'visitor_id'                    => $visitorId,
                'discovered_via_celebration_id' => $first?->celebration_id,
                'discovered_at'                 => $first?->viewed_at,
                'first_referrer'                => $earliest?->referrer ? Str::limit(parse_url($earliest->referrer, PHP_URL_HOST) ?: $earliest->referrer, 250, '') : null,
            ])->save();

            PageView::where('visitor_id', $visitorId)->whereNull('user_id')->update(['user_id' => $user->id]);
        } catch (\Throwable $e) {
            Log::warning('Sign-up not attributed', ['user' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /** An id is a UUID or a device id: nothing else is trusted into the database. */
    private static function valid(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9._-]{8,64}$/', $value) ? $value : null;
    }

    /**
     * What one visit is. On the website, the session — a hash of it, since
     * the real id opens the account. In the app, which has no session, the
     * device within the same half hour.
     */
    private static function sessionKey(Request $request, string $visitorId): string
    {
        $raw = $request->hasSession()
            ? $request->session()->getId()
            : $visitorId . ':' . intdiv(time(), 1800);

        return substr(sha1($raw), 0, 40);
    }

    /** The referring page, only when it is on another site. */
    private static function externalReferrer(Request $request): ?string
    {
        $referrer = (string) $request->headers->get('referer', '');
        $host     = parse_url($referrer, PHP_URL_HOST);

        if (! $host || strcasecmp($host, (string) $request->getHost()) === 0) {
            return null;
        }

        return Str::limit($referrer, 500, '');
    }
}
