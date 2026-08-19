<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A registry item.
 *
 * display_target / display_current are set on the model by the controller
 * before this runs (same as the web page does) so the client never converts
 * currency itself.
 */
class WishResource extends JsonResource
{
    public function toArray($request): array
    {
        $target  = (float) ($this->displayTarget ?? $this->target_amount ?? 0);
        $current = (float) ($this->displayCurrent ?? $this->current_amount ?? 0);

        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'wish_type'   => $this->wish_type,
            'wish_image'  => Media::url($this->wish_image),
            'wish_link'   => $this->wish_link,

            'display_target'  => round($target, 2),
            'display_current' => round($current, 2),
            // Clamped: an over-funded item should read 100%, not 130%.
            'percent_funded'  => $target > 0 ? min(100, (int) round($current / $target * 100)) : 0,
            'is_funded'       => $target > 0 && $current >= $target,

            'base_currency'      => $this->base_currency,
            'converted_currency' => $this->converted_currency,
            'currency'           => $this->currency,

            'priority_level'             => $this->priority_level,
            'status'                     => $this->status,
            'allow_partial_contribution' => (bool) $this->allow_partial_contribution,
            'contribution_count'         => (int) $this->contribution_count,

            'celebration_id' => $this->celebration_id,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
