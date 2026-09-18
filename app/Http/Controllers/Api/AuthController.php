<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Client;
use App\Models\User;
use App\Models\Voyageur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur (profil Client ou Voyageur avec pièces d'identité).
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated, $request) {
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
            } elseif (! empty($validated['avatar']) && is_string($validated['avatar'])) {
                $avatarPath = $validated['avatar'];
            }

            $user = User::create([
                'nom' => $validated['nom'],
                'prenom' => $validated['prenom'],
                'telephone' => $validated['telephone'],
                'email' => $validated['email'] ?? null,
                'mot_de_passe' => $validated['mot_de_passe'],
                'adresse' => $validated['adresse'] ?? null,
                'avatar' => $avatarPath,
                'statut' => 'actif',
            ]);

            // S'assurer que le rôle "client" existe et l'attribuer
            $roleClient = Role::firstOrCreate(['name' => 'client', 'guard_name' => 'api']);
            $user->assignRole($roleClient);

            // Créer le profil Client associé par défaut
            Client::create([
                'user_id' => $user->id,
            ]);

            // Vérifier si le profil Voyageur est sélectionné
            $isVoyageur = ($validated['profile_type'] ?? '') === 'voyageur' || !empty($validated['type_piece']);
            if ($isVoyageur) {
                $roleVoyageur = Role::firstOrCreate(['name' => 'voyageur', 'guard_name' => 'api']);
                $user->assignRole($roleVoyageur);

                $cniRectoPath = null;
                if ($request->hasFile('cni_recto')) {
                    $cniRectoPath = $request->file('cni_recto')->store('cni', 'public');
                }

                $cniVersoPath = null;
                if ($request->hasFile('cni_verso')) {
                    $cniVersoPath = $request->file('cni_verso')->store('cni', 'public');
                }

                Voyageur::create([
                    'user_id' => $user->id,
                    'type_piece' => $validated['type_piece'] ?? 'cni',
                    'numero_piece' => $validated['numero_piece'] ?? null,
                    'cni_recto' => $cniRectoPath,
                    'cni_verso' => $cniVersoPath,
                    'mode_client' => false,
                    'statut' => 'en_attente',
                ]);
            }

            return $user;
        });

        $token = auth('api')->login($user);

        return $this->respondWithToken($token, $user, 'Inscription réussie', 201);
    }

    /**
     * Vérification de l'adresse email et identité du voyageur suite à la validation par l'admin.
     */
    public function verifyVoyageur(string $token): JsonResponse
    {
        $voyageur = Voyageur::where('verification_token', $token)->first();

        if (!$voyageur) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jeton de vérification invalide ou expiré.',
            ], 404);
        }

        $voyageur->update([
            'email_verifie_at' => now(),
            'verification_token' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Votre identité et compte voyageur ont été vérifiés avec succès !',
            'voyageur' => $voyageur->fresh(['user']),
        ]);
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

        if ($user->statut === 'suspendu') {
            auth('api')->logout();
            return response()->json([
                'status' => 'error',
                'message' => 'Votre compte a été suspendu ou bloqué par un administrateur.',
            ], 403);
        }

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

        $roles = $user->getRoleNames();
        $isAdmin = $roles->contains('admin');

        $modeActuel = 'client';
        if ($isAdmin) {
            $modeActuel = 'admin';
        } elseif ($user->voyageur) {
            $modeActuel = $user->voyageur->mode_client ? 'client' : 'voyageur';
        }

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
            'roles' => $roles,
            'client' => $user->client,
            'voyageur' => $user->voyageur,
            'mode_actuel' => $modeActuel,
        ];
    }
}
