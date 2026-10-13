<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CelebrationResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\FrameResource;
use App\Http\Resources\PlatformGiftResource;
use App\Http\Resources\TemplateResource;
use App\Http\Resources\WishResource;
use App\Models\Celebration;
use App\Models\CelebrationTemplate;
use App\Models\Frame;
use App\Models\PlatformAvailableGift;
use App\Support\CelebrationVideos;
use App\Services\PaymentSystem\CurrencyService;
use App\Services\PaymentSystem\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * The celebration page, and the owner's edits to it.
 *
 * show() is the mobile counterpart of the web show(): it does the same currency
 * conversion server-side so the client never has to, and returns each tab's
 * data under its own key.
 */
class CelebrationController extends Controller
{
    /** Keep in step with CelebrationController::TYPE_LABELS on the web side. */
    private const TYPE_LABELS = [
        'birthday'    => 'Birthday',
        'wedding'     => 'Wedding',
        'memorial'    => 'Memorial',
        'graduation'  => 'Graduation',
        'anniversary' => 'Anniversary',
        'baby_shower' => 'Baby Shower',
        'other'       => 'Celebration',
    ];

    private const RESERVED_SLUGS = [
        'create', 'edit', 'new', 'admin', 'api', 'null', 'undefined', 'celebration',
    ];

    public function __construct(
        private CurrencyService $currency,
        private WalletService $wallet,
    ) {}

