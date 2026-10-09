<?php

namespace Database\Factories;

use App\Models\Attestation;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attestation>
 */
class AttestationFactory extends Factory
{
    public function definition(): array
    {
        $dateDebut = fake()->dateTimeBetween('-1 year', '-1 month');

        return [
            'mission_id' => Mission::factory(),
            // Par défaut, l'attestation suit le consultant de la mission
            'consultant_id' => fn (array $attributes) => Mission::findOrFail($attributes['mission_id'])->consultant_id,
            'fonction' => fake()->jobTitle(),
            'date_attestation' => fake()->dateTimeBetween($dateDebut, 'now'),
            'date_signature' => fake()->dateTimeBetween($dateDebut, 'now'),
            'date_debut' => $dateDebut,
            'date_fin' => fake()->dateTimeBetween($dateDebut, 'now'),
            'client' => fake()->company(),
        ];
    }

    public function aVenir(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_attestation' => now()->addDay(),
            'date_signature' => now()->addDay(),
            'date_debut' => now()->addDay(),
            'date_fin' => now()->addMonths(3),
        ]);
    }
}
