<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'bank_name'      => $this->bank_name,
            'account_number' => $this->account_number,
            // What the list rows and the "Paid into" tile show.
            'masked_number'  => '••'.substr((string) $this->account_number, -4),
            'account_name'   => $this->account_name,
            'is_default'     => (bool) $this->is_default,
            'is_verified'    => (bool) $this->is_verified,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
