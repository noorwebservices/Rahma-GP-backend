<?php

namespace Database\Seeders;

use App\Models\Evaluation;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;

class EvaluationSeeder extends Seeder
{
    public function run(): void
    {
        $res1 = Reservation::where('numero', 'RES-2026-001')->first();
        $amadou = User::where('email', 'amadou.sow@example.com')->first();
        $cheikh = User::where('email', 'cheikh.fall@example.com')->first();

        // Evaluation 1 : Client Amadou -> Voyageur Cheikh
        Evaluation::firstOrCreate(
            [
                'reservation_id' => $res1->id,
                'evaluateur_id' => $amadou->id,
            ],
            [
                'evalue_id' => $cheikh->id,
                'note' => 5,
                'commentaire' => 'Voyageur très disponible et poli. Prise en charge parfaite !',
            ]
        );

        // Evaluation 2 : Voyageur Cheikh -> Client Amadou
        Evaluation::firstOrCreate(
            [
                'reservation_id' => $res1->id,
                'evaluateur_id' => $cheikh->id,
            ],
            [
                'evalue_id' => $amadou->id,
                'note' => 5,
                'commentaire' => 'Client très ponctuel et réactif.',
            ]
        );
    }
}
