<?php

namespace Database\Seeders;

use App\Models\Reservation;
use App\Models\Revenus_voyageur;
use App\Models\User;
use App\Models\Voyageur;
use Illuminate\Database\Seeder;

class RevenusVoyageurSeeder extends Seeder
{
    public function run(): void
    {
        $res1 = Reservation::where('numero', 'RES-2026-001')->first();
        $user1 = User::where('email', 'cheikh.fall@example.com')->first();
        $voyageur1 = Voyageur::where('user_id', $user1->id)->first();

        Revenus_voyageur::firstOrCreate(
            ['reservation_id' => $res1->id],
            [
                'voyageur_id' => $voyageur1->id,
                'montant' => 135.00,
                'statut' => 'disponible',
            ]
        );
    }
}
