<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Consultant;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Models\Mission;
use App\Services\FactureRattachementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class FactureRattachementTest extends TestCase
{
    use RefreshDatabase;

    private int $compteur = 0;

    private function creerFacture(array $attributes = []): Facture
    {
        $this->compteur++;

        $donnees = array_merge([
            'numero_facture' => '2026-'.str_pad((string) $this->compteur, 4, '0', STR_PAD_LEFT),
            'date_facture' => '2026-03-15',
            'total_ht' => 100,
            'montant_ttc' => 120,
        ], $attributes);

        $donnees['fournisseur_id'] ??= Fournisseur::factory()->create()->id;
        $donnees['client_id'] ??= Client::factory()->create()->id;

        return Facture::create($donnees);
    }

    private function service(): FactureRattachementService
    {
        return app(FactureRattachementService::class);
    }

    public function test_rattache_consultant_et_mission(): void
    {
        $consultant = Consultant::factory()->create(['nom' => 'Amina Benali']);
        $client = Client::factory()->create();
        $fournisseur = Fournisseur::factory()->create();

        $mission = Mission::factory()->create([
            'consultant_id' => $consultant->id,
            'client_id' => $client->id,
            'fournisseur_id' => $fournisseur->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-12-31',
        ]);

        $facture = $this->creerFacture([
            'beneficiaire' => 'AMINA BENALI',
            'client_id' => $client->id,
            'fournisseur_id' => $fournisseur->id,
        ]);

        $rapport = $this->service()->rattacher([$facture->id]);

        $this->assertSame(1, $rapport['consultants']);
        $this->assertSame(1, $rapport['missions']);
        $this->assertSame([], $rapport['non_rattachees']);

        $this->assertDatabaseHas('factures', [
            'id' => $facture->id,
            'consultant_id' => $consultant->id,
            'mission_id' => $mission->id,
        ]);
    }

    public function test_mission_en_cours_sans_date_fin_est_retenue(): void
    {
        $consultant = Consultant::factory()->create(['nom' => 'Amina Benali']);
        $client = Client::factory()->create();
        $fournisseur = Fournisseur::factory()->create();

        $mission = Mission::factory()->create([
            'consultant_id' => $consultant->id,
            'client_id' => $client->id,
            'fournisseur_id' => $fournisseur->id,
            'date_debut' => '2026-01-01',
            'date_fin' => null,
        ]);

        $facture = $this->creerFacture([
            'beneficiaire' => 'Amina Benali',
            'client_id' => $client->id,
            'fournisseur_id' => $fournisseur->id,
        ]);

        $this->service()->rattacher([$facture->id]);

        $this->assertDatabaseHas('factures', [
            'id' => $facture->id,
            'mission_id' => $mission->id,
        ]);
    }

    public function test_choisit_la_mission_la_plus_recente_parmi_les_chevauchements(): void
    {
        $consultant = Consultant::factory()->create(['nom' => 'Amina Benali']);
        $client = Client::factory()->create();
        $fournisseur = Fournisseur::factory()->create();

        $ancienne = Mission::factory()->create([
            'consultant_id' => $consultant->id,
            'client_id' => $client->id,
            'fournisseur_id' => $fournisseur->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-12-31',
        ]);

        $recente = Mission::factory()->create([
            'consultant_id' => $consultant->id,
            'client_id' => $client->id,
            'fournisseur_id' => $fournisseur->id,
            'date_debut' => '2026-03-01',
            'date_fin' => '2026-12-31',
        ]);

        $facture = $this->creerFacture([
            'beneficiaire' => 'Amina Benali',
            'client_id' => $client->id,
            'fournisseur_id' => $fournisseur->id,
        ]);

        $this->service()->rattacher([$facture->id]);

        $this->assertDatabaseHas('factures', [
            'id' => $facture->id,
            'mission_id' => $recente->id,
        ]);
        $this->assertDatabaseMissing('factures', [
            'id' => $facture->id,
            'mission_id' => $ancienne->id,
        ]);
    }

    public function test_ignore_les_missions_supprimees(): void
    {
        $consultant = Consultant::factory()->create(['nom' => 'Amina Benali']);
        $client = Client::factory()->create();
        $fournisseur = Fournisseur::factory()->create();

        $mission = Mission::factory()->create([
            'consultant_id' => $consultant->id,
            'client_id' => $client->id,
            'fournisseur_id' => $fournisseur->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-12-31',
        ]);
        $mission->delete();

        $facture = $this->creerFacture([
            'beneficiaire' => 'Amina Benali',
            'client_id' => $client->id,
            'fournisseur_id' => $fournisseur->id,
        ]);

        $rapport = $this->service()->rattacher([$facture->id]);

        $this->assertSame(1, $rapport['consultants']);
        $this->assertSame(0, $rapport['missions']);
        $this->assertSame('mission introuvable', $rapport['non_rattachees'][0]['raison']);
        $this->assertDatabaseHas('factures', [
            'id' => $facture->id,
            'mission_id' => null,
        ]);
    }

    public function test_ne_touche_pas_aux_factures_hors_du_lot_importe(): void
    {
        $consultant = Consultant::factory()->create(['nom' => 'Amina Benali']);
        $client = Client::factory()->create();
        $fournisseur = Fournisseur::factory()->create();

        Mission::factory()->create([
            'consultant_id' => $consultant->id,
            'client_id' => $client->id,
            'fournisseur_id' => $fournisseur->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-12-31',
        ]);

        $historique = $this->creerFacture([
            'beneficiaire' => 'Amina Benali',
            'client_id' => $client->id,
            'fournisseur_id' => $fournisseur->id,
        ]);

        $importee = $this->creerFacture([
            'beneficiaire' => 'Amina Benali',
            'client_id' => $client->id,
            'fournisseur_id' => $fournisseur->id,
        ]);

        $this->service()->rattacher([$importee->id]);

        $this->assertDatabaseHas('factures', [
            'id' => $importee->id,
            'consultant_id' => $consultant->id,
        ]);
        $this->assertDatabaseHas('factures', [
            'id' => $historique->id,
            'consultant_id' => null,
            'mission_id' => null,
        ]);
    }

    public function test_rapporte_un_consultant_introuvable(): void
    {
        $facture = $this->creerFacture([
            'beneficiaire' => 'Inconnu Total',
        ]);

        $rapport = $this->service()->rattacher([$facture->id]);

        $this->assertSame(0, $rapport['consultants']);
        $this->assertSame('consultant introuvable', $rapport['non_rattachees'][0]['raison']);
        $this->assertSame($facture->id, $rapport['non_rattachees'][0]['id']);
    }

    public function test_nom_ambigu_retenu_sur_le_plus_petit_id(): void
    {
        $premier = Consultant::factory()->create([
            'nom' => 'Amina Benali',
            'email' => 'amina.premier@example.test',
        ]);
        $second = Consultant::factory()->create([
            'nom' => 'Amina Benali',
            'email' => 'amina.second@example.test',
        ]);

        $facture = $this->creerFacture([
            'beneficiaire' => 'Amina Benali',
        ]);

        $rapport = $this->service()->rattacher([$facture->id]);

        $this->assertSame(1, $rapport['consultants']);
        $this->assertContains('Amina Benali', $rapport['ambigus']);
        $this->assertDatabaseHas('factures', [
            'id' => $facture->id,
            'consultant_id' => $premier->id,
        ]);
        $this->assertDatabaseMissing('factures', [
            'id' => $facture->id,
            'consultant_id' => $second->id,
        ]);
    }

    public function test_simulation_ne_modifie_rien(): void
    {
        $consultant = Consultant::factory()->create(['nom' => 'Amina Benali']);
        $client = Client::factory()->create();
        $fournisseur = Fournisseur::factory()->create();

        Mission::factory()->create([
            'consultant_id' => $consultant->id,
            'client_id' => $client->id,
            'fournisseur_id' => $fournisseur->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-12-31',
        ]);

        $facture = $this->creerFacture([
            'beneficiaire' => 'Amina Benali',
            'client_id' => $client->id,
            'fournisseur_id' => $fournisseur->id,
        ]);

        $rapport = $this->service()->rattacher([$facture->id], true);

        $this->assertSame(1, $rapport['consultants']);
        $this->assertSame(1, $rapport['missions']);
        $this->assertDatabaseHas('factures', [
            'id' => $facture->id,
            'consultant_id' => null,
            'mission_id' => null,
        ]);
    }

    public function test_previsualisation_sans_ecriture(): void
    {
        $consultant = Consultant::factory()->create(['nom' => 'Amina Benali']);
        $client = Client::factory()->create();
        $fournisseur = Fournisseur::factory()->create();

        Mission::factory()->create([
            'consultant_id' => $consultant->id,
            'client_id' => $client->id,
            'fournisseur_id' => $fournisseur->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-12-31',
        ]);

        $rapport = $this->service()->previsualiser([
            [
                'ligne' => 12,
                'beneficiaire' => 'Amina Benali',
                'client_id' => $client->id,
                'fournisseur_id' => $fournisseur->id,
                'date_facture' => '2026-03-15',
            ],
        ]);

        $this->assertSame(1, $rapport['consultants']);
        $this->assertSame(1, $rapport['missions']);
        $this->assertSame([], $rapport['non_rattachees']);
        $this->assertDatabaseCount('factures', 0);
    }

    public function test_previsualisation_rapporte_la_ligne_non_rattachee(): void
    {
        $rapport = $this->service()->previsualiser([
            [
                'ligne' => 42,
                'beneficiaire' => 'Inconnu Total',
                'client_id' => null,
                'fournisseur_id' => null,
                'date_facture' => '2026-03-15',
            ],
        ]);

        $this->assertSame(0, $rapport['consultants']);
        $this->assertSame(42, $rapport['non_rattachees'][0]['ligne']);
        $this->assertSame('consultant introuvable', $rapport['non_rattachees'][0]['raison']);
    }

    public function test_echec_du_rattachement_ne_bloque_pas_l_import(): void
    {
        $client = Client::factory()->create(['nom' => 'Client Import']);
        $fournisseur = Fournisseur::factory()->create(['nom' => 'Fournisseur Import']);

        $fichier = 'test-rattachement-echec-'.uniqid().'.xlsx';
        $chemin = storage_path('app/'.$fichier);

        $classeur = new Spreadsheet;
        $classeur->getActiveSheet()->fromArray([
            ['Client', 'Fournisseur', 'Date', 'Désignation', 'Quantité', 'Prix Unitaire', 'Montant TTC', 'Manager', 'N° Facture', 'Devise'],
            ['Client Import', 'Fournisseur Import', '2026-03-15', 'Prestation', 1, 100, 120, 'Amina Benali', '2026-0001', 'MAD'],
        ]);
        (new Xlsx($classeur))->save($chemin);
        $classeur->disconnectWorksheets();

        $this->mock(FactureRattachementService::class, function (MockInterface $mock) {
            $mock->shouldReceive('rattacher')
                ->once()
                ->andThrow(new \RuntimeException('panne simulée'));
        });

        try {
            $this->artisan('factures:importer', [
                '--file' => $fichier,
                '--execute' => true,
            ])->assertExitCode(0);
        } finally {
            @unlink($chemin);
        }

        $this->assertDatabaseCount('factures', 1);
        $this->assertDatabaseHas('factures', [
            'client_id' => $client->id,
            'fournisseur_id' => $fournisseur->id,
            'consultant_id' => null,
            'mission_id' => null,
        ]);
    }
}