    /**
     * Public — a celebration page is viewable without an account, exactly as on
     * the web. An optional bearer token only decides whether you are the owner.
     */
    public function show(Request $request, string $slug)
    {
        $celebration = Celebration::where('slug', $slug)
            ->with(['user', 'wishes.user', 'gifts.platformGift', 'comments.user', 'comments.reactions', 'template', 'frame', 'contributions'])
            ->firstOrFail();

        $viewer  = $request->user();
        $isOwner = $viewer && $viewer->id === $celebration->user_id;

        if (! $isOwner) {
            $celebration->increment('view_count');
        }

        $viewerCurrency = $viewer
            ? $this->currency->forUser($viewer)
            : $this->currency->forVisitor();
        $symbol = config("currency.currencies.{$viewerCurrency}.symbol", $viewerCurrency);

        // Amount raised: only money actually paid, every row converted into the
        // viewer's currency, registry contributions counted alongside gifts.
        $toViewer = fn ($amount, ?string $from) => $this->currency->convert(
            (float) $amount,
            $from ?: config('currency.base'),
            $viewerCurrency
        );

        $paidGifts         = $celebration->gifts->where('payment_status', 'paid');
        $paidContributions = $celebration->contributions->where('payment_status', 'paid');

        $totalRaised = $paidGifts->sum(fn ($g) => $toViewer($g->amount, $g->currency))
            + $paidContributions->sum(fn ($c) => $toViewer($c->amount, $c->currency));

        $platformGifts = PlatformAvailableGift::active()->with('prices')->ordered()->get()
            ->each(function ($gift) use ($viewerCurrency) {
                $gift->displayPrice = $gift->priceIn($viewerCurrency);
            });

        $paidByWish = $paidContributions->groupBy('wish_id');

        $wishes = $celebration->wishes->each(function ($wish) use ($viewerCurrency, $paidByWish) {
            $wish->displayTarget  = $wish->displayAmount($viewerCurrency);
            $wish->displayCurrent = $wish->raisedIn(
                $viewerCurrency,
                $this->currency,
                $paidByWish->get($wish->id, collect())
            );
        });

        $countdownDate = $celebration->end_date ?? $celebration->start_date;
        $countdown = null;
        if ($countdownDate && now()->lt($countdownDate)) {
            $diff = now()->diff($countdownDate);
            $countdown = ['days' => $diff->days, 'hours' => $diff->h, 'mins' => $diff->i];
        }

        return response()->json([
            'celebration' => new CelebrationResource($celebration->loadCount(['gifts', 'wishes'])),
            'is_owner'    => $isOwner,
            'is_authenticated' => $viewer !== null,

            'currency' => ['code' => $viewerCurrency, 'symbol' => $symbol],
            'totals'   => [
                'raised'   => round($totalRaised, 2),
                'wishes'   => $celebration->wishes->count(),
                'comments' => $celebration->comments->count(),
            ],
            'wallet_balance' => $viewer ? round($this->wallet->balance($viewer, $viewerCurrency), 2) : 0.0,
            'countdown'      => $countdown,

            // One key per tab on the page.
            'comments'       => CommentResource::collection(
                $celebration->comments->sortByDesc('created_at')->values()
            ),
            'wishes'         => WishResource::collection($wishes),
            'platform_gifts' => PlatformGiftResource::collection($platformGifts),
            'supporters'     => $this->supporters($celebration, $viewerCurrency),

            'template'  => $celebration->template ? new TemplateResource($celebration->template) : null,
            'frame'     => $celebration->frame ? new FrameResource($celebration->frame) : null,
            // Only the owner can restyle the page, so only the owner needs the lists.
            'templates' => $isOwner
                ? TemplateResource::collection(CelebrationTemplate::where('is_active', true)->orderBy('sort_order')->get())
                : [],
            'frames'    => $isOwner && \App\Support\Features::framesEnabled() ? FrameResource::collection(Frame::all()) : [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'celebrantName' => ['required', 'string', 'max:255'],
            'eventType'     => ['required', Rule::in(array_keys(self::TYPE_LABELS))],
            'startDate'     => ['required', 'date'],
            'endDate'       => ['nullable', 'date', 'after_or_equal:startDate'],
            'eventTitle'    => ['nullable', 'string', 'max:255'],
        ]);

        $celebration = Celebration::create([
            'uuid'             => (string) str()->uuid(),
            // An omitted optional field is absent from validated(), not null.
            'title'            => ($data['eventTitle'] ?? null)
                ?: $data['celebrantName']."'s ".self::TYPE_LABELS[$data['eventType']],
            'user_id'          => $request->user()->id,
            'celebrant_name'   => $data['celebrantName'],
            'celebration_type' => $data['eventType'],
            'start_date'       => $data['startDate'],
            'end_date'         => $data['endDate'] ?? null,
            'slug'             => str()->slug($data['celebrantName']).'-'.random_int(1000, 9999),
        ]);

        return (new CelebrationResource($celebration))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $slug)
    {
        $celebration = $this->ownedBySlug($request, $slug);

        $data = $request->validate([
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

        // Going live for the first time stamps when.
        if ($data['status'] === 'published' && ! $celebration->published_at) {
            $data['published_at'] = now();
        }

        $celebration->update($data);

        return new CelebrationResource($celebration->fresh());
    }

    public function destroy(Request $request, string $slug)
    {
        $celebration = $this->ownedBySlug($request, $slug);
        $title = $celebration->title;
        $celebration->delete();

        return response()->json(['message' => "\"{$title}\" was deleted."]);
    }

    /**
     * Replace the cover photos. Up to four, as on the web editor.
     */
    public function updateCoverPhoto(Request $request, string $slug)
    {
        $celebration = $this->ownedBySlug($request, $slug);

        $request->validate([
            'cover_photos'   => ['required', 'array', 'max:4'],
            'cover_photos.*' => ['image', \App\Support\UploadLimits::imageRule()],
        ], [
            'cover_photos.*.max' => 'Each photo must be ' . \App\Support\UploadLimits::label() . ' or smaller.',
        ]);

        $paths = [];
        foreach ($request->file('cover_photos') as $file) {
            $paths[] = \App\Support\Images::store($file, 'covers');
        }

        // Single photo stays a bare string, several become a JSON array — the
        // shape the cover_photos accessor expects.
        $celebration->update([
            'cover_photo' => count($paths) === 1 ? $paths[0] : json_encode($paths),
        ]);

        return new CelebrationResource($celebration->fresh());
    }

    /**
     * Upload the celebration's video. One per celebration; a new one replaces
     * the old.
     *
     * The app sends the finished file — an MP4 it has already trimmed to a
     * minute and shrunk on the phone — with a poster it took itself. Both are
     * checked against config/video.php and published at once.
     */
    public function updateCoverVideo(Request $request, string $slug, CelebrationVideos $videos)
    {
        $celebration = $this->ownedBySlug($request, $slug);

        $maxMb = (int) config('video.max_upload_mb');

        $request->validate([
            'video'   => ['required', 'file', 'mimetypes:' . implode(',', config('video.mimetypes')), 'max:' . $maxMb * 1024],
            // A still the app took from the video, for the poster.
            'poster'  => ['nullable', 'image', 'max:4096'],
            // The length the app measured, for a file whose header does not say.
            'seconds' => ['nullable', 'numeric', 'min:0'],
        ], [
            'video.required'  => 'Choose a video to upload.',
            // What PHP reports when the file is over its own upload limit.
            'video.uploaded'  => "That video is too large. Keep it under {$maxMb}MB.",
            'video.max'       => "That video is too large. Keep it under {$maxMb}MB.",
            'video.mimetypes' => 'That file type is not supported. Upload an MP4 video.',
        ]);

        $videos->accept($celebration, $request->file('video'), $request->file('poster'), $request->input('seconds'));

        return new CelebrationResource($celebration->fresh());
    }

    /**
     * One piece of a video being uploaded in parallel chunks.
     *
     * The app cuts the file into ~1MB pieces and sends several at once, which
     * on a long-latency connection is several times faster than one stream.
     * Pieces wait on the private disk under the upload's id until
     * completeCoverVideo() stitches them together.
     */
    public function uploadCoverVideoChunk(Request $request, string $slug)
    {
        $celebration = $this->ownedBySlug($request, $slug);

        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'index'     => ['required', 'integer', 'min:0', 'max:255'],
            'total'     => ['required', 'integer', 'min:1', 'max:256'],
            'chunk'     => ['required', 'file', 'max:4096'],
        ]);

        $request->file('chunk')->storeAs(
            $this->chunkDir($celebration->id, $data['upload_id']),
            (string) $data['index'],
            'local'
        );

        return response()->json(['received' => (int) $data['index']]);
    }

    /**
     * All the pieces are up: put them together and treat the result exactly
     * as a video that arrived in one go.
     */
    public function completeCoverVideo(Request $request, string $slug, CelebrationVideos $videos)
    {
        $celebration = $this->ownedBySlug($request, $slug);

        $maxMb = (int) config('video.max_upload_mb');

        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'total'     => ['required', 'integer', 'min:1', 'max:256'],
            // Bytes the whole file should come to — proof nothing went missing.
            'size'      => ['required', 'integer', 'min:1', 'max:' . $maxMb * 1024 * 1024],
            'poster'    => ['nullable', 'image', 'max:4096'],
            'seconds'   => ['nullable', 'numeric', 'min:0'],
        ], [
            'size.max' => "That video is too large. Keep it under {$maxMb}MB.",
        ]);

        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        $dir  = $this->chunkDir($celebration->id, $data['upload_id']);

