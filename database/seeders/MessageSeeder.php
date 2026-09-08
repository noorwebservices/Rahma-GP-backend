<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        $res1 = Reservation::where('numero', 'RES-2026-001')->first();
        $amadou = User::where('email', 'amadou.sow@example.com')->first();
        $cheikh = User::where('email', 'cheikh.fall@example.com')->first();

        // Message 1 : Amadou -> Cheikh
        Message::create([
            'reservation_id' => $res1->id,
            'expediteur_id' => $amadou->id,
            'destinataire_id' => $cheikh->id,
            'contenu' => 'Bonjour Cheikh, j\'ai effectué la réservation de 10kg pour transporter des vêtements vers Dakar.',
            'piece_jointe' => null,
            'est_lu' => true,
            'date_heure_envoi' => now()->subDays(2)->setHour(14)->setMinute(10),
            'date_heure_lecture' => now()->subDays(2)->setHour(14)->setMinute(25),
        ]);

        // Message 2 : Cheikh -> Amadou
        Message::create([
            'reservation_id' => $res1->id,
            'expediteur_id' => $cheikh->id,
            'destinataire_id' => $amadou->id,
            'contenu' => 'Bonjour Amadou, c\'est bien reçu et la réservation est validée ! Vous pouvez déposer le colis au Relais Paris.',
            'piece_jointe' => null,
            'est_lu' => true,
            'date_heure_envoi' => now()->subDays(1)->setHour(10)->setMinute(0),
            'date_heure_lecture' => now()->subDays(1)->setHour(10)->setMinute(5),
        ]);
    }
}
