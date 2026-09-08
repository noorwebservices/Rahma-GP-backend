<?php

namespace Database\Seeders;

use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaiementSeeder extends Seeder
{
    public function run(): void
    {
        $res1 = Reservation::where('numero', 'RES-2026-001')->first();
        $admin = User::where('email', 'hapsatou.thiam@example.com')->first();

        Paiement::firstOrCreate(
            ['reservation_id' => $res1->id],
            [
                'montant' => 150.00,
                'reference' => 'WAVE-TX-99887766',
                'mode_paiement' => 'wave',
                'statut' => 'reussi',
                'date_paiement' => now()->subDays(1),
                'confirme_par' => $admin->id,
            ]
        );
    }
}
