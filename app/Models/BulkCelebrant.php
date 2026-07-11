<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkCelebrant extends Model
{
    use HasFactory;

    protected $fillable = [
        'organisation_uuid',
        'first_name',
        'last_name',
        'email',
        'phone',
        'celebration_type',
        'celebration_date',
        'photo_path',
        'processed',
        'next_occurrence',
    ];

    protected $dates = ['celebration_date', 'next_occurrence'];
}
?>
