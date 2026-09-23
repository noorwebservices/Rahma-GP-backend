<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentGp extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'agent_gps';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_adhesion' => 'datetime',
            'date_activation' => 'datetime',
            'date_desactivation' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function voyages(): HasMany
    {
        return $this->hasMany(Voyage::class, 'agent_gp_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'agent_gp_id');
    }
}
