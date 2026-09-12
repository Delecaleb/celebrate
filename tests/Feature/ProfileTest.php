<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'first_name' => 'Test',
                'last_name'  => 'User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test', $user->first_name);
        $this->assertSame('User', $user->last_name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    /**
     * There is no self-service account deletion, and there should not be one.
     *
     * An account holds a wallet balance, celebrations other people have given
     * money to, and the gift records behind those payments. Letting the owner
     * erase all of it with a password box destroys other people's receipts,
     * so closing an account is a support conversation.
     */
    public function test_an_account_cannot_be_deleted_from_the_profile_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete('/profile', ['password' => 'password'])
            ->assertStatus(405);

        $this->assertNotNull($user->fresh());
        $this->assertAuthenticated();
    }

    public function test_the_profile_page_offers_no_way_to_delete_an_account(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->get('/profile')->assertOk()->getContent();

        $this->assertStringNotContainsString('Delete Account', $html);
        $this->assertStringNotContainsString('profile.destroy', $html);
    }

    public function test_the_page_uses_the_app_shell_not_the_breeze_default(): void
    {
        $user = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Obi']);

        $html = $this->actingAs($user)->get('/profile')->assertOk()->getContent();

        // The dashboard rail and the house form styles, not Breeze components.
        $this->assertStringContainsString('page-head', $html);
        $this->assertStringContainsString('m-input', $html);
        $this->assertStringContainsString('value="Ada"', $html);
        $this->assertStringNotContainsString('x-text-input', $html);
    }
}
