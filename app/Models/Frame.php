<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Frame extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'css_content',
        'svg_content',
        'preview_image',
    ];

    public function celebrations()
    {
        return $this->hasMany(Celebration::class);
    }
}
?>
