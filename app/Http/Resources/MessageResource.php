<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservation_id,
            'expediteur_id' => $this->expediteur_id,
            'destinataire_id' => $this->destinataire_id,
            'contenu' => $this->contenu,
            'piece_jointe' => $this->piece_jointe,
            'est_lu' => (bool) $this->est_lu,
            'date_heure_envoi' => $this->date_heure_envoi?->toIso8601String(),
            'date_heure_lecture' => $this->date_heure_lecture?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'expediteur' => $this->whenLoaded('expediteur', function () {
                return [
                    'id' => $this->expediteur->id,
                    'nom' => $this->expediteur->nom,
                    'prenom' => $this->expediteur->prenom,
                    'avatar' => $this->expediteur->avatar,
                    'photo_profil' => $this->expediteur->avatar,
                ];
            }),
            'destinataire' => $this->whenLoaded('destinataire', function () {
                return [
                    'id' => $this->destinataire->id,
                    'nom' => $this->destinataire->nom,
                    'prenom' => $this->destinataire->prenom,
                    'avatar' => $this->destinataire->avatar,
                    'photo_profil' => $this->destinataire->avatar,
                ];
            }),
        ];
    }
}
