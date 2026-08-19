<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WithdrawalResource;
use App\Models\Withdrawal;
use App\Services\PaymentSystem\CurrencyService;
use App\Services\PaymentSystem\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WithdrawalController extends Controller
{
    public function __construct(
        private WalletService $wallet,
        private CurrencyService $currency,
    ) {}

    public function index(Request $request)
    {
        return WithdrawalResource::collection(
            $request->user()->withdrawals()->with('bankAccount')->latest()->paginate(30)
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount'          => ['required', 'numeric', 'min:1'],
            'bank_account_id' => ['required', 'integer'],
            'wallet_type'     => ['required', 'string', 'in:local,global'],
        ]);

        $user        = $request->user();
        $bankAccount = $user->bankAccounts()->findOrFail($request->bank_account_id);
        $walletType  = $request->wallet_type;
        $amount      = (float) $request->amount;
        $currency    = $walletType === 'global' ? 'USD' : $this->currency->forUser($user);

        if (! $this->wallet->hasSufficientBalance($user, $amount, $walletType)) {
            return response()->json([
                'message' => 'Insufficient wallet balance.',
                'code'    => 'insufficient_balance',
            ], 422);
        }

        $reference = 'wd-'.Str::uuid();

        $withdrawal = DB::transaction(function () use ($user, $bankAccount, $amount, $currency, $walletType, $reference) {
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
                description:      "Withdrawal to {$bankAccount->bank_name} ••".substr((string) $bankAccount->account_number, -4),
                reference:        $reference,
                source:           $withdrawal,
                originalAmount:   $amount,
                originalCurrency: $currency,
                walletType:       $walletType,
            );

            return $withdrawal;
        });

        return (new WithdrawalResource($withdrawal))
            ->additional([
                'message' => 'Withdrawal request submitted. We will process it within 1–2 business days.',
                'wallet'  => [
                    'local'  => round($this->wallet->balance($user->fresh(), 'local'), 2),
                    'global' => round($this->wallet->balance($user->fresh(), 'global'), 2),
                ],
            ])
            ->response()
            ->setStatusCode(201);
    }
}
