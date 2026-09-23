<?php

namespace App\Http\Middleware;

use App\Models\Voyage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAgentAssignment
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        // Si l'utilisateur est le gérant, il a accès à tous les voyages de son entreprise
        if ($user->isGerantEntreprise()) {
            return $next($request);
        }

        $agentGp = $user->agentGp;

        if (! $agentGp) {
            return response()->json(['message' => 'Accès refusé. Profil agent non trouvé.'], 403);
        }

        $voyageId = $request->route('voyage') ?? $request->route('id') ?? $request->input('voyage_id');

        if ($voyageId) {
            $voyage = Voyage::find($voyageId);

            if ($voyage && $voyage->entreprise_id === $agentGp->entreprise_id) {
                if ($voyage->agent_gp_id !== $agentGp->id) {
                    return response()->json(['message' => 'Accès refusé. Ce voyage n\'est pas affecté à votre compte.'], 403);
                }
            }
        }

        return $next($request);
    }
}
