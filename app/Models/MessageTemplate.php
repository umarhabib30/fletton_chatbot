<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    use HasFactory;

    protected $fillable=[
        'title', 'template_id', 'image', 'is_default', 'used_for'
    ];
}
