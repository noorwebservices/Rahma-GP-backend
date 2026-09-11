<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'titre' => $this->faker->sentence(3),
            'contenu' => $this->faker->sentence(10),
            'type' => $this->faker->randomElement(['reservation', 'colis', 'message', 'paiement', 'general']),
            'date_envoi' => now(),
            'lu' => false,
        ];
    }

    public function lue(): static
    {
        return $this->state(['lu' => true]);
    }
}
