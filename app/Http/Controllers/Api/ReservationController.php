<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Colis;
use App\Models\Reservation;
use App\Models\Suivi_colis;
use App\Models\Voyage;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = Reservation::with(['voyage.voyageur.user', 'client.user', 'colis']);

        if ($user->hasRole('admin')) {
            // L'administrateur voit tout.
        } elseif ($user->voyageur && ! $user->voyageur->mode_client) {
            // Mode Voyageur : voir les réservations associées à ses propres voyages.
            $query->whereHas('voyage', function ($q) use ($user) {
                $q->where('voyageur_id', $user->voyageur->id);
            });
        } elseif ($user->client) {
            // Mode Client : voir toutes ses réservations (y compris annulées).
            $query->where('client_id', $user->client->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        $reservations = $query->latest()->paginate(15);

        return ReservationResource::collection($reservations);
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->client) {
            return response()->json([
                'message' => 'Vous devez disposer d\'un profil client pour effectuer une réservation.',
            ], 403);
        }

        $voyage = Voyage::findOrFail($request->voyage_id);

        if ($voyage->statut !== 'publie') {
            return response()->json([
                'message' => 'Ce voyage n\'est pas disponible pour des réservations.',
            ], 422);
        }

        $colisPoids = (float) $request->input('colis.poids');

        if ($colisPoids > (float) $voyage->capacite_dispo) {
            return response()->json([
                'message' => sprintf(
                    'La capacité disponible du voyage est insuffisante pour ce colis (Disponible : %.2f kg).',
                    $voyage->capacite_dispo
                ),
            ], 422);
        }

        $prixKg = (float) ($voyage->prix_kg ?? 0);
        $prixObjet = (float) ($voyage->prix_objet ?? 0);
        $montantTotal = ($colisPoids * $prixKg) + $prixObjet;

        $reservation = DB::transaction(function () use ($request, $user, $voyage, $colisPoids, $montantTotal) {
            do {
                $numero = 'RES-'.strtoupper(Str::random(8));
            } while (Reservation::where('numero', $numero)->exists());

            $res = Reservation::create([
                'numero' => $numero,
                'voyage_id' => $voyage->id,
                'client_id' => $user->client->id,
                'montant_total' => $montantTotal,
                'mode_paiement_souhaite' => $request->mode_paiement_souhaite,
                'statut' => 'en_attente',
                'date_demande' => now(),
            ]);

            do {
                $numeroSuivi = 'TRK-'.strtoupper(Str::random(10));
            } while (Colis::where('numero_suivi', $numeroSuivi)->exists());

            $colisData = $request->input('colis');
            $colis = Colis::create([
                'reservation_id' => $res->id,
                'numero_suivi' => $numeroSuivi,
                'type' => $colisData['type'],
                'description' => $colisData['description'] ?? null,
                'valeur_estimee' => $colisData['valeur_estimee'] ?? null,
                'poids' => $colisPoids,
                'est_fragile' => $colisData['est_fragile'] ?? false,
                'destinataire_nom' => $colisData['destinataire_nom'],
                'destinataire_prenom' => $colisData['destinataire_prenom'],
                'destinataire_numero' => $colisData['destinataire_numero'],
                'destinataire_adresse' => $colisData['destinataire_adresse'],
                'photo' => $colisData['photo'] ?? null,
                'statut' => 'demande_envoyee',
            ]);

            Suivi_colis::create([
                'colis_id' => $colis->id,
                'statut' => 'demande_envoyee',
                'date_changement' => now(),
                'commentaire' => 'Demande de réservation et colis enregistrés.',
                'mis_a_jour_par' => $user->id,
            ]);

            // Notifier le Voyageur
            NotificationService::send(
                $voyage->voyageur->user_id,
                'Nouvelle demande de réservation',
                sprintf('Vous avez reçu une nouvelle demande de réservation (%s) pour votre voyage %s -> %s.', $res->numero, $voyage->ville_depart, $voyage->ville_destination),
                'reservation'
            );

            return $res;
        });

        return response()->json([
            'message' => 'Réservation effectuée avec succès.',
            'data' => new ReservationResource($reservation->load(['voyage', 'client.user', 'colis'])),
        ], 201);
    }

    public function show(Request $request, Reservation $reservation): JsonResponse
    {
        $user = $request->user();

        if (! $this->userCanAccessReservation($user, $reservation)) {
            return response()->json([
                'message' => 'Accès non autorisé à cette réservation.',
            ], 403);
        }

        return response()->json([
            'data' => new ReservationResource($reservation->load(['voyage.voyageur.user', 'client.user', 'colis.suivis'])),
        ]);
    }

    public function update(UpdateReservationRequest $request, Reservation $reservation): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('admin') && ($user->client?->id !== $reservation->client_id)) {
            return response()->json([
                'message' => 'Accès non autorisé à la modification de cette réservation.',
            ], 403);
        }

        if ($reservation->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Impossible de modifier une réservation qui n\'est plus en attente.',
            ], 422);
        }

        $reservation->update($request->validated());

        return response()->json([
            'message' => 'Réservation mise à jour avec succès.',
            'data' => new ReservationResource($reservation->load(['voyage', 'client.user', 'colis'])),
        ]);
    }

    public function accepter(Request $request, Reservation $reservation): JsonResponse
    {
        $user = $request->user();
        $voyage = $reservation->voyage;

        if (! $user->hasRole('admin') && ($user->voyageur?->id !== $voyage->voyageur_id)) {
            return response()->json([
                'message' => 'Seul le voyageur effectuant le voyage peut accepter cette réservation.',
            ], 403);
        }

        if ($reservation->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Seule une réservation en attente peut être acceptée.',
            ], 422);
        }

        $poidsColis = (float) ($reservation->colis?->poids ?? 0);

        if ($poidsColis > (float) $voyage->capacite_dispo) {
            return response()->json([
                'message' => 'La capacité disponible du voyage est insuffisante pour accepter cette réservation.',
            ], 422);
        }

        DB::transaction(function () use ($reservation, $voyage, $poidsColis, $user) {
            $reservation->update([
                'statut' => 'acceptee',
                'date_acceptation' => now(),
            ]);

            $nouvelleCapacite = max(0, (float) $voyage->capacite_dispo - $poidsColis);
            $nouveauStatut = $nouvelleCapacite == 0 ? 'complet' : $voyage->statut;

            $voyage->update([
                'capacite_dispo' => $nouvelleCapacite,
                'statut' => $nouveauStatut,
            ]);

            if ($reservation->colis) {
                $reservation->colis->update([
                    'statut' => 'reservation_acceptee',
                ]);

                Suivi_colis::create([
                    'colis_id' => $reservation->colis->id,
                    'statut' => 'reservation_acceptee',
                    'date_changement' => now(),
                    'commentaire' => 'Réservation acceptée par le voyageur.',
                    'mis_a_jour_par' => $user->id,
                ]);
            }

            NotificationService::send(
                $reservation->client->user_id,
                'Réservation acceptée',
                sprintf('Votre réservation %s a été acceptée par le voyageur.', $reservation->numero),
                'reservation'
            );
        });

        return response()->json([
            'message' => 'Réservation acceptée avec succès.',
            'data' => new ReservationResource($reservation->fresh(['voyage', 'client.user', 'colis.suivis'])),
        ]);
    }

    public function refuser(Request $request, Reservation $reservation): JsonResponse
    {
        $user = $request->user();
        $voyage = $reservation->voyage;

        if (! $user->hasRole('admin') && ($user->voyageur?->id !== $voyage->voyageur_id)) {
            return response()->json([
                'message' => 'Seul le voyageur effectuant le voyage peut refuser cette réservation.',
            ], 403);
        }

        if ($reservation->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Seule une réservation en attente peut être refusée.',
            ], 422);
        }

        $reservation->update([
            'statut' => 'refusee',
            'date_refus' => now(),
        ]);

        NotificationService::send(
            $reservation->client->user_id,
            'Réservation refusée',
            sprintf('Votre réservation %s a été refusée par le voyageur.', $reservation->numero),
            'reservation'
        );

        return response()->json([
            'message' => 'Réservation refusée.',
            'data' => new ReservationResource($reservation->fresh(['voyage', 'client.user', 'colis'])),
        ]);
    }

    public function annuler(Request $request, Reservation $reservation): JsonResponse
    {
        $user = $request->user();

        if (! $this->userCanAccessReservation($user, $reservation)) {
            return response()->json([
                'message' => 'Accès non autorisé à l\'annulation de cette réservation.',
            ], 403);
        }

        if (in_array($reservation->statut, ['annulee', 'refusee'])) {
            return response()->json([
                'message' => 'Cette réservation est déjà annulée ou refusée.',
            ], 422);
        }

        DB::transaction(function () use ($reservation, $user) {
            if ($reservation->statut === 'acceptee' && $reservation->colis) {
                $voyage = $reservation->voyage;
                $poidsColis = (float) $reservation->colis->poids;
                $nouvelleCapacite = (float) $voyage->capacite_dispo + $poidsColis;

                $nouveauStatut = $voyage->statut === 'complet' && $nouvelleCapacite > 0 ? 'publie' : $voyage->statut;

                $voyage->update([
                    'capacite_dispo' => $nouvelleCapacite,
                    'statut' => $nouveauStatut,
                ]);
            }

            $reservation->update(['statut' => 'annulee']);

            $targetUserId = ($user->id === $reservation->client->user_id)
                ? $reservation->voyage->voyageur->user_id
                : $reservation->client->user_id;

            NotificationService::send(
                $targetUserId,
                'Réservation annulée',
                sprintf('La réservation %s a été annulée.', $reservation->numero),
                'reservation'
            );
        });

        return response()->json([
            'message' => 'Réservation annulée avec succès.',
            'data' => new ReservationResource($reservation->fresh(['voyage', 'client.user', 'colis'])),
        ]);
    }

    public function destroy(Request $request, Reservation $reservation): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('admin') && ($user->client?->id !== $reservation->client_id)) {
            return response()->json([
                'message' => 'Accès non autorisé à la suppression de cette réservation.',
            ], 403);
        }

        if ($reservation->statut === 'acceptee') {
            return response()->json([
                'message' => 'Impossible de supprimer une réservation déjà acceptée. Veuillez d\'abord l\'annuler.',
            ], 422);
        }

        $reservation->delete();

        return response()->json([
            'message' => 'Réservation supprimée avec succès.',
        ]);
    }

    private function userCanAccessReservation($user, Reservation $reservation): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->client && $reservation->client_id === $user->client->id) {
            return true;
        }

        if ($user->voyageur && $reservation->voyage->voyageur_id === $user->voyageur->id) {
            return true;
        }

        return false;
    }
}
