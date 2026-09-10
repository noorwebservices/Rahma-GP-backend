<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoyageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'voyageur_id' => $this->voyageur_id,
            'adresse_depot_id' => $this->adresse_depot_id,
            'adresse_recuperation_id' => $this->adresse_recuperation_id,
            'pays_depart' => $this->pays_depart,
            'ville_depart' => $this->ville_depart,
            'pays_destination' => $this->pays_destination,
            'ville_destination' => $this->ville_destination,
            'date_depart' => $this->date_depart?->toIso8601String(),
            'date_arrivee' => $this->date_arrivee?->toIso8601String(),
            'capacite_totale' => (float) $this->capacite_totale,
            'capacite_dispo' => (float) $this->capacite_dispo,
            'prix_kg' => $this->prix_kg !== null ? (float) $this->prix_kg : null,
            'prix_objet' => $this->prix_objet !== null ? (float) $this->prix_objet : null,
            'devise' => $this->devise,
            'description' => $this->description,
            'objets_autorises' => $this->objets_autorises ?? [],
            'objets_interdits' => $this->objets_interdits ?? [],
            'statut' => $this->statut,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'voyageur' => new VoyageurResource($this->whenLoaded('voyageur')),
            'adresse_depot' => new AdresseDepotResource($this->whenLoaded('adresseDepot')),
            'adresse_recuperation' => new AdresseRecuperationResource($this->whenLoaded('adresseRecuperation')),
            'reservations' => $this->whenLoaded('reservations', function () {
                return $this->reservations->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'voyage_id' => $r->voyage_id,
                        'client_id' => $r->client_id,
                        'type_colis' => $r->type_colis ?? $r->description,
                        'description' => $r->description,
                        'poids' => $r->poids !== null ? (float) $r->poids : null,
                        'prix_total' => $r->prix_total !== null ? (float) $r->prix_total : null,
                        'statut' => $r->statut,
                        'mode_paiement' => $r->mode_paiement,
                        'code_tracking' => $r->code_tracking,
                        'expediteur_nom' => $r->expediteur_nom,
                        'expediteur_telephone' => $r->expediteur_telephone,
                        'created_at' => $r->created_at?->toIso8601String(),
                        'client' => $r->relationLoaded('client') && $r->client ? [
                            'id' => $r->client->id,
                            'user' => $r->client->relationLoaded('user') && $r->client->user ? [
                                'id' => $r->client->user->id,
                                'nom' => $r->client->user->nom,
                                'prenom' => $r->client->user->prenom,
                                'telephone' => $r->client->user->telephone,
                                'email' => $r->client->user->email,
                            ] : null,
                        ] : null,
                    ];
                });
            }),
        ];
    }
}
