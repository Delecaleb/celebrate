<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BankAccount;
use App\Models\Celebration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The admin panel: who gets in, what each level can reach, and what happens
 * when an admin signs in as a customer.
 *
 * Permissions are the kind of thing that quietly stops being enforced during a
 * refactor while every page still renders, so each one is asserted at the
 * route rather than in the sidebar.
 */
class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): Admin
    {
        return Admin::create([
            'name'     => 'Super Admin',
            'email'    => 'super@celebratemi.com',
            'password' => Hash::make('correct-horse-battery-1'),
            'is_super' => true,
            'status'   => 'active',
        ]);
    }

    /** @param array<int, string> $permissions */
    private function staffAdmin(array $permissions = []): Admin
    {
        $admin = Admin::create([
            'name'     => 'Support Staff',
            'email'    => 'support@celebratemi.com',
            'password' => Hash::make('correct-horse-battery-1'),
            'is_super' => false,
            'status'   => 'active',
        ]);

        $admin->syncPermissions($permissions);

        return $admin->fresh('permissions');
    }

    private function celebration(User $owner): Celebration
    {
        return Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $owner->id,
            'title'            => 'Panel celebration',
            'slug'             => 'panel-celebration-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Someone',
            'status'           => 'published',
            'is_public'        => true,
        ]);
    }

    /* ── Access ─────────────────────────────────────────────────────── */

    public function test_a_customer_account_cannot_sign_in_to_the_panel(): void
    {
        // The whole point of the separate table: a user, whatever flags their
        // row carries, is not an admin.
        $user = User::factory()->create(['password' => Hash::make('correct-horse-battery-1')]);
        $user->forceFill(['account_type' => 'admin'])->save();

        $this->post(route('admin.login.post'), [
            'email'    => $user->email,
            'password' => 'correct-horse-battery-1',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_a_super_admin_signs_in_and_reaches_every_section(): void
    {
        $admin = $this->superAdmin();

        $this->post(route('admin.login.post'), [
            'email'    => $admin->email,
            'password' => 'correct-horse-battery-1',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');

        foreach (['dashboard', 'users', 'events', 'payments', 'withdrawals', 'frames', 'staff'] as $section) {
            $this->actingAs($admin, 'admin')
                ->get(route("admin.{$section}"))
                ->assertOk();
        }
    }

    public function test_a_suspended_admin_is_turned_away(): void
    {
        $admin = $this->superAdmin();
        $admin->update(['status' => 'suspended']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    /* ── Permissions ────────────────────────────────────────────────── */

    public function test_staff_reach_only_what_they_were_granted(): void
    {
        $admin = $this->staffAdmin(['payments.view', 'users.view']);

        $this->actingAs($admin, 'admin')->get(route('admin.payments'))->assertOk();
        $this->actingAs($admin, 'admin')->get(route('admin.users'))->assertOk();

        // Everything else is refused at the route, not merely hidden.
        foreach (['events', 'withdrawals', 'frames', 'staff'] as $section) {
            $this->actingAs($admin, 'admin')
                ->get(route("admin.{$section}"))
                ->assertForbidden();
        }
    }

    public function test_staff_without_the_permission_cannot_process_a_withdrawal(): void
    {
        $viewer = $this->staffAdmin(['withdrawals.view']);
        $user   = User::factory()->create();

        $bank = BankAccount::create([
            'user_id'        => $user->id,
            'bank_name'      => 'GTBank',
            'account_number' => '0123456789',
            'account_name'   => 'SOMEONE',
            'is_default'     => true,
            'is_verified'    => true,
        ]);

        $withdrawal = \App\Models\Withdrawal::create([
            'user_id'             => $user->id,
            'bank_account_id'     => $bank->id,
            'wallet_type'         => 'local',
            'bank_name'           => $bank->bank_name,
            'bank_account_number' => $bank->account_number,
            'bank_account_name'   => $bank->account_name,
            'amount'              => 1000,
            'currency'            => 'NGN',
            'original_amount'     => 1000,
            'original_currency'   => 'NGN',
            'status'              => 'pending',
            'reference'           => 'wd-' . Str::uuid(),
        ]);

        $this->actingAs($viewer, 'admin')->get(route('admin.withdrawals'))->assertOk();

        $this->actingAs($viewer, 'admin')
            ->patch(route('admin.withdrawals.approve', $withdrawal))
            ->assertForbidden();

        $this->assertSame('pending', $withdrawal->fresh()->status);
    }

    public function test_only_admins_who_manage_staff_can_create_another_admin(): void
    {
        $this->actingAs($this->staffAdmin(['users.view']), 'admin')
            ->post(route('admin.staff.store'), [
                'name'     => 'Sneaky',
                'email'    => 'sneaky@celebratemi.com',
                'password' => 'correct-horse-battery-1',
                'password_confirmation' => 'correct-horse-battery-1',
                'status'   => 'active',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('admins', ['email' => 'sneaky@celebratemi.com']);
    }

    public function test_a_super_admin_creates_staff_with_an_access_level(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super, 'admin')
            ->post(route('admin.staff.store'), [
                'name'                  => 'Ada Okafor',
                'email'                 => 'ada@celebratemi.com',
                'password'              => 'correct-horse-battery-1',
                'password_confirmation' => 'correct-horse-battery-1',
                'status'                => 'active',
                'permissions'           => ['payments.view', 'payments.revalidate'],
            ])
            ->assertRedirect(route('admin.staff'));

        $ada = Admin::where('email', 'ada@celebratemi.com')->firstOrFail();

        $this->assertFalse($ada->is_super);
        $this->assertSame($super->id, $ada->created_by);
        $this->assertTrue($ada->hasPermission('payments.view'));
        $this->assertFalse($ada->hasPermission('withdrawals.process'));
    }

    public function test_a_permission_outside_the_catalogue_is_refused(): void
    {
        // The form only ever offers real ones, so this arrives from a
        // hand-made POST — rejected rather than quietly ignored.
        $this->actingAs($this->superAdmin(), 'admin')
            ->post(route('admin.staff.store'), [
                'name'                  => 'Ada Okafor',
                'email'                 => 'ada@celebratemi.com',
                'password'              => 'correct-horse-battery-1',
                'password_confirmation' => 'correct-horse-battery-1',
                'status'                => 'active',
                'permissions'           => ['payments.view', 'everything.always'],
            ])
            ->assertSessionHasErrors('permissions.1');

        $this->assertDatabaseMissing('admins', ['email' => 'ada@celebratemi.com']);
        $this->assertDatabaseMissing('admin_permissions', ['permission' => 'everything.always']);
    }

    public function test_the_last_super_admin_cannot_be_demoted(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super, 'admin')
            ->put(route('admin.staff.update', $super), [
                'name'   => $super->name,
                'email'  => $super->email,
                'status' => 'active',
            ])
            ->assertSessionHas('error');

        $this->assertTrue($super->fresh()->is_super);
    }

    /* ── Seeing everything ──────────────────────────────────────────── */

    public function test_the_payments_screen_shows_successful_pending_and_failed(): void
    {
        $admin = $this->superAdmin();
        $user  = User::factory()->create();
        $celebration = $this->celebration($user);

        $gift = fn (string $status, string $ref) => \App\Models\Gift::create([
            'celebration_id'        => $celebration->id,
            'sender_name'           => 'A Guest',
            'sender_email'          => 'guest@example.com',
            'amount'                => 5000,
            'currency'              => 'NGN',
            'guest_currency'        => 'NGN',
            'conversion_rate'       => 1,
            'payment_method'        => 'card',
            'transaction_reference' => $ref,
            'payment_status'        => $status,
            'is_anonymous'          => false,
        ]);

        $gift('paid', 'gift-pay-ok');
        $gift('pending', 'gift-pay-waiting');
        $gift('failed', 'gift-pay-nope');

        $all = $this->actingAs($admin, 'admin')->get(route('admin.payments'));
        $all->assertOk()
            ->assertSee('gift-pay-ok')
            ->assertSee('gift-pay-waiting')
            ->assertSee('gift-pay-nope');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.payments', ['status' => 'failed']))
            ->assertOk()
            ->assertSee('gift-pay-nope')
            ->assertDontSee('gift-pay-ok');
    }

    public function test_a_celebration_page_shows_its_registry_and_money(): void
    {
        $admin = $this->superAdmin();
        $user  = User::factory()->create();
        $celebration = $this->celebration($user);

        \App\Models\Wish::create([
            'celebration_id' => $celebration->id,
            'name'           => 'Trip to Zanzibar',
            'wish_type'      => 'cash',
            'target_amount'  => 500000,
            'current_amount' => 420000,
            'currency'       => 'NGN',
            'status'         => 'active',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.events.show', $celebration))
            ->assertOk()
            ->assertSee('Trip to Zanzibar')
            ->assertSee('Registry');
    }

    /* ── Impersonation ──────────────────────────────────────────────── */

    public function test_an_admin_can_view_an_account_as_its_owner(): void
    {
        $admin = $this->staffAdmin(['users.view', 'users.impersonate']);
        $user  = User::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.users.impersonate', $user))
            ->assertRedirect(route('dashboard'));

        // Both sessions are live: the customer one to browse with, the admin
        // one so stopping does not mean signing in again.
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertSame($admin->id, session('impersonator_admin_id'));

        // Recorded in the admin audit log, not on the customer's account.
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_id'   => $admin->id,
            'action'     => 'admin.impersonation.start',
            'subject_id' => $user->id,
        ]);
    }

    public function test_impersonation_cannot_move_money(): void
    {
        $admin = $this->staffAdmin(['users.impersonate']);
        $user  = User::factory()->create();
        $user->forceFill(['currency' => 'NGN', 'wallet_balance' => 50000])->save();

        $bank = BankAccount::create([
            'user_id'        => $user->id,
            'bank_name'      => 'GTBank',
            'account_number' => '0123456789',
            'account_name'   => 'SOMEONE',
            'is_default'     => true,
            'is_verified'    => true,
        ]);

        $this->actingAs($admin, 'admin')->post(route('admin.users.impersonate', $user));

        // Withdrawing, funding and changing the payout account are all closed.
        $this->post(route('withdrawals.store'), [
            'amount'          => 1000,
            'bank_account_id' => $bank->id,
            'wallet_type'     => 'local',
        ])->assertSessionHas('error');

        $this->assertSame(0, $user->withdrawals()->count());
        $this->assertSame(50000.0, (float) $user->fresh()->wallet_balance);

        $this->delete(route('bank-accounts.destroy', $bank))->assertSessionHas('error');
        $this->assertDatabaseHas('bank_accounts', ['id' => $bank->id]);
    }

    public function test_stopping_impersonation_returns_to_the_panel(): void
    {
        $admin = $this->staffAdmin(['users.view', 'users.impersonate']);
        $user  = User::factory()->create();

        $this->actingAs($admin, 'admin')->post(route('admin.users.impersonate', $user));

        $this->post(route('impersonation.stop'))->assertRedirect(route('admin.users'));

        $this->assertGuest('web');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNull(session('impersonator_admin_id'));

        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_id'   => $admin->id,
            'action'     => 'admin.impersonation.stop',
            'subject_id' => $user->id,
        ]);
    }

    /* ── The audit log ──────────────────────────────────────────────── */

    public function test_viewing_an_account_is_recorded_for_the_super_admin_not_the_customer(): void
    {
        $admin = $this->staffAdmin(['users.view', 'users.impersonate']);
        $user  = User::factory()->create();

        $this->actingAs($admin, 'admin')->post(route('admin.users.impersonate', $user));
        $this->post(route('impersonation.stop'));

        // Recorded where the platform's owner can see it…
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_id'   => $admin->id,
            'admin_email'=> $admin->email,
            'action'     => 'admin.impersonation.start',
            'subject_id' => $user->id,
        ]);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'admin.impersonation.stop']);

        // …and nowhere the customer's own account can surface it.
        $this->assertDatabaseMissing('activity_logs', ['user_id' => $user->id]);
        $this->assertSame(0, $user->activityLogs()->count());
    }

    public function test_only_a_super_admin_can_read_the_audit_log(): void
    {
        // Not even every permission is enough — this records the reader too.
        $everything = $this->staffAdmin(array_keys(Admin::PERMISSIONS));

        $this->actingAs($everything, 'admin')->get(route('admin.audit'))->assertForbidden();
        $this->actingAs($this->superAdmin(), 'admin')->get(route('admin.audit'))->assertOk();
    }

    public function test_a_customer_cannot_reach_the_audit_log(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.audit'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_the_audit_log_shows_who_did_what(): void
    {
        $super = $this->superAdmin();
        $user  = User::factory()->create(['email' => 'watched@example.com']);

        $this->actingAs($super, 'admin')->post(route('admin.users.impersonate', $user));
        $this->post(route('impersonation.stop'));

        $this->actingAs($super, 'admin')
            ->get(route('admin.audit'))
            ->assertOk()
            ->assertSee($super->email)
            ->assertSee('watched@example.com')
            ->assertSee('Started viewing an account');
    }

    public function test_staff_changes_are_recorded_too(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super, 'admin')->post(route('admin.staff.store'), [
            'name'                  => 'Ada Okafor',
            'email'                 => 'ada@celebratemi.com',
            'password'              => 'correct-horse-battery-1',
            'password_confirmation' => 'correct-horse-battery-1',
            'status'                => 'active',
            'permissions'           => ['payments.view'],
        ]);

        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_id' => $super->id,
            'action'   => 'admin.staff.created',
        ]);
    }

    public function test_an_audit_entry_outlives_the_admin_who_made_it(): void
    {
        $super = $this->superAdmin();
        $staff = $this->staffAdmin(['users.impersonate']);
        $user  = User::factory()->create();

        $this->actingAs($staff, 'admin')->post(route('admin.users.impersonate', $user));

        $this->actingAs($super, 'admin')->delete(route('admin.staff.destroy', $staff));

        // The row survives with the email intact — otherwise deleting an
        // account would erase what it had done.
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_id'    => null,
            'admin_email' => $staff->email,
            'action'      => 'admin.impersonation.start',
        ]);
    }

    public function test_an_admin_without_the_permission_cannot_impersonate(): void
    {
        $admin = $this->staffAdmin(['users.view']);
        $user  = User::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.users.impersonate', $user))
            ->assertForbidden();

        $this->assertGuest('web');
    }

    public function test_signing_out_of_the_panel_also_ends_an_impersonated_session(): void
    {
        $admin = $this->staffAdmin(['users.impersonate']);
        $user  = User::factory()->create();

        $this->actingAs($admin, 'admin')->post(route('admin.users.impersonate', $user));

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertGuest('web');
        $this->assertGuest('admin');
    }
}
