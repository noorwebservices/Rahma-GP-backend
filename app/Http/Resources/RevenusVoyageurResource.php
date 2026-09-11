<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RevenusVoyageurResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'voyageur_id' => $this->voyageur_id,
            'reservation_id' => $this->reservation_id,
            'montant' => (float) $this->montant,
            'statut' => $this->statut,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'reservation' => new ReservationResource($this->whenLoaded('reservation')),
        ];
    }
}
