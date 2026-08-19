<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'name'          => trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: $this->username,
            'username'      => $this->username,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'bio'           => $this->bio,
            'profile_photo' => Media::url($this->profile_photo),
            'country'       => $this->country,
            'state'         => $this->state,
            'city'          => $this->city,
            'account_type'  => $this->account_type,
            'currency'      => $this->currency,
            'email_verified' => $this->email_verified_at !== null,
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
