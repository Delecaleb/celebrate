<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\User;
use App\Support\UploadLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Cover photos and registry images may be up to 10MB, and a person who goes
 * over is told so in megabytes — never "10240 kilobytes".
 */
class ImageUploadLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Celebration $celebration;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->owner = User::factory()->create();

        $this->celebration = Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $this->owner->id,
            'title'            => "Yemi's Birthday",
            'slug'             => 'yemi-upload-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Yemi',
            'status'           => 'published',
            'is_public'        => true,
        ]);
    }

    /** A fake image reporting the given size in kilobytes. */
    private function image(int $kb): UploadedFile
    {
        return UploadedFile::fake()->image('photo.jpg')->size($kb);
    }

    private function assertMegabyteMessage(string $message): void
    {
        $this->assertStringContainsString('10MB', $message);
        $this->assertStringNotContainsString('kilobyte', $message);
        $this->assertStringNotContainsString('10240', $message);
    }

    /* ── The limit itself ───────────────────────────────────────────── */

    public function test_the_limit_is_ten_megabytes(): void
    {
        $this->assertSame(10240, UploadLimits::IMAGE_KB);
        $this->assertSame('10MB', UploadLimits::label());
        $this->assertSame(10 * 1024 * 1024, UploadLimits::bytes());
    }

    /* ── Web: celebration cover ─────────────────────────────────────── */

    public function test_a_cover_just_under_ten_megabytes_is_accepted(): void
    {
        $this->actingAs($this->owner)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('celebrant.update-cover', $this->celebration->id), ['cover_photo' => $this->image(10000)])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_a_cover_over_ten_megabytes_is_refused_in_megabytes(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('celebrant.update-cover', $this->celebration->id), ['cover_photo' => $this->image(11000)])
            ->assertStatus(422);

        $this->assertMegabyteMessage($response->json('message'));
    }

    public function test_one_oversized_photo_in_a_set_is_refused_in_megabytes(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('celebrant.update-cover', $this->celebration->id), [
                'cover_photos' => [$this->image(3000), $this->image(11000)],
            ])
            ->assertStatus(422);

        $this->assertMegabyteMessage($response->json('message'));
        $this->assertNull($this->celebration->fresh()->cover_photo);
    }

    /* ── Web: registry image ────────────────────────────────────────── */

    public function test_a_registry_image_just_under_ten_megabytes_is_accepted(): void
    {
        $this->actingAs($this->owner)
            ->post(route('celebrant.create-wishes'), [
                'celebration_id' => $this->celebration->id,
                'wishlist'       => [['name' => 'Air Max', 'image' => $this->image(10000)]],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_a_registry_image_over_ten_megabytes_is_refused_in_megabytes(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('celebrant.create-wishes'), [
                'celebration_id' => $this->celebration->id,
                'wishlist'       => [['name' => 'Air Max', 'image' => $this->image(11000)]],
            ])
            ->assertStatus(422);

        $this->assertMegabyteMessage($response->json('message'));
    }

    /* ── API: the mobile client gets the same limit ─────────────────── */

    public function test_the_api_refuses_an_oversized_cover_in_megabytes(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post("/api/v1/celebrations/{$this->celebration->slug}/cover-photo", [
                'cover_photos' => [$this->image(11000)],
            ])
            ->assertStatus(422);

        $this->assertMegabyteMessage($response->json('message'));
    }

    public function test_the_api_refuses_an_oversized_registry_image_in_megabytes(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/wishes', [
                'celebration_id' => $this->celebration->id,
                'wishlist'       => [['name' => 'Air Max', 'image' => $this->image(11000)]],
            ])
            ->assertStatus(422);

        $this->assertMegabyteMessage($response->json('message'));
    }

    /* ── Every file-size message reads in megabytes ─────────────────── */

    public function test_any_file_size_error_names_megabytes_not_kilobytes(): void
    {
        $message = Validator::make(
            ['file' => UploadedFile::fake()->create('doc.pdf', 3000)],
            ['file' => 'file|max:2048']
        )->errors()->first('file');

        $this->assertStringContainsString('2MB', $message);
        $this->assertStringNotContainsString('kilobyte', $message);
    }

    public function test_a_non_file_max_rule_still_fills_in_its_number(): void
    {
        // The replacer takes over from Laravel's own, so it must still do the
        // ordinary job for strings.
        $message = Validator::make(['name' => 'abcdef'], ['name' => 'string|max:3'])->errors()->first('name');

        $this->assertStringContainsString('3 characters', $message);
        $this->assertStringNotContainsString(':max', $message);
    }

    /* ── The browser is told the same limit ─────────────────────────── */

    public function test_the_page_hands_the_limit_to_the_upload_scripts(): void
    {
        $this->actingAs($this->owner)
            ->get(route('celebrations.show', $this->celebration->slug))
            ->assertOk()
            ->assertSee('imageMaxBytes:   ' . UploadLimits::bytes(), false)
            ->assertSee('imageMaxLabel:   "10MB"', false);
    }
}
