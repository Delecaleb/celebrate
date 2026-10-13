<?php

namespace App\Http\Middleware;

use App\Support\Visitors;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Counts the page, and makes sure the visitor has an id to count it against.
 *
 * On the website this sits on every request: the cookie is minted on the
 * first page someone opens, whatever page that is, so a wish they leave a
 * moment later already carries it. A page is recorded only when it is a real
 * one — a GET that rendered, by a person rather than a crawler, not the admin
 * panel, and not a staff member looking at the site.
 *
 * The app has no cookies; the API celebration route carries this by name and
 * the device header stands in for the cookie.
 */
class TrackVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $api = $request->is('api/*');

        // Before the page runs, so anything it does can read the id.
        if ($request->isMethod('GET') && ! $api) {
            Visitors::resolve($request);
        }

        $response = $next($request);

        try {
            $this->record($request, $response, $api ? 'app' : 'web');
        } catch (\Throwable $e) {
            Log::warning('Visit tracking failed', ['error' => $e->getMessage()]);
        }

        return $response;
    }

    private function record(Request $request, Response $response, string $source): void
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return;
        }

        // Crawlers come to the website. The app announces itself only by its
        // HTTP library (okhttp, CFNetwork), which is no crawler, and without
        // its device header nothing is recorded anyway.
        if ($source === 'web' && ($request->ajax() || $request->expectsJson() || $request->is('admin', 'admin/*', 'up') || Visitors::isBot($request->userAgent()))) {
            return;
        }

        if (auth('admin')->check()) {
            return;
        }

        $visitorId = Visitors::resolve($request);
        if (! $visitorId) {
            return;
        }

        Visitors::record($request, $visitorId, Visitors::celebrationId($request), $source);
    }
}
