<?php

namespace App\Services;

use App\Models\ActiviteEntreprise;

class ActiviteEntrepriseService
{
    /**
     * Enregistre un événement dans l'historique d'activités d'une entreprise.
     */
    public static function log(string $entrepriseId, ?string $userId, string $action, string $description, ?array $metadonnees = null): ActiviteEntreprise
    {
        return ActiviteEntreprise::create([
            'entreprise_id' => $entrepriseId,
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'metadonnees' => $metadonnees,
        ]);
    }
}
