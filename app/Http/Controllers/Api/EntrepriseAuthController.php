<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EntrepriseAccountValidatedMail;
use App\Models\Entreprise;
use App\Models\User;
use App\Services\ActiviteEntrepriseService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class EntrepriseAuthController extends Controller
{
    /**
     * Inscription d'un nouveau compte Gérant et création du profil Entreprise GP.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom_gerant' => 'required|string|max:255',
            'prenom_gerant' => 'required|string|max:255',
            'telephone_gerant' => 'required|string|unique:users,telephone',
            'email_gerant' => 'nullable|email|unique:users,email',
            'mot_de_passe' => 'required|string|min:6',

            'nom_entreprise' => 'required|string|max:255',
            'description' => 'nullable|string',
            'telephone_entreprise' => 'required|string',
            'email_entreprise' => 'required|email|unique:entreprises,email',
            'adresse' => 'required|string',
            'ville' => 'required|string',
            'pays' => 'required|string',
            'ninea' => 'nullable|string',
            'registre_commerce' => 'nullable|string',
            'moyen_paiement_prefere' => 'nullable|string',
            'coordonnees_paiement' => 'nullable|array',
        ]);

        $verificationToken = Str::random(60);

        $data = DB::transaction(function () use ($validated, $request, $verificationToken) {
            $user = User::create([
                'nom' => $validated['nom_gerant'],
                'prenom' => $validated['prenom_gerant'],
                'telephone' => $validated['telephone_gerant'],
                'email' => $validated['email_gerant'] ?? null,
                'adresse' => $request->input('adresse_gerant') ?? $validated['adresse'] ?? null,
                'mot_de_passe' => $validated['mot_de_passe'],
                'statut' => 'actif',
            ]);

            $roleGerant = Role::firstOrCreate(['name' => 'gerant_entreprise', 'guard_name' => 'api']);
            $user->assignRole($roleGerant);

            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('entreprises/logos', 'public');
            }

            $nineaDocPath = null;
            if ($request->hasFile('ninea_doc')) {
                $nineaDocPath = $request->file('ninea_doc')->store('entreprises/docs', 'public');
            }

            $rcDocPath = null;
            if ($request->hasFile('registre_commerce_doc')) {
                $rcDocPath = $request->file('registre_commerce_doc')->store('entreprises/docs', 'public');
            }

            $entreprise = Entreprise::create([
                'gerant_user_id' => $user->id,
                'nom' => $validated['nom_entreprise'],
                'description' => $validated['description'] ?? null,
                'telephone' => $validated['telephone_entreprise'],
                'email' => $validated['email_entreprise'],
                'adresse' => $validated['adresse'],
                'ville' => $validated['ville'],
                'pays' => $validated['pays'],
                'ninea' => $validated['ninea'] ?? null,
                'registre_commerce' => $validated['registre_commerce'] ?? null,
                'logo' => $logoPath,
                'ninea_doc' => $nineaDocPath,
                'registre_commerce_doc' => $rcDocPath,
                'moyen_paiement_prefere' => $validated['moyen_paiement_prefere'] ?? 'wave',
                'coordonnees_paiement' => $validated['coordonnees_paiement'] ?? null,
                'statut_verification' => 'en_attente',
                'verification_token' => $verificationToken,
            ]);

            ActiviteEntrepriseService::log(
                $entreprise->id,
                $user->id,
                'entreprise.creee',
                "Création de l'entreprise GP {$entreprise->nom} par {$user->prenom} {$user->nom}."
            );

            return ['user' => $user, 'entreprise' => $entreprise];
        });

        // Notification des administrateurs pour la nouvelle inscription d'Entreprise GP
        NotificationService::notifyAdmins(
            'Nouvelle inscription Entreprise GP',
            "L'entreprise {$data['entreprise']->nom} (Gérant: {$data['user']->prenom} {$data['user']->nom}) s'est inscrite et attend une validation.",
            'entreprise_inscription'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Compte Entreprise GP créé avec succès ! Votre dossier a été transmis à l\'administration pour vérification. Veuillez consulter régulièrement votre boîte e-mail.',
            'require_verification' => true,
            'entreprise' => $data['entreprise'],
            'user' => $data['user'],
        ], 201);
    }

    /**
     * Vérification de l'adresse email de l'entreprise via le token.
     */
    public function verifyEntreprise(string $token): JsonResponse
    {
        $entreprise = Entreprise::where('verification_token', $token)->first();

        if (! $entreprise) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jeton de vérification invalide ou expiré.',
            ], 404);
        }

        $entreprise->update([
            'email_verifie_at' => now(),
            'verification_token' => null,
            'statut_verification' => 'verifiee',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'L\'adresse e-mail de l\'entreprise '.$entreprise->nom.' a été vérifiée avec succès !',
            'entreprise' => $entreprise->fresh(['gerant']),
        ]);
    }

    /**
     * Renvoyer l'e-mail de vérification de l'entreprise.
     */
    public function resendVerification(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        if (! $user || ! $user->isGerantEntreprise()) {
            return response()->json(['message' => 'Accès réservé au gérant.'], 403);
        }

        $entreprise = $user->entrepriseGeree;
        if (! $entreprise) {
            return response()->json(['message' => 'Entreprise non trouvée.'], 404);
        }

        if ($entreprise->isEmailVerifie()) {
            return response()->json([
                'status' => 'success',
                'message' => 'L\'adresse email de votre entreprise est déjà vérifiée.',
            ]);
        }

        $newToken = Str::random(60);
        $entreprise->update(['verification_token' => $newToken]);

        $this->sendVerificationEmail($entreprise, $newToken);

        return response()->json([
            'status' => 'success',
            'message' => 'Un nouvel e-mail de vérification a été envoyé à '.$entreprise->email.'.',
        ]);
    }

    /**
     * Consulter le profil de l'entreprise GP connectée.
     */
    public function profile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $entrepriseId = $user->getEntrepriseId();
        if (! $entrepriseId) {
            return response()->json(['message' => 'Aucune entreprise associée à ce compte.'], 404);
        }

        $entreprise = Entreprise::with(['gerant', 'agents.user'])->find($entrepriseId);

        return response()->json([
            'status' => 'success',
            'entreprise' => $entreprise,
        ]);
    }

    /**
     * Mettre à jour les informations du profil Entreprise GP.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if (! $user || ! $user->isGerantEntreprise()) {
            return response()->json(['message' => 'Accès réservé au gérant de l\'entreprise.'], 403);
        }

        $entreprise = $user->entrepriseGeree;
        if (! $entreprise) {
            return response()->json(['message' => 'Entreprise introuvable.'], 404);
        }

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'telephone' => 'sometimes|string',
            'email' => 'sometimes|email|unique:entreprises,email,'.$entreprise->id,
            'adresse' => 'sometimes|string',
            'ville' => 'sometimes|string',
            'pays' => 'sometimes|string',
            'ninea' => 'nullable|string',
            'registre_commerce' => 'nullable|string',
            'moyen_paiement_prefere' => 'nullable|string',
            'coordonnees_paiement' => 'nullable|array',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('entreprises/logos', 'public');
        }

        if ($request->hasFile('ninea_doc')) {
            $validated['ninea_doc'] = $request->file('ninea_doc')->store('entreprises/docs', 'public');
        }

        if ($request->hasFile('registre_commerce_doc')) {
            $validated['registre_commerce_doc'] = $request->file('registre_commerce_doc')->store('entreprises/docs', 'public');
        }

        $entreprise->update($validated);

        ActiviteEntrepriseService::log(
            $entreprise->id,
            $user->id,
            'entreprise.mise_a_jour',
            'Mise à jour des informations de l\'entreprise.'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Profil de l\'entreprise mis à jour avec succès.',
            'entreprise' => $entreprise->fresh(),
        ]);
    }

    // ==========================================
    // CORBEILLE & RESTAURATION (SOFT DELETES)
    // ==========================================

    /**
     * Liste des entreprises supprimées (Corbeille / Trash).
     */
    public function trash(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        // Reservé à l'Admin ou au Gérant si son entreprise est supprimée
        $entreprises = Entreprise::onlyTrashed()->with(['gerant'])->latest()->get();

        return response()->json([
            'status' => 'success',
            'corbeille' => $entreprises,
        ]);
    }

    /**
     * Restaurer une entreprise supprimée depuis la corbeille.
     */
    public function restore(string $id): JsonResponse
    {
        $entreprise = Entreprise::onlyTrashed()->findOrFail($id);
        $entreprise->restore();

        ActiviteEntrepriseService::log(
            $entreprise->id,
            auth('api')->id(),
            'entreprise.restauree',
            "L'entreprise {$entreprise->nom} a été restaurée depuis la corbeille."
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Entreprise restaurée avec succès de la corbeille.',
            'entreprise' => $entreprise->fresh(),
        ]);
    }

    /**
     * Suppression définitive (Force Delete).
     */
    public function forceDelete(string $id): JsonResponse
    {
        $entreprise = Entreprise::withTrashed()->findOrFail($id);
        $entreprise->forceDelete();

        return response()->json([
            'status' => 'success',
            'message' => 'Entreprise supprimée définitivement.',
        ]);
    }

    /**
     * Helper pour envoyer l'email de vérification d'entreprise.
     */
    protected function sendVerificationEmail(Entreprise $entreprise, string $token): void
    {
        try {
            $frontendUrl = config('app.frontend_url', 'https://rahma-delivery.com');
            $verificationUrl = rtrim($frontendUrl, '/').'/auth/verify-entreprise?token='.$token;

            Mail::to($entreprise->email)->send(
                new EntrepriseAccountValidatedMail($entreprise, $verificationUrl)
            );
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi de l'email de vérification d'entreprise: ".$e->getMessage());
        }
    }
}
