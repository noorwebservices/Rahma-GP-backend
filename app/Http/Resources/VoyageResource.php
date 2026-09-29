<?php

namespace App\Http\Resources;

use App\Models\Evaluation;
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
        $entId = $this->entreprise_id;
        $agId = $this->agent_gp_id;
        $evalueUserId = $this->voyageur?->user_id ?? $this->agentGp?->user_id ?? $this->entreprise?->gerant_user_id;

        $evalQuery = Evaluation::query();
        if ($evalueUserId || $entId || $agId) {
            $evalQuery->where(function ($q) use ($evalueUserId, $entId, $agId) {
                if ($evalueUserId) {
                    $q->where('evalue_id', $evalueUserId);
                }
                if ($entId) {
                    $q->orWhereHas('reservation', function ($rq) use ($entId) {
                        $rq->where('entreprise_id', $entId)
                           ->orWhereHas('voyage', function ($vq) use ($entId) {
                               $vq->where('entreprise_id', $entId);
                           });
                    });
                }
                if ($agId) {
                    $q->orWhereHas('reservation', function ($rq) use ($agId) {
                        $rq->where('agent_gp_id', $agId)
                           ->orWhereHas('voyage', function ($vq) use ($agId) {
                               $vq->where('agent_gp_id', $agId);
                           });
                    });
                }
            });
        } else {
            $evalQuery->whereRaw('1 = 0');
        }

        $moyenneNotes = round((float) ($evalQuery->avg('note') ?? 0), 2);
        $totalEvaluations = $evalQuery->count();

        $entObj = $this->entreprise 
            ?? $this->agentGp?->entreprise 
            ?? $this->voyageur?->user?->entrepriseGeree 
            ?? $this->voyageur?->user?->agentGp?->entreprise;

        return [
            'id' => $this->id,
            'entreprise_id' => $this->entreprise_id ?? $entObj?->id,
            'agent_gp_id' => $this->agent_gp_id,
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
            'tarifs_speciaux' => $this->tarifs_speciaux ?? [],
            'statut' => $this->statut,
            'type_transporteur' => $this->entreprise_id || $this->agent_gp_id || $entObj ? 'Entreprise GP' : 'Voyageur GP',
            'moyenne_notes' => $moyenneNotes,
            'total_evaluations' => $totalEvaluations,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'voyageur' => new VoyageurResource($this->whenLoaded('voyageur')),
            'agent_gp' => $this->whenLoaded('agentGp', function () {
                return [
                    'id' => $this->agentGp->id,
                    'entreprise_id' => $this->agentGp->entreprise_id,
                    'matricule' => $this->agentGp->matricule,
                    'statut' => $this->agentGp->statut,
                    'user' => $this->agentGp->relationLoaded('user') && $this->agentGp->user ? [
                        'id' => $this->agentGp->user->id,
                        'nom' => $this->agentGp->user->nom,
                        'prenom' => $this->agentGp->user->prenom,
                        'telephone' => $this->agentGp->user->telephone,
                        'email' => $this->agentGp->user->email,
                    ] : null,
                    'entreprise' => $this->agentGp->relationLoaded('entreprise') && $this->agentGp->entreprise ? [
                        'id' => $this->agentGp->entreprise->id,
                        'nom' => $this->agentGp->entreprise->nom ?? $this->agentGp->entreprise->nom_entreprise,
                        'nom_entreprise' => $this->agentGp->entreprise->nom_entreprise ?? $this->agentGp->entreprise->nom,
                        'logo' => $this->agentGp->entreprise->logo,
                    ] : null,
                ];
            }),
            'entreprise' => $entObj ? [
                'id' => $entObj->id,
                'nom' => $entObj->nom ?? $entObj->nom_entreprise,
                'nom_entreprise' => $entObj->nom_entreprise ?? $entObj->nom,
                'logo' => $entObj->logo,
                'telephone' => $entObj->telephone,
                'email' => $entObj->email,
                'moyenne_notes' => $entObj->moyenne_notes ?? 0,
            ] : null,
            'adresse_depot' => new AdresseDepotResource($this->whenLoaded('adresseDepot')),
            'adresse_recuperation' => new AdresseRecuperationResource($this->whenLoaded('adresseRecuperation')),
            'reservations' => $this->whenLoaded('reservations', function () {
                return $this->reservations->map(function ($r) {
                    $colis = $r->relationLoaded('colis') ? $r->colis : null;

                    return [
                        'id' => $r->id,
                        'numero' => $r->numero,
                        'voyage_id' => $r->voyage_id,
                        'client_id' => $r->client_id,
                        'montant_total' => $r->montant_total !== null ? (float) $r->montant_total : null,
                        'mode_paiement_souhaite' => $r->mode_paiement_souhaite,
                        'statut' => $r->statut,
                        'type_colis' => $colis?->type,
                        'description' => $colis?->description,
                        'poids' => $colis?->poids !== null ? (float) $colis->poids : null,
                        'prix_total' => $r->montant_total !== null ? (float) $r->montant_total : null,
                        'mode_paiement' => $r->mode_paiement_souhaite,
                        'code_tracking' => $colis?->numero_suivi,
                        'expediteur_nom' => $r->relationLoaded('client') && $r->client?->relationLoaded('user') ? $r->client->user?->nom : null,
                        'expediteur_telephone' => $r->relationLoaded('client') && $r->client?->relationLoaded('user') ? $r->client->user?->telephone : null,
                        'created_at' => $r->created_at?->toIso8601String(),
                        'colis' => $colis ? new ColisResource($colis) : null,
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
