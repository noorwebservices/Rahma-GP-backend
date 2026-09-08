<?php

namespace Database\Seeders;

use App\Models\Adresse_depot;
use Illuminate\Database\Seeder;

class AdresseDepotSeeder extends Seeder
{
    public function run(): void
    {
        Adresse_depot::firstOrCreate(
            ['adresse' => '15 Rue de Rivoli'],
            [
                'ville' => 'Paris',
                'pays' => 'France',
                'horaire_ouverture' => '09h00 - 18h00 du lundi au samedi',
                'instructions' => 'Déposer au comptoir Relais Rahma avec le code de réservation.',
                'latitude' => 48.856614,
                'longitude' => 2.352221,
            ]
        );

        Adresse_depot::firstOrCreate(
            ['adresse' => 'Avenue Cheikh Anta Diop'],
            [
                'ville' => 'Dakar',
                'pays' => 'Sénégal',
                'horaire_ouverture' => '08h00 - 20h00 7j/7',
                'instructions' => 'En face de l\'Université UCAD, agence Rahma Express.',
                'latitude' => 14.693700,
                'longitude' => -17.444100,
            ]
        );
    }
}
