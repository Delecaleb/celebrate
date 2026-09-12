<?php

namespace Database\Seeders;

use App\Models\PlatformAvailableGift;
use Illuminate\Database\Seeder;

/**
 * The gift catalogue.
 *
 * Thirty gifts running from something a student can send between lectures to
 * something a family clubs together for. That range is the point: a page where
 * the cheapest gift is £20 quietly tells most guests not to bother, and a page
 * where the most expensive is £20 gives the people who want to do something
 * substantial nowhere to put it.
 *
 * Prices are USD, the base currency — a Nigerian guest sees them converted to
 * naira automatically. Icons are Material Design Icons, already loaded on
 * every page.
 *
 * Safe to re-run: gifts are matched by name, so editing a price here and
 * seeding again updates it rather than creating a duplicate.
 */
class PlatformGiftSeeder extends Seeder
{
    /**
     * Naira prices, set by hand rather than converted.
     *
     * A conversion is arithmetically right and commercially wrong: ₦651.37 is
     * not a price anybody puts on a gift. These are the round figures a
     * Nigerian guest expects to see, and they are what gets charged.
     *
     * Any currency without an entry here falls back to converting the USD
     * default, so adding a market never leaves a gift unpriced.
     *
     * @return array<string, float>
     */
    private function nairaPrices(): array
    {
        return [
            'A Wink'               => 800,
            'Warm Hug'             => 1000,
            'Handwritten Note'     => 1500,
            'Bar of Chocolate'     => 2000,
            'Single Rose'          => 3000,
            'Cold Malt'            => 3500,
            'Data Bundle'          => 7500,
            'Suya Night'           => 9000,
            'Coffee for a Week'    => 13000,
            'Box of Cupcakes'      => 15000,
            'Small Chops Tray'     => 18000,
            'The Book They Want'   => 20000,
            'Jollof for the House' => 25000,
            'Round of Cocktails'   => 30000,
            'Movie Night for Two'  => 35000,
            'Birthday Cake'        => 45000,
            'Champagne Toast'      => 65000,
            'Salon Appointment'    => 80000,
            'Their Perfume'        => 95000,
            'Spa Afternoon'        => 120000,
            'A Week of Groceries'  => 60000,
            'Fuel for the Month'   => 100000,
            'Aso-Ebi Fabric'       => 130000,
            'Good Headphones'      => 175000,
            'Photoshoot'           => 220000,
            'Weekend Getaway'      => 450000,
            'Gold Bracelet'        => 650000,
            'Designer Bag'         => 1000000,
            'Flight Home'          => 1300000,
            'The Whole Trip'       => 2200000,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function gifts(): array
    {
        return [
            // ── Small & sweet ── the ones anybody can send ─────────────────
            ['A Wink',             0.50,  'mdi-emoticon-wink-outline',        'small',  '#f59e0b', 'The cheapest way to say I saw your page and I could not scroll past.'],
            ['Warm Hug',           0.75,  'mdi-hand-heart-outline',           'small',  '#f472b6', 'For when you are too far away to give them the real thing.'],
            ['Handwritten Note',   1.00,  'mdi-note-text-outline',            'small',  '#8b5cf6', 'A few lines that will still be on this page in ten years.'],
            ['Bar of Chocolate',   1.50,  'mdi-cookie-outline',               'small',  '#a16207', 'Small, sweet, and gone in four minutes. Worth it.'],
            ['Single Rose',        2.00,  'mdi-flower-outline',               'small',  '#e11d48', 'One stem says it as well as fifty, and they know it.'],
            ['Cold Malt',          2.50,  'mdi-bottle-soda-classic-outline',  'small',  '#0891b2', 'Straight from the freezer, the way it should be.'],

            // ── Treats ── a real gesture, still easy on the pocket ─────────
            ['Data Bundle',        5.00,  'mdi-wifi',                         'treats', '#0ea5e9', 'So they can post the photos before the day is over.'],
            ['Suya Night',         6.00,  'mdi-food-drumstick-outline',       'treats', '#dc2626', 'Extra yaji, extra onions, no apologies.'],
            ['Coffee for a Week',  9.00,  'mdi-coffee-outline',               'treats', '#78350f', 'Five mornings they will not have to face unassisted.'],
            ['Box of Cupcakes',    10.00, 'mdi-cupcake',                      'treats', '#ec4899', 'Six of them. They will admit to sharing two.'],
            ['Small Chops Tray',   12.00, 'mdi-food-fork-drink',              'treats', '#f97316', 'Puff-puff, spring rolls, samosas. The tray that disappears first.'],
            ['The Book They Want', 14.00, 'mdi-book-open-page-variant-outline','treats','#0d9488', 'The one that has been in their basket since March.'],

            // ── Party ── for the day itself ────────────────────────────────
            ['Jollof for the House', 18.00, 'mdi-pot-steam-outline',          'party',  '#ea580c', 'Enough for everyone who turns up unannounced. There will be some.'],
            ['Round of Cocktails', 22.00, 'mdi-glass-cocktail',               'party',  '#db2777', 'Whatever they are drinking, the next one is on you.'],
            ['Movie Night for Two',25.00, 'mdi-movie-open-outline',           'party',  '#6366f1', 'Tickets, popcorn, and the good seats in the middle.'],
            ['Birthday Cake',      30.00, 'mdi-cake-variant',                 'party',  '#f43f5e', 'The one thing the day genuinely cannot happen without.'],
            ['Champagne Toast',    45.00, 'mdi-glass-flute',                  'party',  '#ca8a04', 'For the moment everybody goes quiet and looks at them.'],

            // ── Beauty & self-care ────────────────────────────────────────
            ['Salon Appointment',  55.00, 'mdi-hair-dryer-outline',           'beauty', '#a855f7', 'Washed, set, and photographed from the good side.'],
            ['Their Perfume',      65.00, 'mdi-spray-bottle',                 'beauty', '#7c3aed', 'The bottle they keep picking up and putting back down.'],
            ['Spa Afternoon',      80.00, 'mdi-flower-tulip-outline',         'beauty', '#14b8a6', 'Three hours where nobody is allowed to need anything from them.'],

            // ── Home & everyday ── the practical, deeply appreciated ones ──
            ['A Week of Groceries',40.00, 'mdi-basket-outline',               'home',   '#16a34a', 'Unromantic, enormously useful, quietly remembered.'],
            ['Fuel for the Month', 70.00, 'mdi-gas-station-outline',          'home',   '#334155', 'One less thing to work out at the end of the month.'],
            ['Aso-Ebi Fabric',     90.00, 'mdi-hanger',                       'home',   '#be185d', 'So they stand with the family in the photographs, properly dressed.'],
            ['Good Headphones',    120.00,'mdi-headphones',                   'home',   '#1e293b', 'For the commute, the gym, and ignoring everyone on purpose.'],
            ['Photoshoot',         150.00,'mdi-camera-outline',               'home',   '#0f766e', 'Because this year deserves a picture that is not a selfie.'],

            // ── Grand gestures ── the ones people club together for ────────
            ['Weekend Getaway',    300.00,'mdi-bag-suitcase-outline',         'grand',  '#0284c7', 'Two nights somewhere they have to switch the laptop off.'],
            ['Gold Bracelet',      450.00,'mdi-diamond-stone',                'grand',  '#eab308', 'Something with weight to it, that outlasts the day by decades.'],
            ['Designer Bag',       700.00,'mdi-purse-outline',                'grand',  '#7c2d12', 'The one they have described to you, in detail, more than once.'],
            ['Flight Home',        900.00,'mdi-airplane',                     'grand',  '#2563eb', 'For the person who has not seen their mother in three years.'],
            ['The Whole Trip',     1500.00,'mdi-earth',                       'grand',  '#4f46e5','Flights, hotel, the lot. Somebody has to start the contributions.'],
        ];
    }

    public function run(): void
    {
        $naira = $this->nairaPrices();

        foreach ($this->gifts() as $index => [$name, $price, $icon, $category, $accent, $description]) {
            $gift = PlatformAvailableGift::updateOrCreate(
                ['gift_name' => $name],
                [
                    'gift_description' => $description,
                    'gift_icon'        => $icon,
                    'accent_color'     => $accent,
                    'category'         => $category,
                    'gift_price'       => $price,
                    'status'           => PlatformAvailableGift::STATUS_ACTIVE,
                    'sort_order'       => ($index + 1) * 10,
                ]
            );

            // Only for currencies this install actually supports — seeding a
            // price for a currency that is not configured would be a row
            // nothing ever reads.
            $supported = array_keys(PlatformAvailableGift::supportedCurrencies());

            if (isset($naira[$name]) && in_array('NGN', $supported, true)) {
                $gift->syncPrices(['NGN' => $naira[$name]]);
            }
        }

        $this->command?->info('Seeded ' . count($this->gifts()) . ' platform gifts, $0.50 to $1,500, with naira prices set by hand.');
    }
}
