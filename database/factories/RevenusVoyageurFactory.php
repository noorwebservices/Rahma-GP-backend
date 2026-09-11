<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Revenus_voyageur;
use App\Models\Voyageur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Revenus_voyageur>
 */
class RevenusVoyageurFactory extends Factory
{
    protected $model = Revenus_voyageur::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'voyageur_id' => Voyageur::factory(),
            'reservation_id' => Reservation::factory(),
            'montant' => $this->faker->randomFloat(2, 10, 500),
            'statut' => 'disponible',
        ];
    }

    public function retire(): static
    {
        return $this->state(['statut' => 'retire']);
    }
}
