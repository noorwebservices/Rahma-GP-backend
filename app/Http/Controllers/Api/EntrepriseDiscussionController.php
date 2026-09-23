<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntrepriseDiscussionController extends Controller
{
    /**
     * Obtenir les messages d'une réservation pour l'agent ou le client.
     */
    public function getMessages(string $reservationId): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $entrepriseId = $user->getEntrepriseId();

        $reservation = Reservation::where('entreprise_id', $entrepriseId)
            ->orWhere('client_id', optional($user->client)->id)
            ->findOrFail($reservationId);

        $messages = Message::with(['expediteur', 'destinataire'])
            ->where('reservation_id', $reservation->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'reservation_id' => $reservation->id,
            'messages' => $messages,
        ]);
    }

    /**
     * Envoi d'un message par l'agent ou le client lié à la réservation.
     */
    public function sendMessage(Request $request, string $reservationId): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $entrepriseId = $user->getEntrepriseId();

        $reservation = Reservation::with(['client.user', 'agentGp.user'])
            ->where('entreprise_id', $entrepriseId)
            ->orWhere('client_id', optional($user->client)->id)
            ->findOrFail($reservationId);

        $validated = $request->validate([
            'contenu' => 'required|string',
        ]);

        // Déterminer le destinataire (si l'expéditeur est le client, le destinataire est l'agent ou gérant)
        $destinataireId = null;
        if ($user->id === $reservation->client->user_id) {
            $destinataireId = optional(optional($reservation->agentGp)->user)->id ?? $reservation->entreprise->gerant_user_id;
        } else {
            $destinataireId = $reservation->client->user_id;
        }

        $pieceJointePath = null;
        if ($request->hasFile('piece_jointe')) {
            $pieceJointePath = $request->file('piece_jointe')->store('messages/fichiers', 'public');
        }

        $message = Message::create([
            'reservation_id' => $reservation->id,
            'expediteur_id' => $user->id,
            'destinataire_id' => $destinataireId,
            'contenu' => $validated['contenu'],
            'piece_jointe' => $pieceJointePath,
            'est_lu' => false,
            'date_heure_envoi' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $message->load(['expediteur', 'destinataire']),
        ], 201);
    }

    /**
     * Vue Gérant : Consultation et supervision de l'ensemble des discussions de l'entreprise.
     */
    public function entrepriseDiscussions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if (! $user->isGerantEntreprise()) {
            return response()->json(['message' => 'Accès réservé au gérant.'], 403);
        }

        $entrepriseId = $user->getEntrepriseId();

        $reservations = Reservation::with([
            'client.user',
            'agentGp.user',
            'voyage',
            'messages' => function ($q) {
                $q->latest()->limit(1);
            },
        ])
            ->where('entreprise_id', $entrepriseId)
            ->whereHas('messages')
            ->get();

        return response()->json([
            'status' => 'success',
            'discussions' => $reservations,
        ]);
    }
}
