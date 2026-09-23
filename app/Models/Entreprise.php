<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entreprise extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'autres_documents_legaux' => 'array',
            'coordonnees_paiement' => 'array',
            'email_verifie_at' => 'datetime',
        ];
    }

    public function isEmailVerifie(): bool
    {
        return ! empty($this->email_verifie_at);
    }

    /** Logo en URL absolue servable */
    protected function logo(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Media::url($value),
        );
    }

    /** Document NINEA en URL absolue */
    protected function nineaDoc(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Media::url($value),
        );
    }

    /** Document Registre du commerce en URL absolue */
    protected function registreCommerceDoc(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Media::url($value),
        );
    }

    public function gerant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gerant_user_id');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(AgentGp::class);
    }

    public function voyages(): HasMany
    {
        return $this->hasMany(Voyage::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(InvitationAgent::class);
    }

    public function activites(): HasMany
    {
        return $this->hasMany(ActiviteEntreprise::class);
    }
}
