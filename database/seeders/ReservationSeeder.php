<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Voyage;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $userClient1 = User::where('email', 'amadou.sow@example.com')->first();
        $client1 = Client::where('user_id', $userClient1->id)->first();

        $userClient2 = User::where('email', 'fatou.diallo@example.com')->first();
        $client2 = Client::where('user_id', $userClient2->id)->first();

        $voyage1 = Voyage::where('ville_depart', 'Paris')->first();
        $voyage2 = Voyage::where('ville_depart', 'Dakar')->first();

        // Réservation 1 : Amadou Sow réserve 10kg sur le voyage Paris -> Dakar
        Reservation::firstOrCreate(
            ['numero' => 'RES-2026-001'],
            [
                'voyage_id' => $voyage1->id,
                'client_id' => $client1->id,
                'montant_total' => 150.00,
                'mode_paiement_souhaite' => 'wave',
                'statut' => 'acceptee',
                'date_demande' => now()->subDays(2),
                'date_acceptation' => now()->subDays(1),
            ]
        );

        // Réservation 2 : Fatou Diallo réserve 10kg sur le voyage Dakar -> Abidjan
        Reservation::firstOrCreate(
            ['numero' => 'RES-2026-002'],
            [
                'voyage_id' => $voyage2->id,
                'client_id' => $client2->id,
                'montant_total' => 50000.00,
                'mode_paiement_souhaite' => 'espece_depot',
                'statut' => 'en_attente',
                'date_demande' => now()->subDays(1),
            ]
        );
    }
}
