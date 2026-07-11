<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Celebration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log;
use App\Models\Wish;
use App\Models\Comment;
use App\Models\PlatformAvailableGift;
use App\Services\PaymentSystem\CurrencyService;
use App\Services\PaymentSystem\WalletService;
use App\Models\CelebrationTemplate;

class CelebrationController extends Controller
{
    public function store(Request $request)
{
    try {

        $validated = $request->validate([
            'celebrantName' => ['required', 'string', 'max:255'],
            'eventType'     => ['required', 'string'],
            'startDate'     => ['required', 'date'],
            'endDate'       => ['nullable', 'date', 'after_or_equal:startDate'],
            'eventTitle'    => ['nullable', 'string', 'max:255'],
        ]);

        if (!Auth::check()) {

            $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', Password::min(6)],
            ]);

            $user = User::where('email', $request->email)->first();

            if ($user) {

                if (!Hash::check($request->password, $user->password)) {

                    return response()->json([
                        'message' => 'Invalid login credentials'
                    ], 422);
                }

                Auth::login($user);

            } else {

                $uuid = (string) \Illuminate\Support\Str::uuid();

                $firstName = explode(' ', $request->celebrantName)[0] ?? '--';

                $lastName = explode(' ', $request->celebrantName)[1] ?? '--';

                $user = User::create([
                    'uuid' => $uuid,
                    'name' => $request->celebrantName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);

                Auth::login($user);
            }
        }

        $celebration = Celebration::create([
            'uuid'           => (string) \Illuminate\Support\Str::uuid(),
            'title'          => $validated['eventTitle'] ?? $validated['celebrantName'] . "'s " . $validated['eventType'],
            'user_id'        => Auth::id(),
            'celebrant_name' => $validated['celebrantName'],
            'event_type'     => $validated['eventType'],
            'start_date'     => $validated['startDate'],
            'end_date'       => $validated['endDate'],
            'slug'           => str()->slug($validated['celebrantName']) . '-' . rand(1000, 9999),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Celebration created successfully',
            'redirect' => route('celebrations.show', $celebration->slug)
        ]);

    } catch (\Throwable $e) {

        Log::error('Celebration creation failed', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}


    public function show($slug)
    {
        $celebration = Celebration::where('slug', $slug)
            ->with(['user', 'wishes.user', 'gifts.platformGift', 'comments.user', 'template'])
            ->firstOrFail();

        $isOwner = Auth::check() && Auth::id() === $celebration->user_id;

        $totalWishes = $celebration->wishes->count();
        $totalGifts  = $celebration->gifts->sum('amount');

        if (! $isOwner) {
            $celebration->increment('view_count');
        }

        $currencyService = app(CurrencyService::class);
        $walletService   = app(WalletService::class);
        $visitorCurrency = $currencyService->forVisitor();
        $visitorSymbol   = config("currency.currencies.{$visitorCurrency}.symbol", $visitorCurrency);

        $walletBalance = Auth::check()
            ? $walletService->balance(Auth::user(), $visitorCurrency)
            : 0.0;

        // Platform gifts with visitor-currency price pre-computed
        $platformGifts = PlatformAvailableGift::where('is_active', true)
            ->get()
            ->map(function ($gift) use ($currencyService, $visitorCurrency) {
                $gift->displayPrice = round($currencyService->convert((float) $gift->gift_price, 'USD', $visitorCurrency), 2);
                return $gift;
            });

        // Sidebar items with display totals
        $sidebarItems = $celebration
            ->sidebarGifts($platformGifts)
            ->map(function ($item) use ($currencyService, $visitorCurrency) {
                $item->displayTotal = $currencyService->convert($item->totalUsd, 'USD', $visitorCurrency);
                $item->received     = $item->totalUsd > 0;
                return $item;
            });

        // Wishes with visitor-currency amounts pre-computed (no @php in the view)
        $wishes = $celebration->wishes->map(function ($wish) use ($visitorCurrency) {
            $wish->displayTarget  = $wish->displayAmount($visitorCurrency);
            $rate = (float) ($wish->conversion_rate ?? 1);
            $wish->displayCurrent = match (true) {
                $visitorCurrency === $wish->base_currency                              => (float) $wish->current_amount,
                $visitorCurrency === $wish->converted_currency && $rate > 0            => round((float) $wish->current_amount * $rate, 2),
                default                                                                => (float) $wish->current_amount,
            };
            return $wish;
        });

        // Countdown to end (or start) date
        $countdownDate = $celebration->end_date ?? $celebration->start_date;
        $countdown = null;
        if ($countdownDate && now()->lt($countdownDate)) {
            $diff      = now()->diff($countdownDate);
            $countdown = ['days' => $diff->days, 'hours' => $diff->h, 'mins' => $diff->i];
        }

        $templates = CelebrationTemplate::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('celebrations.show', [
            'celebration'     => $celebration,
            'isOwner'         => $isOwner,
            'totalWishes'     => $totalWishes,
            'totalGifts'      => $totalGifts,
            'platformGifts'   => $platformGifts,
            'sidebarItems'    => $sidebarItems,
            'wishes'          => $wishes,
            'countdown'       => $countdown,
            'templates'       => $templates,
            'visitorCurrency' => $visitorCurrency,
            'visitorSymbol'   => $visitorSymbol,
            'walletBalance'   => $walletBalance,
            'isAuthenticated' => Auth::check(),
        ]);
    }

    public function createWishes(Request $request)
    {
        try {

            $request->validate([
                'celebration_id' => ['required', 'exists:celebrations,id'],
                'wishlist' => ['required', 'array'],
                'wishlist.*.name' => ['required', 'string'],
                'wishlist.*.amount' => ['nullable', 'numeric', 'min:0'],
                'wishlist.*.image' => ['nullable', 'image', 'max:2048'],
            ]);

            $currency = app(CurrencyService::class)->forUser(Auth::user());

            foreach ($request->wishlist as $wish) {

                $imagePath = null;

                if (
                    isset($wish['image']) &&
                    $wish['image'] instanceof \Illuminate\Http\UploadedFile
                ) {
                    $imagePath = $wish['image']->store('wishlist', 'public');
                }

                $rawAmount    = isset($wish['amount']) && $wish['amount'] !== '' ? (float) $wish['amount'] : null;
                $currencyData = $rawAmount !== null
                    ? app(CurrencyService::class)->computeAmounts($rawAmount, $currency)
                    : ['currency' => $currency, 'amount_usd' => null, 'amount_ngn' => null, 'conversion_rate' => null];

                Wish::create([
                    'celebration_id'     => $request->celebration_id,
                    'name'               => $wish['name'],
                    'target_amount'      => $rawAmount,
                    'currency'           => $currencyData['currency'],
                    'base_currency'      => $currencyData['base_currency'],
                    'amount_base'        => $currencyData['amount_base'],
                    'converted_currency' => $currencyData['converted_currency'],
                    'amount_converted'   => $currencyData['amount_converted'],
                    'conversion_rate'    => $currencyData['conversion_rate'],
                    'wish_image'         => $imagePath,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Wishlist created successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }


public function storeComment(Request $request)
{
    $guestNames = [
        'Anonymous Admirer',
        'Secret Well-Wisher',
        'Birthday Fan',
        'Celebration Friend',
        'Mystery Guest',
        'Joy Bringer',
        'Secret Supporter',
    ];
    try {

        $request->validate([
            'celebration_id' => ['required', 'exists:celebrations,id'],
            'anonymous' => ['nullable', 'boolean'],
            'comment' => [
                'nullable',
                'string',
                'max:1000',
                'required_without:image'
            ],

            'image' => [
                'nullable',
                'image',
                'max:5120',
                'required_without:comment'
            ]
        ]);

        $anonymous = filter_var(
            $request->anonymous,
            FILTER_VALIDATE_BOOLEAN
        );

        $user = Auth::user() ?? null;  
        $fullname = $user ? $user->first_name . ' ' . $user->last_name : null;
        $guest_email = $user ? $user->email : null;

        // image handling 
        $imagePath = null;
        $media_type = null;
        $media_url = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')
                ->store('comments', 'public');
            $media_type = 'local-image';
            $media_url = asset('storage/' . $imagePath);
        }

        $comment = Comment::create([

            'celebration_id' => $request->celebration_id,

            'user_id' =>  Auth::id() ?? null,

            'guest_name' => $fullname ?? $guestNames[array_rand($guestNames)],

            'guest_email' => $guest_email,

            'message' => $request->comment,

            'media_type' => $media_type,
            'media_url' => $media_url,

            'is_pinned' => false,

            // auto approve for now
            'is_approved' => true,

            'like_count' => 0,
            'reply_count' => 0,
        ]);

        $comment->load('user');
        $comment->celebration()->increment('comment_count');
        return response()->json([
            'success' => true,
            'message' => 'Your wish has been delivered 🎉',
            'comment' => [
                'id' => $comment->id,
                'message' => $comment->message,
                'created_at' => $comment->created_at->diffForHumans(),

                'name' => $comment->user
                    ? $comment->user->name
                    : $comment->guest_name,

                'avatar' => $comment->user
                    ? $comment->user->profile_photo_url ?? null
                    : null
            ]
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => collect($e->errors())->flatten()->first()
        ], 422);

    } catch (\Exception $e) {

        \Log::error($e);

        return response()->json([
            'success' => false,
            'message' => 'Unable to submit wish right now.'
        ], 500);
    }
}

public function updateCoverPhoto(Request $request, $id)
{
    try {

        $celebration = Celebration::findOrFail($id);

        if (Auth::id() !== $celebration->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'cover_photo' => ['required', 'image', 'max:4096']
        ]);

        $path = $request->file('cover_photo')->store('covers', 'public');

        $celebration->cover_photo = $path;
        $celebration->save();

        return response()->json([
            'success' => true,
            'message' => 'Cover photo updated successfully',
            'cover_url' => asset('storage/' . $path)
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => collect($e->errors())->flatten()->first()
        ], 422);

    } catch (\Exception $e) {

        \Log::error($e);

        return response()->json([
            'success' => false,
            'message' => 'Unable to update cover photo right now.'
        ], 500);
    }
}
}   