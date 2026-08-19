<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Celebration;
use App\Models\PlatformNotification;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /** Every rail item, keyed by route name. */
    private const PAGES = [
        'dashboard'             => 'My Events',
        'dashboard.upcoming'    => 'Upcoming',
        'dashboard.activity'    => 'Activity',
        'dashboard.wallet'      => 'Wallet',
        'dashboard.bank'        => 'Bank Account',
        'dashboard.discover'    => 'Discover',
    ];

    private function user(array $attributes = []): User
    {
        $currency = $attributes['currency'] ?? 'NGN';
        unset($attributes['currency']);

        $user = User::factory()->create($attributes + [
            'wallet_balance'        => 0.00,
            'global_wallet_balance' => 0.00,
        ]);

        // `currency` is guarded on User — it's set from IP at registration and
        // is not meant to be mass-assignable, so tests have to force it.
        $user->forceFill(['currency' => $currency])->save();

        return $user;
    }

    public function test_every_dashboard_page_renders_the_full_shell(): void
    {
        $this->actingAs($this->user());

        foreach (array_keys(self::PAGES) as $routeName) {
            $response = $this->get(route($routeName));

            $response->assertOk();
            // the shell, not just the partial
            $response->assertSee('rail-nav', false);
        }
    }

    public function test_every_dashboard_page_returns_a_partial_to_the_router(): void
    {
        $this->actingAs($this->user());

        foreach (self::PAGES as $routeName => $heading) {
            $response = $this->withHeader('X-Partial', '1')->get(route($routeName));

            $response->assertOk();
            $response->assertJsonStructure(['title', 'nav', 'html', 'cache']);
            // dashboard pages carry live figures, so the router must not cache them
            $response->assertJsonPath('cache', false);
            $this->assertStringContainsString($heading, $response->json('title'));
            // the partial only — no shell markup
            $this->assertStringNotContainsString('rail-nav', $response->json('html'));
        }
    }

    public function test_dashboard_pages_require_authentication(): void
    {
        foreach (array_keys(self::PAGES) as $routeName) {
            $this->get(route($routeName))->assertRedirect(route('login'));
        }
    }

    public function test_marking_activity_read_clears_the_unread_badge(): void
    {
        $user = $this->user();

        PlatformNotification::factory()->count(3)->create([
            'user_id' => $user->id,
            'is_read' => false,
        ]);
        $read = PlatformNotification::factory()->create([
            'user_id' => $user->id,
            'is_read' => true,
        ]);

        $this->actingAs($user);

        // the badge and the button are both showing beforehand
        $this->get(route('dashboard.activity'))->assertSee('Mark all as read');

        $this->post(route('dashboard.activity.read'))
            ->assertRedirect(route('dashboard.activity'))
            ->assertSessionHas('success', '3 notifications marked as read.');

        $this->assertSame(0, $user->notifications()->where('is_read', false)->count());
        $this->assertTrue($read->refresh()->is_read);

        $this->get(route('dashboard.activity'))->assertDontSee('Mark all as read');
    }

    public function test_marking_activity_read_only_touches_your_own_notifications(): void
    {
        $user      = $this->user();
        $otherUser = $this->user();

        PlatformNotification::factory()->create(['user_id' => $user->id,      'is_read' => false]);
        $theirs = PlatformNotification::factory()->create(['user_id' => $otherUser->id, 'is_read' => false]);

        $this->actingAs($user)->post(route('dashboard.activity.read'));

        $this->assertFalse($theirs->refresh()->is_read);
    }

    public function test_marking_activity_read_requires_authentication(): void
    {
        $this->post(route('dashboard.activity.read'))->assertRedirect(route('login'));
    }

    /**
     * The tests above all hit the empty states. This one fills every collection
     * the shell loads so the list/grid branches actually get rendered.
     */
    public function test_every_dashboard_page_renders_with_data(): void
    {
        $user  = $this->user();
        $other = $this->user();

        $mine = $this->celebration($user, [
            'title'       => 'My Live Party',
            'status'      => 'published',
            'event_date'  => now()->addDays(9),
            'view_count'  => 120,
            'comment_count' => 7,
        ]);
        $this->celebration($user, ['title' => 'A Draft', 'status' => 'draft']);
        $this->celebration($other, [
            'title'      => 'Someone Elses Party',
            'status'     => 'published',
            'is_public'  => true,
            'event_date' => now()->addDays(3),
        ]);

        $bank = BankAccount::create([
            'user_id'        => $user->id,
            'bank_name'      => 'GT Bank',
            'account_number' => '0123456789',
            'account_name'   => 'Test User',
            'is_default'     => true,
        ]);

        Withdrawal::create([
            'user_id'         => $user->id,
            'bank_account_id' => $bank->id,
            'amount'          => 5000,
            'currency'        => 'NGN',
            'status'          => 'completed',
            'reference'       => 'WD-COMPLETED-1',
        ]);
        Withdrawal::create([
            'user_id'         => $user->id,
            'bank_account_id' => $bank->id,
            'amount'          => 1200,
            'currency'        => 'NGN',
            'status'          => 'pending',
            'reference'       => 'WD-PENDING-1',
            'note'            => 'Awaiting review',
        ]);

        WalletTransaction::create([
            'user_id'     => $user->id,
            'type'        => 'credit',
            'amount'      => 5000,
            'currency'    => 'NGN',
            'description' => 'Gift received',
            'reference'   => 'TX-CREDIT-1',
            'status'      => 'completed',
        ]);
        WalletTransaction::create([
            'user_id'     => $user->id,
            'type'        => 'debit',
            'amount'      => 1200,
            'currency'    => 'NGN',
            'description' => 'Withdrawal',
            'reference'   => 'TX-DEBIT-1',
            'status'      => 'completed',
        ]);

        PlatformNotification::factory()->count(2)->create(['user_id' => $user->id]);

        $this->actingAs($user);

        foreach (array_keys(self::PAGES) as $routeName) {
            $this->get(route($routeName))->assertOk();
        }

        $this->get(route('dashboard'))->assertSee('My Live Party');
        $this->get(route('dashboard.upcoming'))->assertSee('My Live Party');
        $this->get(route('dashboard.discover'))->assertSee('Someone Elses Party');
        $this->get(route('dashboard.bank'))->assertSee('GT Bank');
        $this->get(route('dashboard.wallet'))->assertSee('Awaiting review');

        // your own drafts stay out of Discover, and so do other people's pages
        $this->get(route('dashboard.discover'))->assertDontSee('My Live Party');

        $this->assertSame('My Live Party', $mine->fresh()->title);
    }

    /**
     * The Wallet page leads with balances and withdrawals. The full ledger is
     * still reachable, but folded behind a quiet disclosure rather than shown
     * as a running total of what someone has spent.
     */
    public function test_the_wallet_page_leads_with_balances_and_withdrawals(): void
    {
        $user = $this->user(['wallet_balance' => 7500.00, 'global_wallet_balance' => 42.50]);

        $bank = BankAccount::create([
            'user_id'        => $user->id,
            'bank_name'      => 'GT Bank',
            'account_number' => '0123456789',
            'account_name'   => 'Test User',
            'is_default'     => true,
        ]);

        Withdrawal::create([
            'user_id'         => $user->id,
            'bank_account_id' => $bank->id,
            'amount'          => 1200,
            'currency'        => 'NGN',
            'status'          => 'completed',
            'reference'       => 'WD-WALLET-1',
        ]);

        WalletTransaction::create([
            'user_id'     => $user->id,
            'type'        => 'debit',
            'amount'      => 900,
            'currency'    => 'NGN',
            'description' => 'Spent on a gift',
            'reference'   => 'TX-SPENT-1',
            'status'      => 'completed',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.wallet'))->assertOk();

        // both balances, always — local and global
        $response->assertSee('Local wallet (NGN)');
        $response->assertSee('Global wallet (USD)');
        $response->assertSee('7,500.00');
        $response->assertSee('42.50');

        // withdrawals took the transaction table's place
        $response->assertSee('Withdrawals');
        $response->assertSee('GT Bank');

        // the summary cards that totalled money in and out are gone
        $response->assertDontSee('Total in');
        $response->assertDontSee('Total out');

        // the ledger is present but behind the disclosure
        $response->assertSee('View transaction history');
        $response->assertSee('Spent on a gift');
    }

    public function test_the_wallet_page_shows_both_balances_for_a_usd_user(): void
    {
        $user = $this->user([
            'currency'              => 'USD',
            'wallet_balance'        => 10.00,
            'global_wallet_balance' => 20.00,
        ]);

        $this->actingAs($user)->get(route('dashboard.wallet'))
            ->assertOk()
            ->assertSee('Local wallet (USD)')
            ->assertSee('Global wallet (USD)');
    }

    public function test_the_wallet_page_hides_the_disclosure_when_there_are_no_transactions(): void
    {
        $this->actingAs($this->user())
            ->get(route('dashboard.wallet'))
            ->assertOk()
            ->assertDontSee('View transaction history')
            ->assertSee('No withdrawals yet');
    }

    /**
     * bank_account_id is nullOnDelete, so a withdrawal outlives the account it
     * was paid into. The history has to keep rendering from the snapshot.
     */
    public function test_withdrawal_history_renders_after_its_bank_account_is_deleted(): void
    {
        $user = $this->user();

        $bank = BankAccount::create([
            'user_id'        => $user->id,
            'bank_name'      => 'Zenith Bank',
            'account_number' => '0987654321',
            'account_name'   => 'Test User',
            'is_default'     => true,
        ]);

        Withdrawal::create([
            'user_id'             => $user->id,
            'bank_account_id'     => $bank->id,
            'bank_name'           => 'Zenith Bank',
            'bank_account_number' => '0987654321',
            'bank_account_name'   => 'Test User',
            'amount'              => 2500,
            'currency'            => 'NGN',
            'status'              => 'completed',
            'reference'           => 'WD-SNAPSHOT-1',
        ]);

        $bank->delete();

        $this->actingAs($user)
            ->get(route('dashboard.wallet'))
            ->assertOk()
            ->assertSee('Zenith Bank');
    }

    /**
     * Older rows predate the bank snapshot columns, so they have neither a
     * snapshot nor a bank account to read from. The history falls back to the
     * placeholder on Withdrawal::bankAccount()'s withDefault() rather than
     * blowing up on a null relation.
     */
    public function test_withdrawal_history_renders_when_it_has_no_bank_details_at_all(): void
    {
        $user = $this->user();

        Withdrawal::create([
            'user_id'         => $user->id,
            'bank_account_id' => null,
            'amount'          => 800,
            'currency'        => 'NGN',
            'status'          => 'failed',
            'reference'       => 'WD-ORPHAN-1',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.wallet'))
            ->assertOk()
            ->assertSee('Failed')
            ->assertSee('Removed account');
    }

    private function celebration(User $user, array $attributes = []): Celebration
    {
        static $n = 0;
        $n++;

        return Celebration::create($attributes + [
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $user->id,
            'title'            => "Celebration {$n}",
            'slug'             => "celebration-{$n}",
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Someone',
            'status'           => 'draft',
            'is_public'        => true,
        ]);
    }
}
