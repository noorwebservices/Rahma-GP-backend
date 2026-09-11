<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'telephone' => fake()->unique()->numerify('+2217########'),
            'email' => fake()->unique()->safeEmail(),
            'mot_de_passe' => static::$password ??= Hash::make('password'),
            'statut' => 'actif',
            'remember_token' => Str::random(10),
        ];
    }
}
