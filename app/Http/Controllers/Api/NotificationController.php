<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        // Suppression automatique des notifications datant de plus de 3 jours
        Notification::where('created_at', '<', now()->subDays(3))
            ->orWhere('date_envoi', '<', now()->subDays(3))
            ->delete();

        $user = $request->user();

        $query = Notification::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('created_at', '>=', now()->subDays(3))
                  ->orWhere('date_envoi', '>=', now()->subDays(3));
            });

        if ($request->query('unread') === 'true' || $request->query('unread') === '1' || $request->boolean('unread')) {
            $query->where('lu', false);
        }

        $notifications = $query->latest('date_envoi')->paginate(20);

        return NotificationResource::collection($notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        // Suppression automatique des notifications datant de plus de 3 jours
        Notification::where('created_at', '<', now()->subDays(3))
            ->orWhere('date_envoi', '<', now()->subDays(3))
            ->delete();

        $user = $request->user();

        $count = Notification::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('created_at', '>=', now()->subDays(3))
                  ->orWhere('date_envoi', '>=', now()->subDays(3));
            })
            ->where('lu', false)
            ->count();

        return response()->json([
            'status' => 'success',
            'unread_count' => $count,
        ]);
    }

    public function marquerLue(Request $request, Notification $notification): JsonResponse
    {
        $user = $request->user();

        if ($notification->user_id !== $user->id) {
            return response()->json([
                'message' => 'Accès non autorisé à cette notification.',
            ], 403);
        }

        $notification->update(['lu' => true]);

        return response()->json([
            'message' => 'Notification marquée comme lue.',
            'data' => new NotificationResource($notification),
        ]);
    }

    public function marquerToutesLues(Request $request): JsonResponse
    {
        $user = $request->user();

        Notification::where('user_id', $user->id)
            ->where('lu', false)
            ->update(['lu' => true]);

        return response()->json([
            'message' => 'Toutes vos notifications ont été me comme lues.',
        ]);
    }
}
