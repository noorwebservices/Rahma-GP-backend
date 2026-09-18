<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemandePartenariat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DemandePartenariatController extends Controller
{
    /**
     * Soumettre une nouvelle demande de partenariat depuis le portail.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom_complet' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telephone' => 'required|string|max:50',
            'entreprise' => 'nullable|string|max:255',
            'type_partenariat' => 'nullable|string|max:100',
            'message' => 'required|string|max:2000',
        ]);

        $demande = DemandePartenariat::create([
            'nom_complet' => $validated['nom_complet'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'],
            'entreprise' => $validated['entreprise'] ?? null,
            'type_partenariat' => $validated['type_partenariat'] ?? 'autre',
            'message' => $validated['message'],
            'statut' => 'en_attente',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Votre demande de partenariat a été enregistrée avec succès. Notre équipe vous recontactera sous peu.',
            'data' => $demande,
        ], 201);
    }
}
