<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\Gift;
use App\Models\PlatformAvailableGift;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Wish;
use App\Models\WishContribution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The wallet ledger names whoever sent the money.
 *
 * "Gift received: Warm Hug" does not tell a celebrant who to thank. The giver
 * is already on the row through transactionable, so this only surfaces it —
 * with one limit: a giver who chose to be anonymous stays anonymous, since the
 * ledger must not be a back door around the choice they made on the page.
 */
class LedgerGiverNameTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
    }

    private function celebration(): Celebration
    {
        return Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => $this->owner->id,
            'title'            => "Yemi's Birthday",
            'slug'             => 'yemi-ledger-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Yemi',
            'status'           => 'published',
            'is_public'        => true,
        ]);
    }

    private function giftTransaction(bool $anonymous = false): WalletTransaction
    {
        $celebration  = $this->celebration();
        $platformGift = PlatformAvailableGift::create([
            'gift_name' => 'Warm Hug', 'gift_icon' => 'mdi-hand-heart',
            'gift_price' => 5, 'category' => 'small', 'status' => 'active',
        ]);

        $gift = Gift::create([
            'celebration_id'        => $celebration->id,
            'platform_gift_id'      => $platformGift->id,
            'sender_name'           => 'Ada Obi',
            'sender_email'          => 'ada@example.com',
            'amount'                => 5000,
            'currency'              => 'NGN',
            'guest_currency'        => 'NGN',
            'conversion_rate'       => 1.0,
            'payment_method'        => 'card',
            'transaction_reference' => 'ps-' . Str::uuid(),
            'payment_status'        => 'paid',
            'is_anonymous'          => $anonymous,
        ]);

        return WalletTransaction::create([
            'user_id'              => $this->owner->id,
            'type'                 => 'credit',
            'wallet_type'          => 'local',
            'amount'               => 5000,
            'currency'             => 'NGN',
            'description'          => 'Gift received: Warm Hug',
            'reference'            => $gift->transaction_reference,
            'status'               => 'completed',
            'transactionable_type' => Gift::class,
            'transactionable_id'   => $gift->id,
        ]);
    }

    public function test_a_gift_names_its_sender(): void
    {
        $this->assertSame('Ada Obi', $this->giftTransaction()->giverName());
    }

    public function test_an_anonymous_gift_stays_anonymous(): void
    {
        $this->assertSame('Anonymous', $this->giftTransaction(anonymous: true)->giverName());
    }

    public function test_a_registry_contribution_names_its_contributor(): void
    {
        $celebration = $this->celebration();

        $wish = Wish::create([
            'celebration_id' => $celebration->id, 'name' => 'Air Max',
            'wish_type' => 'cash', 'target_amount' => 100, 'amount_base' => 100,
            'base_currency' => 'USD', 'current_amount' => 0, 'currency' => 'USD',
            'status' => 'active',
        ]);

        $contribution = WishContribution::create([
            'celebration_id'    => $celebration->id,
            'wish_id'           => $wish->id,
            'contributor_name'  => 'Tolu Bello',
            'contributor_email' => 'tolu@example.com',
            'amount'            => 25,
            'currency'          => 'USD',
            'original_amount'   => 25,
            'contribution_type' => 'cash',
            'payment_reference' => 'wish-pay-' . Str::uuid(),
            'payment_status'    => 'paid',
            'is_anonymous'      => false,
        ]);

        $tx = WalletTransaction::create([
            'user_id'              => $this->owner->id,
            'type'                 => 'credit',
            'wallet_type'          => 'global',
            'amount'               => 25,
            'currency'             => 'USD',
            'description'          => 'Wish contribution: Air Max',
            'reference'            => $contribution->payment_reference,
            'status'               => 'completed',
            'transactionable_type' => WishContribution::class,
            'transactionable_id'   => $contribution->id,
        ]);

        $this->assertSame('Tolu Bello', $tx->giverName());
    }

    public function test_a_signed_in_giver_shows_their_account_name(): void
    {
        // Nobody types a name when they are signed in — it is picked up from
        // the account at the moment they give.
        $giver = User::factory()->create(['first_name' => 'Chidi', 'last_name' => 'Nwosu']);

        $tx = $this->giftTransaction();
        $tx->transactionable->update([
            'sender_user_id' => $giver->id,
            'sender_name'    => trim($giver->first_name . ' ' . $giver->last_name),
        ]);

        $this->assertSame('Chidi Nwosu', $tx->fresh()->giverName());
    }

    public function test_a_guest_shows_the_name_they_typed(): void
    {
        $tx = $this->giftTransaction();
        $tx->transactionable->update(['sender_user_id' => null, 'sender_name' => 'aunty bisi']);

        // Verbatim, including their own capitalisation.
        $this->assertSame('aunty bisi', $tx->fresh()->giverName());
    }

    public function test_a_signed_in_giver_with_an_empty_profile_still_gets_named(): void
    {
        $giver = User::factory()->create(['first_name' => 'Ngozi', 'last_name' => 'Eze']);

        $tx = $this->giftTransaction();
        $tx->transactionable->update(['sender_user_id' => $giver->id, 'sender_name' => '']);

        // Nothing was stored, but the account behind it is still known.
        $this->assertSame('Ngozi Eze', $tx->fresh()->giverName());
    }

    public function test_a_top_up_has_nobody_to_name(): void
    {
        // Funding your own wallet is not a gift from anyone.
        $tx = WalletTransaction::create([
            'user_id'     => $this->owner->id,
            'type'        => 'credit',
            'wallet_type' => 'local',
            'amount'      => 10000,
            'currency'    => 'NGN',
            'description' => 'Wallet funding',
            'reference'   => 'wf-' . Str::uuid(),
            'status'      => 'completed',
        ]);

        $this->assertNull($tx->giverName());
    }

    public function test_the_wallet_page_shows_the_name(): void
    {
        $this->giftTransaction();

        $this->actingAs($this->owner)
            ->get(route('dashboard.wallet'))
            ->assertOk()
            ->assertSee('Gift received: Warm Hug')
            ->assertSee('from Ada Obi', false);
    }

    public function test_the_wallet_page_does_not_leak_an_anonymous_giver(): void
    {
        $this->giftTransaction(anonymous: true);

        $html = $this->actingAs($this->owner)
            ->get(route('dashboard.wallet'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('from Anonymous', $html);
        $this->assertStringNotContainsString('Ada Obi', $html);
    }
}
