<?php

namespace App\Http\Resources;

use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation publique d'un voyage (visible sans authentification).
 * N'expose aucune donnée privée du voyageur (téléphone, email, pièce d'identité,
 * adresse complète de dépôt/récupération) : seulement de quoi présenter l'annonce.
 */
class PublicVoyageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userVoyageur = $this->voyageur?->user;

        return [
            'id' => $this->id,
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
            'ville_depot' => $this->adresseDepot?->ville,
            'ville_recuperation' => $this->adresseRecuperation?->ville,
            'moyenne_notes' => round((float) (Evaluation::where('evalue_id', $this->voyageur?->user_id)->avg('note') ?? 0), 2),
            'total_evaluations' => Evaluation::where('evalue_id', $this->voyageur?->user_id)->count(),
            'voyageur' => $userVoyageur ? [
                'prenom' => $userVoyageur->prenom,
                // Nom réduit à l'initiale pour préserver la vie privée avant réservation.
                'nom_initial' => $userVoyageur->nom ? mb_substr($userVoyageur->nom, 0, 1).'.' : null,
                'avatar' => $userVoyageur->avatar,
            ] : null,
        ];
    }
}
