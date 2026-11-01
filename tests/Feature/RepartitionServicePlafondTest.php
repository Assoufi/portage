<?php

// tests/Feature/RepartitionServicePlafondTest.php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Consultant;
use App\Models\Fournisseur;
use App\Models\Paiement;
use App\Models\Repartition;
use App\Services\RepartitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepartitionServicePlafondTest extends TestCase
{
    use RefreshDatabase;

    private RepartitionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RepartitionService;
    }

    private function creerPaiement(float $montant): Paiement
    {
        return Paiement::create([
            'client_id' => Client::factory()->create()->id,
            'fournisseur_id' => Fournisseur::factory()->create()->id,
            'reference' => 'PAY-TEST-'.uniqid(),
            'montant' => $montant,
            'date_paiement' => '2026-01-15',
            'mode_paiement' => 'virement',
        ]);
    }

    private function creerConsultant(string $nom): Consultant
    {
        return Consultant::create(['nom' => $nom, 'statut' => true]);
    }

    public function test_repartition_dans_le_plafond_est_acceptee(): void
    {
        $paiement = $this->creerPaiement(100);
        $consultant = $this->creerConsultant('Ali Test');

        $repartition = $this->service->createRepartition($paiement, [
            'consultant_id' => $consultant->id,
            'montant' => 80,
            'mode_paiement' => 'virement',
            'date_paiement' => '2026-01-20',
        ]);

        $this->assertDatabaseHas('repartitions', [
            'id' => $repartition->id,
            'montant' => 80,
        ]);
    }

    public function test_repartition_depassant_le_plafond_est_rejetee(): void
    {
        $paiement = $this->creerPaiement(100);
        $consultant = $this->creerConsultant('Ali Test');

        $this->service->createRepartition($paiement, [
            'consultant_id' => $consultant->id,
            'montant' => 80,
            'mode_paiement' => 'virement',
            'date_paiement' => '2026-01-20',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('dépasse le montant du paiement');

        $this->service->createRepartition($paiement, [
            'consultant_id' => $this->creerConsultant('Salma Test')->id,
            'montant' => 30,
            'mode_paiement' => 'virement',
            'date_paiement' => '2026-01-20',
        ]);
    }

    public function test_rien_n_est_inscrit_quand_le_plafond_est_depasse(): void
    {
        $paiement = $this->creerPaiement(100);
        $consultant = $this->creerConsultant('Ali Test');

        $this->service->createRepartition($paiement, [
            'consultant_id' => $consultant->id,
            'montant' => 100,
            'mode_paiement' => 'virement',
            'date_paiement' => '2026-01-20',
        ]);

        try {
            $this->service->createRepartition($paiement, [
                'consultant_id' => $consultant->id,
                'montant' => 1,
                'mode_paiement' => 'virement',
                'date_paiement' => '2026-01-20',
            ]);
            $this->fail('Le plafond aurait dû rejeter la répartition.');
        } catch (\RuntimeException) {
            // attendu
        }

        $this->assertSame(100.0, Repartition::where('paiement_id', $paiement->id)->sum('montant'));
    }

    public function test_mise_a_jour_depassant_le_plafond_est_rejetee(): void
    {
        $paiement = $this->creerPaiement(100);
        $consultant = $this->creerConsultant('Ali Test');

        $repartition = $this->service->createRepartition($paiement, [
            'consultant_id' => $consultant->id,
            'montant' => 80,
            'mode_paiement' => 'virement',
            'date_paiement' => '2026-01-20',
        ]);

        $this->expectException(\RuntimeException::class);

        $this->service->updateRepartition($repartition, ['montant' => 120]);
    }
}
