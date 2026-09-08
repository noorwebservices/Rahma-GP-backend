<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Voyage extends Model
{
    /** @use HasFactory<\Database\Factories\VoyageFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_depart' => 'datetime',
            'date_arrivee' => 'datetime',
            'objets_autorises' => 'array',
            'objets_interdits' => 'array',
        ];
    }
 
    public function voyageur(): BelongsTo
    {
        return $this->belongsTo(Voyageur::class);
    }
 
    public function adresseDepot(): BelongsTo
    {
        return $this->belongsTo(Adresse_depot::class);
    }
 
    public function adresseRecuperation(): BelongsTo
    {
        return $this->belongsTo(Adresse_recuperation::class);
    }
 
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
