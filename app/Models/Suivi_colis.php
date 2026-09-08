<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Suivi_colis extends Model
{
    /** @use HasFactory<\Database\Factories\SuiviColisFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_changement' => 'datetime',
        ];
    }
 
    public function colis(): BelongsTo
    {
        return $this->belongsTo(Colis::class);
    }
 
    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mis_a_jour_par');
    }
}
