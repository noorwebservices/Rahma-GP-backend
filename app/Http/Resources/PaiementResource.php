<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaiementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservation_id,
            'montant' => (float) $this->montant,
            'reference' => $this->reference,
            'mode_paiement' => $this->mode_paiement,
            'statut' => $this->statut,
            'date_paiement' => $this->date_paiement?->toIso8601String(),
            'confirme_par' => $this->confirme_par,
            'created_at' => $this->created_at?->toIso8601String(),
            'reservation' => new ReservationResource($this->whenLoaded('reservation')),
            'confirme_par_user' => $this->whenLoaded('confirmePar', function () {
                return [
                    'id' => $this->confirmePar->id,
                    'nom' => $this->confirmePar->nom,
                    'prenom' => $this->confirmePar->prenom,
                ];
            }),
        ];
    }
}
