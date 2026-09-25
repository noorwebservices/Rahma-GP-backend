<?php

namespace App\Models;

use Database\Factories\VoyageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voyage extends Model
{
    /** @use HasFactory<VoyageFactory> */
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

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function agentGp(): BelongsTo
    {
        return $this->belongsTo(AgentGp::class);
    }

    public function adresseDepot(): BelongsTo
    {
        return $this->belongsTo(Adresse_depot::class);
    }

    public function adresseRecuperation(): BelongsTo
    {
        return $this->belongsTo(Adresse_recuperation::class);
    }

    public static function closePastVoyages(): void
    {
        static::where('date_depart', '<', now())
            ->whereNotIn('statut', ['complet', 'en_cours', 'termine', 'annule'])
            ->update(['statut' => 'termine']);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
