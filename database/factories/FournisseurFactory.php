<?php

namespace Database\Factories;

use App\Models\Fournisseur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fournisseur>
 */
class FournisseurFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->company(),
            'adresse' => fake()->address(),
            'ville' => fake()->city(),
            'email' => fake()->companyEmail(),
            'responsable' => fake()->name(),
            'ice' => fake()->unique()->numerify('###############'),
            'rib' => fake()->iban('FR'),
            'iban' => fake()->numerify('FR##################'),
            'taux' => 0.00,
            'statut' => true,
        ];
    }

    public function inactif(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => false,
        ]);
    }
}
