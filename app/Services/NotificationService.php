<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Créer et enregistrer une notification pour un utilisateur.
     */
    public static function send(User|string $userOrId, string $titre, string $contenu, string $type = 'general'): Notification
    {
        $userId = $userOrId instanceof User ? $userOrId->id : $userOrId;

        return Notification::create([
            'user_id' => $userId,
            'titre' => $titre,
            'contenu' => $contenu,
            'type' => $type,
            'date_envoi' => now(),
            'lu' => false,
        ]);
    }

    /**
     * Envoie une notification à tous les administrateurs.
     */
    public static function notifyAdmins(string $titre, string $contenu, string $type = 'admin'): void
    {
        User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get()->each(function (User $admin) use ($titre, $contenu, $type) {
            self::send($admin->id, $titre, $contenu, $type);
        });
    }
}
