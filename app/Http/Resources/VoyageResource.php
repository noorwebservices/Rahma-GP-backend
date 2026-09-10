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
        ];
    }
}
