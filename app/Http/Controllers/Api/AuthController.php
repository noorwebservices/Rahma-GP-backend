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

        if ($user->voyageur) {
            return response()->json([
                'status' => 'success',
                'message' => 'Inscription Voyageur réussie. Votre dossier a été transmis à l\'administration.',
                'require_verification' => true,
                'user' => $this->formatUserResponse($user),
            ], 201);
        }

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
            'mode_client' => false,
        ]);

        $user = $voyageur->user->fresh(['client', 'voyageur', 'roles']);

        return response()->json([
            'status' => 'success',
            'message' => 'Votre identité et compte voyageur ont été vérifiés avec succès !',
            'user' => $this->formatUserResponse($user),
            'voyageur' => $voyageur->fresh(['user']),
        ]);
    }

    /**
     * Renvoyer l'email de confirmation du compte voyageur.
     */
    public function resendVoyageurVerification(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if (!$user || !$user->voyageur) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucun profil voyageur associé à ce compte.',
            ], 404);
        }

        $voyageur = $user->voyageur;
        if ($voyageur->statut !== 'verifie') {
            return response()->json([
                'status' => 'error',
                'message' => 'Votre compte voyageur n\'a pas encore été approuvé par l\'administration.',
            ], 400);
        }

        if (!empty($voyageur->email_verifie_at)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Votre compte voyageur est déjà totalement vérifié et actif.',
                'user' => $this->formatUserResponse($user),
            ]);
        }

        $token = \Illuminate\Support\Str::random(60);
        $voyageur->update(['verification_token' => $token]);

        $frontendUrl = env('APP_FRONTEND_URL', env('FRONTEND_URL', 'http://localhost:5173'));
        $verificationUrl = rtrim($frontendUrl, '/') . '/auth/verify-voyageur?token=' . $token;

        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\VoyageurAccountValidatedMail($voyageur, $verificationUrl)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur renvoi mail voyageur: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Un nouvel email de confirmation a été envoyé à ' . $user->email . ' avec succès !',
            'verification_url' => $verificationUrl,
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

        $isVoyageurVerifie = $user->voyageur && $user->voyageur->statut === 'verifie' && !empty($user->voyageur->email_verifie_at);

        $modeActuel = 'client';
        if ($isAdmin) {
            $modeActuel = 'admin';
        } elseif ($isVoyageurVerifie) {
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
            'is_voyageur_verifie' => $isVoyageurVerifie,
            'mode_actuel' => $modeActuel,
        ];
    }
}
