<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Celebration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Models\Wish;
use App\Models\Comment;
use App\Models\PlatformAvailableGift;
use App\Services\PaymentSystem\CurrencyService;
use App\Services\PaymentSystem\WalletService;
use App\Models\CelebrationTemplate;

class CelebrationController extends Controller
{
    /**
     * The celebration_type enum, with the wording used when we have to build a
     * title ourselves. Keep in step with the selects in layouts/dashboard,
     * layouts/marketing and welcome.blade.php.
     */
    private const TYPE_LABELS = [
        'birthday'    => 'Birthday',
        'wedding'     => 'Wedding',
        'memorial'    => 'Memorial',
        'graduation'  => 'Graduation',
        'anniversary' => 'Anniversary',
        'baby_shower' => 'Baby Shower',
        'other'       => 'Celebration',
    ];

    public function store(Request $request)
{
    try {

        $validated = $request->validate([
            'celebrantName' => ['required', 'string', 'max:255'],
            // Must be one of the celebration_type enum values — anything else
            // is rejected by the database rather than silently stored.
            'eventType'     => ['required', Rule::in(array_keys(self::TYPE_LABELS))],
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
            'uuid'             => (string) \Illuminate\Support\Str::uuid(),
            // "John's Baby Shower", not "John's baby_shower"
            'title'            => $validated['eventTitle']
                ?? $validated['celebrantName'] . "'s " . self::TYPE_LABELS[$validated['eventType']],
            'user_id'          => Auth::id(),
            'celebrant_name'   => $validated['celebrantName'],
            // the column is celebration_type; 'event_type' isn't fillable, so
            // the chosen type used to be dropped and every page came out a birthday
            'celebration_type' => $validated['eventType'],
            'start_date'       => $validated['startDate'],
            'end_date'         => $validated['endDate'] ?? null,
            'slug'             => str()->slug($validated['celebrantName']) . '-' . rand(1000, 9999),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Celebration created successfully',
            'redirect' => route('celebrations.show', $celebration->slug)
        ]);

    } catch (ValidationException $e) {

        // Let Laravel turn this into a 422 with the field errors. Without this
        // the catch below swallowed it and every bad field came back as a 500.
        throw $e;

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
            ->with(['user', 'wishes.user', 'gifts.platformGift', 'comments.user', 'template', 'frame', 'contributions'])
            ->firstOrFail();

        $isOwner = Auth::check() && Auth::id() === $celebration->user_id;

        $totalWishes = $celebration->wishes->count();

        if (! $isOwner) {
            $celebration->increment('view_count');
        }

        $currencyService = app(CurrencyService::class);
        $walletService   = app(WalletService::class);
        $visitorCurrency = $currencyService->forVisitor();
        $visitorSymbol   = config("currency.currencies.{$visitorCurrency}.symbol", $visitorCurrency);

        /*
         * Amount raised.
         *
         * This has to be everything actually paid, in one currency:
         *   - only payment_status = paid; pending and failed are not money
         *   - each row converted from the currency it was taken in, since a
         *     page collects in whatever currency each giver saw
         *   - registry contributions as well as platform gifts — the registry
         *     is where most of the money comes in
         */
        $paidGifts         = $celebration->gifts->where('payment_status', 'paid');
        $paidContributions = $celebration->contributions->where('payment_status', 'paid');

        $toVisitor = fn ($amount, ?string $from) => $currencyService->convert(
            (float) $amount,
            $from ?: config('currency.base'),
            $visitorCurrency
        );

        $totalGifts = $paidGifts->sum(fn ($g) => $toVisitor($g->amount, $g->currency))
            + $paidContributions->sum(fn ($c) => $toVisitor($c->amount, $c->currency));

        $walletBalance = Auth::check()
            ? $walletService->balance(Auth::user(), $visitorCurrency)
            : 0.0;

        // Platform gifts with visitor-currency price pre-computed
        // priceIn() prefers a price an admin set for this currency by hand and
        // only converts the default where there is none.
        $platformGifts = PlatformAvailableGift::active()
            ->with('prices')
            ->ordered()
            ->get()
            ->map(function ($gift) use ($visitorCurrency) {
                $gift->displayPrice = $gift->priceIn($visitorCurrency);
                return $gift;
            });

        // Sidebar items with display totals
        $sidebarItems = $celebration
            ->sidebarGifts()
            ->map(function ($item) use ($currencyService, $visitorCurrency) {
                $item->displayTotal = $currencyService->convert($item->totalUsd, 'USD', $visitorCurrency);
                $item->received     = $item->totalUsd > 0;
                return $item;
            });

        // Wishes with visitor-currency amounts pre-computed (no @php in the view).
        // Grouped up front so raisedIn() never goes back to the database.
        $paidByWish = $paidContributions->groupBy('wish_id');

        $wishes = $celebration->wishes->map(function ($wish) use ($visitorCurrency, $currencyService, $paidByWish) {
            $wish->displayTarget  = $wish->displayAmount($visitorCurrency);
            $wish->displayCurrent = $wish->raisedIn(
                $visitorCurrency,
                $currencyService,
                $paidByWish->get($wish->id, collect())
            );

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

        $frames = $isOwner ? \App\Models\Frame::all() : collect();

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
            'frames'          => $frames,
            'visitorCurrency' => $visitorCurrency,
            'visitorSymbol'   => $visitorSymbol,
            'walletBalance'   => $walletBalance,
            'isAuthenticated' => Auth::check(),
            'supporters'      => $this->supporters($celebration, $currencyService, $visitorCurrency),
        ]);
    }

    /**
     * Everyone who has actually paid, newest first, with their total in the
     * visitor's currency. Drives the "From Johnson and 19 others" line and the
     * list behind it.
     *
     * Covers both platform gifts and registry contributions — someone who paid
     * for a registry item is as much a supporter as someone who sent a gift.
     * Anonymous givers keep their amount but lose their name.
     */
    private function supporters(
        Celebration $celebration,
        CurrencyService $currencyService,
        string $visitorCurrency
    ): \Illuminate\Support\Collection {
        $base = config('currency.base');

        $normalise = fn ($row, string $nameField, string $prefix) => (object) [
            'name'   => $row->is_anonymous
                ? 'Anonymous'
                : (trim($row->{$nameField} ?? '') ?: 'Guest'),
            'anonKey' => $row->is_anonymous ? $prefix . $row->id : null,
            'amount' => $currencyService->convert(
                (float) $row->amount,
                $row->currency ?: $base,
                $visitorCurrency
            ),
            'when'   => $row->created_at,
        ];

        $entries = $celebration->gifts
            ->where('payment_status', 'paid')
            ->map(fn ($g) => $normalise($g, 'sender_name', 'gift-'))
            ->concat(
                $celebration->contributions
                    ->where('payment_status', 'paid')
                    ->map(fn ($c) => $normalise($c, 'contributor_name', 'wish-'))
            );

        return $entries
            // anonymous givers never merge with each other
            ->groupBy(fn ($e) => $e->anonKey ?? mb_strtolower($e->name))
            ->map(fn ($group) => (object) [
                'name'  => $group->first()->name,
                'count' => $group->count(),
                'total' => $group->sum('amount'),
                'when'  => $group->max('when'),
            ])
            ->sortByDesc('when')
            ->values();
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

            // Only the celebrant may add to their own registry. This route sits
            // outside the auth middleware group, so it has to check for itself.
            $celebration = Celebration::findOrFail($request->celebration_id);

            if (! Auth::check() || Auth::id() !== $celebration->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only add items to your own registry.',
                ], 403);
            }

