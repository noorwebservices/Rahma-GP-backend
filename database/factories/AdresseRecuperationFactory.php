<?php

namespace Database\Factories;

use App\Models\Adresse_recuperation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Adresse_recuperation>
 */
class AdresseRecuperationFactory extends Factory
{
    protected $model = Adresse_recuperation::class;

    public function definition(): array
    {
        return [
            'adresse' => $this->faker->streetAddress(),
            'ville' => $this->faker->city(),
            'pays' => $this->faker->country(),
        ];
    }
}
