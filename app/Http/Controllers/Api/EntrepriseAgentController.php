<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentGp;
use App\Models\InvitationAgent;
use App\Models\User;
use App\Services\ActiviteEntrepriseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class EntrepriseAgentController extends Controller
{
    /**
     * Liste des agents de l'entreprise GP connectée.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $entrepriseId = $user->getEntrepriseId();

        $query = AgentGp::with(['user', 'voyages' => function ($q) {
            $q->latest()->limit(5);
        }])->where('entreprise_id', $entrepriseId);

        if ($request->has('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        $agents = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'count' => $agents->count(),
            'agents' => $agents,
        ]);
    }

    /**
     * Création directe d'un Agent GP par le Gérant.
     */
    public function directCreate(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if (! $user->isGerantEntreprise()) {
            return response()->json(['message' => 'Accès réservé au gérant de l\'entreprise.'], 403);
        }

        $entrepriseId = $user->getEntrepriseId();

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'telephone' => 'required|string|unique:users,telephone',
            'email' => 'nullable|email|unique:users,email',
            'mot_de_passe' => 'required|string|min:6',
            'matricule' => 'nullable|string',
        ]);

        $agent = DB::transaction(function () use ($validated, $user, $entrepriseId) {
            $agentUser = User::create([
                'nom' => $validated['nom'],
                'prenom' => $validated['prenom'],
                'telephone' => $validated['telephone'],
                'email' => $validated['email'] ?? null,
                'mot_de_passe' => $validated['mot_de_passe'],
                'statut' => 'actif',
            ]);

            $roleAgent = Role::firstOrCreate(['name' => 'agent_gp', 'guard_name' => 'api']);
            $agentUser->assignRole($roleAgent);

            $agentGp = AgentGp::create([
                'user_id' => $agentUser->id,
                'entreprise_id' => $entrepriseId,
                'matricule' => $validated['matricule'] ?? 'AG-'.strtoupper(Str::random(6)),
                'statut' => 'actif',
                'date_adhesion' => now(),
                'date_activation' => now(),
            ]);

            ActiviteEntrepriseService::log(
                $entrepriseId,
                $user->id,
                'agent.cree_directement',
                "L'agent GP {$agentUser->prenom} {$agentUser->nom} a été créé directement par le gérant."
            );

            return $agentGp->load('user');
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Agent GP créé et rattaché à l\'entreprise avec succès.',
            'agent' => $agent,
        ], 201);
    }

    /**
     * Invitation d'un agent via Email ou WhatsApp.
     */
    public function invite(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if (! $user->isGerantEntreprise()) {
            return response()->json(['message' => 'Accès réservé au gérant de l\'entreprise.'], 403);
        }

        $entrepriseId = $user->getEntrepriseId();

        $validated = $request->validate([
            'canal' => 'required|in:email,whatsapp,sms',
            'email' => 'required_if:canal,email|nullable|email',
            'telephone' => 'required_if:canal,whatsapp,sms|nullable|string',
        ]);

        $token = Str::random(40);
        $frontendUrl = config('app.frontend_url', 'https://rahma-delivery.com');
        $lienRegister = rtrim($frontendUrl, '/')."/agent/register-invite?token={$token}";

        $invitation = InvitationAgent::create([
            'entreprise_id' => $entrepriseId,
            'canal' => $validated['canal'],
            'email' => $validated['email'] ?? null,
            'telephone' => $validated['telephone'] ?? null,
            'token' => $token,
            'lien_invitation' => $lienRegister,
            'statut' => 'en_attente',
            'expires_at' => now()->addDays(7),
        ]);

        $whatsappUrl = null;
        if ($validated['canal'] === 'whatsapp' && ! empty($validated['telephone'])) {
            $phoneClean = preg_replace('/[^0-9]/', '', $validated['telephone']);
            $text = urlencode("Bonjour ! Vous avez été invité à rejoindre Rahma Delivery en tant qu'Agent GP. Cliquez sur ce lien pour activer votre compte : {$lienRegister}");
            $whatsappUrl = "https://wa.me/{$phoneClean}?text={$text}";
        }

        ActiviteEntrepriseService::log(
            $entrepriseId,
            $user->id,
            'agent.invitation_envoyee',
            "Invitation agent envoyée via {$validated['canal']} vers ".($validated['email'] ?? $validated['telephone']).'.'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Invitation créée avec succès !',
            'invitation' => $invitation,
            'whatsapp_link' => $whatsappUrl,
        ], 201);
    }

    /**
     * Inscription d'un agent via le token d'invitation.
     */
    public function registerWithToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'telephone' => 'required|string|unique:users,telephone',
            'email' => 'nullable|email|unique:users,email',
            'mot_de_passe' => 'required|string|min:6',
        ]);

        $invitation = InvitationAgent::where('token', $validated['token'])->first();

        if (! $invitation || $invitation->isExpired()) {
            return response()->json(['message' => 'Lien d\'invitation invalide ou expiré.'], 400);
        }

        $agent = DB::transaction(function () use ($validated, $invitation) {
            $agentUser = User::create([
                'nom' => $validated['nom'],
                'prenom' => $validated['prenom'],
                'telephone' => $validated['telephone'],
                'email' => $validated['email'] ?? $invitation->email,
                'mot_de_passe' => $validated['mot_de_passe'],
                'statut' => 'actif',
            ]);

            $roleAgent = Role::firstOrCreate(['name' => 'agent_gp', 'guard_name' => 'api']);
            $agentUser->assignRole($roleAgent);

            $agentGp = AgentGp::create([
                'user_id' => $agentUser->id,
                'entreprise_id' => $invitation->entreprise_id,
                'matricule' => 'AG-'.strtoupper(Str::random(6)),
                'statut' => 'actif',
                'date_adhesion' => now(),
                'date_activation' => now(),
            ]);

            $invitation->update(['statut' => 'acceptee']);

            ActiviteEntrepriseService::log(
                $invitation->entreprise_id,
                $agentUser->id,
                'agent.inscription_terminee',
                "L'agent {$agentUser->prenom} {$agentUser->nom} a finalisé son inscription suite à l'invitation."
            );

            return $agentGp->load('user');
        });

        $token = auth('api')->login($agent->user);

        return response()->json([
            'status' => 'success',
            'message' => 'Compte Agent GP activé avec succès.',
            'access_token' => $token,
            'token_type' => 'bearer',
            'agent' => $agent,
        ], 201);
    }

    /**
     * Modification du statut d'un agent GP.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $entrepriseId = $user->getEntrepriseId();

        $agent = AgentGp::where('entreprise_id', $entrepriseId)->findOrFail($id);

        $validated = $request->validate([
            'statut' => 'required|in:en_attente,actif,indisponible,en_voyage,desactive',
        ]);

        $updateData = ['statut' => $validated['statut']];

        if ($validated['statut'] === 'actif' && empty($agent->date_activation)) {
            $updateData['date_activation'] = now();
        } elseif ($validated['statut'] === 'desactive') {
            $updateData['date_desactivation'] = now();
        }

        $agent->update($updateData);

        ActiviteEntrepriseService::log(
            $entrepriseId,
            $user->id,
            'agent.statut_modifie',
            "Le statut de l'agent {$agent->user->prenom} {$agent->user->nom} a été mis à jour vers {$validated['statut']}."
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Statut de l\'agent mis à jour.',
            'agent' => $agent->fresh('user'),
        ]);
    }

    /**
     * Suppression (Soft Delete) d'un agent GP.
     */
    public function destroy(string $id): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if (! $user->isGerantEntreprise()) {
            return response()->json(['message' => 'Accès réservé au gérant.'], 403);
        }

        $entrepriseId = $user->getEntrepriseId();
        $agent = AgentGp::where('entreprise_id', $entrepriseId)->findOrFail($id);

        $agentName = "{$agent->user->prenom} {$agent->user->nom}";
        $agent->delete();

        ActiviteEntrepriseService::log(
            $entrepriseId,
            $user->id,
            'agent.supprime',
            "L'agent GP {$agentName} a été désactivé/retiré de l'entreprise."
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Agent GP retiré avec succès.',
        ]);
    }
}
