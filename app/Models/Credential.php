<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credential extends Model
{
    use HasFactory;

    protected $fillable = [
        'open_ai_key', 'assistant_id', 'twilio_sid', 'twilio_token', 'twilio_whats_app', 'keap_api_key'
    ];
}
