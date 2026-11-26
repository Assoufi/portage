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

    private function creerFacture(array $modeLivraison, ?string $telephone = '0612345678'): Facture
    {
        $fournisseur = Fournisseur::factory()->create();
        $client = Client::factory()->create([
            'mode_livraison' => $modeLivraison,
            'telephone' => $telephone,
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

    public function test_mode_vide_genere_un_zip_signe_meme_sans_telephone(): void
    {
        $data = $this->dispatcher($this->creerFacture([], null));

        $this->assertSame(1, $data['stats']['signe']);
        $this->assertSame(1, $data['stats']['sans_mode']);
        $this->assertNotNull($data['zip_signe_url']);
        $this->assertNull($data['zip_url']);
        $this->assertArrayNotHasKey('whatsapp', $data);
        $this->assertSame([], $data['erreurs']);
    }

    public function test_mode_whatsapp_genere_zip_signe_sans_lien(): void
    {
        $data = $this->dispatcher($this->creerFacture(['Whatsapp'], null));

        $this->assertSame(1, $data['stats']['signe']);
        $this->assertNotNull($data['zip_signe_url']);
        $this->assertArrayNotHasKey('whatsapp', $data);
        $this->assertArrayNotHasKey('whatsapp', $data['stats']);
        $this->assertSame([], $data['erreurs']);
    }

    public function test_mode_email_sans_telephone_reste_inchange(): void
    {
        Mail::fake();

        $data = $this->dispatcher($this->creerFacture(['Email'], null));

        $this->assertSame(1, $data['stats']['email']);
        $this->assertSame(0, $data['stats']['signe']);
        $this->assertNull($data['zip_signe_url']);
        Mail::assertSent(FactureLivraison::class);
    }

    public function test_telephone_renseigne_genere_aussi_la_facture_signee_pour_email(): void
    {
        Mail::fake();

        $data = $this->dispatcher($this->creerFacture(['Email'], '0612345678'));

        $this->assertSame(1, $data['stats']['email']);
        $this->assertSame(1, $data['stats']['signe']);
        $this->assertNotNull($data['zip_signe_url']);
        Mail::assertSent(FactureLivraison::class);
    }

    public function test_mode_papier_sans_telephone_reste_non_signe(): void
    {
        $data = $this->dispatcher($this->creerFacture(['Papier'], null));

        $this->assertSame(1, $data['stats']['papier']);
        $this->assertSame(0, $data['stats']['signe']);
        $this->assertNotNull($data['zip_url']);
        $this->assertNull($data['zip_signe_url']);
    }

    public function test_mode_papier_avec_telephone_genere_les_deux_zip(): void
    {
        $data = $this->dispatcher($this->creerFacture(['Papier'], '0612345678'));

        $this->assertSame(1, $data['stats']['papier']);
        $this->assertSame(1, $data['stats']['signe']);
        $this->assertNotNull($data['zip_url']);
        $this->assertNotNull($data['zip_signe_url']);
    }
}
