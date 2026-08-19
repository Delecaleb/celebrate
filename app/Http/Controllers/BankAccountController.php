<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'bank_name'      => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],
            'account_name'   => ['required', 'string', 'max:150'],
        ]);

        $user    = auth()->user();
        $isFirst = $user->bankAccounts()->count() === 0;

        $user->bankAccounts()->create([
            'bank_name'      => $request->bank_name,
            'account_number' => $request->account_number,
            'account_name'   => $request->account_name,
            'is_default'     => $isFirst,
        ]);

        return back()
            ->with('success', 'Bank account saved successfully.');
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        abort_if($bankAccount->user_id !== auth()->id(), 403);

        $request->validate([
            'bank_name'      => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],
            'account_name'   => ['required', 'string', 'max:150'],
        ]);

        $bankAccount->update([
            'bank_name'      => $request->bank_name,
            'account_number' => $request->account_number,
            'account_name'   => $request->account_name,
        ]);

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
