<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActiviteEntreprise;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntrepriseActivityController extends Controller
{
    /**
     * Obtenir l'historique d'activités (journal d'audit) de l'entreprise GP (Section 14).
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if (! $user->isGerantEntreprise()) {
            return response()->json(['message' => 'Accès réservé au gérant.'], 403);
        }

        $entrepriseId = $user->getEntrepriseId();

        $query = ActiviteEntreprise::with('user')
            ->where('entreprise_id', $entrepriseId);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->has('action')) {
            $query->where('action', 'like', '%'.$request->query('action').'%');
        }

        $activites = $query->latest()->paginate(25);

        return response()->json([
            'status' => 'success',
            'activites' => $activites,
        ]);
    }
}
