<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'lead_id',
        'template_snapshot',
        'status',
        'provider_message_id',
        'last_error',
        'scheduled_for',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'template_snapshot' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function events()
    {
        return $this->hasMany(MessageEvent::class);
    }
}
