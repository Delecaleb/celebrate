<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Creating a celebration while signed out, with an email that already has an
 * account.
 *
 * The form reads as sign-up, so a wrong password used to come back as a bare
 * "Invalid login credentials" that the page never showed — people were left on
 * the form with nothing happening. They must be told the email is taken.
 */
class CreateCelebrationExistingEmailTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        return array_merge([
            'celebrantName' => 'Ada Obi',
            'eventType'     => 'birthday',
            'startDate'     => now()->addWeek()->toDateString(),
        ], $extra);
    }

    public function test_an_email_that_already_has_an_account_is_reported_on_the_email_field(): void
    {
        User::factory()->create([
            'email'    => 'known@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson(route('celebrations.store'), $this->payload([
            'email'    => 'known@example.com',
            'password' => 'not-the-password',
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('code', 'email_taken')
            ->assertJsonValidationErrors('email');

        $this->assertStringContainsString('already', $response->json('errors.email.0'));
        $this->assertStringContainsString('already registered', $response->json('message'));

        $this->assertGuest();
        $this->assertSame(0, Celebration::count());
    }

    public function test_the_right_password_for_that_account_still_creates_the_page(): void
    {
        $user = User::factory()->create([
            'email'    => 'known@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->postJson(route('celebrations.store'), $this->payload([
            'email'    => 'known@example.com',
            'password' => 'secret123',
        ]))->assertOk()->assertJson(['success' => true]);

        $this->assertAuthenticatedAs($user);
        $this->assertSame($user->id, Celebration::sole()->user_id);
    }

    public function test_the_create_form_shows_server_errors_to_the_person(): void
    {
        // The form used to throw every error response away and only follow a
        // redirect. It must now read the errors and put them on the page.
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString("x-text=\"errors.email\"", $html);
        $this->assertStringContainsString("'Accept': 'application/json'", $html);
    }
}
