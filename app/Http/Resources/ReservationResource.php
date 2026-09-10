<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'voyage_id' => $this->voyage_id,
            'client_id' => $this->client_id,
            'montant_total' => (float) $this->montant_total,
            'mode_paiement_souhaite' => $this->mode_paiement_souhaite,
            'statut' => $this->statut,
            'date_demande' => $this->date_demande?->toIso8601String(),
            'date_acceptation' => $this->date_acceptation?->toIso8601String(),
            'date_refus' => $this->date_refus?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'voyage' => new VoyageResource($this->whenLoaded('voyage')),
            'colis' => new ColisResource($this->whenLoaded('colis')),
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'user_id' => $this->client->user_id,
                    'user' => $this->client->relationLoaded('user') && $this->client->user ? [
                        'id' => $this->client->user->id,
                        'nom' => $this->client->user->nom,
                        'prenom' => $this->client->user->prenom,
                        'telephone' => $this->client->user->telephone,
                        'email' => $this->client->user->email,
                    ] : null,
                ];
            }),
        ];
    }
}