            $currency = app(CurrencyService::class)->forUser(Auth::user());

            foreach ($request->wishlist as $wish) {

                $imagePath = null;

                if (
                    isset($wish['image']) &&
                    $wish['image'] instanceof \Illuminate\Http\UploadedFile
                ) {
                    $imagePath = $wish['image']->store('wishlist', 'public');
                }

                $rawAmount = isset($wish['amount']) && $wish['amount'] !== '' ? (float) $wish['amount'] : null;

                // The no-amount branch has to carry the same keys as
                // computeAmounts(), or the Wish::create() below reads keys that
                // aren't there and the whole save 500s.
                $currencyData = $rawAmount !== null
                    ? app(CurrencyService::class)->computeAmounts($rawAmount, $currency)
                    : [
                        'currency'           => $currency,
                        'base_currency'      => config('currency.base'),
                        'amount_base'        => null,
                        'converted_currency' => null,
                        'amount_converted'   => null,
                        'conversion_rate'    => null,
                    ];

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
    try {

        $request->validate([
            'celebration_id' => ['required', 'exists:celebrations,id'],
            'anonymous' => ['nullable', 'boolean'],
            'comment' => [
                'nullable',
                'string',
                'max:1000',
                'required_without_all:image,video'
            ],
            'image' => [
                'nullable',
                'image',
                'max:5120',
                'required_without_all:comment,video'
            ],
            'video' => [
                'nullable',
                'file',
                'mimes:mp4,webm,ogg,quicktime',
                'max:20480',
                'required_without_all:comment,image'
            ]
        ]);

        $anonymous = filter_var(
            $request->anonymous,
            FILTER_VALIDATE_BOOLEAN
        );

        $user = Auth::user() ?? null;  
        $fullname = $user ? $user->first_name . ' ' . $user->last_name : null;
        $guest_email = $user ? $user->email : null;

        // media handling
        //
        // comments.media_type is NOT NULL, and every existing text-only wish
        // stores ''. Leaving this as null made the insert fail, so a plain
        // text wish could never be posted at all.
        $media_type = '';
        $media_url = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')
                ->store('comments', 'public');
            $media_type = 'local-image';
            $media_url = $imagePath;
        } elseif ($request->hasFile('video')) {
            $videoPath = $request->file('video')
                ->store('comments', 'public');
            $media_type = 'video';
            $media_url = $videoPath;
        }

        $comment = Comment::create([

            'celebration_id' => $request->celebration_id,

            'user_id' =>  Auth::id() ?? null,

            // A wish from someone not signed in is posted as Anonymous. The
            // playful stand-in names this used to pick read as real people,
            // which made an empty page look busier than it was.
            'guest_name' => $fullname ?? 'Anonymous',

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

        if ($request->hasFile('cover_photos')) {
            $request->validate([
                'cover_photos' => ['required', 'array', 'max:4'],
                'cover_photos.*' => ['image', 'max:5120']
            ]);

            $paths = [];
            foreach ($request->file('cover_photos') as $file) {
                $paths[] = $file->store('covers', 'public');
            }

            $celebration->cover_photo = json_encode($paths);
            $celebration->save();

            return response()->json([
                'success' => true,
                'message' => 'Cover photos updated successfully',
                'cover_urls' => array_map(fn($p) => asset('storage/' . $p), $paths)
            ]);
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

public function updateFrame(Request $request, $id)
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
            'frame_id' => ['nullable', 'exists:frames,id']
        ]);

        $celebration->frame_id = $request->frame_id;
        $celebration->save();

        $frame = $celebration->frame;

        return response()->json([
            'success' => true,
            'message' => 'Frame updated successfully',
            'frame' => $frame ? [
                'id' => $frame->id,
                'name' => $frame->name,
                'type' => $frame->type,
                'css_content' => $frame->css_content,
                'svg_content' => $frame->svg_content,
            ] : null
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
            'message' => 'Unable to update frame right now.'
        ], 500);
    }
}

/**
 * Slugs a celebrant may not take. Celebration URLs live under /celebration/…
 * so they can't collide with top-level routes, but these would still make for
 * confusing or ambiguous links.
 */
private const RESERVED_SLUGS = [
    'create', 'edit', 'new', 'admin', 'api', 'null', 'undefined', 'celebration',
];

/**
 * Let the owner choose their own celebration URL.
 */
public function updateSlug(Request $request, $id)
{
    try {
        $celebration = Celebration::findOrFail($id);

        if (! Auth::check() || Auth::id() !== $celebration->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'slug' => [
                'required', 'string', 'min:3', 'max:60',
                // lowercase letters, numbers and single inner hyphens only
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(self::RESERVED_SLUGS),
                Rule::unique('celebrations', 'slug')->ignore($celebration->id),
            ],
        ], [
            'slug.regex'  => 'Use lowercase letters, numbers and hyphens only.',
            'slug.unique' => 'That link is already taken — try another.',
            'slug.not_in' => 'That word is reserved. Please pick another.',
            'slug.min'    => 'Links need at least 3 characters.',
        ]);

        $celebration->slug = $validated['slug'];
        $celebration->save();

        return response()->json([
            'success' => true,
            'message' => 'Your celebration link has been updated.',
            'slug'    => $celebration->slug,
            'url'     => route('celebrations.show', $celebration->slug),
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => collect($e->errors())->flatten()->first(),
        ], 422);
    } catch (\Exception $e) {
        \Log::error($e);
        return response()->json([
            'success' => false,
            'message' => 'Unable to update the link right now.',
        ], 500);
    }
}

