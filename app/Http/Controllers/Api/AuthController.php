<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur + création automatique du profil Client.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated, $request) {
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
            } elseif (!empty($validated['avatar']) && is_string($validated['avatar'])) {
                $avatarPath = $validated['avatar'];
            }

            $user = User::create([
                'nom' => $validated['nom'],
                'prenom' => $validated['prenom'],
                'telephone' => $validated['telephone'],
                'email' => $validated['email'],
                'mot_de_passe' => $validated['mot_de_passe'],
                'adresse' => $validated['adresse'] ?? null,
                'avatar' => $avatarPath,
                'statut' => 'actif',
            ]);

            // S'assurer que le rôle "client" existe et l'attribuer
            $roleClient = Role::firstOrCreate(['name' => 'client', 'guard_name' => 'api']);
            $user->assignRole($roleClient);

            // Créer le profil Client associé
            Client::create([
                'user_id' => $user->id,
            ]);

            return $user;
        });

        $token = auth('api')->login($user);

        return $this->respondWithToken($token, $user, 'Inscription réussie', 201);
    }

    /**
     * Connexion JWT par email ou numéro de téléphone.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->getCredentials();

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Identifiants incorrects (email/téléphone ou mot de passe invalide).',
            ], 401);
        }

        /** @var User $user */
        $user = auth('api')->user();

        // Mettre à jour la date de dernière connexion
        $user->update([
            'dernier_connexion' => now(),
        ]);

        return $this->respondWithToken($token, $user, 'Connexion réussie');
    }

    /**
     * Obtenir les détails de l'utilisateur connecté.
     */
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        return response()->json([
            'status' => 'success',
            'user' => $this->formatUserResponse($user),
        ]);
    }

    /**
     * Déconnexion de l'utilisateur (invalidation du token JWT).
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json([
            'status' => 'success',
            'message' => 'Déconnexion réussie.',
        ]);
    }

    /**
     * Rafraîchissement du token JWT.
     */
    public function refresh(): JsonResponse
    {
        $newToken = auth('api')->refresh();

        return $this->respondWithToken($newToken, null, 'Token rafraîchi avec succès');
    }

    /**
     * Formater la réponse utilisateur réutilisable.
     */
    protected function respondWithToken(string $token, ?User $user = null, ?string $message = null, int $code = 200): JsonResponse
    {
        $user = $user ?? auth('api')->user();

        return response()->json([
            'status' => 'success',
            'message' => $message ?? 'Succès',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user ? $this->formatUserResponse($user) : null,
        ], $code);
    }

    /**
     * Formater les données de l'utilisateur avec ses profils, rôles et mode actuel.
     */
    protected function formatUserResponse(User $user): array
    {
        $user->loadMissing(['client', 'voyageur', 'roles']);

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
            'client' => $user->client,
            'voyageur' => $user->voyageur,
            'mode_actuel' => $user->voyageur ? ($user->voyageur->mode_client ? 'client' : 'voyageur') : 'client',
        ];
    }
}
