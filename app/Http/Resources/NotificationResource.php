<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'titre' => $this->titre,
            'contenu' => $this->contenu,
            'type' => $this->type,
            'lu' => (bool) $this->lu,
            'date_envoi' => $this->date_envoi?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
