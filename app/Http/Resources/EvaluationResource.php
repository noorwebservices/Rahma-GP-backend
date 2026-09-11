<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservation_id,
            'evaluateur_id' => $this->evaluateur_id,
            'evalue_id' => $this->evalue_id,
            'note' => (int) $this->note,
            'commentaire' => $this->commentaire,
            'created_at' => $this->created_at?->toIso8601String(),
            'evaluateur' => $this->whenLoaded('evaluateur', function () {
                return [
                    'id' => $this->evaluateur->id,
                    'nom' => $this->evaluateur->nom,
                    'prenom' => $this->evaluateur->prenom,
                    'photo_profil' => $this->evaluateur->photo_profil ?? null,
                ];
            }),
            'evalue' => $this->whenLoaded('evalue', function () {
                return [
                    'id' => $this->evalue->id,
                    'nom' => $this->evalue->nom,
                    'prenom' => $this->evalue->prenom,
                    'photo_profil' => $this->evalue->photo_profil ?? null,
                ];
            }),
        ];
    }
}
