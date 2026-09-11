<?php

namespace App\Models;

use Database\Factories\RevenusVoyageurFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revenus_voyageur extends Model
{
    /** @use HasFactory<RevenusVoyageurFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected static function newFactory(): RevenusVoyageurFactory
    {
        return RevenusVoyageurFactory::new();
    }

    public function voyageur(): BelongsTo
    {
        return $this->belongsTo(Voyageur::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
