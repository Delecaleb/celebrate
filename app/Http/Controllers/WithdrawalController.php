<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Services\PaymentSystem\CurrencyService;
use App\Services\PaymentSystem\PaystackService;
use App\Services\PaymentSystem\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WithdrawalController extends Controller
{
    public function __construct(
        private WalletService   $wallet,
        private CurrencyService $currency,
    ) {}

    public function store(Request $request)
    {
        $request->validate([
            'amount'          => ['required', 'numeric', 'min:1'],
            'bank_account_id' => ['required', 'integer'],
            'wallet_type'     => ['required', 'string', 'in:local,global'],
        ]);

        $user        = auth()->user();
        $bankAccount = $user->bankAccounts()->findOrFail($request->bank_account_id);

        // A payout goes to a name the bank itself confirmed. Accounts saved
        // before verification existed — or while the gateway was unreachable —
        // carry is_verified = false, and have to be re-saved before we will
        // send money to them.
        if (app(PaystackService::class)->isConfigured() && ! $bankAccount->is_verified) {
            return back()
                ->withInput()
                ->with('error', 'That bank account has not been confirmed with your bank yet. Open it, re-enter the account number, and save it again.');
        }
        // A USD user only has the global wallet — taking 'local' at face value
        // here checked one balance and debited the other.
        $walletType  = $this->wallet->resolveWalletType($user, $request->wallet_type);
        $amount      = (float) $request->amount;

        $userCurrency = $this->currency->forUser($user);
        $currency     = ($walletType === 'global') ? 'USD' : $userCurrency;

        // A payout smaller than the transfer is worth. Admins set the figure
        // per currency, so ₦ and $ are not held to one converted number.
        if (! \App\Support\WithdrawalLimits::allows($amount, $currency)) {
            return back()
                ->withInput()
                ->with('error', \App\Support\WithdrawalLimits::message($currency));
        }

        if (! $this->wallet->hasSufficientBalance($user, $amount, $walletType)) {
            return back()
                ->withInput()
                ->with('error', 'Insufficient wallet balance.');
        }

        $reference = 'wd-' . Str::uuid();

        DB::transaction(function () use (
            $user, $bankAccount, $amount, $currency, $walletType, $reference
        ) {
            $withdrawal = Withdrawal::create([
                'user_id'             => $user->id,
                'bank_account_id'     => $bankAccount->id,
                'wallet_type'         => $walletType,
                'bank_name'           => $bankAccount->bank_name,
                'bank_account_number' => $bankAccount->account_number,
                'bank_account_name'   => $bankAccount->account_name,
                'amount'              => $amount,
                'currency'            => $currency,
                'original_amount'     => $amount,
                'original_currency'   => $currency,
                'status'              => 'pending',
                'reference'           => $reference,
            ]);

            $this->wallet->debit(
                user:             $user,
                amount:           $amount,
                description:      "Withdrawal to {$bankAccount->bank_name} â€¢â€¢{$bankAccount->account_number}",
                reference:        $reference,
                source:           $withdrawal,
                originalAmount:   $amount,
                originalCurrency: $currency,
                walletType:       $walletType
            );
        });

        return back()
            ->with('success', 'Withdrawal request submitted. We will process it within 1 to 2 business days.');
    }
}
