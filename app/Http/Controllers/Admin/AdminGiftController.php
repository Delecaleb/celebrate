<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Gift;
use App\Models\PlatformAvailableGift;
use App\Support\SvgSanitizer;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The gift catalogue.
 *
 * What a guest can send from any celebration page, what it costs, and whether
 * it is live. Prices are in USD — the base currency — and converted to the
 * visitor's currency at display time, so one catalogue covers every market.
 */
class AdminGiftController extends Controller
{
    public function index(Request $request)
    {
        $status   = (string) $request->query('status', '');
        $category = (string) $request->query('category', '');
        $search   = trim((string) $request->query('q', ''));

        $gifts = PlatformAvailableGift::query()
            ->with('prices')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($category !== '', fn ($q) => $q->where('category', $category))
            ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('gift_name', 'like', "%{$search}%")
                      ->orWhere('gift_description', 'like', "%{$search}%");
            }))
            ->ordered()
            ->get();

        // How often each one is actually sent — the number that says whether a
        // gift earns its place in the list.
        $sentCounts = Gift::selectRaw('platform_gift_id, COUNT(*) as total')
            ->where('payment_status', 'paid')
            ->groupBy('platform_gift_id')
            ->pluck('total', 'platform_gift_id');

        return view('admin.gifts.index', [
            'gifts'      => $gifts,
            'sentCounts' => $sentCounts,
            'status'     => $status,
            'category'   => $category,
            'search'     => $search,
            'categories'   => PlatformAvailableGift::CATEGORIES,
            'currencies'   => PlatformAvailableGift::supportedCurrencies(),
            'baseCurrency' => PlatformAvailableGift::baseCurrency(),
            'counts'     => [
                'all'     => PlatformAvailableGift::count(),
                'active'  => PlatformAvailableGift::where('status', PlatformAvailableGift::STATUS_ACTIVE)->count(),
                'pending' => PlatformAvailableGift::where('status', PlatformAvailableGift::STATUS_PENDING)->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.gifts.form', [
            'gift'       => new PlatformAvailableGift([
                'status'     => PlatformAvailableGift::STATUS_PENDING,
                'sort_order' => (int) PlatformAvailableGift::max('sort_order') + 10,
            ]),
            'categories'   => PlatformAvailableGift::CATEGORIES,
            'icons'        => $this->suggestedIcons(),
            'currencies'   => PlatformAvailableGift::supportedCurrencies(),
            'baseCurrency' => PlatformAvailableGift::baseCurrency(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($path = $this->storeArtwork($request)) {
            $data['gift_image_url'] = $path;
        }

        $gift = PlatformAvailableGift::create($data);
        $gift->syncPrices($request->input('prices', []));

        AdminAuditLog::record(
            'admin.gift.created',
            sprintf('Added the gift "%s" at $%s (%s)', $gift->gift_name, $gift->gift_price, $gift->status),
            $gift,
            $gift->gift_name,
        );

        return redirect()->route('admin.gifts')
            ->with('success', "\"{$gift->gift_name}\" has been added" . ($gift->isActive() ? ' and is live.' : ' as pending.'));
    }

    public function edit(PlatformAvailableGift $gift)
    {
        return view('admin.gifts.form', [
            'gift'         => $gift->load('prices'),
            'categories'   => PlatformAvailableGift::CATEGORIES,
            'icons'        => $this->suggestedIcons(),
            'currencies'   => PlatformAvailableGift::supportedCurrencies(),
            'baseCurrency' => PlatformAvailableGift::baseCurrency(),
        ]);
    }

    public function update(Request $request, PlatformAvailableGift $gift)
    {
        $before = $gift->only(['gift_price', 'status']);
        $data   = $this->validated($request, $gift);

        if ($path = $this->storeArtwork($request)) {
            $this->deleteArtwork($gift->gift_image_url);
            $data['gift_image_url'] = $path;
        } elseif ($request->boolean('remove_image')) {
            $this->deleteArtwork($gift->gift_image_url);
            $data['gift_image_url'] = null;
        }

        $gift->update($data);
        $gift->syncPrices($request->input('prices', []));

        AdminAuditLog::record(
            'admin.gift.updated',
            sprintf(
                'Changed "%s" — price $%s → $%s, %s → %s',
                $gift->gift_name,
                $before['gift_price'],
                $gift->gift_price,
                $before['status'],
                $gift->status
            ),
            $gift,
            $gift->gift_name,
        );

        return redirect()->route('admin.gifts')->with('success', "\"{$gift->gift_name}\" has been updated.");
    }

    /**
     * Flip between active and pending.
     *
     * Separate from update() so the list can do it in one click — the thing
     * staff actually need most often.
     */
    public function toggle(PlatformAvailableGift $gift)
    {
        $gift->update([
            'status' => $gift->isActive()
                ? PlatformAvailableGift::STATUS_PENDING
                : PlatformAvailableGift::STATUS_ACTIVE,
        ]);

        AdminAuditLog::record(
            'admin.gift.updated',
            sprintf('Set "%s" to %s', $gift->gift_name, $gift->status),
            $gift,
            $gift->gift_name,
        );

        return back()->with('success', $gift->isActive()
            ? "\"{$gift->gift_name}\" is now live on celebration pages."
            : "\"{$gift->gift_name}\" is pending and no longer offered to guests.");
    }

    public function destroy(PlatformAvailableGift $gift)
    {
        // A gift that has been sent is part of somebody's celebration and part
        // of the payment record behind it. Deleting the row would orphan both,
        // so it is retired instead.
        if (Gift::where('platform_gift_id', $gift->id)->exists()) {
            $gift->update(['status' => PlatformAvailableGift::STATUS_PENDING]);

            AdminAuditLog::record(
                'admin.gift.updated',
                sprintf('Retired "%s" — it has been sent before, so the record was kept', $gift->gift_name),
                $gift,
                $gift->gift_name,
            );

            return back()->with('success', "\"{$gift->gift_name}\" has been sent before, so it was set to pending rather than deleted.");
        }

        $name = $gift->gift_name;

        AdminAuditLog::record('admin.gift.deleted', "Deleted the gift \"{$name}\"", $gift, $name);

        $this->deleteArtwork($gift->gift_image_url);
        $gift->delete();

        return redirect()->route('admin.gifts')->with('success', "\"{$name}\" has been deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?PlatformAvailableGift $gift = null): array
    {
        return $request->validate([
            'gift_name'        => ['required', 'string', 'max:120', Rule::unique('platform_available_gifts', 'gift_name')->ignore($gift?->id)],
            'gift_description' => ['nullable', 'string', 'max:300'],
            // Stored in USD whatever the admin's own currency is — the label on
            // the form says so.
            'gift_price'       => ['required', 'numeric', 'min:0.01', 'max:100000'],

            // One optional price per supported currency. The keys are checked
            // against the config, so a stray currency in the payload is
            // rejected rather than stored and never read.
            'prices'   => ['sometimes', 'array', function ($attribute, $value, $fail) {
                $unknown = array_diff(array_keys((array) $value), array_keys(PlatformAvailableGift::supportedCurrencies()));

                if ($unknown !== []) {
                    $fail('Unknown currency: ' . implode(', ', $unknown) . '. Add it to config/currency.php first.');
                }
            }],
            'prices.*' => ['nullable', 'numeric', 'min:0', 'max:100000000'],

            // Artwork. PNG for photographs and rendered art, SVG for flat icons
            // that stay sharp at any size. Two megabytes is generous for both.
            'gift_image'       => ['nullable', 'file', 'max:2048', 'mimes:png,svg', 'mimetypes:image/png,image/svg+xml,text/plain'],
            'remove_image'     => ['sometimes', 'boolean'],

            // Kept as the stand-in for a gift with no artwork yet, so the
            // catalogue never renders an empty square.
            'gift_icon'        => ['nullable', 'string', 'max:60', 'regex:/^mdi-[a-z0-9-]+$/'],
            'accent_color'     => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'category'         => ['required', Rule::in(array_keys(PlatformAvailableGift::CATEGORIES))],
            'status'           => ['required', Rule::in([PlatformAvailableGift::STATUS_ACTIVE, PlatformAvailableGift::STATUS_PENDING])],
            'sort_order'       => ['required', 'integer', 'min:0', 'max:9999'],
            'gift_link_url'    => ['nullable', 'url', 'max:255'],
        ], [
            'gift_image.mimes'     => 'Upload a PNG or an SVG.',
            'gift_image.mimetypes' => 'Upload a PNG or an SVG.',
            'gift_image.max'       => 'Keep the image under 2MB.',
            'gift_icon.regex'      => 'Icons are Material Design Icon names, like mdi-cake-variant.',
        ]);
    }

    /**
     * Put an uploaded image on disk and return its path, or null if none came.
     *
     * An SVG is a document the browser will execute, served from our own
     * origin — so it is rewritten from an allow-list before it is stored, and
     * refused outright if what is left is not a usable drawing. A PNG is
     * stored as it arrives.
     *
     * @throws ValidationException when an SVG cannot be made safe
     */
    private function storeArtwork(Request $request): ?string
    {
        $file = $request->file('gift_image');

        if (! $file instanceof UploadedFile) {
            return null;
        }

        $isSvg = strtolower($file->getClientOriginalExtension()) === 'svg'
            || $file->getMimeType() === 'image/svg+xml';

        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'gift';
        $name = substr($name, 0, 60) . '-' . Str::random(8);

        if (! $isSvg) {
            return $file->storeAs('gifts', "{$name}.png", 'public');
        }

        $clean = app(SvgSanitizer::class)->clean((string) file_get_contents($file->getRealPath()));

        if ($clean === null) {
            throw ValidationException::withMessages([
                'gift_image' => 'That SVG could not be read, or contained scripting we will not serve. Export it as a plain SVG, or upload a PNG.',
            ]);
        }

        $path = "gifts/{$name}.svg";
        Storage::disk('public')->put($path, $clean);

        return $path;
    }

    private function deleteArtwork(?string $path): void
    {
        // Only ever our own directory — never a path that arrived in a request.
        if ($path && str_starts_with($path, 'gifts/')) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * A starting palette for the icon field.
     *
     * Not a limit — any mdi-* name is accepted — but staring at an empty text
     * box wondering what the icon font is called helps nobody.
     *
     * @return array<int, string>
     */
    private function suggestedIcons(): array
    {
        return [
            'mdi-gift-outline', 'mdi-cake-variant', 'mdi-glass-flute', 'mdi-glass-cocktail',
            'mdi-flower-outline', 'mdi-flower-tulip-outline', 'mdi-cupcake', 'mdi-cookie-outline',
            'mdi-coffee-outline', 'mdi-food-fork-drink', 'mdi-food-drumstick-outline', 'mdi-pot-steam-outline',
            'mdi-bottle-soda-classic-outline', 'mdi-basket-outline', 'mdi-hanger', 'mdi-tshirt-crew-outline',
            'mdi-spray-bottle', 'mdi-hair-dryer-outline', 'mdi-diamond-stone', 'mdi-purse-outline',
            'mdi-headphones', 'mdi-camera-outline', 'mdi-book-open-page-variant-outline', 'mdi-movie-open-outline',
            'mdi-airplane', 'mdi-bag-suitcase-outline', 'mdi-earth', 'mdi-gas-station-outline',
            'mdi-wifi', 'mdi-hand-heart-outline', 'mdi-emoticon-wink-outline', 'mdi-note-text-outline',
            'mdi-cash-multiple', 'mdi-heart', 'mdi-star', 'mdi-party-popper',
        ];
    }
}
