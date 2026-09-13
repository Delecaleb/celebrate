<?php

namespace App\Http\Controllers;

use App\Services\PaymentSystem\CurrencyService;
use App\Support\StoryLibrary;
use Illuminate\Http\Request;

/**
 * Public marketing site.
 *
 * Every page renders the same shell (layouts.marketing) with one of the
 * partials in resources/views/marketing/partials. When the client-side router
 * (resources/js/modules/pageRouter.js) asks for a page it sends `X-Partial: 1`
 * and gets back just that partial's HTML as JSON, so navigation never reloads.
 */
class MainController extends Controller
{
    /**
     * Nav bar items, in order. `route` doubles as the active-state key.
     */
    private const NAV = [
        ['route' => 'features',     'label' => 'Features'],
        ['route' => 'how-it-works', 'label' => 'How it works'],
        ['route' => 'pricing',      'label' => 'Pricing'],
        ['route' => 'stories',      'label' => 'Stories'],
    ];

    /**
     * The three stories quoted on the home page.
     *
     * Chosen by hand rather than sampled: one page somebody made for their own
     * birthday, one wedding whose guests were scattered, and one made for
     * somebody else — the three reasons people arrive here.
     */
    /**
     * The three stories quoted on the home page: a birthday, a wedding and a
     * long marriage, so the section is not three versions of one occasion.
     *
     * pick() drops a slug it cannot find, so a story removed from the roster
     * leaves a hole in the grid rather than an error. StoriesTest asserts all
     * three still resolve.
     */
    private const HOME_VOICES = ['adaeze-at-30', 'chidi-and-amaka', 'yusuf-and-halima-25'];

    /**
     * The home page FAQ, as data.
     *
     * The page renders it and the SEO component publishes it as FAQPage
     * structured data, so the two can never drift apart — a mismatch is
     * exactly what search engines penalise.
     */
    public const FAQ = [
        [
            'q' => 'What does it cost?',
            'a' => "Creating a page is free, and you don't need a card to start. We take a small fee on cash gifts you receive — nothing else.",
        ],
        [
            'q' => 'How long does the page stay up?',
            'a' => 'For good. Nothing expires and nothing gets archived once the day is over — the wishes, photos and voice notes stay exactly where they are.',
        ],
        [
            'q' => 'Is it CelebrateMi or CelebrateMe?',
            'a' => "The site is CelebrateMi, spelled with an i — celebratemi.com. Plenty of people type CelebrateMe or Celebrate Me looking for us, and they land in the right place either way.",
        ],
        [
            'q' => 'Do my guests need to download anything?',
            'a' => 'No. They open the link in whatever browser they already have, write their wish and — if they want to — send a gift. No app, no account, no password.',
        ],
        [
            'q' => 'How do I get the money out?',
            'a' => 'Gifts land in your CelebrateMi wallet as they arrive. Withdraw to your bank account whenever you like, in full or in parts.',
        ],
        [
            'q' => 'Can guests send from another country?',
            'a' => 'Yes. Local cards and transfers go through Paystack, international cards through Stripe.',
        ],
        [
            'q' => 'Is it only for birthdays?',
            'a' => 'Weddings, graduations, baby showers, naming ceremonies, anniversaries, new jobs, housewarmings, retirements, memorials — anything where people want to say something and send something.',
        ],
    ];

    public function home(Request $request, StoryLibrary $library)
    {
        return $this->respond($request, 'home', [
            'nav'         => 'home',
            'voices'      => $library->pick(self::HOME_VOICES),
            'title'       => 'CelebrateMi — celebration pages for wishes, photos and gifts',
            'description' => 'Create your celebration page in 30 seconds. Collect wishes, gifts and money from everyone who loves you — and keep the memories forever.',
            'structured'  => [$this->faqSchema()],
        ]);
    }

    /**
     * The FAQ, as schema.org FAQPage.
     *
     * @return array<string, mixed>
     */
    private function faqSchema(): array
    {
        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => array_map(fn (array $item) => [
                '@type'          => 'Question',
                'name'           => $item['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
            ], self::FAQ),
        ];
    }

    public function features(Request $request)
    {
        return $this->respond($request, 'features', [
            'nav'         => 'features',
            'title'       => 'Features — CelebrateMi',
            'description' => 'Wishes, cash gifts, group gifting, photobooks, custom themes and one shareable link. Everything a celebration page needs.',
        ]);
    }

    public function howItWorks(Request $request)
    {
        return $this->respond($request, 'how-it-works', [
            'nav'         => 'how-it-works',
            'title'       => 'How it works — CelebrateMi',
            'description' => 'Create a page, share the link, collect wishes and gifts, then withdraw and keep the memories. Three steps, thirty seconds.',
        ]);
    }

    public function pricing(Request $request)
    {
        // Same resolver the celebration pages use: a signed-in account keeps
        // the currency it was opened with, and a visitor is placed by IP —
        // Nigeria to naira, everywhere unmapped to the base currency. The
        // country lookup behind it is cached for a day per address, so this
        // costs a public page nothing after the first hit.
        $currency = app(CurrencyService::class)->forVisitor();

        return $this->respond($request, 'pricing', [
            'nav'         => 'pricing',
            'currency'     => $currency,
            'currencyName' => config("currency.currencies.{$currency}.name", $currency),
            'symbol'       => config("currency.currencies.{$currency}.symbol", $currency),
            'decimals'     => (int) config("currency.currencies.{$currency}.decimals", 2),
            'title'       => 'Pricing — CelebrateMi',
            'description' => 'Free to create a celebration page. You only pay a small fee on the cash gifts you receive.',
        ]);
    }

