<?php

namespace App\Models;

use App\Support\Media;
use Database\Factories\ColisFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Colis extends Model
{
    /** @use HasFactory<ColisFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    /** Photo du colis en URL absolue servable. */
    protected function photo(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Media::url($value),
        );
    }

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
