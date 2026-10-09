<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->company(),
            'adresse' => fake()->address(),
            'email' => fake()->companyEmail(),
            'type_identification' => 'ICE',
            'num_identification' => fake()->unique()->numerify('###############'),
            'tva' => 20.00,
            'devise' => 'MAD',
            'statut' => true,
            'periodicite' => fake()->randomElement(['Mensuelle', 'Occasionnelle']),
            'mode_livraison' => fake()->randomElements(['Papier', 'Email', 'Whatsapp'], fake()->numberBetween(1, 3)),
            'telephone' => fake()->numerify('06########'),
            'notifyto' => fake()->companyEmail(),
            'notifycc' => fake()->companyEmail(),
        ];
    }

    public function inactif(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => false,
        ]);
    }
}
