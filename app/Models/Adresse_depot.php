<?php

namespace App\Models;

use Database\Factories\AdresseDepotFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Adresse_depot extends Model
{
    /** @use HasFactory<AdresseDepotFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected static function newFactory(): AdresseDepotFactory
    {
        return AdresseDepotFactory::new();
    }

    public function voyageur(): BelongsTo
    {
        return $this->belongsTo(Voyageur::class);
    }

    public function voyages(): HasMany
    {
        return $this->hasMany(Voyage::class);
    }
}
