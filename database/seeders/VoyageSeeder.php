<?php

namespace Database\Seeders;

use App\Models\Adresse_depot;
use App\Models\Adresse_recuperation;
use App\Models\User;
use App\Models\Voyage;
use App\Models\Voyageur;
use Illuminate\Database\Seeder;

class VoyageSeeder extends Seeder
{
    public function run(): void
    {
        $user1 = User::where('email', 'cheikh.fall@example.com')->first();
        $voyageur1 = Voyageur::where('user_id', $user1->id)->first();

        $user2 = User::where('email', 'moussa.ndiaye@example.com')->first();
        $voyageur2 = Voyageur::where('user_id', $user2->id)->first();

        $depotParis = Adresse_depot::where('ville', 'Paris')->first();
        $depotDakar = Adresse_depot::where('ville', 'Dakar')->first();

        $recupDakar = Adresse_recuperation::where('ville', 'Dakar')->first();
        $recupAbidjan = Adresse_recuperation::where('ville', 'Abidjan')->first();

        // Voyage 1 : Paris -> Dakar par Cheikh Fall
        Voyage::firstOrCreate(
            [
                'voyageur_id' => $voyageur1->id,
                'pays_depart' => 'France',
                'ville_depart' => 'Paris',
                'pays_destination' => 'Sénégal',
                'ville_destination' => 'Dakar',
            ],
            [
                'adresse_depot_id' => $depotParis->id,
                'adresse_recuperation_id' => $recupDakar->id,
                'date_depart' => now()->addDays(3)->setHour(14)->setMinute(0),
                'date_arrivee' => now()->addDays(4)->setHour(18)->setMinute(30),
                'capacite_totale' => 30.0,
                'capacite_dispo' => 20.0,
                'prix_kg' => 15.00,
                'prix_objet' => 25.00,
                'devise' => 'EUR',
                'description' => 'Voyage direct Paris-Dakar avec Air Sénégal. Bagages sécurisés.',
                'objets_autorises' => ['Vêtements', 'Chaussures', 'Cosmétiques', 'Documents'],
                'objets_interdits' => ['Liquides dangereux', 'Batteries lithium non scellées', 'Armes'],
                'statut' => 'publie',
            ]
        );

        // Voyage 2 : Dakar -> Abidjan par Moussa Ndiaye
        Voyage::firstOrCreate(
            [
                'voyageur_id' => $voyageur2->id,
                'pays_depart' => 'Sénégal',
                'ville_depart' => 'Dakar',
                'pays_destination' => 'Côte d\'Ivoire',
                'ville_destination' => 'Abidjan',
            ],
            [
                'adresse_depot_id' => $depotDakar->id,
                'adresse_recuperation_id' => $recupAbidjan->id,
                'date_depart' => now()->addDays(5)->setHour(10)->setMinute(0),
                'date_arrivee' => now()->addDays(6)->setHour(15)->setMinute(0),
                'capacite_totale' => 50.0,
                'capacite_dispo' => 40.0,
                'prix_kg' => 5000.00,
                'prix_objet' => 10000.00,
                'devise' => 'XOF',
                'description' => 'Voyage professionnel Dakar-Abidjan, livraison rapide sous 48h.',
                'objets_autorises' => ['Vêtements', 'Produits locaux', 'Électronique sous emballage'],
                'objets_interdits' => ['Produits périssables sans emballage isotherme', 'Produits illégaux'],
                'statut' => 'publie',
            ]
        );
    }
}
