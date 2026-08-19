<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'amount'      => round((float) $this->amount, 2),
            'currency'    => $this->currency,
            'status'      => $this->status,
            'reference'   => $this->reference,
            'note'        => $this->note,
            'wallet_type' => $this->wallet_type,

            'bank_name'           => $this->bank_name,
            'bank_account_name'   => $this->bank_account_name,
            'bank_account_number' => $this->bank_account_number,
            'masked_number'       => '••'.substr((string) $this->bank_account_number, -4),

            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at'   => $this->created_at?->toIso8601String(),
        ];
    }
}
