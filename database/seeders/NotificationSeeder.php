<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $amadou = User::where('email', 'amadou.sow@example.com')->first();
        $cheikh = User::where('email', 'cheikh.fall@example.com')->first();

        // Notification 1 : Amadou Sow
        Notification::create([
            'user_id' => $amadou->id,
            'titre' => 'Réservation acceptée',
            'contenu' => 'Cheikh Fall a accepté votre réservation RES-2026-001.',
            'type' => 'reservation',
            'date_envoi' => now()->subDays(1),
            'lu' => true,
        ]);

        // Notification 2 : Cheikh Fall
        Notification::create([
            'user_id' => $cheikh->id,
            'titre' => 'Colis déposé au relais',
            'contenu' => 'Le colis SUIVI-AMADOU-001 a été déposé au Relais Dépôt Paris.',
            'type' => 'colis',
            'date_envoi' => now()->subHours(12),
            'lu' => false,
        ]);
    }
}
