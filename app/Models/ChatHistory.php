<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'conversation_sid',
        'message_sid',
        'body',
        'author',
        'date_created',
        'is_starred',
        'attachments',
        'has_images',
    ];
}
