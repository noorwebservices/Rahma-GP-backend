<?php

namespace App\Models;

use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory, HasUuids, MassPrunable;

    protected $guarded = [];

    /**
     * Déterminer les notifications à supprimer automatiquement (plus de 3 jours).
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(3));
    }

    protected function casts(): array
    {
        return [
            'lu' => 'boolean',
            'date_envoi' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
