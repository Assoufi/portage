<?php

namespace Database\Factories;

use App\Models\Commande;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commande>
 */
class CommandeFactory extends Factory
{
    public function definition(): array
    {
        $dateCommande = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'mission_id' => Mission::factory(),
            'num_commande' => 'CMD-'.date('Y').'-'.fake()->unique()->numerify('####'),
            'date_commande' => $dateCommande,
            'date_debut' => $dateCommande,
            'date_fin' => fake()->dateTimeBetween('+1 day', '+6 months'),
            'quantite' => fake()->numberBetween(1, 30),
            'tjm' => fn (array $attributes) => Mission::findOrFail($attributes['mission_id'])->tjm,
            'statut' => 'ACTIF',
        ];
    }

    public function inactif(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'INACTIF',
        ]);
    }
}
