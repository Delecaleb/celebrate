<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'category',
        'description',
        'logo',
        'cover_photo',
        'address',
        'phone',
        'email',
        'website',
        'instagram',
        'facebook',
        'verification_status',
        'rating',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function services()
    {
        return $this->hasMany(VendorService::class);
    }
}
