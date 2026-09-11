<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Voyageur;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     */
    public function updateStatutVoyageur(Request $request, Voyageur $voyageur): JsonResponse
    {
        $validated = $request->validate([
            'statut' => 'required|string|in:en_attente,verifie,refuse',
        ]);

        $voyageur->update([
            'statut' => $validated['statut'],
        ]);

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
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Statut du voyageur mis à jour avec succès.',
            'data' => $voyageur->fresh(['user']),
        ]);
    }
}
