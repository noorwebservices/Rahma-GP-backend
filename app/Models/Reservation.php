<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Reservation extends Model
{
    /** @use HasFactory<\Database\Factories\ReservationFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_demande' => 'datetime',
            'date_acceptation' => 'datetime',
            'date_refus' => 'datetime',
        ];
    }
 
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
 
    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }
 
    public function colis(): HasOne
    {
        return $this->hasOne(Colis::class);
    }
 
    public function paiement(): HasOne
    {
        return $this->hasOne(Paiement::class);
    }
 
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
 
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
 
    public function revenuVoyageur(): HasOne
    {
        return $this->hasOne(Revenus_voyageur::class);
    }
}
