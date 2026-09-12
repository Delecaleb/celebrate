<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Celebration;
use App\Models\User;
use App\Models\Withdrawal;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'users'               => User::count(),
            'events'              => Celebration::count(),
            'events_live'         => Celebration::where('status', 'published')->count(),
            'withdrawals_pending' => Withdrawal::where('status', 'pending')->count(),
            // The tile is labelled USD, so it can only be the USD wallets —
            // local balances are in mixed currencies and don't sum to a number.
            'total_wallet'        => User::sum('global_wallet_balance'),
            'total_credited'      => WalletTransaction::where('type', 'credit')->where('status', 'completed')->sum('amount'),
            'total_withdrawn'     => Withdrawal::whereIn('status', ['completed', 'processing'])->sum('amount'),
        ];

        $recentWithdrawals = Withdrawal::with(['user', 'bankAccount'])
            ->where('status', 'pending')
            ->latest()
            ->limit(8)
            ->get();

        $recentUsers = User::latest()->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'recentWithdrawals', 'recentUsers'));
    }

    public function users(Request $request)
    {
        $query = User::withCount('celebrations')
            ->when($request->search, fn ($q) =>
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name',  'like', "%{$request->search}%")
                  ->orWhere('email',      'like', "%{$request->search}%")
            )
            ->when($request->type, fn ($q) => $q->where('account_type', $request->type))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest();

        $users = $query->paginate(25)->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function events(Request $request)
    {
        $query = Celebration::with('user')
            ->withCount(['gifts', 'comments'])
            ->when($request->search, fn ($q) =>
                $q->where('title', 'like', "%{$request->search}%")
            )
            ->when($request->type,   fn ($q) => $q->where('celebration_type', $request->type))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest();

        $events = $query->paginate(25)->withQueryString();

        return view('admin.events', compact('events'));
    }

    public function withdrawals(Request $request)
    {
        $status = $request->get('status', 'pending');

        $query = Withdrawal::with('user')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest();

        $withdrawals = $query->paginate(30)->withQueryString();

        $counts = [
            'pending'    => Withdrawal::where('status', 'pending')->count(),
            'processing' => Withdrawal::where('status', 'processing')->count(),
            'completed'  => Withdrawal::where('status', 'completed')->count(),
            'failed'     => Withdrawal::where('status', 'failed')->count(),
            'rejected'   => Withdrawal::where('status', 'rejected')->count(),
            'all'        => Withdrawal::count(),
        ];

        return view('admin.withdrawals', compact('withdrawals', 'counts', 'status'));
    }

    public function approveWithdrawal(Request $request, Withdrawal $withdrawal)
    {
        abort_if(! in_array($withdrawal->status, ['pending', 'processing']), 422);

        $withdrawal->update([
            'status'       => 'completed',
            'processed_at' => now(),
        ]);

        \App\Models\AdminAuditLog::record(
            'admin.withdrawal.approved',
            sprintf(
                'Approved withdrawal #%d — %s %s to %s ••%s',
                $withdrawal->id,
                $withdrawal->currency,
                number_format((float) $withdrawal->amount, 2),
                $withdrawal->bank_name,
                substr((string) $withdrawal->bank_account_number, -4)
            ),
            $withdrawal,
            $withdrawal->reference,
        );

        return back()->with('success', "Withdrawal #{$withdrawal->id} marked as completed.");
    }

    public function rejectWithdrawal(Request $request, Withdrawal $withdrawal)
    {
        abort_if($withdrawal->status === 'completed', 422);

        $request->validate(['reason' => ['required', 'string', 'max:300']]);

        // Refund the wallet
        $walletService = app(\App\Services\PaymentSystem\WalletService::class);
        $walletService->credit(
            user:             $withdrawal->user,
            amount:           (float) $withdrawal->amount,
            description:      "Refund: rejected withdrawal #{$withdrawal->id}",
            reference:        'refund-' . $withdrawal->reference,
            source:           $withdrawal,
            originalAmount:   (float) $withdrawal->original_amount,
            originalCurrency: $withdrawal->original_currency,
            walletType:       $withdrawal->wallet_type ?? 'local'
        );

        $withdrawal->update([
            'status'       => 'rejected',
            'note'         => $request->reason,
            'processed_at' => now(),
        ]);

        \App\Models\AdminAuditLog::record(
            'admin.withdrawal.rejected',
            sprintf(
                'Rejected withdrawal #%d — %s %s refunded to the wallet. Reason: %s',
                $withdrawal->id,
                $withdrawal->currency,
                number_format((float) $withdrawal->amount, 2),
                $request->reason
            ),
            $withdrawal,
            $withdrawal->reference,
        );

        return back()->with('success', "Withdrawal #{$withdrawal->id} rejected and wallet refunded.");
    }

    public function frames(Request $request)
    {
        $frames = \App\Models\Frame::latest()->paginate(20);
        return view('admin.frames', compact('frames'));
    }

    public function storeFrame(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:css,svg'],
            'css_content' => ['nullable', 'string', 'required_if:type,css'],
            'svg_content' => ['nullable', 'string', 'required_if:type,svg'],
            'preview_image' => ['required', 'image', 'max:2048'],
        ]);

        $imagePath = null;
        if ($request->hasFile('preview_image')) {
            $file = $request->file('preview_image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            
            // Ensure directory exists
            $dir = public_path('images/frames');
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            $file->move($dir, $fileName);
            $imagePath = $fileName;
        }

        \App\Models\Frame::create([
            'name' => $request->name,
            'type' => $request->type,
            'css_content' => $request->css_content,
            'svg_content' => $request->svg_content,
            'preview_image' => $imagePath,
        ]);

        return back()->with('success', 'Frame added successfully.');
    }

    public function deleteFrame(\App\Models\Frame $frame)
    {
        if ($frame->preview_image && file_exists(public_path('images/frames/' . $frame->preview_image))) {
            @unlink(public_path('images/frames/' . $frame->preview_image));
        }

        $frame->delete();
        return back()->with('success', 'Frame deleted successfully.');
    }
}

