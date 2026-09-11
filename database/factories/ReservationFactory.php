<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Reservation;
use App\Models\Voyage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero' => 'RES-'.strtoupper(Str::random(8)),
            'voyage_id' => Voyage::factory(),
            'client_id' => Client::factory(),
            'montant_total' => $this->faker->randomFloat(2, 10, 500),
            'mode_paiement_souhaite' => 'livraison',
            'statut' => 'en_attente',
            'date_demande' => now(),
        ];
    }

    public function acceptee(): static
    {
        return $this->state(['statut' => 'acceptee', 'date_acceptation' => now()]);
    }
}
