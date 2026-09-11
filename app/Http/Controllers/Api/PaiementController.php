<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaiementRequest;
use App\Http\Resources\PaiementResource;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\Revenus_voyageur;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaiementController extends Controller
{
    public function store(StorePaiementRequest $request, Reservation $reservation): JsonResponse
    {
        $user = $request->user();

        // Vérifier si un paiement existe déjà pour cette réservation
        if (Paiement::where('reservation_id', $reservation->id)->exists()) {
            $existingPaiement = Paiement::where('reservation_id', $reservation->id)->first();

            if ($existingPaiement->statut === 'reussi') {
                return response()->json([
                    'message' => 'Un paiement réussi existe déjà pour cette réservation.',
                ], 422);
            }
        }

        $montant = (float) ($request->montant ?? $reservation->montant_total);
        $statut = $request->statut ?? 'reussi';
        $modePaiement = $request->mode_paiement;

        $paiement = DB::transaction(function () use ($request, $reservation, $montant, $statut, $modePaiement, $user) {
            do {
                $reference = $request->reference ?? 'PAY-'.strtoupper(Str::random(10));
            } while (Paiement::where('reference', $reference)->exists());

            $p = Paiement::updateOrCreate(
                ['reservation_id' => $reservation->id],
                [
                    'montant' => $montant,
                    'reference' => $reference,
                    'mode_paiement' => $modePaiement,
                    'statut' => $statut,
                    'date_paiement' => $statut === 'reussi' ? now() : null,
                    'confirme_par' => $user->id,
                ]
            );

            if ($statut === 'reussi') {
                $voyageur = $reservation->voyage->voyageur;

                // Crédit automatique des revenus du voyageur
                Revenus_voyageur::updateOrCreate(
                    ['reservation_id' => $reservation->id],
                    [
                        'voyageur_id' => $voyageur->id,
                        'montant' => $montant,
                        'statut' => 'disponible',
                    ]
                );

                // Notifications au Voyageur et au Client
                NotificationService::send(
                    $voyageur->user_id,
                    'Paiement confirmé',
                    sprintf('Un paiement de %.2f %s pour la réservation %s a été validé.', $montant, $reservation->voyage->devise ?? 'EUR', $reservation->numero),
                    'paiement'
                );

                NotificationService::send(
                    $reservation->client->user_id,
                    'Paiement reçu',
                    sprintf('Votre paiement de %.2f %s pour la réservation %s a été confirmé avec succès.', $montant, $reservation->voyage->devise ?? 'EUR', $reservation->numero),
                    'paiement'
                );
            }

            return $p;
        });

        return response()->json([
            'message' => 'Paiement enregistré avec succès.',
            'data' => new PaiementResource($paiement->load(['reservation', 'confirmePar'])),
        ], 201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = Paiement::with(['reservation.voyage.voyageur.user', 'reservation.client.user', 'confirmePar']);

        if ($user->hasRole('admin')) {
            // L'administrateur voit tout.
        } elseif ($user->voyageur && ! $user->voyageur->mode_client) {
            $query->whereHas('reservation.voyage', function ($q) use ($user) {
                $q->where('voyageur_id', $user->voyageur->id);
            });
        } elseif ($user->client) {
            $query->whereHas('reservation', function ($q) use ($user) {
                $q->where('client_id', $user->client->id);
            });
        } else {
            $query->whereRaw('1 = 0');
        }

        $paiements = $query->latest()->paginate(15);

        return PaiementResource::collection($paiements);
    }

    public function show(Request $request, Paiement $paiement): JsonResponse
    {
        $user = $request->user();

        if (! $this->userCanAccessPaiement($user, $paiement)) {
            return response()->json([
                'message' => 'Accès non autorisé à ce paiement.',
            ], 403);
        }

        return response()->json([
            'data' => new PaiementResource($paiement->load(['reservation.voyage.voyageur.user', 'reservation.client.user', 'confirmePar'])),
        ]);
    }

    private function userCanAccessPaiement($user, Paiement $paiement): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->client && $paiement->reservation->client_id === $user->client->id) {
            return true;
        }

        if ($user->voyageur && $paiement->reservation->voyage->voyageur_id === $user->voyageur->id) {
            return true;
        }

        return false;
    }
}
