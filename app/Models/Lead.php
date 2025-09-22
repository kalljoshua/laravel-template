<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'name',
        'email',
        'metadata',
        'opted_out',
    ];

    protected $casts = [
        'metadata' => 'array',
        'opted_out' => 'boolean',
    ];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
