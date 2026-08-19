<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\Gift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CelebrationPageTest extends TestCase
{
    use RefreshDatabase;

    private function celebration(User $owner, array $attributes = []): Celebration
    {
        static $n = 0;
        $n++;

        return Celebration::create($attributes + [
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $owner->id,
            'title'            => "Sandra's Birthday",
            'slug'             => "sandra-{$n}",
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Sandra',
            'status'           => 'published',
            'is_public'        => true,
        ]);
    }

    public function test_a_visitor_sees_three_tabs_and_no_owner_tools(): void
    {
        $celebration = $this->celebration(User::factory()->create());

        $response = $this->get(route('celebrations.show', $celebration->slug))->assertOk();

        $response->assertSee('Wishes');
        $response->assertSee('Registry');
        $response->assertSee('Gifts');

        // owner-only tabs stay off the page entirely
        $response->assertDontSee("tab = 'settings'", false);
        $response->assertDontSee("tab = 'photobook'", false);
        $response->assertDontSee('Page details');
    }

    public function test_the_owner_sees_all_five_tabs(): void
    {
        $owner       = User::factory()->create();
        $celebration = $this->celebration($owner);

        $response = $this->actingAs($owner)
            ->get(route('celebrations.show', $celebration->slug))
            ->assertOk();

        foreach (['wishes', 'registry', 'gifts', 'settings', 'photobook'] as $tab) {
            $response->assertSee("tab = '{$tab}'", false);
        }
        $response->assertSee('Page details');
    }

    public function test_the_page_title_is_the_celebration(): void
    {
        $celebration = $this->celebration(User::factory()->create());

        $this->get(route('celebrations.show', $celebration->slug))
            ->assertOk()
            // Blade escapes the apostrophe on the way out
            ->assertSee('<title>Sandra&#039;s Birthday', false);
    }

    /** The composer is for guests to write in — the owner shouldn't see it. */
    public function test_only_visitors_get_the_message_composer(): void
    {
        $owner       = User::factory()->create();
        $celebration = $this->celebration($owner);

        $this->get(route('celebrations.show', $celebration->slug))
            ->assertSee('wishForm()', false);

        $this->actingAs($owner)
            ->get(route('celebrations.show', $celebration->slug))
            ->assertDontSee('wishForm()', false);
    }

    public function test_an_owner_can_save_the_page_details(): void
    {
        $owner       = User::factory()->create();
        $celebration = $this->celebration($owner, ['status' => 'draft']);

        $this->actingAs($owner)
            ->putJson(route('celebrant.update', $celebration->slug), [
                'title'            => 'Sandra turns 30',
                'celebrant_name'   => 'Sandra A.',
                'celebration_type' => 'baby_shower',
                'description'      => 'Come celebrate with us',
                'venue'            => 'Lagos',
                'event_date'       => '2026-09-01',
                'start_date'       => null,
                'end_date'         => null,
                'is_public'        => false,
                'status'           => 'published',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $celebration->refresh();
        $this->assertSame('Sandra turns 30', $celebration->title);
        $this->assertSame('baby_shower', $celebration->celebration_type);
        $this->assertFalse((bool) $celebration->is_public);
        $this->assertSame('published', $celebration->status);
        // going live for the first time stamps published_at
        $this->assertNotNull($celebration->published_at);
    }

    public function test_someone_else_cannot_save_your_page_details(): void
    {
        $celebration = $this->celebration(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->putJson(route('celebrant.update', $celebration->slug), [
                'title'            => 'Hijacked',
                'celebrant_name'   => 'Nope',
                'celebration_type' => 'birthday',
                'is_public'        => true,
                'status'           => 'published',
            ])
            ->assertStatus(403);

        $this->assertSame("Sandra's Birthday", $celebration->fresh()->title);
    }

    public function test_a_guest_cannot_save_page_details(): void
    {
        $celebration = $this->celebration(User::factory()->create());

        $this->putJson(route('celebrant.update', $celebration->slug), [
            'title'            => 'Hijacked',
            'celebrant_name'   => 'Nope',
            'celebration_type' => 'birthday',
            'is_public'        => true,
            'status'           => 'published',
        ])->assertStatus(403);
    }

    public function test_saving_rejects_an_unknown_type_and_a_backwards_date_range(): void
    {
        $owner       = User::factory()->create();
        $celebration = $this->celebration($owner);

        $this->actingAs($owner)
            ->putJson(route('celebrant.update', $celebration->slug), [
                'title'            => 'Still fine',
                'celebrant_name'   => 'Sandra',
                'celebration_type' => 'quinceanera',
                'start_date'       => '2026-09-10',
                'end_date'         => '2026-09-01',
                'is_public'        => true,
                'status'           => 'published',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['celebration_type', 'end_date']);
    }

    public function test_an_owner_can_delete_their_celebration(): void
    {
        $owner       = User::factory()->create();
        $celebration = $this->celebration($owner);

        $this->actingAs($owner)
            ->delete(route('celebrant.destroy', $celebration->slug))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(0, Celebration::where('id', $celebration->id)->count());
    }

    public function test_someone_else_cannot_delete_your_celebration(): void
    {
        $celebration = $this->celebration(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->delete(route('celebrant.destroy', $celebration->slug))
            ->assertStatus(403);

        $this->assertSame(1, Celebration::where('id', $celebration->id)->count());
    }

    /**
     * "From Johnson and 2 other supporters" — named after the most recent
     * giver, counting distinct people rather than gifts.
     */
    public function test_the_raised_line_names_the_latest_supporter_and_counts_the_rest(): void
    {
        $celebration = $this->celebration(User::factory()->create());

        // Ada gave twice — she should still count as one supporter
        $this->paidGift($celebration, 'Ada Lovelace', 10, now()->subDays(4));
        $this->paidGift($celebration, 'Ada Lovelace', 15, now()->subDays(3));
        $this->paidGift($celebration, 'Grace Hopper', 20, now()->subDays(2));
        $this->paidGift($celebration, 'Johnson Peters', 25, now()->subDay());

        // pending gifts are not supporters
        $this->paidGift($celebration, 'Not Paid', 99, now(), 'pending');

        $response = $this->get(route('celebrations.show', $celebration->slug))->assertOk();

        // the lead name is the most recent giver, first name only — asserted on
        // the <strong> so a match elsewhere in the list cannot satisfy it
        $response->assertSee('<strong>Johnson</strong>', false);
        $response->assertDontSee('<strong>Ada</strong>', false);
        $response->assertSee('and 2 other supporters', false);

        // the full list is on the page, behind the toggle
        $response->assertSee('Ada Lovelace');
        $response->assertSee('Grace Hopper');
        $response->assertDontSee('Not Paid');
    }

    public function test_a_single_supporter_is_not_pluralised(): void
    {
        $celebration = $this->celebration(User::factory()->create());
        $this->paidGift($celebration, 'Johnson Peters', 25, now());

        $this->get(route('celebrations.show', $celebration->slug))
            ->assertOk()
            ->assertSee('Johnson', false)
            ->assertDontSee('other supporters', false);
    }

    public function test_anonymous_gifts_are_never_named(): void
    {
        $celebration = $this->celebration(User::factory()->create());
        $this->paidGift($celebration, 'Secret Santa', 40, now(), 'paid', true);

        $this->get(route('celebrations.show', $celebration->slug))
            ->assertOk()
            ->assertSee('Anonymous')
            ->assertDontSee('Secret Santa');
    }

    public function test_a_page_with_no_supporters_says_so(): void
    {
        $celebration = $this->celebration(User::factory()->create());

        $this->get(route('celebrations.show', $celebration->slug))
            ->assertOk()
            ->assertSee('Be the first to give')
            ->assertDontSee('other supporters', false);
    }

    private function paidGift(
        Celebration $celebration,
        string $name,
        float $amount,
        $when,
        string $status = 'paid',
        bool $anonymous = false
    ): Gift {
        static $n = 0;
        $n++;

        $gift = Gift::create([
            'celebration_id'        => $celebration->id,
            'sender_user_id'        => null,
            'sender_name'           => $name,
            'sender_email'          => 'giver' . $n . '@example.com',
            'amount'                => $amount,
            'currency'              => 'USD',
            'payment_method'        => 'card',
            'transaction_reference' => 'ref-' . $n . '-' . Str::random(6),
            'payment_status'        => $status,
            'is_anonymous'          => $anonymous,
        ]);

        // created_at is not fillable, so it has to be forced — otherwise every
        // gift lands on the same timestamp and "most recent" means nothing.
        $gift->forceFill(['created_at' => $when])->save();

        return $gift;
    }

    /** Viewing someone else's page counts; viewing your own does not. */
    public function test_view_count_ignores_the_owner(): void
    {
        $owner       = User::factory()->create();
        $celebration = $this->celebration($owner);

        $this->actingAs($owner)->get(route('celebrations.show', $celebration->slug));
        $this->assertSame(0, (int) $celebration->fresh()->view_count);

        $this->actingAs(User::factory()->create())->get(route('celebrations.show', $celebration->slug));
        $this->assertSame(1, (int) $celebration->fresh()->view_count);
    }
}
