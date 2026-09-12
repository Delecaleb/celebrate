<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The profile photo and the email preference.
 *
 * Both already existed in the database and neither could be reached from the
 * web: the mobile app could upload an avatar, and CelebrantController already
 * honoured email_notifications_enabled, but there was no way to set either.
 */
class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    private function payload(User $user, array $overrides = []): array
    {
        return array_merge([
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'email'      => $user->email,
        ], $overrides);
    }

    public function test_a_photo_can_be_uploaded(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', $this->payload($user, [
                'photo' => UploadedFile::fake()->image('me.jpg', 400, 400),
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertNotNull($user->profile_photo);
        Storage::disk('public')->assertExists($user->profile_photo);
        $this->assertStringContainsString($user->profile_photo, $user->profile_photo_url);
    }

    public function test_replacing_a_photo_deletes_the_old_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user, [
            'photo' => UploadedFile::fake()->image('first.jpg'),
        ]));

        $first = $user->fresh()->profile_photo;

        $this->actingAs($user)->patch('/profile', $this->payload($user, [
            'photo' => UploadedFile::fake()->image('second.jpg'),
        ]));

        $second = $user->fresh()->profile_photo;

        $this->assertNotSame($first, $second);
        // Otherwise every change leaves a file behind for good.
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_a_photo_can_be_removed(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user, [
            'photo' => UploadedFile::fake()->image('me.jpg'),
        ]));

        $path = $user->fresh()->profile_photo;

        $this->actingAs($user)->patch('/profile', $this->payload($user, ['remove_photo' => 1]))
            ->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->profile_photo);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_saving_without_touching_the_photo_keeps_it(): void
    {
        // The trap: opening the page, changing your name, and losing your
        // picture because the file input rendered empty.
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->payload($user, [
            'photo' => UploadedFile::fake()->image('me.jpg'),
        ]));

        $path = $user->fresh()->profile_photo;

        $this->actingAs($user)->patch('/profile', $this->payload($user, ['first_name' => 'Renamed']));

        $this->assertSame($path, $user->fresh()->profile_photo);
        Storage::disk('public')->assertExists($path);
    }

    public function test_a_file_that_is_not_an_image_is_refused(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->patch('/profile', $this->payload($user, [
                'photo' => UploadedFile::fake()->create('invoice.pdf', 40, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->fresh()->profile_photo);
    }

    public function test_the_email_preference_can_be_turned_off_and_on(): void
    {
        $user = User::factory()->create(['email_notifications_enabled' => true]);

        // An unticked box is simply absent from the request.
        $this->actingAs($user)->patch('/profile', $this->payload($user))
            ->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->email_notifications_enabled);

        $this->actingAs($user)->patch('/profile', $this->payload($user, [
            'email_notifications_enabled' => 1,
        ]));
        $this->assertTrue($user->fresh()->email_notifications_enabled);
    }

    public function test_the_currency_cannot_be_changed_from_the_profile(): void
    {
        $user = User::factory()->create();
        $before = $user->currency;

        $this->actingAs($user)->patch('/profile', $this->payload($user, ['currency' => 'GBP']));

        // currency is guarded: it is decided at signup and drives the wallet.
        $this->assertSame($before, $user->fresh()->currency);
    }
}
