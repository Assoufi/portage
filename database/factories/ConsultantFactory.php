<?php

namespace Database\Factories;

use App\Models\Consultant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consultant>
 */
class ConsultantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->name(),
            'cin' => strtoupper(fake()->bothify('??#########')),
            'fonction' => fake()->jobTitle(),
            'email' => fake()->unique()->safeEmail(),
            'tel' => fake()->numerify('06########'),
            'rib' => fake()->iban('FR'),
            'mode_paiement' => 'virement',
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
