<?php

namespace Tests\Feature;

use App\Models\EmailQueue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Everyone who makes an account gets exactly one welcome email — however they
 * made it.
 *
 * There are three ways in: the sign-up page, the mobile app, and creating a
 * celebration without an account. The last never announced the new account,
 * so it sent no welcome at all; the first two sent two, because the welcome
 * listener was registered twice.
 */
class WelcomeMailOnSignupTest extends TestCase
{
    use RefreshDatabase;

    private function welcomesTo(string $email): int
    {
        return EmailQueue::where('type', 'user.welcome')->where('to_address', $email)->count();
    }

    private function howItWorksTo(string $email): int
    {
        return EmailQueue::where('type', 'user.how-it-works')->where('to_address', $email)->count();
    }

    private function celebrationPayload(array $extra = []): array
    {
        return array_merge([
            'celebrantName' => 'Ada Obi',
            'eventType'     => 'birthday',
            'startDate'     => now()->addWeek()->toDateString(),
        ], $extra);
    }

    /* ── Each way in sends exactly one ──────────────────────────────── */

    public function test_the_sign_up_page_sends_one_welcome(): void
    {
        $this->post(route('register'), [
            'name'                  => 'Ada Obi',
            'email'                 => 'ada@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        // Exactly one: the listener used to be registered twice.
        $this->assertSame(1, $this->welcomesTo('ada@example.com'));
        $this->assertSame(1, $this->howItWorksTo('ada@example.com'));
    }

    public function test_creating_a_celebration_without_an_account_sends_one_welcome(): void
    {
        $this->postJson(route('celebrations.store'), $this->celebrationPayload([
            'email'    => 'new@example.com',
            'password' => 'secret123',
        ]))->assertOk()->assertJson(['success' => true]);

        $this->assertTrue(User::where('email', 'new@example.com')->exists());
        $this->assertSame(1, $this->welcomesTo('new@example.com'));
        $this->assertSame(1, $this->howItWorksTo('new@example.com'));
    }

    public function test_signing_up_in_the_app_sends_one_welcome(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Ada Obi',
            'email'                 => 'app@example.com',
            'password'              => 'correct-horse-battery-1',
            'password_confirmation' => 'correct-horse-battery-1',
        ])->assertCreated();

        $this->assertSame(1, $this->welcomesTo('app@example.com'));
    }

    /* ── Nobody is welcomed twice ───────────────────────────────────── */

    public function test_an_existing_account_creating_a_celebration_is_not_welcomed_again(): void
    {
        // The create form doubles as a login for an address it already knows.
        User::factory()->create([
            'email'    => 'known@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->postJson(route('celebrations.store'), $this->celebrationPayload([
            'email'    => 'known@example.com',
            'password' => 'secret123',
        ]))->assertOk();

        $this->assertSame(0, $this->welcomesTo('known@example.com'));
    }

    public function test_a_signed_in_user_creating_a_celebration_is_not_welcomed(): void
    {
        $user = User::factory()->create(['email' => 'member@example.com']);

        $this->actingAs($user)
            ->postJson(route('celebrations.store'), $this->celebrationPayload())
            ->assertOk();

        $this->assertSame(0, $this->welcomesTo('member@example.com'));
    }

    public function test_a_wrong_password_on_the_create_form_welcomes_nobody(): void
    {
        User::factory()->create([
            'email'    => 'known@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->postJson(route('celebrations.store'), $this->celebrationPayload([
            'email'    => 'known@example.com',
            'password' => 'not-the-password',
        ]))->assertStatus(422);

        $this->assertSame(0, EmailQueue::where('type', 'user.welcome')->count());
    }
}
