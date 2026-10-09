<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Consultant;
use App\Models\Fournisseur;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mission>
 */
class MissionFactory extends Factory
{
    public function definition(): array
    {
        $dateDebut = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'consultant_id' => Consultant::factory(),
            'client_id' => Client::factory(),
            'fournisseur_id' => Fournisseur::factory(),
            'titre' => fake()->sentence(3),
            'formule' => fake()->randomElement(['Forfait', 'TJM', 'Régie']),
            'taux' => fake()->randomFloat(2, 10, 100),
            'tjm' => fake()->randomFloat(2, 500, 1500),
            'prix_vente' => fake()->randomFloat(2, 5000, 50000),
            'date_debut' => $dateDebut,
            'date_fin' => fake()->dateTimeBetween($dateDebut, '+6 months'),
            'delai_paiement' => 30,
            'remarques' => null,
        ];
    }

    public function enCours(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_debut' => now()->subMonth(),
            'date_fin' => now()->addMonth(),
        ]);
    }
}
