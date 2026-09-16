<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signalement extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'signalements';

    protected $guarded = [];

    /**
     * L'utilisateur qui a émis le signalement.
     */
    public function signaleur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signaleur_id');
    }

    /**
     * L'utilisateur qui fait l'objet du signalement.
     */
    public function signale(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signale_id');
    }

    /**
     * L'administrateur qui a traité le signalement.
     */
    public function agentAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }
}