    public function terms(Request $request)
    {
        return $this->respond($request, 'terms', [
            'nav'         => 'terms',
            'title'       => 'Terms of service — CelebrateMi',
            'description' => 'The agreement between you and CelebrateMi: how gifts, fees, withdrawals, refunds and your celebration page work.',
        ]);
    }

    public function privacy(Request $request)
    {
        return $this->respond($request, 'privacy', [
            'nav'         => 'privacy',
            'title'       => 'Privacy policy — CelebrateMi',
            'description' => 'What CelebrateMi collects, why, who else ever sees it, and the rights you have over it. We never see card numbers and we never sell data.',
        ]);
    }

    public function stories(Request $request, StoryLibrary $library)
    {
        // ?q= is the target of the site-wide SearchAction in our structured
        // data, so it has to be a real filter, not a promise.
        $query   = trim((string) $request->query('q', ''));
        $stories = $query === '' ? $library->all() : $library->search($query);

        return $this->respond($request, 'stories', [
            'nav'         => 'stories',
            'title'       => $query === ''
                ? 'Stories — real CelebrateMi celebration pages'
                : "Stories matching “{$query}” — CelebrateMi",
            'description' => 'Thirty-six celebrations built on CelebrateMi — birthdays, weddings, graduations, memorials, and the pages they left behind.',
            'stories'     => $stories,
            'query'       => $query,
            // A filtered list is the same content sliced differently: point it
            // at the full list so the two never compete in the index.
            'canonical'   => route('stories'),
        ]);
    }

    /**
     * One archived celebration page.
     *
     * Rendered straight from resources/data/stories.json — these carry no
     * database rows, and the page itself is closed: no wishes, reactions or
     * gifts can be added to it.
     */
    public function story(Request $request, string $slug, StoryLibrary $library)
    {
        $story = $library->find($slug);

        abort_if($story === null, 404);

        return $this->respond($request, 'story', [
            'nav'         => 'stories',
            'title'       => "{$story['title']} — a {$story['occasion_label']} on CelebrateMi",
            'description' => "{$story['occasion_label']} for {$story['celebrant']} in {$story['location']} — {$story['wish_count']} wishes, kept exactly as they were left.",
            'story'       => $story,
            'more'        => $library->others($slug),
            // Shared links show the celebration's own cover, not the site card.
            'ogImage'     => StoryLibrary::photo($story['cover'], 1200, 630),
            'ogImageAlt'  => "{$story['title']} — {$story['occasion_label']} in {$story['location']}",
            'ogType'      => 'article',
            'structured'  => [$this->storyBreadcrumbs($story), $this->storySchema($story)],
        ]);
    }

    /**
     * Where this story sits, so search results can show the path rather than
     * a bare URL.
     *
     * @param  array<string, mixed>  $story
     * @return array<string, mixed>
     */
    private function storyBreadcrumbs(array $story): array
    {
        $crumbs = [
            ['name' => 'Home',    'url' => route('home')],
            ['name' => 'Stories', 'url' => route('stories')],
            ['name' => $story['title'], 'url' => route('stories.show', $story['slug'])],
        ];

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => array_map(fn (int $i, array $crumb) => [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['name'],
                'item'     => $crumb['url'],
            ], array_keys($crumbs), $crumbs),
        ];
    }

    /**
     * The story itself.
     *
     * Deliberately an Article rather than an Event: these pages are write-ups
     * of celebrations that have already happened, and the site — not any of
     * the people quoted — is the author.
     *
     * @param  array<string, mixed>  $story
     * @return array<string, mixed>
     */
    private function storySchema(array $story): array
    {
        return [
            '@context'         => 'https://schema.org',
            '@type'            => 'Article',
            'headline'         => "{$story['title']} — a {$story['occasion_label']} on CelebrateMi",
            'description'      => "{$story['occasion_label']} for {$story['celebrant']} in {$story['location']}.",
            'image'            => StoryLibrary::photo($story['cover'], 1200, 630),
            'articleSection'   => $story['occasion_label'],
            'contentLocation'  => ['@type' => 'Place', 'name' => $story['location']],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => route('stories.show', $story['slug']),
            ],
            'author'    => ['@type' => 'Organization', 'name' => config('seo.name')],
            'publisher' => ['@id' => rtrim(config('seo.url'), '/') . '/#organization'],
        ];
    }

    /**
     * Render the full shell, or just the partial when the router asks for it.
     */
    private function respond(Request $request, string $page, array $data)
    {
        $data['page']     = $page;
        $data['navItems'] = self::NAV;

        // One URL, two representations. Without these headers a browser can
        // cache the JSON under the page's address and show it raw on Back.
        if ($request->header('X-Partial')) {
            return response()->json([
                'title'       => $data['title'],
                'description' => $data['description'],
                'nav'         => $data['nav'],
                'html'        => view("marketing.partials.{$page}", $data)->render(),
            ])->header('Vary', 'X-Partial')->header('Cache-Control', 'no-store, private');
        }

        return response()->view('layouts.marketing', $data)->header('Vary', 'X-Partial');
    }
}
