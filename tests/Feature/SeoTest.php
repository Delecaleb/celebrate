<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\User;
use App\Support\StoryLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The tags that decide how this site appears in search results and in a
 * shared link.
 *
 * These break silently — a page keeps rendering perfectly while quietly
 * telling Google not to index it, or pointing every canonical at localhost.
 * Hence the coverage.
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    /** Pull one attribute value out of the rendered head. */
    private function meta(string $html, string $attr, string $name): ?string
    {
        preg_match('/<meta\s+' . $attr . '="' . preg_quote($name, '/') . '"\s+content="([^"]*)"/', $html, $m);

        return $m[1] ?? null;
    }

    /** @return array<int, array<string, mixed>> every JSON-LD block on the page */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

        return array_map(fn ($json) => json_decode(html_entity_decode($json), true), $m[1]);
    }

    public function test_the_home_page_carries_a_full_set_of_tags(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<title>CelebrateMi', $html);
        $this->assertNotEmpty($this->meta($html, 'name', 'description'));
        $this->assertStringContainsString('<link rel="canonical" href="' . config('app.url') . '"', $html);
        $this->assertStringContainsString('index, follow', $this->meta($html, 'name', 'robots'));

        // Open Graph and Twitter, both complete enough to render a card.
        foreach (['og:site_name', 'og:type', 'og:url', 'og:title', 'og:description', 'og:image', 'og:image:width'] as $tag) {
            $this->assertNotEmpty($this->meta($html, 'property', $tag), "{$tag} is missing");
        }
        $this->assertSame('summary_large_image', $this->meta($html, 'name', 'twitter:card'));
        $this->assertStringEndsWith('/og-image.png', $this->meta($html, 'property', 'og:image'));

        // Icons and the manifest.
        foreach (['/favicon.ico', '/icon-32.png', '/apple-touch-icon.png', '/site.webmanifest'] as $asset) {
            $this->assertStringContainsString($asset, $html);
            $this->assertFileExists(public_path(ltrim($asset, '/')), "{$asset} is referenced but not shipped");
        }
    }

    public function test_the_home_page_publishes_the_brand_and_its_spellings(): void
    {
        $types = collect($this->jsonLd($this->get('/')->getContent()))->keyBy('@type');

        $this->assertTrue($types->has('Organization'));
        $this->assertTrue($types->has('WebSite'));

        // The spellings people actually type have to be attached to the brand,
        // or a search for "CelebrateMe" never connects to this site.
        foreach (['CelebrateMe', 'Celebrate Me'] as $alias) {
            $this->assertContains($alias, $types['Organization']['alternateName']);
            $this->assertContains($alias, $types['WebSite']['alternateName']);
        }
    }

    public function test_the_faq_on_the_page_and_the_faq_in_the_schema_are_the_same(): void
    {
        $html = $this->get('/')->getContent();

        $faq = collect($this->jsonLd($html))->firstWhere('@type', 'FAQPage');

        $this->assertNotNull($faq, 'the home page publishes no FAQPage');
        $this->assertCount(count($faq['mainEntity']), \App\Http\Controllers\MainController::FAQ);

        // Marking up an answer that is not on the page is a manual action
        // waiting to happen, so check each one is really rendered.
        foreach ($faq['mainEntity'] as $entry) {
            $this->assertStringContainsString(e($entry['name']), $html);
            $this->assertStringContainsString(e($entry['acceptedAnswer']['text']), $html);
        }
    }

    public function test_a_story_page_shares_its_own_cover_and_breadcrumbs(): void
    {
        $story = app(StoryLibrary::class)->find('adaeze-at-30');
        $html  = $this->get(route('stories.show', 'adaeze-at-30'))->assertOk()->getContent();

        $this->assertSame('article', $this->meta($html, 'property', 'og:type'));
        $this->assertStringContainsString($story['cover'], $this->meta($html, 'property', 'og:image'));

        $graph = collect($this->jsonLd($html))->keyBy('@type');
        $this->assertTrue($graph->has('BreadcrumbList'));
        $this->assertTrue($graph->has('Article'));
        $this->assertCount(3, $graph['BreadcrumbList']['itemListElement']);
    }

    public function test_a_filtered_story_list_points_back_at_the_full_one(): void
    {
        $html = $this->get('/stories?q=wedding')->assertOk()->getContent();

        // Otherwise every ?q= combination competes with /stories in the index.
        $this->assertStringContainsString('<link rel="canonical" href="' . route('stories') . '"', $html);
    }

    public function test_the_story_search_actually_filters(): void
    {
        $all       = app(StoryLibrary::class)->all();
        $weddings  = app(StoryLibrary::class)->search('wedding');

        $this->assertNotEmpty($weddings);
        $this->assertLessThan(count($all), count($weddings));

        $this->get('/stories?q=wedding')
            ->assertOk()
            ->assertSee('matching');
    }

    public function test_signed_in_and_auth_pages_are_never_indexed(): void
    {
        $user = User::factory()->create();

        $pages = [
            '/login'           => null,
            '/register'        => null,
            '/forgot-password' => null,
            '/dashboard'       => $user,
        ];

        foreach ($pages as $url => $as) {
            $response = $as ? $this->actingAs($as)->get($url) : $this->get($url);
            $html     = $response->assertOk()->getContent();

            $this->assertSame('noindex, follow', $this->meta($html, 'name', 'robots'), "{$url} is indexable");
            $this->assertStringNotContainsString('rel="canonical"', $html, "{$url} should not claim a canonical");
        }
    }

    public function test_a_public_celebration_is_indexable_and_a_private_one_is_not(): void
    {
        $user = User::factory()->create();

        $public = $this->celebration($user, ['status' => 'published', 'is_public' => true]);
        $hidden = $this->celebration($user, ['status' => 'published', 'is_public' => false]);

        $this->assertStringContainsString(
            'index, follow',
            $this->meta($this->get(route('celebrations.show', $public->slug))->getContent(), 'name', 'robots')
        );

        $this->assertSame(
            'noindex, follow',
            $this->meta($this->get(route('celebrations.show', $hidden->slug))->getContent(), 'name', 'robots')
        );
    }

    public function test_the_sitemap_lists_the_public_pages_and_every_story(): void
    {
        $user      = User::factory()->create();
        $published = $this->celebration($user, ['status' => 'published', 'is_public' => true]);
        $draft     = $this->celebration($user, ['status' => 'draft', 'is_public' => true]);

        $xml = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->getContent();

        $this->assertStringStartsWith('<?xml', ltrim($xml));

        foreach (['home', 'features', 'how-it-works', 'pricing', 'stories'] as $route) {
            $this->assertStringContainsString('<loc>' . route($route) . '</loc>', $xml);
        }

        foreach (app(StoryLibrary::class)->all() as $story) {
            $this->assertStringContainsString(route('stories.show', $story['slug']), $xml);
        }

        $this->assertStringContainsString($published->slug, $xml);
        $this->assertStringNotContainsString($draft->slug, $xml, 'drafts must never be submitted for indexing');
    }

    public function test_robots_txt_points_at_the_sitemap_and_hides_private_areas(): void
    {
        $body = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Sitemap: ' . url('/sitemap.xml'), $body);

        foreach (['/dashboard', '/admin', '/login', '/wallet'] as $private) {
            $this->assertStringContainsString('Disallow: ' . $private, $body);
        }
    }

    private function celebration(User $user, array $attributes = []): Celebration
    {
        static $n = 0;
        $n++;

        return Celebration::create($attributes + [
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $user->id,
            'title'            => "Seo celebration {$n}",
            'slug'             => "seo-celebration-{$n}",
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Someone',
            'status'           => 'published',
            'is_public'        => true,
        ]);
    }
}
