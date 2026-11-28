<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ClientImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function entetes(): array
    {
        return [
            'Client',
            'Adresse',
            'type_identification',
            'num_identification',
            'Delai',
            'Devise',
            'Livraison',
            'Remarques',
            'Nom facture',
            'date_import',
        ];
    }

    private function fichierExcel(array $lignes, ?array $entetes = null): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $feuille = $spreadsheet->getActiveSheet();
        $feuille->setTitle('Clients');
        $feuille->fromArray(array_merge([$entetes ?? $this->entetes()], $lignes), null, 'A1');

        $chemin = sys_get_temp_dir().DIRECTORY_SEPARATOR.'clients_import_'.uniqid('', true).'.xlsx';
        (new Xlsx($spreadsheet))->save($chemin);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile(
            $chemin,
            'clients.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    private function importer(User $user, array $lignes, ?array $entetes = null)
    {
        return $this->actingAs($user)->post(route('clients.importer'), [
            'fichier' => $this->fichierExcel($lignes, $entetes),
        ]);
    }

    public function test_import_requires_authentication(): void
    {
        $this->post('/clients/importer')->assertRedirect('/login');
    }

    public function test_import_creates_client_with_address_fallback_and_defaults(): void
    {
        $user = User::factory()->create();

        $response = $this->importer($user, [
            ['Nouveau Client', null, 'ICE', '123456789012345', 30, null, null, null, null, null],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('clients.index'));
        $response->assertSessionHas('success');

        $client = Client::firstWhere('num_identification', '123456789012345');

        $this->assertNotNull($client);
        $this->assertSame('Nouveau Client', $client->nom);
        $this->assertSame('Nouveau Client', $client->adresse);
        $this->assertSame('MAD', $client->devise);
        $this->assertSame(20.0, (float) $client->tva);
        $this->assertTrue((bool) $client->statut);
        $this->assertSame(30, $client->delai_paiement);
    }

    public function test_import_updates_existing_client_without_overwriting_blanks_or_name(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'nom' => 'ACME Existing',
            'adresse' => 'Ancienne adresse',
            'num_identification' => '111111111111111',
            'remarques' => 'Ancienne remarque',
            'devise' => 'MAD',
        ]);

        $response = $this->importer($user, [
            ['acme existing', null, null, null, null, null, null, 'Nouvelle remarque', null, null],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertSame(1, Client::count());

        $client->refresh();

        $this->assertSame('Acme Existing', $client->nom);
        $this->assertSame('Ancienne adresse', $client->adresse);
        $this->assertSame('Nouvelle remarque', $client->remarques);
        $this->assertSame('111111111111111', $client->num_identification);
        $this->assertSame('MAD', $client->devise);
    }

    public function test_import_restores_soft_deleted_client_and_updates_it(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'nom' => 'Client Archive',
            'adresse' => 'Ancienne adresse',
            'num_identification' => '222222222222222',
        ]);
        $client->delete();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);

        $response = $this->importer($user, [
            ['client archive', 'Nouvelle adresse', null, null, null, null, null, null, null, null],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertNotSoftDeleted('clients', ['id' => $client->id]);
        $this->assertSame(1, Client::count());

        $client->refresh();

        $this->assertSame('Client Archive', $client->nom);
        $this->assertSame('Nouvelle adresse', $client->adresse);
    }

    public function test_ignored_columns_are_reported(): void
    {
        $user = User::factory()->create();

        $response = $this->importer($user, [
            ['Client Test', 'Adresse Test', 'ICE', '666666666666666', 30, 'MAD', null, null, null, null],
        ]);

        $response->assertSessionHas('import_rapport');

        $rapport = $response->getSession()->get('import_rapport');

        $this->assertStringContainsString('Nom facture', $rapport);
        $this->assertStringContainsString('date_import', $rapport);
        $this->assertStringContainsString('Colonnes ignorées (2)', $rapport);
    }

    public function test_euro_currency_and_multiple_delivery_modes_are_parsed(): void
    {
        $user = User::factory()->create();

        $response = $this->importer($user, [
            ['Client Euro', 'Adresse Euro', 'ICE', '333333333333333', 30, '€', 'Papier, Email', null, null, null],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $client = Client::firstWhere('num_identification', '333333333333333');

        $this->assertNotNull($client);
        $this->assertSame('EUR', $client->devise);
        $this->assertSame(['Papier', 'Email'], $client->mode_livraison);
    }

    public function test_duplicate_identification_number_is_rejected_and_reported(): void
    {
        $user = User::factory()->create();
        Client::factory()->create([
            'nom' => 'Client Existant',
            'num_identification' => '444444444444444',
        ]);

        $response = $this->importer($user, [
            ['Autre Client', 'Adresse Autre', 'ICE', '444444444444444', 30, 'MAD', null, null, null, null],
        ]);

        $response->assertSessionHas('error');

        $this->assertSame(1, Client::count());

        $rapport = $response->getSession()->get('import_rapport');

        $this->assertStringContainsString('appartient déjà à un autre client', $rapport);
        $this->assertStringContainsString('Lignes invalides : 1', $rapport);
    }

    public function test_blank_name_is_reported_as_invalid(): void
    {
        $user = User::factory()->create();

        $response = $this->importer($user, [
            [null, 'Adresse sans nom', 'ICE', '555555555555555', 30, 'MAD', null, null, null, null],
        ]);

        $response->assertSessionHas('error');

        $this->assertSame(0, Client::count());

        $rapport = $response->getSession()->get('import_rapport');

        $this->assertStringContainsString('Le nom du client est obligatoire.', $rapport);
        $this->assertStringContainsString('Lignes invalides : 1', $rapport);
    }

    public function test_import_of_real_workbook_creates_all_clients(): void
    {
        $chemin = storage_path('app/imports/clients/customers-import.xlsx');

        if (! is_file($chemin)) {
            $this->markTestSkipped('Le classeur clients de référence est absent.');
        }

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('clients.importer'), [
            'fichier' => new UploadedFile(
                $chemin,
                'customers-import.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            ),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertSame(13, Client::count());

        $rapport = $response->getSession()->get('import_rapport');

        $this->assertStringContainsString('Lignes lues : 13', $rapport);
        $this->assertStringContainsString('Clients créés : 13', $rapport);
        $this->assertStringContainsString('Lignes invalides : 0', $rapport);
    }
}
