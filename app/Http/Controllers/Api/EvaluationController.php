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

        $evalueId = $reservation->voyage->voyageur->user_id;

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

        $query = Evaluation::where('evalue_id', $userId)->with(['evaluateur', 'evalue']);

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

        $evaluations = Evaluation::where('evaluateur_id', $user->id)
            ->orWhere('evalue_id', $user->id)
            ->with(['evaluateur', 'evalue'])
            ->latest()
            ->paginate(15);

        return EvaluationResource::collection($evaluations);
    }
}
