<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Services\PaymentSystem\PaymentFulfilmentService;
use App\Services\PaymentSystem\PaystackService;
use App\Services\PaymentSystem\StripeService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Every payment on the platform, in one list.
 *
 * Money arrives through three different tables — a platform gift, a
 * contribution towards a wish, or a wallet top-up — and support needs to see
 * them together, including the ones that failed. A union keeps that a single
 * ordered, paginated query rather than three lists to reconcile by eye.
 */
class AdminPaymentController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));

        $payments = $this->paginatedPayments($status, $search, $request);

        return view('admin.payments', [
            'payments' => $payments,
            'status'   => $status,
            'search'   => $search,
            'counts'   => $this->counts(),
            'totals'   => $this->totals(),
        ]);
    }

    /**
     * Ask the gateway again about one payment, and settle it if it went
     * through. This is the manual version of the ten-minute sweep — support
     * uses it while a customer is on the phone.
     */
    public function revalidate(
        Request $request,
        PaymentFulfilmentService $fulfilment,
        PaystackService $paystack,
        StripeService $stripe,
    ) {
        $reference = (string) $request->validate([
            'reference' => ['required', 'string', 'max:190'],
        ])['reference'];

        $admin = Auth::guard('admin')->user();

        try {
            $paid = false;

            if ($paystack->isConfigured()) {
                $data = $paystack->verifyTransaction($reference);
                $paid = ($data['status'] ?? null) === 'success';
            }

            if (! $paid && $stripe->isConfigured()) {
                $session = $stripe->findSessionByReference($reference);
                $paid    = ($session?->payment_status ?? null) === 'paid';
            }
        } catch (\Throwable $e) {
            Log::warning('Admin revalidation could not reach the gateway', [
                'reference' => $reference,
                'admin'     => $admin->email,
                'error'     => $e->getMessage(),
            ]);

            return back()->with('error', "Could not reach the gateway for {$reference}. Nothing was changed — try again shortly.");
        }

        if (! $paid) {
            Log::info('Admin revalidated a payment: still unpaid', [
                'reference' => $reference,
                'admin'     => $admin->email,
            ]);

            return back()->with('error', "The gateway says {$reference} was not paid. Left as it is.");
        }

        $result = $fulfilment->fulfil($reference);

        AdminAuditLog::record(
            'admin.payment.revalidated',
            "Re-checked {$reference} with the gateway: paid, {$result}",
            null,
            $reference,
        );

        Log::info('Admin revalidated a payment', [
            'reference' => $reference,
            'admin'     => $admin->email,
            'result'    => $result,
        ]);

        return back()->with('success', match ($result) {
            PaymentFulfilmentService::DONE    => "{$reference} was paid — it has now been credited.",
            PaymentFulfilmentService::ALREADY => "{$reference} was already credited. Nothing to do.",
            default                           => "The gateway confirmed {$reference}, but no matching record exists here.",
        });
    }

    /**
     * The three payment tables as one list.
     */
    private function paginatedPayments(string $status, string $search, Request $request): LengthAwarePaginator
    {
        $gifts = DB::table('gifts')
            ->leftJoin('celebrations', 'celebrations.id', '=', 'gifts.celebration_id')
            ->selectRaw("
                gifts.id as source_id,
                'gift' as kind,
                gifts.transaction_reference as reference,
                gifts.amount as amount,
                gifts.currency as currency,
                gifts.payment_status as status,
                gifts.sender_name as payer,
                gifts.sender_email as payer_email,
                celebrations.title as context,
                celebrations.slug as context_slug,
                gifts.created_at as created_at
            ");

        $contributions = DB::table('wish_contributions')
            ->leftJoin('celebrations', 'celebrations.id', '=', 'wish_contributions.celebration_id')
            ->selectRaw("
                wish_contributions.id as source_id,
                'contribution' as kind,
                wish_contributions.payment_reference as reference,
                wish_contributions.amount as amount,
                wish_contributions.currency as currency,
                wish_contributions.payment_status as status,
                wish_contributions.contributor_name as payer,
                wish_contributions.contributor_email as payer_email,
                celebrations.title as context,
                celebrations.slug as context_slug,
                wish_contributions.created_at as created_at
            ");

        // MySQL joins strings with CONCAT, SQLite with ||. The test suite runs
        // on SQLite, so the query has to say which it is talking to.
        $fullName = DB::connection()->getDriverName() === 'sqlite'
            ? "TRIM(COALESCE(users.first_name, '') || ' ' || COALESCE(users.last_name, ''))"
            : "TRIM(CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')))";

        $topUps = DB::table('wallet_transactions')
            ->leftJoin('users', 'users.id', '=', 'wallet_transactions.user_id')
            ->where('wallet_transactions.type', 'credit')
            ->where('wallet_transactions.description', 'Wallet top-up')
            ->selectRaw("
                wallet_transactions.id as source_id,
                'top-up' as kind,
                wallet_transactions.reference as reference,
                wallet_transactions.amount as amount,
                wallet_transactions.currency as currency,
                wallet_transactions.status as status,
                {$fullName} as payer,
                users.email as payer_email,
                'Wallet top-up' as context,
                NULL as context_slug,
                wallet_transactions.created_at as created_at
            ");

        $union = $gifts->unionAll($contributions)->unionAll($topUps);

        $query = DB::query()->fromSub($union, 'payments');

        // 'completed' on a wallet transaction and 'paid' on a gift mean the
        // same thing to whoever is reading this screen.
        match ($status) {
            'successful' => $query->whereIn('status', ['paid', 'completed', 'success']),
            'pending'    => $query->whereIn('status', ['pending', 'processing']),
            'failed'     => $query->whereIn('status', ['failed', 'cancelled', 'abandoned']),
            default      => null,
        };

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('payer', 'like', "%{$search}%")
                  ->orWhere('payer_email', 'like', "%{$search}%")
                  ->orWhere('context', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at')
            ->paginate(40)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        $tally = fn (string $table, string $column) => DB::table($table)
            ->selectRaw("{$column} as status, COUNT(*) as total")
            ->groupBy($column)
            ->pluck('total', 'status')
            ->all();

        $gifts         = $tally('gifts', 'payment_status');
        $contributions = $tally('wish_contributions', 'payment_status');
        $topUps        = DB::table('wallet_transactions')
            ->where('type', 'credit')
            ->where('description', 'Wallet top-up')
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $sum = fn (array $keys) => collect([$gifts, $contributions, $topUps])
            ->sum(fn (array $set) => collect($set)->only($keys)->sum());

        return [
            'all'        => collect([$gifts, $contributions, $topUps])->sum(fn ($set) => collect($set)->sum()),
            'successful' => $sum(['paid', 'completed', 'success']),
            'pending'    => $sum(['pending', 'processing']),
            'failed'     => $sum(['failed', 'cancelled', 'abandoned']),
        ];
    }

    /**
     * @return array<string, float>
     */
    private function totals(): array
    {
        return [
            'gifts' => (float) DB::table('gifts')->where('payment_status', 'paid')->sum('amount'),
            'wishes' => (float) DB::table('wish_contributions')->where('payment_status', 'paid')->sum('amount'),
            'topups' => (float) DB::table('wallet_transactions')
                ->where('type', 'credit')
                ->where('status', 'completed')
                ->where('description', 'Wallet top-up')
                ->sum('amount'),
        ];
    }
}
