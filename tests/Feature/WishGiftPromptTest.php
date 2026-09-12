<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\PlatformAvailableGift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The gift prompt shown after somebody posts a wish.
 *
 * Posting a wish is the moment a visitor is most inclined to give: they have
 * just written something warm and the page is still in front of them. The
 * prompt lives on the confirmation screen so it is asked once, there, rather
 * than as a banner that gets scrolled past.
 */
class WishGiftPromptTest extends TestCase
{
    use RefreshDatabase;

    private function celebration(?User $owner = null): Celebration
    {
        return Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => ($owner ?? User::factory()->create())->id,
            'title'            => "Yemi's Birthday",
            'slug'             => 'yemi-prompt-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Yemi',
            'status'           => 'published',
            'is_public'        => true,
        ]);
    }

    private function aGift(): void
    {
        PlatformAvailableGift::create([
            'gift_name' => 'Warm Hug', 'gift_icon' => 'mdi-hand-heart',
            'gift_price' => 5, 'category' => 'small', 'status' => 'active',
        ]);
    }

    public function test_a_visitor_is_offered_the_gift_prompt(): void
    {
        $this->aGift();
        $celebration = $this->celebration();

        $this->get(route('celebrations.show', $celebration->slug))
            ->assertOk()
            ->assertSee('Make it land')
            ->assertSee('Send Yemi a gift');
    }

    public function test_the_celebrant_is_not_asked_to_gift_themselves(): void
    {
        $this->aGift();
        $owner       = User::factory()->create();
        $celebration = $this->celebration($owner);

        $this->actingAs($owner)
            ->get(route('celebrations.show', $celebration->slug))
            ->assertOk()
            ->assertDontSee('Make it land');
    }

    public function test_nothing_is_offered_when_there_are_no_gifts_to_send(): void
    {
        // An empty catalogue would open a picker with nothing in it.
        $celebration = $this->celebration();

        $this->get(route('celebrations.show', $celebration->slug))
            ->assertOk()
            ->assertDontSee('Make it land');
    }

    public function test_the_gifts_flag_opens_the_picker_on_arrival(): void
    {
        $this->aGift();
        $celebration = $this->celebration();

        $this->get(route('celebrations.show', ['slug' => $celebration->slug, 'gifts' => 1]))
            ->assertOk()
            ->assertSee('alpine:initialized', false)
            ->assertSee("detail: 'show-gifts'", false);
    }

    public function test_the_picker_stays_shut_without_the_flag(): void
    {
        $this->aGift();
        $celebration = $this->celebration();

        $this->get(route('celebrations.show', $celebration->slug))
            ->assertOk()
            ->assertDontSee("detail: 'show-gifts'", false);
    }
}
