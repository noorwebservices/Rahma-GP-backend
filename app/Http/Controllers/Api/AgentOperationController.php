<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Colis;
use App\Models\Reservation;
use App\Models\Suivi_colis;
use App\Models\User;
use App\Services\ActiviteEntrepriseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentOperationController extends Controller
{
    /**
     * Acceptation d'une réservation par l'agent ou le gérant.
     */
    public function acceptReservation(string $id): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $entrepriseId = $user->getEntrepriseId();

        $reservation = Reservation::where('entreprise_id', $entrepriseId)->findOrFail($id);

        $reservation->update([
            'statut' => 'acceptee',
            'date_acceptation' => now(),
        ]);

        ActiviteEntrepriseService::log(
            $entrepriseId,
            $user->id,
            'reservation.acceptee',
            "Réservation #{$reservation->numero} acceptée."
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Réservation acceptée avec succès.',
            'reservation' => $reservation->fresh(['client.user', 'colis']),
        ]);
    }

    /**
     * Refus d'une réservation par l'agent ou le gérant.
     */
    public function refuseReservation(string $id): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $entrepriseId = $user->getEntrepriseId();

        $reservation = Reservation::where('entreprise_id', $entrepriseId)->findOrFail($id);

        $reservation->update([
            'statut' => 'refusee',
            'date_refus' => now(),
        ]);

        ActiviteEntrepriseService::log(
            $entrepriseId,
            $user->id,
            'reservation.refusee',
            "Réservation #{$reservation->numero} refusée."
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Réservation refusée.',
            'reservation' => $reservation->fresh(),
        ]);
    }

    /**
     * Mise à jour du statut d'un colis par l'Agent GP (prise en charge, transit, livraison).
     */
    public function updateColisStatut(Request $request, string $colisId): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $entrepriseId = $user->getEntrepriseId();

        $validated = $request->validate([
            'statut' => 'required|string|in:enregistre,receptionne,en_transit,arrive_destination,livre,annule',
            'localisation' => 'nullable|string',
            'commentaire' => 'nullable|string',
        ]);

        $colis = Colis::whereHas('reservation', function ($q) use ($entrepriseId) {
            $q->where('entreprise_id', $entrepriseId);
        })->findOrFail($colisId);

        DB::transaction(function () use ($colis, $validated, $user, $entrepriseId) {
            $updateData = ['statut' => $validated['statut']];

            if ($validated['statut'] === 'receptionne') {
                $updateData['date_depot'] = now();
            } elseif ($validated['statut'] === 'livre') {
                $updateData['date_livraison'] = now();
            }

            $colis->update($updateData);

            // Ajouter un événement dans le suivi du colis
            Suivi_colis::create([
                'colis_id' => $colis->id,
                'statut' => $validated['statut'],
                'localisation' => $validated['localisation'] ?? 'En transit / Agence Entreprise',
                'commentaire' => $validated['commentaire'] ?? "Statut mis à jour par l'agent {$user->prenom} {$user->nom}",
                'mis_a_jour_par' => $user->id,
                'date_suivi' => now(),
            ]);

            ActiviteEntrepriseService::log(
                $entrepriseId,
                $user->id,
                'colis.statut_mis_a_jour',
                "Statut du colis #{$colis->numero_suivi} mis à jour : {$validated['statut']}."
            );
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Statut du colis mis à jour avec succès.',
            'colis' => $colis->fresh(['suivis']),
        ]);
    }
}
