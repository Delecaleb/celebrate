<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Services\PaymentSystem\CurrencyService;
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
            'amount'          => ['required', 'numeric', 'min:100'],
            'bank_account_id' => ['required', 'integer'],
        ]);

        $user        = auth()->user();
        $bankAccount = $user->bankAccounts()->findOrFail($request->bank_account_id);

        // Resolve user's display currency and convert to base (USD)
        $userCurrency  = $this->currency->forUser($user);
        $baseCurrency  = config('currency.base', 'USD');
        $amountDisplay = (float) $request->amount;

        $amountBase = $userCurrency === $baseCurrency
            ? $amountDisplay
            : $this->currency->convert($amountDisplay, $userCurrency, $baseCurrency);

        if (! $this->wallet->hasSufficientBalance($user, $amountBase)) {
            return back()
                ->withInput()
                ->with('error', 'Insufficient wallet balance.')
                ->with('active_tab', 'withdrawals');
        }

        $reference = 'wd-' . Str::uuid();

        DB::transaction(function () use (
            $user, $bankAccount, $amountBase, $amountDisplay,
            $userCurrency, $baseCurrency, $reference
        ) {
            $withdrawal = Withdrawal::create([
                'user_id'             => $user->id,
                'bank_account_id'     => $bankAccount->id,
                'bank_name'           => $bankAccount->bank_name,
                'bank_account_number' => $bankAccount->account_number,
                'bank_account_name'   => $bankAccount->account_name,
                'amount'              => $amountBase,
                'currency'            => $baseCurrency,
                'original_amount'     => $amountDisplay,
                'original_currency'   => $userCurrency,
                'status'              => 'pending',
                'reference'           => $reference,
            ]);

            $this->wallet->debit(
                user:             $user,
                amountBase:       $amountBase,
                description:      "Withdrawal to {$bankAccount->bank_name} ••{$bankAccount->account_number}",
                reference:        $reference,
                source:           $withdrawal,
                originalAmount:   $amountDisplay,
                originalCurrency: $userCurrency,
            );
        });

        return back()
            ->with('success', 'Withdrawal request submitted. We will process it within 1–2 business days.')
            ->with('active_tab', 'withdrawals');
    }
}
