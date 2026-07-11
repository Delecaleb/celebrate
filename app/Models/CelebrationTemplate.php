<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CelebrationTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'page_bg',
        'card_bg',
        'text_primary',
        'text_secondary',
        'accent_color',
        'photo_border_style',
        'photo_border_color',
        'photo_border_width',
        'wishes_layout',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'photo_border_width'  => 'integer',
        'sort_order'          => 'integer',
    ];

    public function celebrations(): HasMany
    {
        return $this->hasMany(Celebration::class, 'template_id');
    }

    /**
     * Returns an inline style string of CSS custom properties for this template.
     * Applied on the page wrapper so all child elements can read the vars.
     */
    public function toCssVars(): string
    {
        $borderWidth = $this->photo_border_style === 'none' ? '0px' : "{$this->photo_border_width}px";

        return implode(';', [
            "--tpl-bg:{$this->page_bg}",
            "--tpl-card:{$this->card_bg}",
            "--tpl-text:{$this->text_primary}",
            "--tpl-text-muted:{$this->text_secondary}",
            "--tpl-accent:{$this->accent_color}",
            "--tpl-border-color:{$this->photo_border_color}",
            "--tpl-border-width:{$borderWidth}",
            "--tpl-border-style:{$this->photo_border_style}",
        ]);
    }
}