        for ($i = 0; $i < $data['total']; $i++) {
            if (! $disk->exists("{$dir}/{$i}")) {
                return response()->json(['message' => "Part {$i} of the video did not arrive. Please try the upload again."], 422);
            }
        }

        $assembled = $disk->path("{$dir}/assembled.mp4");
        $out = fopen($assembled, 'wb');
        for ($i = 0; $i < $data['total']; $i++) {
            $in = fopen($disk->path("{$dir}/{$i}"), 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);

        if (filesize($assembled) !== (int) $data['size']) {
            $disk->deleteDirectory($dir);

            return response()->json(['message' => 'The video did not arrive intact. Please try the upload again.'], 422);
        }

        $mime = (string) mime_content_type($assembled);
        if (! in_array($mime, config('video.mimetypes'), true)) {
            $disk->deleteDirectory($dir);

            return response()->json(['message' => 'That file type is not supported. Upload an MP4 video.'], 422);
        }

        // From here it is an ordinary upload; accept() moves the file out of
        // the chunk folder before that folder is removed.
        $file = new \Illuminate\Http\UploadedFile($assembled, 'celebration.mp4', $mime, null, true);

        try {
            $videos->accept($celebration, $file, $request->file('poster'), $request->input('seconds'));
        } finally {
            $disk->deleteDirectory($dir);
        }

        return new CelebrationResource($celebration->fresh());
    }

    private function chunkDir(int $celebrationId, string $uploadId): string
    {
        return "video-chunks/{$celebrationId}/{$uploadId}";
    }

    public function destroyCoverVideo(Request $request, string $slug, CelebrationVideos $videos)
    {
        $celebration = $this->ownedBySlug($request, $slug);

        $videos->remove($celebration);

        return new CelebrationResource($celebration->fresh());
    }

