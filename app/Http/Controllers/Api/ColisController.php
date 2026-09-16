<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreColisRequest;
use App\Http\Requests\UpdateColisRequest;
use App\Http\Requests\UpdateColisStatutRequest;
use App\Http\Resources\ColisResource;
use App\Models\Colis;
use App\Models\Suivi_colis;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ColisController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = Colis::with(['reservation.voyage.voyageur.user', 'reservation.client.user', 'suivis.auteur']);

        if ($user->hasRole('admin')) {
            // L'administrateur voit tout.
        } elseif ($user->voyageur && ! $user->voyageur->mode_client) {
            // Mode Voyageur : voir les colis des réservations sur ses propres voyages.
            $query->whereHas('reservation.voyage', function ($q) use ($user) {
                $q->where('voyageur_id', $user->voyageur->id);
            });
        } elseif ($user->client) {
            // Mode Client : voir ses colis.
            $query->whereHas('reservation', function ($q) use ($user) {
                $q->where('client_id', $user->client->id);
            });
        } else {
            $query->whereRaw('1 = 0');
        }

        $colisList = $query->latest()->paginate(15);

        return ColisResource::collection($colisList);
    }

    public function store(StoreColisRequest $request): JsonResponse
    {
        do {
            $numeroSuivi = 'TRK-'.strtoupper(Str::random(10));
        } while (Colis::where('numero_suivi', $numeroSuivi)->exists());

        $colis = DB::transaction(function () use ($request, $numeroSuivi) {
            $c = Colis::create(array_merge($request->validated(), [
                'numero_suivi' => $numeroSuivi,
                'statut' => 'demande_envoyee',
            ]));

            Suivi_colis::create([
                'colis_id' => $c->id,
                'statut' => 'demande_envoyee',
                'date_changement' => now(),
                'commentaire' => 'Colis créé et enregistré.',
                'mis_a_jour_par' => $request->user()->id,
            ]);

            return $c;
        });

        return response()->json([
            'message' => 'Colis enregistré avec succès.',
            'data' => new ColisResource($colis->load(['reservation', 'suivis'])),
        ], 201);
    }

    public function show(Request $request, Colis $colis): JsonResponse
    {
        $user = $request->user();

        if (! $this->userCanAccessColis($user, $colis)) {
            return response()->json([
                'message' => 'Accès non autorisé à ce colis.',
            ], 403);
        }

        return response()->json([
            'data' => new ColisResource($colis->load(['reservation.voyage.voyageur.user', 'reservation.client.user', 'suivis.auteur'])),
        ]);
    }

    public function update(UpdateColisRequest $request, Colis $colis): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('admin') && ($user->client?->id !== $colis->reservation->client_id)) {
            return response()->json([
                'message' => 'Accès non autorisé à la modification de ce colis.',
            ], 403);
        }

        if ($colis->reservation->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Impossible de modifier un colis d\'une réservation qui n\'est plus en attente.',
            ], 422);
        }

        $colis->update($request->validated());

        return response()->json([
            'message' => 'Informations du colis mises à jour avec succès.',
            'data' => new ColisResource($colis->load(['reservation', 'suivis'])),
        ]);
    }

    public function updateStatut(UpdateColisStatutRequest $request, Colis $colis): JsonResponse
    {
        $user = $request->user();
        $voyageurId = $colis->reservation->voyage->voyageur_id;

        if (! $user->hasRole('admin') && ($user->voyageur?->id !== $voyageurId)) {
            return response()->json([
                'message' => 'Seul le voyageur du voyage associé peut modifier le statut d\'expédition du colis.',
            ], 403);
        }

        // Auto-fermer les voyages dont la date de départ est passée
        \App\Models\Voyage::closePastVoyages();

        $voyage = $colis->reservation->voyage->fresh();
        $nouveauStatut = $request->statut;

        $statusLevels = [
            'demande_envoyee' => 1,
            'reservation_acceptee' => 2,
            'colis_depose' => 3,
            'colis_pris_en_charge' => 4,
            'en_transit' => 5,
            'arrive' => 6,
            'livre' => 7,
            'livree' => 7,
        ];

        $statutActuel = $colis->statut ?? 'demande_envoyee';
        $reservationStatut = $colis->reservation->statut ?? 'en_attente';

        // 1. Ne pas permettre de modifier le statut du colis tant que la réservation n'est pas acceptée
        if ($reservationStatut !== 'acceptee' && ! in_array($nouveauStatut, ['demande_envoyee', 'reservation_acceptee'], true)) {
            return response()->json([
                'message' => 'La réservation doit d\'abord être acceptée par le voyageur avant de pouvoir modifier le statut du colis.',
            ], 422);
        }

        // 2. Forcer le respect de l'ordre séquentiel des étapes
        $niveauActuel = $statusLevels[$statutActuel] ?? 1;
        $niveauNouveau = $statusLevels[$nouveauStatut] ?? 1;

        if ($niveauNouveau > $niveauActuel + 1) {
            return response()->json([
                'message' => 'Veuillez respecter l\'ordre d\'acheminement du colis. Vous ne pouvez pas sauter des étapes (ex: marquer comme livré sans passer par le transit/arrivée).',
            ], 422);
        }

        // Restriction : Impossible d'utiliser (en_transit, arrive, livre) tant que le voyage n'est pas complet/fermé ou date de départ passée
        if (in_array($nouveauStatut, ['en_transit', 'arrive', 'livre', 'livree'], true)) {
            $isClosedOrCompleted = $voyage && (
                in_array($voyage->statut, ['complet', 'ferme', 'cloture', 'termine'], true) ||
                ($voyage->date_depart && $voyage->date_depart <= now())
            );

            if (! $isClosedOrCompleted) {
                return response()->json([
                    'message' => 'Le statut du colis ne peut pas être passé en transit, arrivé ou livré tant que le voyage n\'est pas complet ou fermé (ou que sa date de départ n\'est pas passée).',
                ], 422);
            }
        }

        $commentaire = $request->commentaire;

        DB::transaction(function () use ($colis, $nouveauStatut, $commentaire, $user) {
            $updates = ['statut' => $nouveauStatut];

            if (in_array($nouveauStatut, ['colis_depose', 'colis_pris_en_charge']) && ! $colis->date_depot) {
                $updates['date_depot'] = now();
            }

            if ($nouveauStatut === 'livre' && ! $colis->date_livraison) {
                $updates['date_livraison'] = now();
            }

            $colis->update($updates);

            Suivi_colis::create([
                'colis_id' => $colis->id,
                'statut' => $nouveauStatut,
                'date_changement' => now(),
                'commentaire' => $commentaire ?? sprintf('Mise à jour du statut du colis : %s', $nouveauStatut),
                'mis_a_jour_par' => $user->id,
            ]);

            NotificationService::send(
                $colis->reservation->client->user_id,
                'Statut du colis mis à jour',
                sprintf('Le statut de votre colis (%s) a évolué vers : %s.', $colis->numero_suivi, $nouveauStatut),
                'colis'
            );
        });

        return response()->json([
            'message' => 'Statut du colis mis à jour avec succès.',
            'data' => new ColisResource($colis->fresh(['reservation', 'suivis.auteur'])),
        ]);
    }

    public function suiviPublic(string $numero_suivi): JsonResponse
    {
        $colis = Colis::where('numero_suivi', $numero_suivi)
            ->with(['suivis' => function ($q) {
                $q->orderBy('date_changement', 'asc');
            }, 'reservation.voyage'])
            ->first();

        if (! $colis) {
            return response()->json([
                'message' => 'Numéro de suivi introuvable.',
            ], 404);
        }

        return response()->json([
            'message' => 'Informations de suivi récupérées.',
            'data' => new ColisResource($colis),
        ]);
    }

    private function userCanAccessColis($user, Colis $colis): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->client && $colis->reservation->client_id === $user->client->id) {
            return true;
        }

        if ($user->voyageur && $colis->reservation->voyage->voyageur_id === $user->voyageur->id) {
            return true;
        }

        return false;
    }
}
