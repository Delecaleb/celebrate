<?php

namespace App\Http\Controllers;

use App\Models\Celebration;
use App\Support\StoryLibrary;
use Illuminate\Http\Response;

/**
 * sitemap.xml and robots.txt.
 *
 * Both are generated rather than static files, so a new story or a newly
 * published celebration is listed the moment it exists — and so the sitemap
 * URL inside robots.txt always matches the domain the app is actually served
 * from (APP_URL), instead of a hard-coded guess.
 */
class SitemapController extends Controller
{
    /**
     * Marketing pages, with how often they change and how much they matter
     * relative to each other. Priority is a hint, not a ranking.
     */
    private const PAGES = [
        ['route' => 'home',         'changefreq' => 'weekly',  'priority' => '1.0'],
        ['route' => 'how-it-works', 'changefreq' => 'monthly', 'priority' => '0.9'],
        ['route' => 'features',     'changefreq' => 'monthly', 'priority' => '0.9'],
        ['route' => 'pricing',      'changefreq' => 'monthly', 'priority' => '0.8'],
        ['route' => 'stories',      'changefreq' => 'weekly',  'priority' => '0.8'],
        ['route' => 'terms',        'changefreq' => 'yearly',  'priority' => '0.3'],
        ['route' => 'privacy',      'changefreq' => 'yearly',  'priority' => '0.3'],
    ];

    public function index(StoryLibrary $library): Response
    {
        $urls = [];

        foreach (self::PAGES as $page) {
            $urls[] = [
                'loc'        => route($page['route']),
                'changefreq' => $page['changefreq'],
                'priority'   => $page['priority'],
            ];
        }

        foreach ($library->all() as $story) {
            $urls[] = [
                'loc'        => route('stories.show', $story['slug']),
                'changefreq' => 'yearly',
                'priority'   => '0.7',
            ];
        }

        // Real celebration pages, but only the ones their owner made public and
        // published. Drafts and private pages must never appear here.
        Celebration::query()
            ->where('is_public', true)
            ->where('status', 'published')
            ->orderByDesc('updated_at')
            ->limit(5000)
            ->get(['slug', 'updated_at'])
            ->each(function (Celebration $celebration) use (&$urls) {
                $urls[] = [
                    'loc'        => route('celebrations.show', $celebration->slug),
                    'lastmod'    => $celebration->updated_at?->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority'   => '0.6',
                ];
            });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * robots.txt.
     *
     * Everything public is crawlable. Signed-in areas, auth screens and
     * anything with a query string that only reorders existing content are
     * kept out — they would spend crawl budget without adding a page.
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            '# Signed-in and transactional areas — nothing to index.',
            'Disallow: /dashboard',
            'Disallow: /profile',
            'Disallow: /admin',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /confirm-password',
            'Disallow: /verify-email',
            'Disallow: /wallet',
            'Disallow: /withdrawals',
            'Disallow: /bank-accounts',
            '',
            '# Filtered views of pages that are already listed in full.',
            'Disallow: /*?q=',
            '',
            'Sitemap: ' . url('/sitemap.xml'),
            '',
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
