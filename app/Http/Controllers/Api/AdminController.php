<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VoyageurAccountValidatedMail;
use App\Models\User;
use App\Models\Voyageur;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * Obtenir la liste paginée de tous les utilisateurs (Réservé à l'Admin).
     */
    public function users(Request $request): JsonResponse
    {
        $query = User::with(['client', 'voyageur', 'roles']);

        // Recherche par mot-clé (nom, prénom, email, téléphone)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        // Filtre par statut (actif, inactif, suspendu)
        if ($statut = $request->input('statut')) {
            $query->where('statut', $statut);
        }

        // Filtre par rôle (client, voyageur, admin)
        if ($role = $request->input('role')) {
            $query->role($role);
        }

        $perPage = (int) $request->input('per_page', 15);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    /**
     * Modifier le statut de vérification d'un voyageur (en_attente, verifie, refuse).
     * Envoie un email de vérification/confirmation au voyageur si le statut passe à 'verifie'.
     */
    public function updateStatutVoyageur(Request $request, Voyageur $voyageur): JsonResponse
    {
        $validated = $request->validate([
            'statut' => 'required|string|in:en_attente,verifie,refuse',
        ]);

        $updateData = [
            'statut' => $validated['statut'],
        ];

        $token = null;
        if ($validated['statut'] === 'verifie') {
            $token = Str::random(60);
            $updateData['verification_token'] = $token;
        }

        $voyageur->update($updateData);

        $messageNotification = match ($validated['statut']) {
            'verifie' => 'Félicitations, votre compte voyageur a été vérifié avec succès.',
            'refuse' => 'Votre demande de vérification de compte voyageur a été refusée.',
            default => 'Le statut de votre compte voyageur est en attente de vérification.',
        };

        if ($voyageur->user_id) {
            NotificationService::send(
                $voyageur->user_id,
                'Statut compte voyageur mis à jour',
                $messageNotification,
                'systeme'
            );

            // Charger l'utilisateur associé pour l'email
            $user = $voyageur->user;
            if ($user && $user->email && $validated['statut'] === 'verifie') {
                try {
                    $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
                    $verificationUrl = rtrim($frontendUrl, '/') . '/auth/verify-voyageur?token=' . $token;

                    Mail::to($user->email)->send(new VoyageurAccountValidatedMail($voyageur, $verificationUrl));
                } catch (\Exception $e) {
                    Log::error("Erreur lors de l'envoi de l'email de validation voyageur: " . $e->getMessage());
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Statut du voyageur mis à jour avec succès.',
            'data' => $voyageur->fresh(['user']),
        ]);
    }
}
