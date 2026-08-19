<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One of the gifts on the gift plate.
 *
 * display_price is stamped on the model by the controller in the viewer's
 * currency; gift_price stays the canonical USD figure.
 */
class PlatformGiftResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->gift_name,
            'description'   => $this->gift_description,
            'price_usd'     => round((float) $this->gift_price, 2),
            'display_price' => round((float) ($this->displayPrice ?? $this->gift_price), 2),
            'image'         => Media::url($this->gift_image_url),
            'link'          => $this->gift_link_url,
        ];
    }
}
