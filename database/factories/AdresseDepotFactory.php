<?php

namespace Database\Factories;

use App\Models\Adresse_depot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Adresse_depot>
 */
class AdresseDepotFactory extends Factory
{
    protected $model = Adresse_depot::class;

    public function definition(): array
    {
        return [
            'adresse' => $this->faker->streetAddress(),
            'ville' => $this->faker->city(),
            'pays' => $this->faker->country(),
        ];
    }
}
