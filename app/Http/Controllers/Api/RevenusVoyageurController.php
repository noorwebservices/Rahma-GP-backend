<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DemandeRetraitRequest;
use App\Http\Resources\RevenusVoyageurResource;
use App\Models\Revenus_voyageur;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevenusVoyageurController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->voyageur && ! $user->hasRole('admin')) {
            return response()->json([
                'message' => 'Seul un voyageur peut accéder à la gestion des revenus.',
            ], 403);
        }

        $voyageurId = $user->voyageur?->id;

        if ($voyageurId) {
            // Auto-création de l'entrée Revenus_voyageur si un Paiement réussi existe mais n'a pas encore de fiche revenu
            $missingRevenuReservations = \App\Models\Reservation::whereHas('voyage', function ($q) use ($voyageurId) {
                $q->where('voyageur_id', $voyageurId);
            })
            ->whereHas('paiement', function ($q) {
                $q->where('statut', 'reussi');
            })
            ->whereDoesntHave('revenuVoyageur')
            ->with('paiement')
            ->get();

            foreach ($missingRevenuReservations as $resItem) {
                Revenus_voyageur::create([
                    'voyageur_id' => $voyageurId,
                    'reservation_id' => $resItem->id,
                    'montant' => $resItem->paiement->montant ?? $resItem->montant_total,
                    'statut' => 'disponible',
                ]);
            }
        }

        $totalRevenus = (float) Revenus_voyageur::where('voyageur_id', $voyageurId)->whereIn('statut', ['disponible', 'paye', 'retire', 'reussi'])->sum('montant');
        $soldeDisponible = (float) Revenus_voyageur::where('voyageur_id', $voyageurId)->whereIn('statut', ['disponible', 'paye', 'reussi'])->sum('montant');
        $soldeRetire = (float) Revenus_voyageur::where('voyageur_id', $voyageurId)->where('statut', 'retire')->sum('montant');
        $soldeEnAttente = (float) Revenus_voyageur::where('voyageur_id', $voyageurId)->where('statut', 'en_attente')->sum('montant');

        $revenus = Revenus_voyageur::where('voyageur_id', $voyageurId)
            ->with(['reservation.client.user', 'reservation.voyage', 'reservation.paiement'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'total_revenus' => round($totalRevenus, 2),
            'solde_disponible' => round($soldeDisponible, 2),
            'solde_retire' => round($soldeRetire, 2),
            'solde_en_attente' => round($soldeEnAttente, 2),
            'data' => RevenusVoyageurResource::collection($revenus)->response()->getData(true)['data'],
        ]);
    }

    public function retirer(DemandeRetraitRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->voyageur) {
            return response()->json([
                'message' => 'Seul un voyageur peut faire une demande de retrait de solde.',
            ], 403);
        }

        $voyageurId = $user->voyageur->id;

        $disponibles = Revenus_voyageur::where('voyageur_id', $voyageurId)
            ->where('statut', 'disponible')
            ->get();

        if ($disponibles->isEmpty()) {
            return response()->json([
                'message' => 'Vous n\'avez aucun solde disponible à retirer.',
            ], 422);
        }

        $montantRetire = (float) $disponibles->sum('montant');

        Revenus_voyageur::where('voyageur_id', $voyageurId)
            ->where('statut', 'disponible')
            ->update(['statut' => 'retire']);

        NotificationService::send(
            $user->id,
            'Demande de retrait enregistrée',
            sprintf('Votre demande de retrait d\'un montant de %.2f a été effectuée avec succès.', $montantRetire),
            'revenu'
        );

        return response()->json([
            'status' => 'success',
            'message' => sprintf('Demande de retrait de %.2f effectuée avec succès.', $montantRetire),
            'montant_retire' => round($montantRetire, 2),
        ]);
    }
}
