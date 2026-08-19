<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A celebration page theme. Every field here is a colour or a layout token the
 * client applies directly, so the mobile page can look like the web one.
 */
class TemplateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'icon'        => $this->icon,
            'description' => $this->description,

            'page_bg'        => $this->page_bg,
            'card_bg'        => $this->card_bg,
            'text_primary'   => $this->text_primary,
            'text_secondary' => $this->text_secondary,
            'accent_color'   => $this->accent_color,

            'photo_border_style' => $this->photo_border_style,
            'photo_border_color' => $this->photo_border_color,
            'photo_border_width' => (int) $this->photo_border_width,
            'wishes_layout'      => $this->wishes_layout,

            'sort_order' => (int) $this->sort_order,
        ];
    }
}
