<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SignalementController extends Controller
{
    /**
     * Créer un nouveau signalement de compte utilisateur.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'signale_id' => 'required|uuid|exists:users,id',
            'motif' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $me = auth('api')->user();

        if ($me->id === $validated['signale_id']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous ne pouvez pas signaler votre propre compte.',
            ], 422);
        }

        $signalement = Signalement::create([
            'signaleur_id' => $me->id,
            'signale_id' => $validated['signale_id'],
            'motif' => $validated['motif'],
            'description' => $validated['description'] ?? null,
            'statut' => 'en_attente',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Votre signalement a été transmis à l\'équipe de modération.',
            'data' => $signalement->load(['signale:id,nom,prenom,email']),
        ], 201);
    }
}
