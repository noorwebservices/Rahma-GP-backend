<?php

namespace Database\Factories;

use App\Models\Adresse_depot;
use App\Models\Adresse_recuperation;
use App\Models\Voyage;
use App\Models\Voyageur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voyage>
 */
class VoyageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'voyageur_id' => Voyageur::factory(),
            'adresse_depot_id' => Adresse_depot::factory(),
            'adresse_recuperation_id' => Adresse_recuperation::factory(),
            'ville_depart' => $this->faker->city(),
            'ville_destination' => $this->faker->city(),
            'pays_depart' => $this->faker->countryCode(),
            'pays_destination' => $this->faker->countryCode(),
            'date_depart' => now()->addDays(5),
            'date_arrivee' => now()->addDays(10),
            'capacite_totale' => 30,
            'capacite_dispo' => 30,
            'prix_kg' => 5.00,
            'prix_objet' => 0,
            'devise' => 'EUR',
            'statut' => 'publie',
        ];
    }
}
