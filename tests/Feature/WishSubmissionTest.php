<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class WishSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function celebration(): Celebration
    {
        return Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => User::factory()->create()->id,
            'title'            => "Yemi's Birthday",
            'slug'             => 'yemi-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Yemi',
            'status'           => 'published',
            'is_public'        => true,
        ]);
    }

    /**
     * A wish with no photo or video is the common case. comments.media_type is
     * NOT NULL, so posting one used to fail the insert and 500.
     */
    public function test_a_guest_can_post_a_text_only_wish(): void
    {
        $celebration = $this->celebration();

        $this->postJson(route('celebration.comment.store'), [
            'celebration_id' => $celebration->id,
            'comment'        => 'Happy birthday Yemi, have a wonderful day!',
        ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $comment = Comment::where('celebration_id', $celebration->id)->sole();
        $this->assertSame('Happy birthday Yemi, have a wonderful day!', $comment->message);
        $this->assertSame('', $comment->media_type);
        $this->assertNull($comment->media_url);
    }

    public function test_a_signed_in_user_can_post_a_text_only_wish(): void
    {
        $celebration = $this->celebration();
        $sender      = User::factory()->create(['first_name' => 'Amara', 'last_name' => 'Obi']);

        $this->actingAs($sender)
            ->postJson(route('celebration.comment.store'), [
                'celebration_id' => $celebration->id,
                'comment'        => 'Congratulations!',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('', Comment::where('celebration_id', $celebration->id)->sole()->media_type);
    }

    /** Spaces and punctuation must survive exactly as typed. */
    public function test_a_wish_keeps_its_spacing(): void
    {
        $celebration = $this->celebration();
        $message     = 'Wishing you the happiest of birthdays Yemi — enjoy every moment!';

        $this->postJson(route('celebration.comment.store'), [
            'celebration_id' => $celebration->id,
            'comment'        => $message,
        ])->assertOk();

        $this->assertSame($message, Comment::where('celebration_id', $celebration->id)->sole()->message);
    }

    public function test_a_wish_with_a_photo_records_the_media_type(): void
    {
        Storage::fake('public');
        $celebration = $this->celebration();

        $this->post(route('celebration.comment.store'), [
            'celebration_id' => $celebration->id,
            'comment'        => 'Look at this!',
            'image'          => UploadedFile::fake()->image('party.jpg'),
        ])->assertOk();

        $comment = Comment::where('celebration_id', $celebration->id)->sole();
        $this->assertSame('local-image', $comment->media_type);
        $this->assertNotNull($comment->media_url);
    }

    public function test_an_empty_wish_is_rejected(): void
    {
        $celebration = $this->celebration();

        $this->postJson(route('celebration.comment.store'), [
            'celebration_id' => $celebration->id,
            'comment'        => '',
        ])->assertStatus(422);

        $this->assertSame(0, Comment::where('celebration_id', $celebration->id)->count());
    }

    public function test_posting_a_wish_bumps_the_comment_count(): void
    {
        $celebration = $this->celebration();
        $before      = $celebration->comment_count;

        $this->postJson(route('celebration.comment.store'), [
            'celebration_id' => $celebration->id,
            'comment'        => 'Many happy returns',
        ])->assertOk();

        $this->assertSame($before + 1, $celebration->fresh()->comment_count);
    }
}
