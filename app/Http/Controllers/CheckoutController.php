<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PaymentSystem\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CheckoutController extends Controller
{
    protected $checkout;

    public function __construct(CheckoutService $checkout)
    {
        $this->checkout = $checkout;
    }

    /**
     * Process a checkout request.
     *
     * Expected JSON payload:
     *   {
     *     "amount": 100.00,
     *     "metadata": { "order_id": 123, "email": "customer@example.com" }
     *   }
     */
    public function process(Request $request)
    {
        $request->validate([
            'amount'   => 'required|numeric|min:0.01',
            'metadata' => 'sometimes|array',
        ]);

        /** @var User $user */
        $user = $request->user(); // assumes auth middleware

        $result = $this->checkout->process(
            $user,
            $request->input('amount'),
            $request->input('metadata', [])
        );

        return response()->json($result);
    }
}