/**
 * Live availability check for the slug editor (debounced from the UI).
 */
public function checkSlug(Request $request, $id)
{
    $celebration = Celebration::findOrFail($id);

    if (! Auth::check() || Auth::id() !== $celebration->user_id) {
        return response()->json(['available' => false], 403);
    }

    $slug = (string) $request->query('slug', '');

    if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || strlen($slug) < 3 || strlen($slug) > 60) {
        return response()->json(['available' => false, 'reason' => 'invalid']);
    }

    if (in_array($slug, self::RESERVED_SLUGS, true)) {
        return response()->json(['available' => false, 'reason' => 'reserved']);
    }

    $taken = Celebration::where('slug', $slug)
        ->where('id', '!=', $celebration->id)
        ->exists();

    return response()->json([
        'available' => ! $taken,
        'reason'    => $taken ? 'taken' : null,
    ]);
}

/**
 * Remove a registry item.
 *
 * Soft delete, never a hard one — a wish may already have contributions
 * against it, and that money and its history have to survive.
 */
public function destroyWish(Wish $wish)
{
    $celebration = $wish->celebration;

    if (! Auth::check() || Auth::id() !== $celebration->user_id) {
        return response()->json([
            'success' => false,
            'message' => 'You can only remove items from your own registry.',
        ], 403);
    }

    $wish->delete();

    return response()->json([
        'success' => true,
        'message' => "\"{$wish->name}\" removed from your registry.",
    ]);
}

