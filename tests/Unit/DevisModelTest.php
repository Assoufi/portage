<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Devis;
use App\Models\Fournisseur;
use App\Models\Mission;
use Carbon\Carbon;
use Tests\TestCase;

class DevisModelTest extends TestCase
{
    public function test_devis_fillable_attributes_are_protected(): void
    {
        $devis = new Devis;

        foreach ([
            'fournisseur_id',
            'client_id',
            'mission_id',
            'numero_devis',
            'date_devis',
            'description',
            'quantite',
            'prix_unitaire',
        ] as $attribut) {
            $this->assertTrue($devis->isFillable($attribut), "L'attribut {$attribut} devrait être fillable.");
        }

        $this->assertFalse($devis->isFillable('id'), 'L\'identifiant ne devrait pas être fillable.');
        $this->assertFalse($devis->isFillable('created_at'), 'Le timestamp de création ne devrait pas être fillable.');
    }

    public function test_numeric_and_date_attributes_are_cast(): void
    {
        $devis = new Devis([
            'date_devis' => '2026-01-15',
            'quantite' => '3',
            'prix_unitaire' => '1250.50',
        ]);

        $this->assertInstanceOf(Carbon::class, $devis->date_devis);
        $this->assertSame('2026-01-15', $devis->date_devis->format('Y-m-d'));
        $this->assertSame(3.0, $devis->quantite);
        $this->assertSame(1250.5, $devis->prix_unitaire);
    }

    public function test_total_ht_tva_and_ttc_accessors(): void
    {
        $devis = new Devis([
            'quantite' => 2,
            'prix_unitaire' => 100,
        ]);

        $this->assertSame(200.0, $devis->total_ht);
        $this->assertSame('200,00', $devis->total_ht_formate);
        // Sans client lié, la TVA par défaut est de 20 %
        $this->assertSame(40.0, $devis->montant_tva);
        $this->assertSame(240.0, $devis->montant_ttc);
        $this->assertSame('240,00', $devis->montant_ttc_formate);
    }

    public function test_numero_devis_mutator_uppercases_and_trims(): void
    {
        $devis = new Devis(['numero_devis' => '  dev-2026-0001  ']);

        $this->assertSame('DEV-2026-0001', $devis->numero_devis);
    }

    public function test_description_mutator_trims_and_nullifies_empty(): void
    {
        $devis = new Devis(['description' => '   ']);

        $this->assertNull($devis->description);

        $devis->description = '  Prestation  ';
        $this->assertSame('Prestation', $devis->description);
    }

    public function test_devise_accessor_defaults_to_mad(): void
    {
        $devis = new Devis;

        $this->assertSame('MAD', $devis->devise);
    }

    public function test_relationships_are_defined(): void
    {
        $devis = new Devis;

        $this->assertTrue(method_exists($devis, 'mission'));
        $this->assertTrue(method_exists($devis, 'client'));
        $this->assertTrue(method_exists($devis, 'fournisseur'));
        $this->assertSame(Mission::class, $devis->mission()->getRelated()::class);
        $this->assertSame(Client::class, $devis->client()->getRelated()::class);
        $this->assertSame(Fournisseur::class, $devis->fournisseur()->getRelated()::class);
    }

    public function test_query_scopes_are_defined(): void
    {
        $devis = new Devis;

        foreach (['scopeParClient', 'scopeParMission', 'scopeParFournisseur', 'scopeParPeriode', 'scopeRecherche'] as $scope) {
            $this->assertTrue(method_exists($devis, $scope), "Le scope {$scope} devrait exister.");
        }
    }
}
