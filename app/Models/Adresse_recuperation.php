<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Adresse_recuperation extends Model
{
    /** @use HasFactory<\Database\Factories\AdresseRecuperationFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function voyages(): HasMany
    {
        return $this->hasMany(Voyage::class);
    }
}
