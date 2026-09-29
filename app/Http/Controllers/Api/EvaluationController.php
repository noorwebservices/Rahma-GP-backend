<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEvaluationRequest;
use App\Http\Resources\EvaluationResource;
use App\Models\Evaluation;
use App\Models\Reservation;
use App\Models\Voyageur;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EvaluationController extends Controller
{
    public function store(StoreEvaluationRequest $request, Reservation $reservation): JsonResponse
    {
        $user = $request->user();

        if (! $user->client || $reservation->client_id !== $user->client->id) {
            return response()->json([
                'message' => 'Seul le client ayant effectué la réservation peut laisser une évaluation.',
            ], 403);
        }

        if (in_array($reservation->statut, ['en_attente', 'refusee', 'annulee'])) {
            return response()->json([
                'message' => 'Vous ne pouvez évaluer une réservation qu\'une fois celle-ci acceptée ou effectuée.',
            ], 422);
        }

        $dejaEvalue = Evaluation::where('reservation_id', $reservation->id)
            ->where('evaluateur_id', $user->id)
            ->exists();

        if ($dejaEvalue) {
            return response()->json([
                'message' => 'Vous avez déjà soumis une évaluation pour cette réservation.',
            ], 422);
        }

        $voyage = $reservation->voyage;
        $evalueId = null;

        if ($voyage && $voyage->voyageur) {
            $evalueId = $voyage->voyageur->user_id;
        } elseif ($voyage && $voyage->entreprise) {
            $evalueId = $voyage->entreprise->gerant_user_id;
        } elseif ($voyage && $voyage->agentGp) {
            $evalueId = $voyage->agentGp->user_id;
        } elseif ($reservation->entreprise) {
            $evalueId = $reservation->entreprise->gerant_user_id;
        } elseif ($reservation->agentGp) {
            $evalueId = $reservation->agentGp->user_id;
        }

        if (! $evalueId) {
            return response()->json([
                'message' => 'Impossible de déterminer l\'utilisateur destinataire de l\'évaluation.',
            ], 422);
        }

        $evaluation = Evaluation::create([
            'reservation_id' => $reservation->id,
            'evaluateur_id' => $user->id,
            'evalue_id' => $evalueId,
            'note' => $request->note,
            'commentaire' => $request->commentaire ?? null,
        ]);

        NotificationService::send(
            $evalueId,
            'Nouvelle évaluation reçue',
            sprintf('Vous avez reçu une évaluation de %d/5 étoiles de la part de %s %s pour la réservation %s.', $request->note, $user->prenom, $user->nom, $reservation->numero),
            'evaluation'
        );

        return response()->json([
            'message' => 'Évaluation enregistrée avec succès.',
            'data' => new EvaluationResource($evaluation->load(['evaluateur', 'evalue'])),
        ], 201);
    }

    public function indexForVoyageur(string $voyageur_id): JsonResponse
    {
        $voyageur = Voyageur::find($voyageur_id);
        $userId = $voyageur ? $voyageur->user_id : $voyageur_id;

        $query = Evaluation::query()
            ->where(function ($q) use ($voyageur_id, $userId) {
                $q->where('evalue_id', $userId)
                  ->orWhereHas('reservation', function ($rq) use ($voyageur_id, $userId) {
                      $rq->where('entreprise_id', $voyageur_id)
                         ->orWhere('agent_gp_id', $voyageur_id)
                         ->orWhereHas('voyage', function ($vq) use ($voyageur_id, $userId) {
                             $vq->where('entreprise_id', $voyageur_id)
                                ->orWhere('agent_gp_id', $voyageur_id)
                                ->orWhere('voyageur_id', $voyageur_id);
                         });
                  });
            })
            ->with(['evaluateur', 'evalue']);

        $moyenne = (float) ($query->avg('note') ?? 0);
        $total = $query->count();
        $evaluations = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'moyenne_notes' => round($moyenne, 2),
            'total_evaluations' => $total,
            'data' => EvaluationResource::collection($evaluations)->response()->getData(true)['data'],
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = Evaluation::query();

        if ($user->entreprise) {
            $entreprise = $user->entreprise;
            $agentUserIds = $entreprise->agents()->pluck('user_id')->filter()->toArray();
            $allowedUserIds = array_unique(array_merge([$user->id], $agentUserIds));

            $query->where(function ($q) use ($user, $entreprise, $allowedUserIds) {
                $q->whereIn('evalue_id', $allowedUserIds)
                  ->orWhere('evaluateur_id', $user->id)
                  ->orWhereHas('reservation', function ($rq) use ($entreprise) {
                      $rq->where('entreprise_id', $entreprise->id)
                         ->orWhereHas('voyage', function ($vq) use ($entreprise) {
                             $vq->where('entreprise_id', $entreprise->id);
                         });
                  });
            });
        } elseif ($user->agentGp) {
            $agentGp = $user->agentGp;
            $query->where(function ($q) use ($user, $agentGp) {
                $q->where('evalue_id', $user->id)
                  ->orWhere('evaluateur_id', $user->id)
                  ->orWhereHas('reservation', function ($rq) use ($agentGp) {
                      $rq->where('agent_gp_id', $agentGp->id)
                         ->orWhereHas('voyage', function ($vq) use ($agentGp) {
                             $vq->where('agent_gp_id', $agentGp->id);
                         });
                  });
            });
        } else {
            $query->where('evaluateur_id', $user->id)
                  ->orWhere('evalue_id', $user->id);
        }

        $evaluations = $query->with(['evaluateur', 'evalue', 'reservation.voyage'])
            ->latest()
            ->paginate(20);

        return EvaluationResource::collection($evaluations);
    }
}
