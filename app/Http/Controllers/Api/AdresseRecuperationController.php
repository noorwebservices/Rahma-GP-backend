<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdresse_recuperationRequest;
use App\Http\Requests\UpdateAdresse_recuperationRequest;
use App\Http\Resources\AdresseRecuperationResource;
use App\Models\Adresse_recuperation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdresseRecuperationController extends Controller
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

        $query = Adresse_recuperation::query();

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
            'message' => 'Liste des adresses de récupération récupérée avec succès.',
            'data' => AdresseRecuperationResource::collection($adresses),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdresse_recuperationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $voyageur = $user?->voyageur()->first();
        $entrepriseId = $user?->getEntrepriseId();

        $validated = $request->validated();
        $validated['user_id'] = $user?->id;
        $validated['voyageur_id'] = $voyageur?->id;
        $validated['entreprise_id'] = $entrepriseId;

        $adresse = Adresse_recuperation::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Adresse de récupération créée avec succès.',
            'data' => new AdresseRecuperationResource($adresse),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Adresse_recuperation $adresseRecuperation): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Détails de l\'adresse de récupération récupérés avec succès.',
            'data' => new AdresseRecuperationResource($adresseRecuperation),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdresse_recuperationRequest $request, Adresse_recuperation $adresseRecuperation): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $voyageur = $user?->voyageur()->first();
        $entrepriseId = $user?->getEntrepriseId();
        $isAdmin = $user?->hasRole('admin', 'api');

        $isOwner = $isAdmin
            || ($adresseRecuperation->user_id && $adresseRecuperation->user_id === $user?->id)
            || ($entrepriseId && $adresseRecuperation->entreprise_id === $entrepriseId)
            || ($voyageur && $adresseRecuperation->voyageur_id === $voyageur->id);

        if (! $isOwner) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à modifier cette adresse de récupération.',
            ], 403);
        }

        $adresseRecuperation->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Adresse de récupération mise à jour avec succès.',
            'data' => new AdresseRecuperationResource($adresseRecuperation->fresh()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Adresse_recuperation $adresseRecuperation): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $voyageur = $user?->voyageur()->first();
        $entrepriseId = $user?->getEntrepriseId();
        $isAdmin = $user?->hasRole('admin', 'api');

        $isOwner = $isAdmin
            || ($adresseRecuperation->user_id && $adresseRecuperation->user_id === $user?->id)
            || ($entrepriseId && $adresseRecuperation->entreprise_id === $entrepriseId)
            || ($voyageur && $adresseRecuperation->voyageur_id === $voyageur->id);

        if (! $isOwner) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à supprimer cette adresse de récupération.',
            ], 403);
        }

        if ($adresseRecuperation->voyages()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer cette adresse de récupération car elle est actuellement liée à un ou plusieurs voyages.',
            ], 422);
        }

        $adresseRecuperation->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Adresse de récupération supprimée avec succès.',
        ]);
    }
}
