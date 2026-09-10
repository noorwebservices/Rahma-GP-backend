<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ColisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservation_id,
            'numero_suivi' => $this->numero_suivi,
            'type' => $this->type,
            'photo' => $this->photo,
            'description' => $this->description,
            'valeur_estimee' => $this->valeur_estimee !== null ? (float) $this->valeur_estimee : null,
            'poids' => (float) $this->poids,
            'est_fragile' => (bool) $this->est_fragile,
            'destinataire_nom' => $this->destinataire_nom,
            'destinataire_prenom' => $this->destinataire_prenom,
            'destinataire_numero' => $this->destinataire_numero,
            'destinataire_adresse' => $this->destinataire_adresse,
            'statut' => $this->statut,
            'date_depot' => $this->date_depot?->toIso8601String(),
            'date_livraison' => $this->date_livraison?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'suivis' => $this->whenLoaded('suivis', function () {
                return $this->suivis->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'statut' => $s->statut,
                        'date_changement' => $s->date_changement?->toIso8601String(),
                        'commentaire' => $s->commentaire,
                        'mis_a_jour_par' => $s->mis_a_jour_par,
                        'auteur' => $s->relationLoaded('auteur') && $s->auteur ? [
                            'id' => $s->auteur->id,
                            'nom' => $s->auteur->nom,
                            'prenom' => $s->auteur->prenom,
                        ] : null,
                    ];
                });
            }),
        ];
    }
}
