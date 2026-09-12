<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The staff door itself: guests, lockouts, and the console command that makes
 * the first account on a new server.
 *
 * What each admin can reach once inside is AdminPanelTest.
 */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attributes = []): Admin
    {
        return Admin::create($attributes + [
            'name'     => 'Ops',
            'email'    => 'ops@celebratemi.com',
            'password' => Hash::make('correct-horse-battery-1'),
            'is_super' => true,
            'status'   => 'active',
        ]);
    }

    public function test_a_guest_cannot_reach_any_admin_page(): void
    {
        foreach (['admin.dashboard', 'admin.users', 'admin.events', 'admin.payments', 'admin.withdrawals', 'admin.staff'] as $route) {
            $this->get(route($route))->assertRedirect(route('admin.login'));
        }
    }

    public function test_guessing_is_locked_out_after_five_attempts(): void
    {
        $admin = $this->admin();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('admin.login.post'), [
                'email'    => $admin->email,
                'password' => 'wrong-' . $attempt,
            ]);
        }

        // The sixth is refused before the password is checked, so the real one
        // does not get in either while the lockout stands.
        $this->post(route('admin.login.post'), [
            'email'    => $admin->email,
            'password' => 'correct-horse-battery-1',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_the_first_console_account_is_made_a_super_admin(): void
    {
        // Otherwise there would be nobody able to add the second one.
        $this->artisan('admin:create', [
            'email'      => 'first@celebratemi.com',
            '--name'     => 'First Admin',
            '--password' => 'correct-horse-battery-1',
        ])->assertSuccessful();

        $admin = Admin::where('email', 'first@celebratemi.com')->firstOrFail();

        $this->assertTrue($admin->is_super);
        $this->assertTrue(Hash::check('correct-horse-battery-1', $admin->password));
    }

    public function test_the_console_can_create_staff_with_named_permissions(): void
    {
        $this->admin();   // so the new one is not the first

        $this->artisan('admin:create', [
            'email'         => 'support@celebratemi.com',
            '--name'        => 'Support',
            '--password'    => 'correct-horse-battery-1',
            '--permissions' => 'payments.view,users.view,made.up.one',
        ])->assertSuccessful();

        $support = Admin::where('email', 'support@celebratemi.com')->firstOrFail();

        $this->assertFalse($support->is_super);
        $this->assertTrue($support->hasPermission('payments.view'));
        $this->assertTrue($support->hasPermission('users.view'));

        // Anything outside the catalogue is warned about and dropped.
        $this->assertFalse($support->hasPermission('made.up.one'));
        $this->assertDatabaseMissing('admin_permissions', ['permission' => 'made.up.one']);
    }

    public function test_the_console_can_suspend_an_admin(): void
    {
        $this->admin();
        $other = $this->admin(['email' => 'other@celebratemi.com', 'is_super' => false]);

        $this->artisan('admin:create', ['email' => $other->email, '--suspend' => true])
            ->assertSuccessful();

        $this->assertSame('suspended', $other->fresh()->status);
    }

    public function test_the_last_super_admin_cannot_be_suspended(): void
    {
        $admin = $this->admin();

        $this->artisan('admin:create', ['email' => $admin->email, '--suspend' => true])
            ->assertFailed();

        $this->assertSame('active', $admin->fresh()->status);
    }
}
