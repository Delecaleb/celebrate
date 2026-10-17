<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Someone else's account, as it appears on their celebration page or a
 * Discover card — never the private parts.
 *
 * No email, ever. The phone number goes only to signed-in viewers: the app
 * matches it against the viewer's contacts on the phone to put people they
 * know first. Discover is open to guests, and a guest never gets it.
 *
 * UserResource is the signed-in user's own account and is not for this.
 */
class PublicUserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'name'          => trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: $this->username,
            'username'      => $this->username,
            'profile_photo' => Media::url($this->profile_photo),
            'phone'         => $this->when($request->user() !== null, $this->phone),
        ];
    }
}
