<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CelebrationGuestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->guest_name,
            'phone'          => $this->guest_phone,
            'invite_message' => $this->invite_message,
            'sms_status'     => $this->sms_status,
            'sms_sent_at'    => $this->sms_sent_at?->toIso8601String(),
            'rsvp_status'    => $this->rsvp_status,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
