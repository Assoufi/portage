<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactureDestroyFilterTest extends TestCase
{
    use RefreshDatabase;

    private function creerFacture(array $attributes = []): Facture
    {
        $fournisseur = Fournisseur::factory()->create();
        $client      = Client::factory()->create();

        return Facture::create(array_merge([
            'fournisseur_id' => $fournisseur->id,
            'client_id'      => $client->id,
            'numero_facture' => '2026-00' . random_int(100, 999),
            'numero_bcm'     => 'BCM-' . random_int(1000, 9999),
            'date_facture'   => '2026-01-15',
            'total_ht'       => 100,
            'montant_ttc'    => 120,
        ], $attributes));
    }

    public function test_destroy_redirige_vers_index_avec_les_criteres_conserves(): void
    {
        $user    = User::factory()->create();
        $facture = $this->creerFacture();

        $response = $this->actingAs($user)
            ->delete('/factures/' . $facture->id . '?recherche=novancy&numero_facture=2026&facture_reglee=non&periode=2026-01');

        $response->assertRedirect(route('factures.index', [
            'recherche'       => 'novancy',
            'numero_facture'  => '2026',
            'facture_reglee'  => 'non',
            'periode'         => '2026-01',
        ]));

        $this->assertSoftDeleted('factures', ['id' => $facture->id]);
        $this->assertDatabaseCount('detail_factures', 0);
    }

    public function test_destroy_supprime_le_parametre_page_de_la_redirection(): void
    {
        $user    = User::factory()->create();
        $facture = $this->creerFacture();

        $response = $this->actingAs($user)
            ->delete('/factures/' . $facture->id . '?recherche=novancy&page=3&direction=asc');

        $location = $response->assertRedirect()->headers->get('Location');

        $this->assertStringContainsString('recherche=novancy', $location);
        $this->assertStringContainsString('direction=asc', $location);
        $this->assertStringNotContainsString('page=3', $location);
    }

    public function test_destroy_sans_filtres_redirige_vers_index_simple(): void
    {
        $user    = User::factory()->create();
        $facture = $this->creerFacture();

        $this->actingAs($user)
            ->delete('/factures/' . $facture->id)
            ->assertRedirect('/factures');
    }

    public function test_le_formulaire_de_suppression_transmet_la_query_string(): void
    {
        $user    = User::factory()->create();
        $facture = $this->creerFacture(['numero_facture' => '2026-693']);

        $this->actingAs($user)
            ->get('/factures?numero_facture=693')
            ->assertOk()
            ->assertSee('/factures/' . $facture->id . '?numero_facture=693', false);
    }

    public function test_destroy_fonctionne_meme_si_la_facture_n_existe_plus(): void
    {
        $user = User::factory()->create();
        $id   = $this->creerFacture()->id;

        $this->actingAs($user)->delete('/factures/' . $id);
        $this->actingAs($user)
            ->delete('/factures/' . $id)
            ->assertStatus(404);
    }
}
