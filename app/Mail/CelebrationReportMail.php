<?php

namespace App\Mail;

use App\Models\Celebration;
use App\Services\PaymentSystem\CurrencyService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * How the day went, sent once it is over.
 *
 * This replaced a weekly and a monthly digest, which arrived whether or not
 * anything had happened and split one celebration across several emails. A
 * celebration is a single event with an end, so the report belongs at the end
 * of it — and again only if more arrives afterwards.
 */
class CelebrationReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Celebration $celebration,
        /** True when this is a second report, covering what came in late. */
        public bool $isUpdate = false,
    ) {
        $celebration->loadMissing('user', 'gifts.platformGift', 'contributions.wish', 'comments');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isUpdate
                ? 'More has come in for "' . $this->celebration->title . '" 🎁'
                : 'How "' . $this->celebration->title . '" went 🎉',
        );
    }

    public function content(): Content
    {
        $celebration = $this->celebration;
        $currency    = app(CurrencyService::class);

        $paidGifts    = $celebration->gifts->where('payment_status', 'paid');
        $paidTowards  = $celebration->contributions->where('payment_status', 'paid');
        $owner        = $celebration->user;
        $ownerCode    = $owner ? $currency->forUser($owner) : (string) config('currency.base');

        // Everything converted into the celebrant's own currency before it is
        // added up — a naira gift and a dollar one are not 250 of anything.
        $into = fn ($amount, ?string $from) => $currency->convert(
            (float) $amount,
            strtoupper($from ?: (string) config('currency.base')),
            $ownerCode
        );

        return new Content(
            view: 'emails.celebration-report',
            with: [
                'firstName'   => $owner?->first_name,
                'celebration' => $celebration,
                'isUpdate'    => $this->isUpdate,

                // What the page collected, and from how many people.
                'total'        => GiftLines::money(
                    $paidGifts->sum(fn ($gift) => $into($gift->amount, $gift->currency))
                        + $paidTowards->sum(fn ($row) => $into($row->amount, $row->currency)),
                    $ownerCode
                ),
                // Quantities, so three cupcakes count as three.
                'giftCount'    => (int) $paidGifts->sum(fn ($gift) => max(1, (int) $gift->quantity)),
                'towardsCount' => $paidTowards->count(),
                'wishCount'    => $celebration->comments->count(),
                'viewCount'    => (int) $celebration->view_count,
                'photoCount'   => count($celebration->cover_photos),

                // Who gave, most first — names the celebrant will want to thank.
                'givers' => $paidGifts
                    ->concat($paidTowards)
                    ->reject(fn ($row) => (bool) $row->is_anonymous)
                    ->groupBy(fn ($row) => trim($row->sender_name ?? $row->contributor_name ?? '') ?: 'A guest')
                    ->map(fn ($rows, $name) => [
                        'name'  => $name,
                        'count' => $rows->count(),
                        'total' => GiftLines::money(
                            $rows->sum(fn ($row) => $into($row->amount, $row->currency)),
                            $ownerCode
                        ),
                    ])
                    ->sortByDesc('count')
                    ->take(8)
                    ->values(),

                // The gifts themselves, tallied by kind.
                'gifts' => $paidGifts
                    ->groupBy(fn ($gift) => $gift->platformGift?->gift_name ?? 'Gift')
                    ->map(fn ($rows, $name) => [
                        'name' => $name,
                        'qty'  => (int) $rows->sum(fn ($gift) => max(1, (int) $gift->quantity)),
                    ])
                    ->sortByDesc('qty')
                    ->values(),
            ],
        );
    }
}
