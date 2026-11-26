<?php

namespace Tests\Feature;

use App\Mail\FactureLivraison;
use App\Models\Client;
use App\Models\Consultant;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FactureDispatcherTest extends TestCase
{
    use RefreshDatabase;

    private int $compteur = 0;

    protected function tearDown(): void
    {
        foreach (glob(storage_path('app/dispatches/*')) ?: [] as $fichier) {
            is_dir($fichier) ? File::deleteDirectory($fichier) : @unlink($fichier);
        }

        parent::tearDown();
    }

    private function creerFacture(array $modeLivraison): Facture
    {
        $fournisseur = Fournisseur::factory()->create();
        $client = Client::factory()->create([
            'mode_livraison' => $modeLivraison,
            'telephone' => '0612345678',
            'notifyto' => 'destinataire@example.com',
        ]);
        $consultant = Consultant::factory()->create();
        $this->compteur++;

        return Facture::create([
            'fournisseur_id' => $fournisseur->id,
            'client_id' => $client->id,
            'consultant_id' => $consultant->id,
            'numero_facture' => '2026-'.str_pad((string) $this->compteur, 4, '0', STR_PAD_LEFT),
            'date_facture' => '2026-01-15',
            'total_ht' => 100,
            'montant_ttc' => 120,
        ]);
    }

    private function dispatcher(Facture $facture): array
    {
        return $this->actingAs(User::factory()->create())
            ->postJson(route('factures.dispatcher'), ['facture_ids' => [$facture->id]])
            ->assertOk()
            ->json();
    }

    public function test_mode_vide_genere_un_zip_signe_sans_erreur(): void
    {
        $data = $this->dispatcher($this->creerFacture([]));

        $this->assertSame(1, $data['stats']['signe']);
        $this->assertSame(1, $data['stats']['sans_mode']);
        $this->assertNotNull($data['zip_signe_url']);
        $this->assertNull($data['zip_url']);
        $this->assertSame([], $data['erreurs']);
    }

    public function test_mode_whatsapp_genere_zip_signe_et_lien(): void
    {
        $data = $this->dispatcher($this->creerFacture(['Whatsapp']));

        $this->assertSame(1, $data['stats']['signe']);
        $this->assertSame(1, $data['stats']['whatsapp']);
        $this->assertNotNull($data['zip_signe_url']);
        $this->assertCount(1, $data['whatsapp']);
        $this->assertSame('https://wa.me/0612345678', $data['whatsapp'][0]['url']);
    }

    public function test_mode_email_reste_inchange_sans_zip_signe(): void
    {
        Mail::fake();

        $data = $this->dispatcher($this->creerFacture(['Email']));

        $this->assertSame(1, $data['stats']['email']);
        $this->assertSame(0, $data['stats']['signe']);
        $this->assertNull($data['zip_signe_url']);
        Mail::assertSent(FactureLivraison::class);
    }

    public function test_mode_papier_reste_non_signe_dans_zip(): void
    {
        $data = $this->dispatcher($this->creerFacture(['Papier']));

        $this->assertSame(1, $data['stats']['papier']);
        $this->assertSame(0, $data['stats']['signe']);
        $this->assertNotNull($data['zip_url']);
        $this->assertNull($data['zip_signe_url']);
    }
}
