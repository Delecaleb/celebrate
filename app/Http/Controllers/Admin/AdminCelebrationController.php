<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Celebration;

/**
 * One celebration, everything on it.
 *
 * The events list answers "what exists"; this answers "what happened" — the
 * registry and how far each item got, every gift and contribution against it
 * including the ones that failed, and the wishes people left.
 */
class AdminCelebrationController extends Controller
{
    public function show(Celebration $celebration)
    {
        $celebration->load([
            'user',
            'wishes' => fn ($q) => $q->orderByDesc('current_amount'),
            'gifts.platformGift',
            'comments' => fn ($q) => $q->latest()->limit(50),
        ]);

        $contributions = \App\Models\WishContribution::where('celebration_id', $celebration->id)
            ->with('wish')
            ->latest()
            ->get();

        $registryTotals = [
            'items'     => $celebration->wishes->count(),
            'funded'    => $celebration->wishes->filter(
                fn ($wish) => (float) $wish->target_amount > 0
                    && (float) $wish->current_amount >= (float) $wish->target_amount
            )->count(),
            'target'    => (float) $celebration->wishes->sum('target_amount'),
            'raised'    => (float) $celebration->wishes->sum('current_amount'),
        ];

        $money = [
            'gifts_paid'          => (float) $celebration->gifts->where('payment_status', 'paid')->sum('amount'),
            'gifts_pending'       => (float) $celebration->gifts->where('payment_status', 'pending')->sum('amount'),
            'contributions_paid'  => (float) $contributions->where('payment_status', 'paid')->sum('amount'),
            'contributions_other' => (float) $contributions->where('payment_status', '!=', 'paid')->sum('amount'),
        ];

        return view('admin.celebration', compact('celebration', 'contributions', 'registryTotals', 'money'));
    }
}
