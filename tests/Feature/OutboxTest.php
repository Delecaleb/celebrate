<?php

namespace Tests\Feature;

use App\Mail\WelcomeMail;
use App\Models\Admin;
use App\Models\EmailQueue;
use App\Models\User;
use App\Support\Outbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The outbox: mail as a readable row rather than a serialised job.
 *
 * What has to hold is that the record survives whatever happens to the send —
 * a refused address, a dead transport, a worker that dies mid-flight — because
 * the record is the only way to answer "did they get it?" afterwards.
 */
class OutboxTest extends TestCase
{
    use RefreshDatabase;

    private function queueMail(?User $user = null): ?EmailQueue
    {
        $user ??= User::factory()->create(['first_name' => 'Ada']);

        return Outbox::queue(
            new WelcomeMail($user),
            $user->email,
            'user.welcome',
            ['user_id' => $user->id],
            $user->first_name,
        );
    }

    /* ── Queueing ───────────────────────────────────────────────────── */

    public function test_queueing_stores_the_email_itself(): void
    {
        $user  = User::factory()->create(['first_name' => 'Ada']);
        $email = $this->queueMail($user);

        $this->assertSame('user.welcome', $email->type);
        $this->assertSame($user->email, $email->to_address);
        $this->assertSame('Ada', $email->to_name);
        $this->assertSame(WelcomeMail::class, $email->mailable);
        $this->assertSame(EmailQueue::PENDING, $email->status);
        $this->assertSame(['user_id' => $user->id], $email->metadata);

        // The body is rendered now, so the row is what the person will read.
        $this->assertNotEmpty($email->subject);
        $this->assertStringContainsString('<', $email->body_html);
        $this->assertSame((string) config('mail.from.address'), $email->from_address);
    }

    public function test_an_unusable_address_is_refused_rather_than_queued(): void
    {
        $user = User::factory()->create();

        $this->assertNull(Outbox::queue(new WelcomeMail($user), 'not-an-address', 'user.welcome'));
        $this->assertSame(0, EmailQueue::count());
    }

    public function test_a_broken_template_never_reaches_the_queue(): void
    {
        // Rendering happens at queue time on purpose: a template that cannot
        // render should fail in front of whoever caused it, not silently in a
        // worker minutes later.
        $broken = new class extends Mailable
        {
            public function build()
            {
                return $this->view('emails.no-such-template-anywhere');
            }
        };

        $this->assertNull(Outbox::queue($broken, 'ada@example.com', 'test'));
        $this->assertSame(0, EmailQueue::count());
    }

    public function test_queueing_never_throws_into_the_thing_that_caused_it(): void
    {
        // A gift has already been paid for by the time its receipt is written.
        $broken = new class extends Mailable
        {
            public function build()
            {
                return $this->view('emails.also-missing');
            }
        };

        $this->assertNull(Outbox::queue($broken, 'ada@example.com', 'gift.sent'));
    }

    /* ── Sending ────────────────────────────────────────────────────── */

    public function test_sending_marks_it_sent(): void
    {
        Mail::fake();
        $email = $this->queueMail();

        $this->artisan('emails:send')->assertSuccessful();

        $email->refresh();
        $this->assertSame(EmailQueue::SENT, $email->status);
        $this->assertNotNull($email->sent_at);
        $this->assertSame(1, $email->attempts);
    }

    public function test_a_sent_email_is_not_sent_again(): void
    {
        Mail::fake();
        $email = $this->queueMail();

        $this->artisan('emails:send');
        $this->artisan('emails:send');

        // The attempt counter is the evidence: a second pass that picked this
        // up again would have incremented it and moved sent_at.
        $email->refresh();
        $this->assertSame(EmailQueue::SENT, $email->status);
        $this->assertSame(1, $email->attempts);
    }

    public function test_a_refusal_is_recorded_and_retried(): void
    {
        $email = $this->queueMail();

        Mail::shouldReceive('html')->once()->andThrow(new \RuntimeException('550 mailbox unavailable'));

        $this->artisan('emails:send');

        $email->refresh();
        // Still pending — one refusal is not a verdict.
        $this->assertSame(EmailQueue::PENDING, $email->status);
        $this->assertSame(1, $email->attempts);
        $this->assertStringContainsString('550 mailbox unavailable', $email->last_error);
        $this->assertNotNull($email->available_at);
    }

    public function test_it_gives_up_after_the_attempt_limit(): void
    {
        $email = $this->queueMail();
        $email->forceFill(['attempts' => 3])->save();

        Mail::shouldReceive('html')->once()->andThrow(new \RuntimeException('no such domain'));

        $this->artisan('emails:send');

        $this->assertSame(EmailQueue::FAILED, $email->fresh()->status);
    }

    public function test_something_not_yet_due_is_left_alone(): void
    {
        Mail::fake();
        $email = $this->queueMail();
        $email->forceFill(['available_at' => now()->addHour()])->save();

        $this->artisan('emails:send');

        $this->assertSame(EmailQueue::PENDING, $email->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_a_held_email_is_never_sent(): void
    {
        Mail::fake();
        $email = $this->queueMail();
        $email->forceFill(['status' => EmailQueue::HELD])->save();

        $this->artisan('emails:send');

        Mail::assertNothingSent();
        $this->assertSame(EmailQueue::HELD, $email->fresh()->status);
    }

    public function test_an_email_stranded_by_a_dead_worker_is_picked_up_again(): void
    {
        Mail::fake();
        $email = $this->queueMail();
        $email->forceFill([
            'status'      => EmailQueue::SENDING,
            'reserved_at' => now()->subHour(),
        ])->save();

        $this->artisan('emails:send');

        $this->assertSame(EmailQueue::SENT, $email->fresh()->status);
    }

    /* ── The panel ──────────────────────────────────────────────────── */

    private function admin(array $permissions = ['settings.manage']): Admin
    {
        $admin = Admin::create([
            'name' => 'Ops', 'email' => 'ops@celebratemi.com',
            'password' => Hash::make('correct-horse-battery-1'),
            'is_super' => false, 'status' => 'active',
        ]);

        $admin->syncPermissions($permissions);

        return $admin->fresh('permissions');
    }

    public function test_the_outbox_needs_the_settings_permission(): void
    {
        // A rendered email carries personal detail; it is not for every seat.
        $this->actingAs($this->admin(['users.view']), 'admin')
            ->get(route('admin.outbox'))
            ->assertForbidden();
    }

    public function test_an_operator_can_read_a_failed_email_and_retry_it(): void
    {
        $email = $this->queueMail();
        $email->forceFill([
            'status'     => EmailQueue::FAILED,
            'attempts'   => 3,
            'last_error' => '550 mailbox unavailable',
        ])->save();

        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.outbox.show', $email))
            ->assertOk()
            ->assertSee('550 mailbox unavailable');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.outbox.retry', $email))
            ->assertRedirect();

        $email->refresh();
        $this->assertSame(EmailQueue::PENDING, $email->status);
        $this->assertSame(0, $email->attempts);
    }

    public function test_something_already_sent_cannot_be_resent_by_accident(): void
    {
        $email = $this->queueMail();
        $email->markSent('ok');

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.outbox.retry', $email))
            ->assertSessionHas('error');

        $this->assertSame(EmailQueue::SENT, $email->fresh()->status);
    }

    public function test_the_preview_refuses_to_run_scripts(): void
    {
        $email = $this->queueMail();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.outbox.preview', $email))
            ->assertOk()
            ->assertHeader('Content-Security-Policy', "default-src 'none'; img-src * data:; style-src 'unsafe-inline'");
    }
}
