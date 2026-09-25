<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdresse_depotRequest;
use App\Http\Requests\UpdateAdresse_depotRequest;
use App\Http\Resources\AdresseDepotResource;
use App\Models\Adresse_depot;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdresseDepotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $voyageur = $user?->voyageur()->first();
        $entrepriseId = $user?->getEntrepriseId();
        $isAdmin = $user?->hasRole('admin', 'api');

        $query = Adresse_depot::query();

        if (! $isAdmin) {
            $query->where(function ($q) use ($user, $voyageur, $entrepriseId) {
                $q->where('user_id', $user?->id);
                if ($entrepriseId) {
                    $q->orWhere('entreprise_id', $entrepriseId);
                }
                if ($voyageur) {
                    $q->orWhere('voyageur_id', $voyageur->id);
                }
            });
        }

        if ($request->filled('ville')) {
            $query->where('ville', 'like', '%'.$request->query('ville').'%');
        }

        if ($request->filled('pays')) {
            $query->where('pays', 'like', '%'.$request->query('pays').'%');
        }

        $adresses = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Liste des adresses de dépôt récupérée avec succès.',
            'data' => AdresseDepotResource::collection($adresses),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdresse_depotRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $voyageur = $user?->voyageur()->first();
        $entrepriseId = $user?->getEntrepriseId();

        $validated = $request->validated();
        $validated['user_id'] = $user?->id;
        $validated['voyageur_id'] = $voyageur?->id;
        $validated['entreprise_id'] = $entrepriseId;

        $adresse = Adresse_depot::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Adresse de dépôt créée avec succès.',
            'data' => new AdresseDepotResource($adresse),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Adresse_depot $adresseDepot): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Détails de l\'adresse de dépôt récupérés avec succès.',
            'data' => new AdresseDepotResource($adresseDepot),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdresse_depotRequest $request, Adresse_depot $adresseDepot): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $voyageur = $user?->voyageur()->first();
        $entrepriseId = $user?->getEntrepriseId();
        $isAdmin = $user?->hasRole('admin', 'api');

        $isOwner = $isAdmin
            || ($adresseDepot->user_id && $adresseDepot->user_id === $user?->id)
            || ($entrepriseId && $adresseDepot->entreprise_id === $entrepriseId)
            || ($voyageur && $adresseDepot->voyageur_id === $voyageur->id);

        if (! $isOwner) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à modifier cette adresse de dépôt.',
            ], 403);
        }

        $adresseDepot->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Adresse de dépôt mise à jour avec succès.',
            'data' => new AdresseDepotResource($adresseDepot->fresh()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Adresse_depot $adresseDepot): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $voyageur = $user?->voyageur()->first();
        $entrepriseId = $user?->getEntrepriseId();
        $isAdmin = $user?->hasRole('admin', 'api');

        $isOwner = $isAdmin
            || ($adresseDepot->user_id && $adresseDepot->user_id === $user?->id)
            || ($entrepriseId && $adresseDepot->entreprise_id === $entrepriseId)
            || ($voyageur && $adresseDepot->voyageur_id === $voyageur->id);

        if (! $isOwner) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à supprimer cette adresse de dépôt.',
            ], 403);
        }

        if ($adresseDepot->voyages()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer cette adresse de dépôt car elle est actuellement liée à un ou plusieurs voyages.',
            ], 422);
        }

        $adresseDepot->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Adresse de dépôt supprimée avec succès.',
        ]);
    }
}
