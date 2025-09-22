<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'event_type',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
