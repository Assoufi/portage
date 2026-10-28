<?php

namespace Tests\Feature;

use App\Exports\FacturesExport;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class FacturesExportFormatTest extends TestCase
{
    use RefreshDatabase;

    private function creerFacture(): Facture
    {
        $fournisseur = Fournisseur::factory()->create();
        $client      = Client::factory()->create();

        return Facture::create([
            'fournisseur_id'     => $fournisseur->id,
            'client_id'          => $client->id,
            'numero_facture'     => '2026-777',
            'numero_bcm'         => 'BCM-777',
            'date_facture'       => '2026-01-15',
            'date_paiement'      => '2026-02-10',
            'date_reglement'     => '2026-02-12',
            'date_reception'     => '2026-01-20',
            'periode'            => '2026-01-01',
            'total_ht'           => 100,
            'montant_ttc'        => 120,
        ]);
    }

    public function test_la_periode_est_exportee_au_format_mm_yyyy(): void
    {
        $user    = User::factory()->create();
        $facture = $this->creerFacture();

        $this->actingAs($user)
            ->get('/factures/export')
            ->assertSessionHasNoErrors();

        $ligne = (new FacturesExport(Facture::query()))->collection()[0];

        $this->assertSame('01/2026', $ligne[14]);
    }

    public function test_les_dates_sont_de_vraies_cellules_dd_mm_yyyy_dans_le_xlsx(): void
    {
        $user    = User::factory()->create();
        $facture = $this->creerFacture();

        $this->actingAs($user)->get('/factures/export')->assertSessionHasNoErrors();

        Excel::store(new FacturesExport(Facture::query()), 'debug_export.xlsx');
        $sheet = IOFactory::load(storage_path('app/private/debug_export.xlsx'))->getActiveSheet();

        // Colonne 2 = Date, 11 = Date Paiement BEN, 13 = Paiement Fournisseur,
        // 17 = Date reception (lignes 1 = entêtes, 2 = premières données)
        foreach (['B' => $facture->date_facture, 'K' => $facture->date_reglement, 'M' => $facture->date_paiement, 'Q' => $facture->date_reception] as $colonne => $attendue) {
            $cell = $sheet->getCell($colonne . '2');

            $this->assertSame('n', $cell->getDataType(), "Colonne {$colonne} : cellule date attendue");
            $this->assertSame('dd/mm/yyyy', $cell->getStyle()->getNumberFormat()->getFormatCode(), "Colonne {$colonne} : format_date");
            $this->assertSame($attendue->format('d/m/Y'), $cell->getFormattedValue(), "Colonne {$colonne} : affichage attendu");
        }
    }
}
