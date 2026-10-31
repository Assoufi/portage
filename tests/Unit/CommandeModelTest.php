<?php

namespace Tests\Unit;

use App\Models\Commande;
use App\Models\Mission;
use Carbon\Carbon;
use Tests\TestCase;

class CommandeModelTest extends TestCase
{
    public function test_commande_fillable_attributes_are_protected(): void
    {
        $commande = new Commande;

        foreach ([
            'mission_id',
            'num_commande',
            'date_commande',
            'date_debut',
            'date_fin',
            'quantite',
            'tjm',
            'statut',
        ] as $attribut) {
            $this->assertTrue($commande->isFillable($attribut), "L'attribut {$attribut} devrait être fillable.");
        }

        $this->assertFalse($commande->isFillable('id'), 'L\'identifiant ne devrait pas être fillable.');
        $this->assertFalse($commande->isFillable('fournisseur_id'), 'Le fournisseur_id ne devrait pas être fillable : il est dérivé de la mission.');
    }

    public function test_numeric_and_date_attributes_are_cast(): void
    {
        $commande = new Commande([
            'date_commande' => '2026-01-15',
            'date_debut' => '2026-01-16',
            'date_fin' => '2026-02-15',
            'quantite' => '10',
            'tjm' => '900.50',
        ]);

        $this->assertInstanceOf(Carbon::class, $commande->date_commande);
        $this->assertSame('2026-01-15', $commande->date_commande->format('Y-m-d'));
        $this->assertInstanceOf(Carbon::class, $commande->date_debut);
        $this->assertInstanceOf(Carbon::class, $commande->date_fin);
        $this->assertSame(10.0, $commande->quantite);
        $this->assertSame(900.5, $commande->tjm);
    }

    public function test_statut_defaults_to_actif(): void
    {
        $commande = new Commande;

        $this->assertSame('ACTIF', $commande->statut);
        $this->assertSame(['ACTIF' => 'Actif', 'INACTIF' => 'Inactif'], Commande::STATUTS);
    }

    public function test_relationships_are_defined(): void
    {
        $commande = new Commande;

        $this->assertTrue(method_exists($commande, 'mission'));
        $this->assertSame(Mission::class, $commande->mission()->getRelated()::class);
        $this->assertFalse(method_exists($commande, 'fournisseur'), 'La commande n\'a plus de relation fournisseur : il est dérivé de la mission.');
    }
}
