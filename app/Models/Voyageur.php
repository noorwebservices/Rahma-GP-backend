<?php

namespace App\Models;

use Database\Factories\VoyageurFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voyageur extends Model
{
    /** @use HasFactory<VoyageurFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'mode_client' => 'boolean',
        ];
    }

    // un voyageur appartient à un utilisateur
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // un voyageur peut avoir plusieurs voyages
    public function voyages(): HasMany
    {
        return $this->hasMany(Voyage::class);
    }

    // un voyageur peut avoir plusieurs revenus
    public function revenus(): HasMany
    {
        return $this->hasMany(Revenus_voyageur::class);
    }

    public function adresseDepots(): HasMany
    {
        return $this->hasMany(Adresse_depot::class);
    }

    public function adresseRecuperations(): HasMany
    {
        return $this->hasMany(Adresse_recuperation::class);
    }
}
