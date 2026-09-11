<?php

namespace Database\Factories;

use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Paiement>
 */
class PaiementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'montant' => $this->faker->randomFloat(2, 10, 500),
            'reference' => 'PAY-'.strtoupper(Str::random(10)),
            'mode_paiement' => 'livraison',
            'statut' => 'reussi',
            'date_paiement' => now(),
            'confirme_par' => User::factory(),
        ];
    }

    public function enAttente(): static
    {
        return $this->state(['statut' => 'en_attente', 'date_paiement' => null]);
    }
}
