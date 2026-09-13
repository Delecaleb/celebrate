<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Celebration;
use App\Models\Frame;
use App\Models\User;
use App\Support\Features;
use App\Support\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The celebrant's own view of their page.
 *
 * The owner lands on Settings with photos at the top, and the frame picker
 * only appears while an admin has frames switched on.
 */
class CelebrantSettingsTabTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Celebration $celebration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();

        $this->celebration = Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $this->owner->id,
            'title'            => "Yemi's Birthday",
            'slug'             => 'yemi-settings-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Yemi',
            'status'           => 'published',
            'is_public'        => true,
        ]);
    }

    private function ownerPage(): string
    {
        return $this->actingAs($this->owner)
            ->get(route('celebrations.show', $this->celebration->slug))
            ->assertOk()
            ->getContent();
    }

    private function frame(): Frame
    {
        return Frame::forceCreate(['name' => 'Gold', 'type' => 'css', 'css_content' => 'border:2px solid gold']);
    }

    /* ── Tabs ───────────────────────────────────────────────────────── */

    public function test_the_owner_lands_on_settings_and_it_is_the_first_tab(): void
    {
        $html = $this->ownerPage();

        $this->assertStringContainsString("x-data=\"{ tab: 'settings' }\"", $html);
        $this->assertLessThan(
            strpos($html, "@click=\"tab = 'wishes'\""),
            strpos($html, "@click=\"tab = 'settings'\""),
            'Settings should be the first tab for the owner.'
        );
    }

    public function test_a_visitor_still_lands_on_wishes_with_no_settings_tab(): void
    {
        $html = $this->get(route('celebrations.show', $this->celebration->slug))->assertOk()->getContent();

        $this->assertStringContainsString("x-data=\"{ tab: 'wishes' }\"", $html);
        $this->assertStringNotContainsString("@click=\"tab = 'settings'\"", $html);
    }

    public function test_photos_come_before_page_details(): void
    {
        $html = $this->ownerPage();

        $this->assertLessThan(
            strpos($html, '<p class="cel-sec-t">Page details</p>'),
            strpos($html, '<p class="cel-sec-t">Photos</p>')
        );
    }

    /* ── Pickers ────────────────────────────────────────────────────── */

    public function test_the_date_and_colour_fields_are_labelled_pickers(): void
    {
        $html = $this->ownerPage();

        $this->assertStringContainsString('Celebration date', $html);
        $this->assertStringContainsString('placeholder="Pick the day"', $html);
        $this->assertStringContainsString("setColour('bg'", $html);
        $this->assertStringContainsString("setColour('text'", $html);
        // The bare native input that showed black for "no colour" is gone.
        $this->assertStringNotContainsString('type="color" x-model="customBg"', $html);
    }

    /* ── Frames switch ──────────────────────────────────────────────── */

    public function test_frames_are_off_unless_switched_on(): void
    {
        config(['features.frames' => null]);
        $this->assertFalse(Features::framesEnabled());

        // The panel stores toggles as strings, and "0" is truthy in PHP.
        config(['features.frames' => '0']);
        $this->assertFalse(Features::framesEnabled());

        config(['features.frames' => '1']);
        $this->assertTrue(Features::framesEnabled());
    }

    public function test_the_frame_picker_is_hidden_while_frames_are_off(): void
    {
        config(['features.frames' => false]);
        $this->frame();

        $this->assertStringNotContainsString('<p class="cel-sec-t">Frame</p>', $this->ownerPage());
    }

    public function test_the_frame_picker_shows_once_frames_are_on(): void
    {
        config(['features.frames' => true]);
        $this->frame();

        $html = $this->ownerPage();

        $this->assertStringContainsString('<p class="cel-sec-t">Frame</p>', $html);
        $this->assertStringContainsString('title="Gold"', $html);
    }

    public function test_a_frame_cannot_be_put_on_while_frames_are_off(): void
    {
        config(['features.frames' => false]);
        $frame = $this->frame();

        $this->actingAs($this->owner)
            ->postJson(route('celebrant.update-frame', $this->celebration->id), ['frame_id' => $frame->id])
            ->assertForbidden();

        $this->assertNull($this->celebration->fresh()->frame_id);
    }

    public function test_a_frame_can_still_be_taken_off_while_frames_are_off(): void
    {
        config(['features.frames' => true]);
        $frame = $this->frame();
        $this->celebration->forceFill(['frame_id' => $frame->id])->save();

        config(['features.frames' => false]);

        $this->actingAs($this->owner)
            ->postJson(route('celebrant.update-frame', $this->celebration->id), ['frame_id' => null])
            ->assertOk();

        $this->assertNull($this->celebration->fresh()->frame_id);
    }

    public function test_an_admin_switches_frames_on_from_settings(): void
    {
        $admin = Admin::create([
            'name'     => 'Ops',
            'email'    => 'ops@celebratemi.com',
            'password' => Hash::make('correct-horse-battery-1'),
            'is_super' => false,
            'status'   => 'active',
        ]);
        $admin->syncPermissions(['settings.manage']);
        $admin = $admin->fresh('permissions');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings', 'features'))
            ->assertOk()
            ->assertSee('Photo frames')
            ->assertDontSee('Test these credentials');

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update', 'features'), ['settings' => ['frames_enabled' => '1']])
            ->assertRedirect();

        $this->assertSame('1', app(SettingsRepository::class)->overlay()['features.frames'] ?? null);
    }
}
