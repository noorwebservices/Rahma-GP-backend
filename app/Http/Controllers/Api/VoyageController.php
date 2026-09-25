<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVoyageRequest;
use App\Http\Requests\UpdateVoyageRequest;
use App\Http\Resources\VoyageResource;
use App\Models\Adresse_depot;
use App\Models\Adresse_recuperation;
use App\Models\User;
use App\Models\Voyage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoyageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        Voyage::closePastVoyages();

        /** @var User $user */
        $user = auth('api')->user();
        $voyageur = $user?->voyageur()->first();
        $client = $user?->client()->first();
        $entrepriseId = $user?->getEntrepriseId();
        $isAdmin = $user?->hasRole('admin', 'api');

        $query = Voyage::with(['adresseDepot', 'adresseRecuperation', 'voyageur.user', 'entreprise', 'agentGp.user']);

        // 1. Voyageur en mode Voyageur (mode_client == false) : il ne voit que ses propres voyages
        if ($voyageur && ! $voyageur->mode_client && ! $isAdmin) {
            $query->where('voyageur_id', $voyageur->id);

            if ($request->filled('statut')) {
                $query->where('statut', $request->query('statut'));
            }
        }
        // 2. Entreprise GP (Gérant ou Agent GP) : accès aux voyages créés par leur entreprise
        elseif ($entrepriseId && ! $isAdmin) {
            $query->where('entreprise_id', $entrepriseId);

            if ($request->filled('statut')) {
                $query->where('statut', $request->query('statut'));
            }
        }
        // 3. Client (ou Voyageur en mode client) : accès aux voyages publiés + aux voyages réservés par le client
        elseif ($client && ! $isAdmin) {
            $query->where(function ($q) use ($client) {
                $q->whereIn('statut', ['publie', 'complet', 'en_cours', 'termine'])
                    ->orWhereHas('reservations', function ($rq) use ($client) {
                        $rq->where('client_id', $client->id);
                    });
            });

            if ($request->filled('statut')) {
                $query->where('statut', $request->query('statut'));
            }
        }
        // 4. Admin ou requête publique : voir les publiés par défaut, ou tous pour l'admin
        else {
            if (! $isAdmin) {
                $query->whereIn('statut', ['publie', 'complet', 'en_cours', 'termine']);
            } elseif ($request->filled('statut')) {
                $query->where('statut', $request->query('statut'));
            }
        }

        // Filtres optionnels (villes, pays, dates)
        if ($request->filled('ville_depart')) {
            $query->where('ville_depart', 'like', '%'.$request->query('ville_depart').'%');
        }

        if ($request->filled('ville_destination')) {
            $query->where('ville_destination', 'like', '%'.$request->query('ville_destination').'%');
        }

        if ($request->filled('pays_depart')) {
            $query->where('pays_depart', 'like', '%'.$request->query('pays_depart').'%');
        }

        if ($request->filled('pays_destination')) {
            $query->where('pays_destination', 'like', '%'.$request->query('pays_destination').'%');
        }

        if ($request->filled('date_depart')) {
            $query->whereDate('date_depart', '>=', $request->query('date_depart'));
        }

        $voyages = $query->latest('date_depart')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Liste des voyages récupérée avec succès.',
            'data' => VoyageResource::collection($voyages),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVoyageRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $voyageur = $user?->voyageur()->first();
        $entrepriseId = $user?->getEntrepriseId();
        $isAdmin = $user?->hasRole('admin', 'api');

        if (! $voyageur && ! $entrepriseId && ! $isAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Seul un utilisateur avec un profil Voyageur ou Entreprise GP peut créer un voyage.',
            ], 403);
        }

        // Vérification impérative du statut vérifié pour le voyageur
        if (! $isAdmin && $voyageur && $voyageur->statut !== 'verifie') {
            return response()->json([
                'status' => 'error',
                'message' => 'Seuls les voyageurs dont la pièce d\'identité a été vérifiée par l\'administrateur peuvent créer un voyage.',
            ], 403);
        }

        $validated = $request->validated();

        // Vérifier l'appartenance de l'adresse de dépôt
        $adresseDepot = Adresse_depot::find($validated['adresse_depot_id']);
        if ($adresseDepot && $adresseDepot->voyageur_id !== null && $adresseDepot->voyageur_id !== $voyageur?->id && $adresseDepot->user_id !== $user?->id && ! $isAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'L\'adresse de dépôt sélectionnée ne vous appartient pas.',
            ], 403);
        }

        // Vérifier l'appartenance de l'adresse de récupération
        $adresseRecup = Adresse_recuperation::find($validated['adresse_recuperation_id']);
        if ($adresseRecup && $adresseRecup->voyageur_id !== null && $adresseRecup->voyageur_id !== $voyageur?->id && $adresseRecup->user_id !== $user?->id && ! $isAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'L\'adresse de récupération sélectionnée ne vous appartient pas.',
            ], 403);
        }

        if ($entrepriseId && ! $voyageur) {
            $validated['entreprise_id'] = $entrepriseId;
            $validated['voyageur_id'] = null;
        } else {
            $validated['voyageur_id'] = $voyageur ? $voyageur->id : $request->input('voyageur_id');
        }
        $validated['capacite_dispo'] = $validated['capacite_totale'];
        $validated['statut'] = $validated['statut'] ?? 'brouillon';

        $voyage = Voyage::create($validated);
        $voyage->load(['adresseDepot', 'adresseRecuperation', 'voyageur.user', 'entreprise', 'agentGp.user']);

        return response()->json([
            'status' => 'success',
            'message' => 'Voyage créé avec succès.',
            'data' => new VoyageResource($voyage),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Voyage $voyage): JsonResponse
    {
        Voyage::closePastVoyages();
        $voyage->refresh();
        $voyage->load(['adresseDepot', 'adresseRecuperation', 'voyageur.user', 'entreprise', 'agentGp.user', 'reservations.client.user', 'reservations.colis']);

        return response()->json([
            'status' => 'success',
            'message' => 'Détails du voyage récupérés avec succès.',
            'data' => new VoyageResource($voyage),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVoyageRequest $request, Voyage $voyage): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $voyageur = $user?->voyageur()->first();
        $entrepriseId = $user?->getEntrepriseId();
        $isAdmin = $user?->hasRole('admin', 'api');

        $isOwner = ($voyageur && $voyage->voyageur_id === $voyageur->id)
            || ($entrepriseId && $voyage->entreprise_id === $entrepriseId);

        if (! $isOwner && ! $isAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à modifier ce voyage.',
            ], 403);
        }

        if (! $isAdmin && $voyageur && $voyageur->statut !== 'verifie') {
            return response()->json([
                'status' => 'error',
                'message' => 'Votre profil voyageur doit être vérifié par un administrateur pour pouvoir modifier un voyage.',
            ], 403);
        }

        $validated = $request->validated();

        // Vérification de la nouvelle adresse de dépôt si fournie
        if (isset($validated['adresse_depot_id'])) {
            $adresseDepot = Adresse_depot::find($validated['adresse_depot_id']);
            if ($adresseDepot && $adresseDepot->voyageur_id !== null && $adresseDepot->voyageur_id !== $voyageur?->id && $adresseDepot->user_id !== $user?->id && ! $isAdmin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'L\'adresse de dépôt sélectionnée ne vous appartient pas.',
                ], 403);
            }
        }

        // Vérification de la nouvelle adresse de récupération si fournie
        if (isset($validated['adresse_recuperation_id'])) {
            $adresseRecup = Adresse_recuperation::find($validated['adresse_recuperation_id']);
            if ($adresseRecup && $adresseRecup->voyageur_id !== null && $adresseRecup->voyageur_id !== $voyageur?->id && $adresseRecup->user_id !== $user?->id && ! $isAdmin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'L\'adresse de récupération sélectionnée ne vous appartient pas.',
                ], 403);
            }
        }

        // Ajustement de la capacité disponible si la capacité totale est modifiée
        if (isset($validated['capacite_totale'])) {
            $capaciteReservee = $voyage->capacite_totale - $voyage->capacite_dispo;
            $nouvelleCapaciteTotale = (float) $validated['capacite_totale'];

            if ($nouvelleCapaciteTotale < $capaciteReservee) {
                return response()->json([
                    'status' => 'error',
                    'message' => "La capacité totale ({$nouvelleCapaciteTotale} kg) ne peut pas être inférieure à la capacité déjà réservée ({$capaciteReservee} kg).",
                ], 422);
            }

            $validated['capacite_dispo'] = $nouvelleCapaciteTotale - $capaciteReservee;
        }

        $voyage->update($validated);
        $voyage->load(['adresseDepot', 'adresseRecuperation', 'voyageur.user', 'entreprise', 'agentGp.user']);

        return response()->json([
            'status' => 'success',
            'message' => 'Voyage mis à jour avec succès.',
            'data' => new VoyageResource($voyage),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Voyage $voyage): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $voyageur = $user?->voyageur()->first();
        $entrepriseId = $user?->getEntrepriseId();
        $isAdmin = $user?->hasRole('admin', 'api');

        $isOwner = ($voyageur && $voyage->voyageur_id === $voyageur->id)
            || ($entrepriseId && $voyage->entreprise_id === $entrepriseId);

        if (! $isOwner && ! $isAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à supprimer ce voyage.',
            ], 403);
        }

        // Empêcher la suppression dès lors qu'il existe des réservations
        if ($voyage->reservations()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer ce voyage car il possède déjà des réservations associées.',
            ], 422);
        }

        $voyage->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Voyage supprimé avec succès.',
        ]);
    }

    /**
     * Publier un voyage (passer le statut à "publie").
     */
    public function publier(Voyage $voyage): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $voyageur = $user?->voyageur()->first();
        $entrepriseId = $user?->getEntrepriseId();
        $isAdmin = $user?->hasRole('admin', 'api');

        $isOwner = ($voyageur && $voyage->voyageur_id === $voyageur->id)
            || ($entrepriseId && $voyage->entreprise_id === $entrepriseId);

        if (! $isOwner && ! $isAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à publier ce voyage.',
            ], 403);
        }

        if (! $isAdmin && $voyageur && $voyageur->statut !== 'verifie') {
            return response()->json([
                'status' => 'error',
                'message' => 'Votre profil voyageur doit être vérifié par un administrateur pour pouvoir publier un voyage.',
            ], 403);
        }

        $voyage->update(['statut' => 'publie']);
        $voyage->load(['adresseDepot', 'adresseRecuperation', 'voyageur.user', 'entreprise', 'agentGp.user']);

        return response()->json([
            'status' => 'success',
            'message' => 'Voyage publié avec succès.',
            'data' => new VoyageResource($voyage),
        ]);
    }

    /**
     * Annuler un voyage (passer le statut à "annule").
     */
    public function annuler(Voyage $voyage): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $voyageur = $user?->voyageur()->first();
        $entrepriseId = $user?->getEntrepriseId();
        $isAdmin = $user?->hasRole('admin', 'api');

        $isOwner = ($voyageur && $voyage->voyageur_id === $voyageur->id)
            || ($entrepriseId && $voyage->entreprise_id === $entrepriseId);

        if (! $isOwner && ! $isAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à annuler ce voyage.',
            ], 403);
        }

        $voyage->update(['statut' => 'annule']);
        $voyage->load(['adresseDepot', 'adresseRecuperation', 'voyageur.user', 'entreprise', 'agentGp.user']);

        return response()->json([
            'status' => 'success',
            'message' => 'Voyage annulé avec succès.',
            'data' => new VoyageResource($voyage),
        ]);
    }

    /**
     * Display a public listing of published voyages (no auth required).
     */
    public function publicIndex(Request $request): JsonResponse
    {
        Voyage::closePastVoyages();

        $query = Voyage::with(['adresseDepot', 'adresseRecuperation', 'voyageur.user'])
            ->whereIn('statut', ['publie', 'complet']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('ville_depart')) {
            $query->where('ville_depart', 'like', '%'.$request->query('ville_depart').'%');
        }

        if ($request->filled('ville_destination')) {
            $query->where('ville_destination', 'like', '%'.$request->query('ville_destination').'%');
        }

        if ($request->filled('pays_depart')) {
            $query->where('pays_depart', 'like', '%'.$request->query('pays_depart').'%');
        }

        if ($request->filled('pays_destination')) {
            $query->where('pays_destination', 'like', '%'.$request->query('pays_destination').'%');
        }

        if ($request->filled('date_depart')) {
            $query->whereDate('date_depart', '>=', $request->query('date_depart'));
        }

        $voyages = $query->latest('date_depart')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Liste des voyages publics récupérée avec succès.',
            'data' => VoyageResource::collection($voyages),
        ]);
    }

    /**
     * Display public details of a single published voyage (no auth required).
     */
    public function publicShow(Voyage $voyage): JsonResponse
    {
        Voyage::closePastVoyages();
        $voyage->refresh();
        $voyage->load(['adresseDepot', 'adresseRecuperation', 'voyageur.user']);

        return response()->json([
            'status' => 'success',
            'message' => 'Détails du voyage public récupérés avec succès.',
            'data' => new VoyageResource($voyage),
        ]);
    }
}

