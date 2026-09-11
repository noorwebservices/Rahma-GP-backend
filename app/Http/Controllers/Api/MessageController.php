<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MessageController extends Controller
{
    public function indexByReservation(Request $request, Reservation $reservation): AnonymousResourceCollection|JsonResponse
    {
        $user = $request->user();

        if (! $this->userIsParticipant($user, $reservation)) {
            return response()->json([
                'message' => 'Accès non autorisé aux messages de cette réservation.',
            ], 403);
        }

        // Marquer les messages reçus non lus comme lus (sauf si mark_read === 'false' ou '0')
        $markReadParam = strtolower((string) $request->query('mark_read', 'true'));
        $shouldMarkRead = ! in_array($markReadParam, ['false', '0', 'no', 'off'], true);

        if ($shouldMarkRead) {
            Message::where('reservation_id', $reservation->id)
                ->where('destinataire_id', $user->id)
                ->where('est_lu', false)
                ->update([
                    'est_lu' => true,
                    'date_heure_lecture' => now(),
                ]);
        }

        $messages = Message::where('reservation_id', $reservation->id)
            ->with(['expediteur', 'destinataire'])
            ->orderBy('date_heure_envoi', 'asc')
            ->paginate(50);

        return MessageResource::collection($messages);
    }

    public function store(StoreMessageRequest $request, Reservation $reservation): JsonResponse
    {
        $user = $request->user();
        $destinataireId = $this->resolveDestinataireId($user, $reservation);

        if (! $destinataireId) {
            return response()->json([
                'message' => 'Seuls le client et le voyageur associés à cette réservation peuvent échanger des messages.',
            ], 403);
        }

        $message = Message::create([
            'reservation_id' => $reservation->id,
            'expediteur_id' => $user->id,
            'destinataire_id' => $destinataireId,
            'contenu' => $request->contenu,
            'piece_jointe' => $request->piece_jointe ?? null,
            'est_lu' => false,
            'date_heure_envoi' => now(),
        ]);

        return response()->json([
            'message' => 'Message envoyé avec succès.',
            'data' => new MessageResource($message->load(['expediteur', 'destinataire'])),
        ], 201);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = Message::where('destinataire_id', $user->id)
            ->where('est_lu', false)
            ->count();

        return response()->json([
            'status' => 'success',
            'unread_count' => $count,
        ]);
    }

    private function userIsParticipant($user, Reservation $reservation): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->client && $reservation->client_id === $user->client->id) {
            return true;
        }

        if ($user->voyageur && $reservation->voyage->voyageur_id === $user->voyageur->id) {
            return true;
        }

        return false;
    }

    private function resolveDestinataireId($user, Reservation $reservation): ?string
    {
        if ($user->client && $reservation->client_id === $user->client->id) {
            return $reservation->voyage->voyageur->user_id;
        }

        if ($user->voyageur && $reservation->voyage->voyageur_id === $user->voyageur->id) {
            return $reservation->client->user_id;
        }

        if ($user->hasRole('admin')) {
            return $reservation->client->user_id;
        }

        return null;
    }
}
