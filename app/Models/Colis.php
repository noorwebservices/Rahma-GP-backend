<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Colis extends Model
{
    /** @use HasFactory<\Database\Factories\ColisFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    
    protected function casts(): array
    {
        return [
            'est_fragile' => 'boolean',
            'date_depot' => 'datetime',
            'date_livraison' => 'datetime',
        ];
    }
 
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
 
    public function suivis(): HasMany
    {
        return $this->hasMany(Suivi_colis::class);
    }
}
