<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Consultant;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Models\User;
use App\Services\FactureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FactureIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    private int $compteur = 0;

    private function creerFacture(array $attributes = []): Facture
    {
        $fournisseur = $attributes['fournisseur_id'] ?? Fournisseur::factory()->create()->id;
        $client = $attributes['client_id'] ?? Client::factory()->create()->id;
        $consultant = $attributes['consultant_id'] ?? Consultant::factory()->create()->id;
        $this->compteur++;

        return Facture::create(array_merge([
            'fournisseur_id' => $fournisseur,
            'client_id' => $client,
            'consultant_id' => $consultant,
            'numero_facture' => '2026-'.str_pad((string) $this->compteur, 4, '0', STR_PAD_LEFT),
            'date_facture' => '2026-01-15',
            'total_ht' => 100,
            'montant_ttc' => 120,
        ], $attributes));
    }

    public function test_recherche_ne_contourne_pas_les_autres_filtres(): void
    {
        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();

        $factureA = $this->creerFacture([
            'client_id' => $clientA->id,
            'beneficiaire' => 'NOVANCY',
        ]);
        $factureB = $this->creerFacture([
            'client_id' => $clientB->id,
            'beneficiaire' => 'NOVANCY',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get('/factures?recherche=novancy&client_id='.$clientA->id)
            ->assertOk();

        $response->assertSee($factureA->numero_facture);
        $response->assertDontSee($factureB->numero_facture);
    }

    public function test_recherche_exclut_les_factures_supprimees(): void
    {
        $facture = $this->creerFacture(['beneficiaire' => 'SOCIETE FANTOME']);
        $numero = $facture->numero_facture;
        $facture->delete();

        $this->actingAs(User::factory()->create())
            ->get('/factures?recherche=fan')
            ->assertOk()
            ->assertDontSee($numero);
    }

    public function test_filtre_periode_utilise_un_intervalle_de_mois(): void
    {
        $janvier = $this->creerFacture(['periode' => '2026-01-01']);
        $fevrier = $this->creerFacture(['periode' => '2026-02-01']);

        $response = $this->actingAs(User::factory()->create())
            ->get('/factures?periode=2026-01')
            ->assertOk();

        $response->assertSee($janvier->numero_facture);
        $response->assertDontSee($fevrier->numero_facture);
    }

    public function test_tri_invalide_est_ignore_sans_erreur(): void
    {
        $facture = $this->creerFacture();

        $this->actingAs(User::factory()->create())
            ->get('/factures?sort=numero_facture%20desc%3B%20DROP%20TABLE%20factures&direction=sideways')
            ->assertOk()
            ->assertSee($facture->numero_facture);
    }

    public function test_les_relations_sont_eager_chargees_pas_de_n_plus_un(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->creerFacture([
                'client_id' => Client::factory()->create()->id,
                'fournisseur_id' => Fournisseur::factory()->create()->id,
                'consultant_id' => Consultant::factory()->create()->id,
            ]);
        }

        $user = User::factory()->create();

        // Chauffage du cache de statistiques
        $this->actingAs($user)->get('/factures')->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($user)->get('/factures')->assertOk();
        $requetes = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 15 lignes ne doivent pas générer 3 requêtes de relation chacune.
        $this->assertLessThan(15, $requetes, "Trop de requêtes ({$requetes}) : N+1 probable.");
    }

    public function test_stats_sont_calculees_et_invalidees_apres_creation(): void
    {
        $this->creerFacture([
            'montant_ttc' => 120,
            'total_ht' => 100,
            'date_reglement' => null,
            'date_echeance' => '2020-01-01',
            'statut' => true,
        ]);

        $service = app(FactureService::class);
        $stats = $service->getStats();

        $this->assertSame(1, $stats['totalFactures']);
        $this->assertEqualsWithDelta(120.0, $stats['montantTotal'], 0.001);
        $this->assertSame(1, $stats['facturesEnAttente']);
        $this->assertSame(1, $stats['facturesEnRetard']);
        $this->assertSame(0, $stats['facturesReglees']);

        // La création suivante doit invalider le cache (événement "saved").
        $this->creerFacture([
            'montant_ttc' => 240,
            'total_ht' => 200,
            'date_reglement' => '2026-01-20',
        ]);

        $stats = $service->getStats();

        $this->assertSame(2, $stats['totalFactures']);
        $this->assertEqualsWithDelta(360.0, $stats['montantTotal'], 0.001);
        $this->assertSame(1, $stats['facturesReglees']);
        $this->assertSame(1, $stats['facturesEnAttente']);
    }
}
