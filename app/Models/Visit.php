<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'visitor_id',
        'user_id',
        'session_id',
        'ip_address',
        'country',
        'country_code',
        'city',
        'platform',
        'device_type',
        'path',
        'referrer',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
