<?php

namespace Database\Seeders;

use App\Models\Colis;
use App\Models\Reservation;
use Illuminate\Database\Seeder;

class ColisSeeder extends Seeder
{
    public function run(): void
    {
        $res1 = Reservation::where('numero', 'RES-2026-001')->first();
        $res2 = Reservation::where('numero', 'RES-2026-002')->first();

        // Colis 1 pour Amadou Sow
        Colis::firstOrCreate(
            ['numero_suivi' => 'SUIVI-AMADOU-001'],
            [
                'reservation_id' => $res1->id,
                'type' => 'Vêtements et Chaussures',
                'photo' => 'uploads/colis/vetements_amadou.jpg',
                'description' => 'Valise de vêtements pour la famille et cadeaux de mariages.',
                'valeur_estimee' => 250.00,
                'poids' => 10.0,
                'est_fragile' => true,
                'destinataire_nom' => 'Sow',
                'destinataire_prenom' => 'Ousmane',
                'destinataire_numero' => '+221773334455',
                'destinataire_adresse' => 'Quartier Liberté 6, Dakar, Sénégal',
                'statut' => 'colis_depose',
                'date_depot' => now()->subHours(12),
            ]
        );

        // Colis 2 pour Fatou Diallo
        Colis::firstOrCreate(
            ['numero_suivi' => 'SUIVI-FATOU-002'],
            [
                'reservation_id' => $res2->id,
                'type' => 'Cosmétiques & Beauté',
                'photo' => 'uploads/colis/cosmetiques_fatou.jpg',
                'description' => 'Gamme de soins capillaires et savons naturels.',
                'valeur_estimee' => 150000.00,
                'poids' => 10.0,
                'est_fragile' => false,
                'destinataire_nom' => 'Diallo',
                'destinataire_prenom' => 'Aminata',
                'destinataire_numero' => '+225070000000',
                'destinataire_adresse' => 'Cocody Angré, Abidjan, Côte d\'Ivoire',
                'statut' => 'demande_envoyee',
            ]
        );
    }
}
