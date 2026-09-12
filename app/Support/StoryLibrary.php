<?php

namespace App\Support;

/**
 * The stories shown on /stories and the archived celebration pages behind them.
 *
 * These are illustrative, not customer records: everything lives in
 * resources/data/stories.json and nothing is ever written to the database. The
 * pages they lead to are rendered read-only and say so — no wish, reaction or
 * gift on them can be added to.
 *
 * Replace the JSON with real, permissioned stories when you have them; the
 * shape is the contract, nothing else needs to change.
 */
class StoryLibrary
{
    /** Occasion key → [label fallback, mdi icon]. */
    private const OCCASIONS = [
        'birthday'     => 'mdi-cake-variant',
        'wedding'      => 'mdi-ring',
        'graduation'   => 'mdi-school',
        'anniversary'  => 'mdi-heart',
        'baby_shower'  => 'mdi-baby-carriage',
        'naming'       => 'mdi-baby-face-outline',
        'memorial'     => 'mdi-candle',
        'retirement'   => 'mdi-beach',
        'promotion'    => 'mdi-trophy-outline',
        'housewarming' => 'mdi-home-heart',
        'other'        => 'mdi-party-popper',
    ];

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $cache = null;

    /**
     * Every story, in file order.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $path = resource_path('data/stories.json');
        $raw  = is_file($path) ? file_get_contents($path) : '[]';

        $stories = json_decode($raw ?: '[]', true) ?: [];

        return self::$cache = array_map(fn (array $story) => $this->decorate($story), $stories);
    }

    /**
     * Named stories, in the order asked for — for the handful the marketing
     * pages feature by hand. Unknown slugs are skipped rather than fatal, so a
     * renamed story degrades to one fewer card instead of a broken page.
     *
     * @param  array<int, string>  $slugs
     * @return array<int, array<string, mixed>>
     */
    public function pick(array $slugs): array
    {
        return array_values(array_filter(array_map(fn (string $slug) => $this->find($slug), $slugs)));
    }

    /**
     * Stories matching a phrase — title, celebrant, occasion or place.
     *
     * This backs /stories?q=, which is the target of the site-wide
     * SearchAction in our structured data, so it has to actually work.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $term): array
    {
        $term = mb_strtolower(trim($term));

        if ($term === '') {
            return $this->all();
        }

        return array_values(array_filter($this->all(), function (array $story) use ($term) {
            $haystack = mb_strtolower(implode(' ', [
                $story['title'],
                $story['celebrant'],
                $story['occasion_label'],
                $story['location'],
                $story['pull_quote'],
            ]));

            return str_contains($haystack, $term);
        }));
    }

    public function find(string $slug): ?array
    {
        foreach ($this->all() as $story) {
            if ($story['slug'] === $slug) {
                return $story;
            }
        }

        return null;
    }

    /**
     * A few other stories, for the foot of a story page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function others(string $slug, int $limit = 3): array
    {
        $others = array_values(array_filter($this->all(), fn ($s) => $s['slug'] !== $slug));

        shuffle($others);

        return array_slice($others, 0, $limit);
    }

    /**
     * Unsplash ids are stored bare so the page can ask for the size it needs.
     */
    /**
     * Artwork for a story.
     *
     * Covers are ours now and live in public/images/covers, sized once and
     * cropped by CSS — the width and height are what the slot asks for, kept
     * so callers read the same either way. Anything still shaped like an
     * Unsplash id (the avatars, and one throwback photo) keeps coming from
     * Unsplash until its replacement lands, so the two can be swapped over one
     * at a time rather than in a single flag day.
     */
    public static function photo(string $name, int $width, int $height): string
    {
        if (! str_starts_with($name, 'photo-')) {
            return asset("images/covers/{$name}.webp");
        }

        return "https://images.unsplash.com/{$name}?w={$width}&h={$height}&fit=crop&q=80";
    }

    /** Initials for a wish author, since these pages carry no guest avatars. */
    public static function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $first = mb_substr($words[0] ?? '?', 0, 1);
        $last  = count($words) > 1 ? mb_substr(end($words), 0, 1) : '';

        return mb_strtoupper($first . $last);
    }

    /**
     * Fill in what the page needs but the file shouldn't have to repeat.
     *
     * @param  array<string, mixed>  $story
     * @return array<string, mixed>
     */
    private function decorate(array $story): array
    {
        $story['icon'] = self::OCCASIONS[$story['occasion'] ?? 'other'] ?? self::OCCASIONS['other'];

        // Progress against the gift goal, capped — a page that raised more than
        // it asked for shows a full bar, not a broken one.
        $goal = (float) ($story['goal'] ?? 0);
        $story['progress'] = $goal > 0
            ? min(100, (int) round(((float) $story['raised'] / $goal) * 100))
            : 100;

        foreach ($story['registry'] ?? [] as $i => $item) {
            $itemGoal = (float) ($item['goal'] ?? 0);
            $story['registry'][$i]['progress'] = $itemGoal > 0
                ? min(100, (int) round(((float) $item['raised'] / $itemGoal) * 100))
                : 100;
            $story['registry'][$i]['funded'] = $itemGoal > 0 && (float) $item['raised'] >= $itemGoal;
        }

        return $story;
    }
}
