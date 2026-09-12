<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Payout accounts are only saved with the name the bank returns for that
 * bank + account number. The form disables its own submit button until the
 * lookup succeeds; these cover the half that a hand-made POST cannot skip.
 */
class BankAccountVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    /**
     * @param  string|null  $accountName  null = Paystack does not know the account
     */
    private function fakePaystack(?string $accountName = 'ADAEZE OKONKWO'): void
    {
        Http::fake([
            'api.paystack.co/bank?*' => Http::response([
                'status' => true,
                'data'   => [
                    ['name' => 'GTBank',      'code' => '058'],
                    ['name' => 'Zenith Bank', 'code' => '057'],
                ],
            ]),
            'api.paystack.co/bank/resolve*' => $accountName === null
                ? Http::response(['status' => false, 'message' => 'Could not resolve account name'], 422)
                : Http::response([
                    'status' => true,
                    'data'   => ['account_number' => '0123456789', 'account_name' => $accountName],
                ]),
        ]);
    }

    public function test_the_lookup_endpoint_returns_the_name_on_the_account(): void
    {
        $this->fakePaystack();

        $this->actingAs($this->user())
            ->postJson(route('bank-accounts.resolve'), [
                'bank_code'      => '058',
                'account_number' => '0123456789',
            ])
            ->assertOk()
            ->assertJson([
                'bank_name'    => 'GTBank',
                'account_name' => 'ADAEZE OKONKWO',
                'verified'     => true,
            ]);
    }

    public function test_an_account_is_saved_under_the_name_the_bank_returns(): void
    {
        $this->fakePaystack();
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('bank-accounts.store'), [
                'bank_code'      => '058',
                'account_number' => '0123456789',
                'account_name'   => 'Adaeze  okonkwo',   // same name, typed loosely
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bank_accounts', [
            'user_id'        => $user->id,
            'bank_name'      => 'GTBank',
            'bank_code'      => '058',
            'account_number' => '0123456789',
            'account_name'   => 'ADAEZE OKONKWO',
            'is_verified'    => true,
            'is_default'     => true,
        ]);
    }

    public function test_a_name_that_is_not_the_one_on_the_account_is_rejected(): void
    {
        $this->fakePaystack();
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('bank-accounts.store'), [
                'bank_code'      => '058',
                'account_number' => '0123456789',
                'account_name'   => 'Someone Else',
            ])
            ->assertSessionHasErrors('account_name');

        $this->assertSame(0, $user->bankAccounts()->count());
    }

    public function test_an_account_the_bank_does_not_recognise_is_rejected(): void
    {
        $this->fakePaystack(accountName: null);
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('bank-accounts.store'), [
                'bank_code'      => '058',
                'account_number' => '0000000000',
            ])
            ->assertSessionHasErrors('account_number');

        $this->assertSame(0, $user->bankAccounts()->count());
    }

    public function test_a_bank_outside_the_list_is_rejected(): void
    {
        $this->fakePaystack();
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('bank-accounts.store'), [
                'bank_code'      => '999',
                'account_number' => '0123456789',
            ])
            ->assertSessionHasErrors('bank_code');

        $this->assertSame(0, $user->bankAccounts()->count());
    }

    /**
     * A payout is the one place the verified flag has to bite. Before this it
     * was a badge on a list and nothing more.
     */
    public function test_an_unverified_account_cannot_be_withdrawn_to(): void
    {
        $this->fakePaystack();
        $user = $this->user();
        $user->forceFill(['currency' => 'NGN', 'wallet_balance' => 50000])->save();

        $unverified = BankAccount::create([
            'user_id'        => $user->id,
            'bank_name'      => 'GTBank',
            'bank_code'      => '058',
            'account_number' => '0123456789',
            'account_name'   => 'ADAEZE OKONKWO',
            'is_default'     => true,
            'is_verified'    => false,
        ]);

        $this->actingAs($user)
            ->post(route('withdrawals.store'), [
                'amount'          => 1000,
                'bank_account_id' => $unverified->id,
                'wallet_type'     => 'local',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, $user->withdrawals()->count());
        $this->assertSame(50000.0, (float) $user->fresh()->wallet_balance);

        // The same account, once confirmed, goes through.
        $unverified->update(['is_verified' => true]);

        $this->actingAs($user)
            ->post(route('withdrawals.store'), [
                'amount'          => 1000,
                'bank_account_id' => $unverified->id,
                'wallet_type'     => 'local',
            ]);

        $this->assertSame(1, $user->withdrawals()->count());
    }

    public function test_editing_an_account_re_checks_it_with_the_bank(): void
    {
        $this->fakePaystack(accountName: 'CHINEDU EZE');
        $user = $this->user();

        $account = BankAccount::create([
            'user_id'        => $user->id,
            'bank_name'      => 'Zenith Bank',
            'bank_code'      => '057',
            'account_number' => '9876543210',
            'account_name'   => 'ADAEZE OKONKWO',
            'is_default'     => true,
            'is_verified'    => true,
        ]);

        $this->actingAs($user)
            ->put(route('bank-accounts.update', $account), [
                'bank_code'      => '058',
                'account_number' => '0123456789',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bank_accounts', [
            'id'           => $account->id,
            'bank_name'    => 'GTBank',
            'account_name' => 'CHINEDU EZE',
        ]);
    }

    public function test_the_api_saves_the_resolved_name_too(): void
    {
        $this->fakePaystack();
        $user = $this->user();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bank-accounts', [
                'bank_code'      => '058',
                'account_number' => '0123456789',
                'account_name'   => 'Not The Owner',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('account_name');

        $this->assertSame(0, $user->bankAccounts()->count());
    }
}
