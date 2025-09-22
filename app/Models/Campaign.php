<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'template_id',
        'rate_limit_per_minute',
        'status',
    ];

    protected $casts = [
        'rate_limit_per_minute' => 'integer',
    ];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
