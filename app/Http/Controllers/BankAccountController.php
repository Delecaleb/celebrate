<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesBankAccounts;
use App\Models\BankAccount;
use App\Services\PaymentSystem\PaystackService;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    use ResolvesBankAccounts;

    /**
     * Name lookup for the add/edit forms.
     *
     * The form calls this as soon as a bank is picked and ten digits are typed,
     * and only enables its submit button once it answers. store() runs the same
     * check again — this endpoint is a convenience, not the gate.
     */
    public function resolve(Request $request)
    {
        $data     = $request->validate($this->bankAccountRules());
        $verified = $this->verifiedBankPayload($data);

        return response()->json([
            'bank_name'    => $verified['bank_name'],
            'account_name' => $verified['account_name'],
            'verified'     => $verified['is_verified'],
        ]);
    }

    /**
     * The banks we can pay into, for the form's dropdown.
     */
    public function banks(PaystackService $paystack)
    {
        return response()->json(['data' => $paystack->banks()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->bankAccountRules());

        $user    = auth()->user();
        $isFirst = $user->bankAccounts()->count() === 0;

        $user->bankAccounts()->create($this->verifiedBankPayload($data) + [
            'is_default' => $isFirst,
        ]);

        return back()
            ->with('success', 'Bank account saved successfully.');
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        abort_if($bankAccount->user_id !== auth()->id(), 403);

        $data = $request->validate($this->bankAccountRules());

        $bankAccount->update($this->verifiedBankPayload($data));

        return back()
            ->with('success', 'Bank account updated.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        abort_if($bankAccount->user_id !== auth()->id(), 403);

        $wasDefault = $bankAccount->is_default;
        $bankAccount->delete();

        // Promote the oldest remaining account to default
        if ($wasDefault) {
            auth()->user()->bankAccounts()->oldest()->first()?->update(['is_default' => true]);
        }

        return back()
            ->with('success', 'Bank account removed.');
    }

    public function setDefault(BankAccount $bankAccount)
    {
        abort_if($bankAccount->user_id !== auth()->id(), 403);

        auth()->user()->bankAccounts()->update(['is_default' => false]);
        $bankAccount->update(['is_default' => true]);

        return back()
            ->with('success', 'Default account updated.');
    }
}
