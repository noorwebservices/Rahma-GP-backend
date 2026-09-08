<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
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
}
