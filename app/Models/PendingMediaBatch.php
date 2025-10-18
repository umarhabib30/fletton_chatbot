<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingMediaBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_number',
        'media_paths',
        'last_received_at',
    ];

    protected $casts = [
        'media_paths' => 'array',
        'last_received_at' => 'datetime',
    ];
}
