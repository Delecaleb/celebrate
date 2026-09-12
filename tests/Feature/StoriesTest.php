<?php

namespace Tests\Feature;

use App\Support\StoryLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The stories index and the archived celebration pages behind it.
 *
 * These come from resources/data/stories.json, never the database, and the
 * pages they lead to must stay read-only — that is what the lock notice on
 * each one promises.
 */
class StoriesTest extends TestCase
{
    use RefreshDatabase;

    private function library(): StoryLibrary
    {
        return app(StoryLibrary::class);
    }

    public function test_every_story_has_artwork_nobody_else_uses(): void
    {
        // The roster is sized by the artwork: one story per cover, one per
        // avatar. Sharing a picture between two stories makes a page of nine
        // read as a page of five.
        $stories = $this->library()->all();

        foreach (['cover', 'avatar'] as $field) {
            $used = array_column($stories, $field);

            $this->assertSame(
                count($used),
                count(array_unique($used)),
                "Two stories share a {$field}: " . implode(', ', array_diff_assoc($used, array_unique($used)))
            );
        }
    }

    public function test_the_index_renders_every_story(): void
    {
        $stories = $this->library()->all();

        $this->assertNotEmpty($stories);

        $response = $this->get(route('stories'));
        $response->assertOk();

        // All 36 are in the markup — the button only reveals what is already there.
        foreach ($stories as $story) {
            $response->assertSee(route('stories.show', $story['slug']));
        }

        $response->assertSee('Show 6 more');
    }

    public function test_a_story_page_renders_its_wishes_and_gifts(): void
    {
        $story = $this->library()->find('adaeze-at-30');

        $this->assertNotNull($story);

        $this->get(route('stories.show', 'adaeze-at-30'))
            ->assertOk()
            ->assertSee($story['title'])
            ->assertSee($story['celebrant'])
            ->assertSee('Thirty looks unbothered on you', false)
            ->assertSee('Trip to Zanzibar')
            ->assertSee('This celebration is closed.');
    }

    public function test_a_story_page_offers_no_way_to_wish_react_or_gift(): void
    {
        $response = $this->get(route('stories.show', 'chidi-and-amaka'));

        $response->assertOk();
        $response->assertSee('Wishes are closed on this celebration');
        $response->assertSee('Gifting is closed.', false);

        // None of the endpoints that would write to a real celebration are
        // reachable from here. (The shell's own create-a-page sheet is not one
        // of them — that is the call to action, not this celebration.)
        foreach (['comment/store', 'comment/reply', 'comment/react', 'gift/payment'] as $endpoint) {
            $this->assertStringNotContainsString($endpoint, $response->getContent());
        }
    }

    /**
     * The home page quotes real stories, not copy of its own.
     *
     * "What actually happened" used to hold three invented testimonials. Each
     * card now carries a story's own words and links to the page it quotes, so
     * a reader can check it.
     */
    public function test_the_home_page_quotes_real_stories(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();

        // Read from the controller rather than repeating the list: pick() drops
        // a slug it cannot find, so trimming the roster would otherwise leave
        // the voices grid quietly a column short.
        $voices = (new \ReflectionClass(\App\Http\Controllers\MainController::class))
            ->getConstant('HOME_VOICES');

        $this->assertCount(3, $voices);

        foreach ($voices as $slug) {
            $story = $this->library()->find($slug);

            $this->assertNotNull($story, "{$slug} is quoted on the home page but no longer exists");
            $response->assertSee($story['pull_quote']);
            $response->assertSee($story['quote_by']);
            $response->assertSee(route('stories.show', $slug));
        }
    }

    public function test_the_register_page_quotes_a_real_story(): void
    {
        $story = $this->library()->find('chidi-and-amaka');

        $this->get('/register')
            ->assertOk()
            ->assertSee($story['quote_by'])
            ->assertSee($story['quote_meta']);
    }

    public function test_an_unknown_story_is_a_404(): void
    {
        $this->get('/stories/no-such-celebration')->assertNotFound();
    }

    public function test_the_stories_are_not_in_the_database(): void
    {
        $this->assertDatabaseCount('celebrations', 0);
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_the_roster_covers_a_spread_of_occasions(): void
    {
        // Nine stories that were all birthdays would say the platform is for
        // birthdays. What the roster has to protect now is the range of
        // reasons somebody makes a page, not the old 60/20/20 geography —
        // that went when the roster was cut to one story per photograph.
        $occasions = array_unique(array_column($this->library()->all(), 'occasion'));

        $this->assertGreaterThanOrEqual(6, count($occasions));

        foreach (['birthday', 'wedding', 'graduation'] as $staple) {
            $this->assertContains($staple, $occasions);
        }
    }

    /**
     * Every wish and gift is signed by one identifiable person.
     *
     * Not "Aunty Bisi" or "Mummy" — a page shows the name on somebody's
     * account, not how the celebrant addresses them. And not a collective
     * either: "ABU class of 2019" or a named club could be taken for a real
     * organisation that never agreed to appear here.
     */
    public function test_every_wish_and_gift_is_signed_by_a_person(): void
    {
        $relational = '/^(uncle|aunt|aunty|auntie|mummy|mum|mom|mamma|mama|daddy|dad|papa|pa|baba|grandma|grandpa|granny|gogo|nani|sister|brother|big bro|dr|mr|mrs|prof|professor|alhaji|hajiya|malam|chief|pastor|padre|father|coach|barrister|engineer)\b/i';
        $collective = '/\b(class|classmates|union|club|society|crew|team|squad|parish|church|choir|department|association|committee|cousins|grandchildren|children|daughters|sons|regulars|boys|girls|women|men|flatmates|apprentices|colleagues|students|staff|group|family|fc|afc|ltd)\b/i';

        foreach ($this->library()->all() as $story) {
            $entries = array_merge($story['wishes'], $story['gifts']);

            foreach ($entries as $entry) {
                $name = $entry['name'];
                $why  = "{$story['slug']} → {$name}";

                $this->assertDoesNotMatchRegularExpression($relational, $name, "{$why} reads as a relationship or a title, not a name");
                $this->assertDoesNotMatchRegularExpression($collective, $name, "{$why} reads as a group, not a person");
                $this->assertStringNotContainsString('&', $name, "{$why} is two people in one signature");
                $this->assertGreaterThanOrEqual(2, count(preg_split('/\s+/', trim($name))), "{$why} needs a surname");
            }
        }
    }

    public function test_every_story_is_complete_enough_to_render(): void
    {
        foreach ($this->library()->all() as $story) {
            $context = $story['slug'];

            foreach (['slug', 'title', 'celebrant', 'occasion_label', 'location', 'date', 'cover', 'currency', 'pull_quote'] as $key) {
                $this->assertNotEmpty($story[$key] ?? null, "{$context} is missing {$key}");
            }

            $this->assertGreaterThanOrEqual(5, count($story['wishes']), "{$context} has too few wishes");
            $this->assertGreaterThanOrEqual(3, count($story['gifts']), "{$context} has too few gifts");
            $this->assertNotEmpty($story['registry'], "{$context} has no registry");

            // The feed is a sample of a bigger page — never more than the total.
            $this->assertGreaterThan(
                count($story['wishes']),
                $story['wish_count'],
                "{$context} shows more wishes than it claims to have"
            );
        }
    }
}