/**
 * Save the page details from the Settings tab.
 *
 * Routed as celebrant.update, which until now pointed at a method that did
 * not exist — the route 500'd on every call.
 */
public function update(Request $request, $slug)
{
    $celebration = Celebration::where('slug', $slug)->firstOrFail();

    if (! Auth::check() || Auth::id() !== $celebration->user_id) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    $validated = $request->validate([
        'title'            => ['required', 'string', 'max:255'],
        'celebrant_name'   => ['required', 'string', 'max:255'],
        'celebration_type' => ['required', Rule::in(array_keys(self::TYPE_LABELS))],
        'description'      => ['nullable', 'string', 'max:2000'],
        'venue'            => ['nullable', 'string', 'max:255'],
        'event_date'       => ['nullable', 'date'],
        'start_date'       => ['nullable', 'date'],
        'end_date'         => ['nullable', 'date', 'after_or_equal:start_date'],
        'is_public'        => ['required', 'boolean'],
        'status'           => ['required', Rule::in(['draft', 'published', 'closed'])],
    ]);

    // Publishing for the first time stamps the date the page went live.
    if ($validated['status'] === 'published' && ! $celebration->published_at) {
        $validated['published_at'] = now();
    }

    $celebration->update($validated);

    return response()->json([
        'success' => true,
        'message' => 'Page details saved.',
    ]);
}

/**
 * Delete a celebration. Routed as celebrant.destroy — the delete button on
 * the dashboard's event cards, which was also pointing at a missing method.
 */
public function destroy($slug)
{
    $celebration = Celebration::where('slug', $slug)->firstOrFail();

    if (! Auth::check() || Auth::id() !== $celebration->user_id) {
        abort(403);
    }

    $celebration->delete();

    return redirect()->route('dashboard')
        ->with('success', "\"{$celebration->title}\" was deleted.");
}
}
