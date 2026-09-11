<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoyageurResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $evaluationsQuery = \App\Models\Evaluation::where('evalue_id', $this->user_id);
        $moyenne = round((float) ($evaluationsQuery->avg('note') ?? 0), 2);
        $total = $evaluationsQuery->count();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type_piece' => $this->type_piece,
            'numero_piece' => $this->numero_piece,
            'mode_client' => (bool) $this->mode_client,
            'statut' => $this->statut,
            'nom' => $this->user?->nom,
            'prenom' => $this->user?->prenom,
            'avatar' => $this->user?->avatar,
            'telephone' => $this->user?->telephone,
            'moyenne_notes' => $moyenne,
            'total_evaluations' => $total,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
