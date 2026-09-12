<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'       => $this->id,
            'type'     => $this->type,     // credit | debit
            'amount'   => round((float) $this->amount, 2),
            'currency' => $this->currency,
            'status'   => $this->status,
            // The web ledger falls back to a generic label when the row has no
            // description; do the same here so the client renders no blanks.
            'description' => $this->description ?: ($this->type === 'credit' ? 'Credit received' : 'Debit'),
            'reference'   => $this->reference,
            // Null for a top-up or a withdrawal — nobody gave those.
            'from'        => $this->giverName(),
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
