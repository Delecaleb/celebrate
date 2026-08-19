<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BankAccountResource;
use App\Models\BankAccount;
use Illuminate\Http\Request;

/**
 * Payout accounts. Same validation as the web controller, JSON in and out.
 */
class BankAccountController extends Controller
{
    private const RULES = [
        'bank_name'      => ['required', 'string', 'max:100'],
        'account_number' => ['required', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],
        'account_name'   => ['required', 'string', 'max:150'],
    ];

    public function index(Request $request)
    {
        return BankAccountResource::collection(
            $request->user()->bankAccounts()->orderByDesc('is_default')->oldest()->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate(self::RULES);
        $user = $request->user();

        $account = $user->bankAccounts()->create($data + [
            // The first account you add becomes the one you get paid into.
            'is_default' => $user->bankAccounts()->count() === 0,
        ]);

        return (new BankAccountResource($account))->response()->setStatusCode(201);
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $this->authorizeOwner($request, $bankAccount);

        $bankAccount->update($request->validate(self::RULES));

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
