<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        // The column has always existed and every writer passes it, but it was
        // missing here — so mass assignment dropped it and the funding callbacks
        // (which branch on $tx->wallet_type) credited the local wallet for USD
        // top-ups. It has to stay fillable.
        'wallet_type',
        'amount',
        'currency',
        'original_amount',
        'original_currency',
        'description',
        'reference',
        'status',
        'transactionable_type',
        'transactionable_id',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'original_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Who this money came from, where there is a person behind it.
     *
     * Only a gift or a registry contribution has a giver — a wallet top-up or
     * a withdrawal does not, and those return null so the ledger shows nothing
     * rather than an empty "from".
     *
     * Someone who gave anonymously stays anonymous here. The celebrant already
     * sees "Anonymous" on the page and in the gift email; the ledger is not a
     * back door around a choice the giver made.
     */
    public function giverName(): ?string
    {
        $source = $this->transactionable;

        if (! $source) {
            return null;
        }

        if (! empty($source->is_anonymous)) {
            return 'Anonymous';
        }

        // What was recorded when they gave: the name a guest typed into the
        // form, or the account name picked up automatically when the giver was
        // signed in. Gifts call the field sender_name; registry contributions
        // call the same person contributor_name.
        $name = trim((string) ($source->sender_name ?? $source->contributor_name ?? ''));

        if ($name !== '') {
            return $name;
        }

        // Signed in, but with nothing on their profile to pick up. The account
        // is still known, so name it rather than showing nobody.
        return $this->accountName($source);
    }

    /**
     * The giver's account name, for the rare row whose stored name is blank.
     *
     * Loaded through the relation so an already-eager-loaded giver costs no
     * query; a blank name is uncommon enough that the occasional lookup here
     * is cheaper than eager-loading a giver on every ledger row.
     */
    private function accountName(object $source): ?string
    {
        $giver = match (true) {
            $source instanceof \App\Models\Gift            => $source->sender,
            $source instanceof \App\Models\WishContribution => $source->contributor,
            default                                         => null,
        };

        if (! $giver) {
            return null;
        }

        $name = trim(($giver->first_name ?? '') . ' ' . ($giver->last_name ?? ''));

        return $name !== '' ? $name : null;
    }
}
