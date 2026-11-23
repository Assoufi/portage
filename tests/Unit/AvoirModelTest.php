<?php

namespace Tests\Unit;

use App\Models\Avoir;
use App\Models\Client;
use App\Models\DetailAvoir;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Models\Mission;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AvoirModelTest extends TestCase
{
    public function test_avoir_fillable_attributes_are_protected(): void
    {
        $avoir = new Avoir;

        foreach ([
            'facture_id',
            'fournisseur_id',
            'client_id',
            'consultant_id',
            'mission_id',
            'numero_avoir',
            'date_avoir',
            'type',
            'motif',
            'total_ht',
            'taux_tva',
            'montant_ttc',
            'remarques',
            'statut',
        ] as $attribut) {
            $this->assertTrue($avoir->isFillable($attribut), "L'attribut {$attribut} devrait être fillable.");
        }

        $this->assertFalse($avoir->isFillable('id'));
        $this->assertFalse($avoir->isFillable('created_at'));
    }

    public function test_date_and_numeric_attributes_are_cast(): void
    {
        $avoir = new Avoir([
            'date_avoir' => '2026-02-01',
            'total_ht' => '1000.50',
            'taux_tva' => '20',
            'montant_ttc' => '1200.60',
            'statut' => '1',
        ]);

        $this->assertInstanceOf(Carbon::class, $avoir->date_avoir);
        $this->assertSame('2026-02-01', $avoir->date_avoir->format('Y-m-d'));
        $this->assertSame(1000.5, $avoir->total_ht);
        $this->assertSame(20.0, $avoir->taux_tva);
        $this->assertSame(1200.6, $avoir->montant_ttc);
        $this->assertTrue($avoir->statut);
    }

    public function test_numero_and_text_mutators(): void
    {
        $avoir = new Avoir([
            'numero_avoir' => '  av-2026-0001  ',
            'motif' => '   Erreur   ',
            'remarques' => '   Note   ',
        ]);

        $this->assertSame('AV-2026-0001', $avoir->numero_avoir);
        $this->assertSame('Erreur', $avoir->motif);
        $this->assertSame('Note', $avoir->remarques);
    }

    public function test_calculate_totals_sums_details_and_tva(): void
    {
        $avoir = new Avoir(['taux_tva' => 20]);
        $avoir->setRelation('details', collect([
            new DetailAvoir(['quantite' => 2, 'prix_unitaire' => 100]),
            new DetailAvoir(['quantite' => 1, 'prix_unitaire' => 50]),
        ]));

        $avoir->calculateTotals();

        $this->assertSame(250.0, $avoir->total_ht);
        $this->assertSame(300.0, $avoir->montant_ttc);
        $this->assertSame('Annulation partielle', $avoir->type_label);
    }

    public function test_type_label_and_formatters(): void
    {
        $avoir = new Avoir(['type' => 'total', 'date_avoir' => '2026-02-01', 'total_ht' => 100, 'montant_ttc' => 120]);

        $this->assertSame('Annulation totale', $avoir->type_label);
        $this->assertSame('01/02/2026', $avoir->date_avoir_formatee);
        $this->assertSame('100,00', $avoir->total_ht_formate);
        $this->assertSame('120,00', $avoir->montant_ttc_formate);
    }

    public function test_relationships_are_defined(): void
    {
        $avoir = new Avoir;

        $this->assertSame(Facture::class, $avoir->facture()->getRelated()::class);
        $this->assertSame(Client::class, $avoir->client()->getRelated()::class);
        $this->assertSame(Fournisseur::class, $avoir->fournisseur()->getRelated()::class);
        $this->assertSame(Mission::class, $avoir->mission()->getRelated()::class);
        $this->assertSame(DetailAvoir::class, $avoir->details()->getRelated()::class);
    }

    public function test_query_scopes_are_defined(): void
    {
        $avoir = new Avoir;

        foreach (['scopeActif', 'scopeParFacture', 'scopeParClient', 'scopeParFournisseur', 'scopeParPeriode', 'scopeRecherche'] as $scope) {
            $this->assertTrue(method_exists($avoir, $scope), "Le scope {$scope} devrait exister.");
        }
    }
}
