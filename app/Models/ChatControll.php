<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatControll extends Model
{
    use HasFactory;

    protected $fillable=[
        'contact','sid','auto_reply','assistant_thread_id','assistant_metadata', 'first_name', 'last_name', 'email', 'address', 'postal_code','last_message', 'unread', 'unread_count',
    ];
}
