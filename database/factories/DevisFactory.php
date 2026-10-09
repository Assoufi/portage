<?php

namespace Database\Factories;

use App\Models\Devis;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Devis>
 */
class DevisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mission_id' => Mission::factory(),
            // Le devis suit par défaut le client et le fournisseur de la mission
            'client_id' => fn (array $attributes) => Mission::findOrFail($attributes['mission_id'])->client_id,
            'fournisseur_id' => fn (array $attributes) => Mission::findOrFail($attributes['mission_id'])->fournisseur_id,
            'numero_devis' => 'DEV-'.date('Y').'-'.fake()->unique()->numerify('####'),
            'date_devis' => fake()->dateTimeBetween('-1 year', 'now'),
            'description' => fake()->sentence(),
            'quantite' => fake()->numberBetween(1, 10),
            'prix_unitaire' => fake()->randomFloat(2, 500, 20000),
        ];
    }
}
