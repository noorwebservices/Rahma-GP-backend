<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revenus_voyageur extends Model
{
    /** @use HasFactory<\Database\Factories\RevenusVoyageurFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function voyageur(): BelongsTo
    {
        return $this->belongsTo(Voyageur::class);
    }
 
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
