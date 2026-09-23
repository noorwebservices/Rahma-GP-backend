<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentGp;
use App\Models\User;
use App\Models\Voyage;
use App\Services\ActiviteEntrepriseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntrepriseVoyageController extends Controller
{
    /**
     * Création d'un voyage par l'Entreprise GP avec option d'affectation immédiate d'un agent.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if (! $user->isGerantEntreprise()) {
            return response()->json(['message' => 'Accès réservé au gérant de l\'entreprise.'], 403);
        }

        $entrepriseId = $user->getEntrepriseId();

        $validated = $request->validate([
            'adresse_depot_id' => 'required|uuid|exists:adresse_depots,id',
            'adresse_recuperation_id' => 'required|uuid|exists:adresse_recuperations,id',
            'pays_depart' => 'required|string',
            'ville_depart' => 'required|string',
            'pays_destination' => 'required|string',
            'ville_destination' => 'required|string',
            'date_depart' => 'required|date',
            'date_arrivee' => 'required|date|after_or_equal:date_depart',
            'capacite_totale' => 'required|numeric|min:0.1',
            'prix_kg' => 'nullable|numeric|min:0',
            'prix_objet' => 'nullable|numeric|min:0',
            'devise' => 'nullable|string',
            'description' => 'nullable|string',
            'objets_autorises' => 'nullable|array',
            'objets_interdits' => 'nullable|array',
            'agent_gp_id' => 'nullable|uuid|exists:agent_gps,id',
        ]);

        $voyage = DB::transaction(function () use ($validated, $entrepriseId, $user) {
            $agentId = $validated['agent_gp_id'] ?? null;

            $voyage = Voyage::create([
                'entreprise_id' => $entrepriseId,
                'agent_gp_id' => $agentId,
                'voyageur_id' => null,
                'adresse_depot_id' => $validated['adresse_depot_id'],
                'adresse_recuperation_id' => $validated['adresse_recuperation_id'],
                'pays_depart' => $validated['pays_depart'],
                'ville_depart' => $validated['ville_depart'],
                'pays_destination' => $validated['pays_destination'],
                'ville_destination' => $validated['ville_destination'],
                'date_depart' => $validated['date_depart'],
                'date_arrivee' => $validated['date_arrivee'],
                'capacite_totale' => $validated['capacite_totale'],
                'capacite_dispo' => $validated['capacite_totale'],
                'prix_kg' => $validated['prix_kg'] ?? null,
                'prix_objet' => $validated['prix_objet'] ?? null,
                'devise' => $validated['devise'] ?? 'XOF',
                'description' => $validated['description'] ?? null,
                'objets_autorises' => $validated['objets_autorises'] ?? null,
                'objets_interdits' => $validated['objets_interdits'] ?? null,
                'statut' => 'publie',
            ]);

            $descr = "Voyage {$voyage->ville_depart} -> {$voyage->ville_destination} créé.";
            if ($agentId) {
                $agent = AgentGp::with('user')->find($agentId);
                if ($agent) {
                    $descr .= " Affecté à l'agent {$agent->user->prenom} {$agent->user->nom}.";
                    $agent->update(['statut' => 'en_voyage']);
                }
            }

            ActiviteEntrepriseService::log($entrepriseId, $user->id, 'voyage.cree', $descr);

            return $voyage;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Voyage créé avec succès.',
            'voyage' => $voyage->load(['entreprise', 'agentGp.user', 'adresseDepot', 'adresseRecuperation']),
        ], 201);
    }

    /**
     * Affectation ou réaffectation d'un voyage à un Agent GP.
     */
    public function assignAgent(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if (! $user->isGerantEntreprise()) {
            return response()->json(['message' => 'Accès réservé au gérant.'], 403);
        }

        $entrepriseId = $user->getEntrepriseId();
        $voyage = Voyage::where('entreprise_id', $entrepriseId)->findOrFail($id);

        $validated = $request->validate([
            'agent_gp_id' => 'required|uuid|exists:agent_gps,id',
        ]);

        $newAgent = AgentGp::with('user')->where('entreprise_id', $entrepriseId)->findOrFail($validated['agent_gp_id']);
        $oldAgentId = $voyage->agent_gp_id;

        $voyage->update([
            'agent_gp_id' => $newAgent->id,
        ]);

        // Mettre à jour le statut du nouvel agent
        $newAgent->update(['statut' => 'en_voyage']);

        $description = "Le voyage {$voyage->ville_depart} -> {$voyage->ville_destination} a été affecté à {$newAgent->user->prenom} {$newAgent->user->nom}.";
        ActiviteEntrepriseService::log($entrepriseId, $user->id, 'voyage.agent_affecte', $description);

        return response()->json([
            'status' => 'success',
            'message' => 'Agent affecté au voyage avec succès.',
            'voyage' => $voyage->fresh(['agentGp.user']),
        ]);
    }

    /**
     * Liste globale des voyages de l'entreprise GP (Vue Gérant).
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $entrepriseId = $user->getEntrepriseId();

        $query = Voyage::with(['agentGp.user', 'reservations.colis', 'adresseDepot', 'adresseRecuperation'])
            ->where('entreprise_id', $entrepriseId);

        if ($request->has('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        $voyages = $query->latest('date_depart')->paginate(15);

        return response()->json([
            'status' => 'success',
            'voyages' => $voyages,
        ]);
    }

    /**
     * Liste des voyages affectés à l'Agent GP connecté (Vue Agent).
     */
    public function agentVoyages(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $agentGp = $user->agentGp;

        if (! $agentGp) {
            return response()->json(['message' => 'Profil Agent GP non trouvé.'], 404);
        }

        $voyages = Voyage::with(['reservations.colis', 'reservations.client.user', 'adresseDepot', 'adresseRecuperation'])
            ->where('agent_gp_id', $agentGp->id)
            ->latest('date_depart')
            ->get();

        return response()->json([
            'status' => 'success',
            'count' => $voyages->count(),
            'voyages' => $voyages,
        ]);
    }
}
