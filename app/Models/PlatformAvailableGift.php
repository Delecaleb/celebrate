<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformAvailableGift extends Model
{
    use HasFactory;

    protected $fillable = [
        'gift_name',
        'gift_description',
        'gift_price',
        'gift_image_url',
        'gift_link_url',
        'is_active',
    ];
    
}
