<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * A celebrant managing their cover photos from the Settings tab.
 *
 * Photos can be removed one at a time, so adding one must join the set rather
 * than replace it — and the page never holds more than four.
 */
class CoverPhotoManageTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->owner = User::factory()->create();
    }

    /** @param  array<int, string>  $photos */
    private function celebration(array $photos = []): Celebration
    {
        foreach ($photos as $path) {
            Storage::disk('public')->put($path, 'image');
        }

        return Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $this->owner->id,
            'title'            => "Yemi's Birthday",
            'slug'             => 'yemi-covers-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Yemi',
            'status'           => 'published',
            'is_public'        => true,
            'cover_photo'      => match (count($photos)) {
                0       => null,
                1       => $photos[0],
                default => json_encode($photos),
            },
        ]);
    }

    private function upload(Celebration $celebration, array $payload)
    {
        return $this->actingAs($this->owner)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('celebrant.update-cover', $celebration->id), $payload);
    }

    /* ── Adding ─────────────────────────────────────────────────────── */

    public function test_adding_a_photo_keeps_the_ones_already_there(): void
    {
        $celebration = $this->celebration(['covers/first.jpg']);

        $this->upload($celebration, ['cover_photo' => UploadedFile::fake()->image('second.jpg')])
            ->assertOk()
            ->assertJson(['success' => true]);

        $photos = $celebration->fresh()->cover_photos;

        $this->assertCount(2, $photos);
        $this->assertSame('covers/first.jpg', $photos[0]);
    }

    public function test_a_page_never_holds_more_than_four_photos(): void
    {
        $celebration = $this->celebration(['covers/1.jpg', 'covers/2.jpg', 'covers/3.jpg']);

        $response = $this->upload($celebration, [
            'cover_photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertStatus(422);

        $this->assertStringContainsString('1 more photo', $response->json('message'));
        $this->assertCount(3, $celebration->fresh()->cover_photos);
    }

    public function test_a_full_page_is_told_to_remove_one_first(): void
    {
        $celebration = $this->celebration(['covers/1.jpg', 'covers/2.jpg', 'covers/3.jpg', 'covers/4.jpg']);

        $response = $this->upload($celebration, ['cover_photo' => UploadedFile::fake()->image('five.jpg')])
            ->assertStatus(422);

        $this->assertStringContainsString('Remove one', $response->json('message'));
    }

    /* ── Removing ───────────────────────────────────────────────────── */

    public function test_the_owner_removes_one_photo_and_its_file(): void
    {
        $celebration = $this->celebration(['covers/keep.jpg', 'covers/drop.jpg']);

        $this->actingAs($this->owner)
            ->deleteJson(route('celebrant.delete-cover', $celebration->id), ['path' => 'covers/drop.jpg'])
            ->assertOk()
            ->assertJson(['success' => true, 'remaining' => 1]);

        $this->assertSame(['covers/keep.jpg'], $celebration->fresh()->cover_photos);
        Storage::disk('public')->assertMissing('covers/drop.jpg');
        Storage::disk('public')->assertExists('covers/keep.jpg');
    }

    public function test_removing_the_last_photo_leaves_the_page_without_one(): void
    {
        $celebration = $this->celebration(['covers/only.jpg']);

        $this->actingAs($this->owner)
            ->deleteJson(route('celebrant.delete-cover', $celebration->id), ['path' => 'covers/only.jpg'])
            ->assertOk();

        $this->assertSame([], $celebration->fresh()->cover_photos);
        $this->assertNull($celebration->fresh()->cover_photo);
    }

    public function test_someone_else_cannot_remove_a_photo(): void
    {
        $celebration = $this->celebration(['covers/mine.jpg']);

        $this->actingAs(User::factory()->create())
            ->deleteJson(route('celebrant.delete-cover', $celebration->id), ['path' => 'covers/mine.jpg'])
            ->assertForbidden();

        $this->assertSame(['covers/mine.jpg'], $celebration->fresh()->cover_photos);
        Storage::disk('public')->assertExists('covers/mine.jpg');
    }

    public function test_a_path_that_is_not_on_the_page_is_not_touched(): void
    {
        $celebration = $this->celebration(['covers/mine.jpg']);
        Storage::disk('public')->put('covers/someone-else.jpg', 'image');

        $this->actingAs($this->owner)
            ->deleteJson(route('celebrant.delete-cover', $celebration->id), ['path' => 'covers/someone-else.jpg'])
            ->assertNotFound();

        Storage::disk('public')->assertExists('covers/someone-else.jpg');
    }

    /* ── The page ───────────────────────────────────────────────────── */

    public function test_the_owner_sees_add_cover_image_on_an_empty_cover(): void
    {
        $celebration = $this->celebration();

        $this->actingAs($this->owner)
            ->get(route('celebrations.show', $celebration->slug))
            ->assertOk()
            ->assertSee('Add Cover Image');
    }

    public function test_settings_offers_a_remove_button_for_each_photo(): void
    {
        $celebration = $this->celebration(['covers/one.jpg', 'covers/two.jpg']);

        $this->actingAs($this->owner)
            ->get(route('celebrations.show', $celebration->slug))
            ->assertOk()
            ->assertSee("removeCover('covers/one.jpg')", false)
            ->assertSee("removeCover('covers/two.jpg')", false)
            ->assertSee('2 of 4');
    }

    public function test_a_visitor_sees_no_add_button(): void
    {
        $celebration = $this->celebration();

        $this->get(route('celebrations.show', $celebration->slug))
            ->assertOk()
            ->assertDontSee('Add Cover Image');
    }
}
