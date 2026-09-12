<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesBankAccounts;
use App\Http\Controllers\Controller;
use App\Http\Resources\BankAccountResource;
use App\Models\BankAccount;
use App\Services\PaymentSystem\PaystackService;
use Illuminate\Http\Request;

/**
 * Payout accounts. Same verification as the web controller, JSON in and out:
 * the account name is whatever the bank says it is, never what the client sent.
 */
class BankAccountController extends Controller
{
    use ResolvesBankAccounts;

    public function index(Request $request)
    {
        return BankAccountResource::collection(
            $request->user()->bankAccounts()->orderByDesc('is_default')->oldest()->get()
        );
    }

    /**
     * The banks we can pay into — name and code, for the app's bank picker.
     */
    public function banks(PaystackService $paystack)
    {
        return response()->json(['data' => $paystack->banks()]);
    }

    /**
     * Look up the name on an account so the app can show it before saving.
     */
    public function resolve(Request $request)
    {
        $verified = $this->verifiedBankPayload($request->validate($this->bankAccountRules()));

        return response()->json([
            'bank_name'    => $verified['bank_name'],
            'account_name' => $verified['account_name'],
            'verified'     => $verified['is_verified'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->bankAccountRules());
        $user = $request->user();

        $account = $user->bankAccounts()->create($this->verifiedBankPayload($data) + [
            // The first account you add becomes the one you get paid into.
            'is_default' => $user->bankAccounts()->count() === 0,
        ]);

        return (new BankAccountResource($account))->response()->setStatusCode(201);
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $this->authorizeOwner($request, $bankAccount);

        $bankAccount->update(
            $this->verifiedBankPayload($request->validate($this->bankAccountRules()))
        );

        return new BankAccountResource($bankAccount->fresh());
    }

    public function destroy(Request $request, BankAccount $bankAccount)
    {
        $this->authorizeOwner($request, $bankAccount);

        $wasDefault = $bankAccount->is_default;
        $bankAccount->delete();

        // Never leave the user with accounts but no default.
        if ($wasDefault) {
            $request->user()->bankAccounts()->oldest()->first()?->update(['is_default' => true]);
        }

        return response()->json(['message' => 'Bank account removed.']);
    }

    public function setDefault(Request $request, BankAccount $bankAccount)
    {
        $this->authorizeOwner($request, $bankAccount);

        $request->user()->bankAccounts()->update(['is_default' => false]);
        $bankAccount->update(['is_default' => true]);

        return new BankAccountResource($bankAccount->fresh());
    }

    private function authorizeOwner(Request $request, BankAccount $bankAccount): void
    {
        abort_if($bankAccount->user_id !== $request->user()->id, 403);
    }
}
