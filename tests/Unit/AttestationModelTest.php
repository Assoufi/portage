<?php

namespace Tests\Unit;

use App\Models\Attestation;
use App\Models\Consultant;
use App\Models\Mission;
use Carbon\Carbon;
use Tests\TestCase;

class AttestationModelTest extends TestCase
{
    public function test_attestation_fillable_attributes_are_protected(): void
    {
        $attestation = new Attestation;

        foreach ([
            'consultant_id',
            'mission_id',
            'fonction',
            'date_attestation',
            'date_signature',
            'date_debut',
            'date_fin',
            'client',
        ] as $attribut) {
            $this->assertTrue($attestation->isFillable($attribut), "L'attribut {$attribut} devrait être fillable.");
        }

        $this->assertFalse($attestation->isFillable('id'), 'L\'identifiant ne devrait pas être fillable.');
        $this->assertFalse($attestation->isFillable('created_at'), 'Le timestamp de création ne devrait pas être fillable.');
    }

    public function test_date_attributes_are_cast_to_carbon_instances(): void
    {
        $attestation = new Attestation([
            'date_attestation' => '2026-01-15',
            'date_signature' => '2026-01-16',
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-01-31',
        ]);

        $this->assertInstanceOf(Carbon::class, $attestation->date_attestation);
        $this->assertInstanceOf(Carbon::class, $attestation->date_signature);
        $this->assertInstanceOf(Carbon::class, $attestation->date_debut);
        $this->assertInstanceOf(Carbon::class, $attestation->date_fin);

        $this->assertSame('2026-01-15', $attestation->date_attestation->format('Y-m-d'));
        $this->assertSame('2026-01-31', $attestation->date_fin->format('Y-m-d'));
    }

    public function test_date_mutators_parse_string_values(): void
    {
        $attestation = new Attestation;
        $attestation->date_debut = '2026-03-10';

        $this->assertInstanceOf(Carbon::class, $attestation->getAttributes()['date_debut']);
        $this->assertSame('10/03/2026', $attestation->date_debut_formattee);
    }

    public function test_fonction_and_client_mutators_trim_whitespace(): void
    {
        $attestation = new Attestation([
            'fonction' => '  Consultant Senior  ',
            'client' => '  ACME SARL  ',
        ]);

        $this->assertSame('Consultant Senior', $attestation->fonction);
        $this->assertSame('ACME SARL', $attestation->client);
    }

    public function test_duree_accessor_calculates_days_between_dates(): void
    {
        $attestation = new Attestation([
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-01-31',
        ]);

        $this->assertSame(30, $attestation->duree);
        $this->assertSame('30 jours', $attestation->duree_formatee);
    }

    public function test_duree_accessor_returns_null_when_dates_are_missing(): void
    {
        $attestation = new Attestation;

        $this->assertNull($attestation->duree);
        $this->assertSame('Non définie', $attestation->duree_formatee);
    }

    public function test_formatted_date_accessors_use_french_format(): void
    {
        $attestation = new Attestation([
            'date_attestation' => '2026-01-15',
            'date_signature' => '2026-01-16',
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-01-31',
        ]);

        $this->assertSame('15/01/2026', $attestation->date_attestation_formattee);
        $this->assertSame('16/01/2026', $attestation->date_signature_formattee);
        $this->assertSame('01/01/2026', $attestation->date_debut_formattee);
        $this->assertSame('31/01/2026', $attestation->date_fin_formattee);
    }

    public function test_relationships_are_defined(): void
    {
        $attestation = new Attestation;

        $this->assertTrue(method_exists($attestation, 'consultant'));
        $this->assertTrue(method_exists($attestation, 'mission'));
        $this->assertSame(Consultant::class, $attestation->consultant()->getRelated()::class);
        $this->assertSame(Mission::class, $attestation->mission()->getRelated()::class);
    }

    public function test_query_scopes_are_defined(): void
    {
        $attestation = new Attestation;

        foreach (['scopeParConsultant', 'scopeParMission', 'scopeParPeriode', 'scopeRecherche'] as $scope) {
            $this->assertTrue(method_exists($attestation, $scope), "Le scope {$scope} devrait exister.");
        }
    }

    public function test_validate_dates_returns_false_when_fin_precedes_debut(): void
    {
        $this->assertTrue(Attestation::validateDates('2026-01-01', '2026-01-31'));
        $this->assertTrue(Attestation::validateDates('2026-01-01', '2026-01-01'));
        $this->assertFalse(Attestation::validateDates('2026-01-31', '2026-01-01'));
        $this->assertTrue(Attestation::validateDates('2026-01-01', null));
    }

    public function test_mission_libelle_accessor_falls_back_to_id(): void
    {
        $attestation = new Attestation(['mission_id' => 42]);
        $attestation->setRelation('mission', null);

        $this->assertSame('Mission #42', $attestation->mission_libelle);
    }
}
