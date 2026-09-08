<?php

namespace Database\Seeders;

use App\Models\Adresse_recuperation;
use Illuminate\Database\Seeder;

class AdresseRecuperationSeeder extends Seeder
{
    public function run(): void
    {
        Adresse_recuperation::firstOrCreate(
            ['adresse' => 'Rue Carnot x Boulevard de la République'],
            [
                'ville' => 'Dakar',
                'pays' => 'Sénégal',
                'horaire_ouverture' => '08h30 - 19h30 du lundi au samedi',
                'instructions' => 'Se munir d\'une pièce d\'identité et du numéro de suivi.',
                'latitude' => 14.668100,
                'longitude' => -17.436100,
            ]
        );

        Adresse_recuperation::firstOrCreate(
            ['adresse' => 'Boulevard de France, Cocody'],
            [
                'ville' => 'Abidjan',
                'pays' => 'Côte d\'Ivoire',
                'horaire_ouverture' => '09h00 - 18h00 du lundi au samedi',
                'instructions' => 'Près de l\'allocodrome, Relais Rahma Abidjan.',
                'latitude' => 5.359900,
                'longitude' => -4.008300,
            ]
        );
    }
}