    public function updateFrame(Request $request, string $slug)
    {
        $celebration = $this->ownedBySlug($request, $slug);

        $request->validate(['frame_id' => ['nullable', 'exists:frames,id']]);

        // Taking a frame off is always allowed; putting one on needs the switch.
        if ($request->frame_id !== null && ! \App\Support\Features::framesEnabled()) {
            return response()->json(['message' => 'Frames are not available right now.'], 403);
        }

        $celebration->update(['frame_id' => $request->frame_id]);

        $frame = $celebration->fresh()->frame;

        return response()->json(['frame' => $frame ? new FrameResource($frame) : null]);
    }

    public function updateSlug(Request $request, string $slug)
    {
        $celebration = $this->ownedBySlug($request, $slug);

        $data = $request->validate([
            'slug' => [
                'required', 'string', 'min:3', 'max:60',
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

        $celebration->update(['slug' => $data['slug']]);

        return response()->json([
            'slug' => $celebration->slug,
            'url'  => route('celebrations.show', $celebration->slug),
        ]);
    }

    /**
     * Live availability check behind the slug editor's debounce.
     */
    public function checkSlug(Request $request, string $slug)
    {
        $celebration = $this->ownedBySlug($request, $slug);
        $candidate   = (string) $request->query('slug', '');

        $invalid = strlen($candidate) < 3
            || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $candidate)
            || in_array($candidate, self::RESERVED_SLUGS, true);

        if ($invalid) {
            return response()->json(['available' => false, 'reason' => 'invalid']);
        }

        $taken = Celebration::where('slug', $candidate)
            ->where('id', '!=', $celebration->id)
            ->exists();

        return response()->json([
            'available' => ! $taken,
            'reason'    => $taken ? 'taken' : null,
        ]);
    }

    public function applyTemplate(Request $request, string $slug)
    {
        $celebration = $this->ownedBySlug($request, $slug);

        $data = $request->validate([
            'template_id' => ['required', 'exists:celebration_templates,id'],
            'custom_bg'   => ['nullable', 'string', 'max:32'],
            'custom_text' => ['nullable', 'string', 'max:32'],
        ]);

        $celebration->update($data);

        return response()->json([
            'template'    => new TemplateResource($celebration->fresh()->template),
            'custom_bg'   => $celebration->custom_bg,
            'custom_text' => $celebration->custom_text,
        ]);
    }

    public function resetTemplate(Request $request, string $slug)
    {
        $celebration = $this->ownedBySlug($request, $slug);

        $celebration->update(['template_id' => null, 'custom_bg' => null, 'custom_text' => null]);

        return response()->json(['template' => null]);
    }

    /**
     * Every person who has actually paid, newest first, with their total in the
     * viewer's currency. Mirrors the web supporters() exactly, including the
     * rule that anonymous givers never merge with one another.
     */
    private function supporters(Celebration $celebration, string $viewerCurrency): array
    {
        $base = config('currency.base');

        $normalise = fn ($row, string $nameField, string $prefix) => [
            'name'    => $row->is_anonymous
                ? 'Anonymous'
                : (trim($row->{$nameField} ?? '') ?: 'Guest'),
            'anonKey' => $row->is_anonymous ? $prefix.$row->id : null,
            'amount'  => $this->currency->convert((float) $row->amount, $row->currency ?: $base, $viewerCurrency),
            'when'    => $row->created_at,
        ];

        return $celebration->gifts
            ->where('payment_status', 'paid')
            ->map(fn ($g) => $normalise($g, 'sender_name', 'gift-'))
            ->concat(
                $celebration->contributions
                    ->where('payment_status', 'paid')
                    ->map(fn ($c) => $normalise($c, 'contributor_name', 'wish-'))
            )
            ->groupBy(fn ($e) => $e['anonKey'] ?? mb_strtolower($e['name']))
            ->map(fn ($group) => [
                'name'  => $group->first()['name'],
                'count' => $group->count(),
                'total' => round($group->sum('amount'), 2),
                'when'  => $group->max('when')?->toIso8601String(),
            ])
            ->sortByDesc('when')
            ->values()
            ->all();
    }

    private function ownedBySlug(Request $request, string $slug): Celebration
    {
        $celebration = Celebration::where('slug', $slug)->firstOrFail();

        abort_if($celebration->user_id !== $request->user()->id, 403, 'You do not own this celebration.');

        return $celebration;
    }
}
