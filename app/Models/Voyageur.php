<?php

namespace App\Models;

use App\Support\Media;
use Database\Factories\VoyageurFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
            'email_verifie_at' => 'datetime',
        ];
    }

    /** Photo recto de la pièce d'identité en URL absolue. */
    protected function cniRecto(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Media::url($value),
        );
    }

    /** Photo verso de la pièce d'identité en URL absolue. */
    protected function cniVerso(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Media::url($value),
        );
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
