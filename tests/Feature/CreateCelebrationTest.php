<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCelebrationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'celebrantName' => 'John',
            'eventType'     => 'birthday',
            'startDate'     => now()->addDays(7)->toDateString(),
            'endDate'       => now()->addDays(8)->toDateString(),
            'eventTitle'    => "John's Birthday",
        ];
    }

    public function test_a_signed_in_user_can_create_a_celebration(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('celebrations.store'), $this->payload());

        $response->assertOk()->assertJson(['success' => true]);

        $celebration = Celebration::sole();
        $this->assertSame($user->id, $celebration->user_id);
        $this->assertSame("John's Birthday", $celebration->title);
        $this->assertSame(0, (int) $celebration->comment_count);
        $response->assertJsonPath('redirect', route('celebrations.show', $celebration->slug));
    }

    /**
     * The chosen type used to be written to a non-existent `event_type` key, so
     * it was dropped and every page came out as the enum default, birthday.
     */
    public function test_the_chosen_celebration_type_is_saved(): void
    {
        $user = User::factory()->create();

        foreach (['wedding', 'graduation', 'anniversary', 'baby_shower', 'memorial', 'other'] as $type) {
            $this->actingAs($user)
                ->postJson(route('celebrations.store'), $this->payload([
                    'celebrantName' => "Celebrant {$type}",
                    'eventType'     => $type,
                    'eventTitle'    => "Page {$type}",
                ]))
                ->assertOk();

            $this->assertSame(
                $type,
                Celebration::where('title', "Page {$type}")->sole()->celebration_type,
                "celebration_type should be {$type}"
            );
        }
    }

    public function test_an_unknown_celebration_type_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('celebrations.store'), $this->payload(['eventType' => 'quinceanera']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('eventType');

        $this->assertSame(0, Celebration::count());
    }

    public function test_the_title_is_built_from_the_type_label_when_none_is_given(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('celebrations.store'), $this->payload([
                'eventType'  => 'baby_shower',
                'eventTitle' => null,
            ]))
            ->assertOk();

        $this->assertSame("John's Baby Shower", Celebration::sole()->title);
    }

    public function test_creating_without_an_end_date_is_allowed(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('celebrations.store'), $this->payload(['endDate' => null]))
            ->assertOk();

        $this->assertNull(Celebration::sole()->end_date);
    }

    public function test_a_guest_is_registered_and_signed_in_while_creating(): void
    {
        $this->postJson(route('celebrations.store'), $this->payload([
            'email'    => 'newcomer@example.com',
            'password' => 'secret123',
        ]))->assertOk();

        $this->assertAuthenticated();
        $user = User::where('email', 'newcomer@example.com')->sole();
        $this->assertSame($user->id, Celebration::sole()->user_id);
    }

    public function test_a_guest_with_the_wrong_password_is_turned_away(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->postJson(route('celebrations.store'), $this->payload([
            'email'    => 'existing@example.com',
            'password' => 'not-the-password',
        ]))->assertStatus(422);

        $this->assertGuest();
        $this->assertSame(0, Celebration::count());
    }
}
