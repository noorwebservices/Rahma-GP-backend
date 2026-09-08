<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\CreateVoyageurProfileRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\Client;
use App\Models\User;
use App\Models\Voyageur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class ProfileController extends Controller
{
    /**
     * Afficher le profil complet de l'utilisateur connecté.
     */
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $user->load(['client', 'voyageur', 'roles', 'permissions']);

        return response()->json([
            'status' => 'success',
            'user' => $this->formatUserResponse($user),
        ]);
    }

    /**
     * Mettre à jour les informations du profil utilisateur.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $validated = $request->validated();

        $user->update(array_filter([
            'nom' => $validated['nom'] ?? $user->nom,
            'prenom' => $validated['prenom'] ?? $user->prenom,
            'email' => $validated['email'] ?? $user->email,
            'telephone' => $validated['telephone'] ?? $user->telephone,
            'adresse' => array_key_exists('adresse', $validated) ? $validated['adresse'] : $user->adresse,
            'avatar' => array_key_exists('avatar', $validated) ? $validated['avatar'] : $user->avatar,
            'mot_de_passe' => !empty($validated['mot_de_passe']) ? $validated['mot_de_passe'] : null,
        ], fn ($value) => $value !== null));

        $user->load(['client', 'voyageur', 'roles', 'permissions']);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil mis à jour avec succès.',
            'user' => $this->formatUserResponse($user),
        ]);
    }

    /**
     * Créer ou activer le profil Client pour l'utilisateur connecté.
     */
    public function createClientProfile(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if ($user->client) {
            return response()->json([
                'status' => 'success',
                'message' => 'Le profil Client existe déjà.',
                'client' => $user->client,
            ]);
        }

        $client = DB::transaction(function () use ($user) {
            Role::firstOrCreate(['name' => 'client', 'guard_name' => 'api']);
            if (!$user->hasRole('client', 'api')) {
                $user->assignRole(Role::findByName('client', 'api'));
            }

            return Client::create([
                'user_id' => $user->id,
            ]);
        });

        $user->load(['client', 'voyageur', 'roles', 'permissions']);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil Client créé avec succès.',
            'client' => $client,
            'user' => $this->formatUserResponse($user),
        ], 201);
    }

    /**
     * Créer le profil Voyageur pour l'utilisateur connecté et attribuer le rôle "voyageur".
     */
    public function createVoyageurProfile(CreateVoyageurProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $validated = $request->validated();

        $voyageur = DB::transaction(function () use ($user, $validated) {
            Role::firstOrCreate(['name' => 'voyageur', 'guard_name' => 'api']);
            if (!$user->hasRole('voyageur', 'api')) {
                $user->assignRole(Role::findByName('voyageur', 'api'));
            }

            return Voyageur::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'type_piece' => $validated['type_piece'],
                    'numero_piece' => $validated['numero_piece'] ?? null,
                    'cni_recto' => $validated['cni_recto'] ?? null,
                    'cni_verso' => $validated['cni_verso'] ?? null,
                    'mode_client' => $validated['mode_client'] ?? true,
                    'statut' => 'en_attente',
                ]
            );
        });

        $user->load(['client', 'voyageur', 'roles', 'permissions']);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil Voyageur créé avec succès.',
            'voyageur' => $voyageur,
            'user' => $this->formatUserResponse($user),
        ], 201);
    }

    /**
     * Basculer en mode Client <-> mode Voyageur pour l'utilisateur ayant le profil Voyageur.
     */
    public function toggleMode(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if (!$user->voyageur) {
            return response()->json([
                'status' => 'error',
                'message' => 'Seul un utilisateur avec un profil Voyageur peut basculer de mode.',
            ], 403);
        }

        $voyageur = $user->voyageur;
        $voyageur->mode_client = !$voyageur->mode_client;
        $voyageur->save();

        $modeActuel = $voyageur->mode_client ? 'client' : 'voyageur';

        return response()->json([
            'status' => 'success',
            'message' => "Mode basculé vers le mode {$modeActuel}.",
            'mode_client' => $voyageur->mode_client,
            'mode_actuel' => $modeActuel,
        ]);
    }

    /**
     * Formater les données de réponse utilisateur.
     */
    protected function formatUserResponse(User $user): array
    {
        return [
            'id' => $user->id,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'avatar' => $user->avatar,
            'adresse' => $user->adresse,
            'statut' => $user->statut,
            'dernier_connexion' => $user->dernier_connexion,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'client' => $user->client,
            'voyageur' => $user->voyageur,
            'mode_actuel' => $user->voyageur ? ($user->voyageur->mode_client ? 'client' : 'voyageur') : 'client',
        ];
    }
}
