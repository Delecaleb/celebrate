<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A cover-photo frame.
 *
 * css_content is web-only and deliberately omitted — the mobile client renders
 * svg_content with react-native-svg, or falls back to the preview image.
 */
class FrameResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'type'          => $this->type,
            'svg_content'   => $this->svg_content,
            'preview_image' => Media::url($this->preview_image),
        ];
    }
}
