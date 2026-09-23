<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEntrepriseAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $requiredRole = null): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $entrepriseId = $user->getEntrepriseId();

        if (! $entrepriseId) {
            return response()->json(['message' => 'Accès refusé. Aucun compte entreprise associé.'], 403);
        }

        if ($requiredRole === 'gerant' && ! $user->isGerantEntreprise()) {
            return response()->json(['message' => 'Accès réservé au gérant de l\'entreprise.'], 403);
        }

        if ($requiredRole === 'agent' && ! $user->isAgentGp()) {
            return response()->json(['message' => 'Accès réservé aux agents GP de l\'entreprise.'], 403);
        }

        // Attacher l'entreprise ID à la requête
        $request->merge(['entreprise_id' => $entrepriseId]);

        return $next($request);
    }
}
