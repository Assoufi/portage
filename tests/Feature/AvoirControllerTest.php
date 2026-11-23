<?php

namespace Tests\Feature;

use App\Models\Avoir;
use App\Models\DetailFacture;
use App\Models\Facture;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvoirControllerTest extends TestCase
{
    use RefreshDatabase;

    private function creerFacture(): Facture
    {
        $mission = Mission::factory()->create();

        $facture = Facture::create([
            'mission_id' => $mission->id,
            'client_id' => $mission->client_id,
            'fournisseur_id' => $mission->fournisseur_id,
            'consultant_id' => $mission->consultant_id,
            'numero_facture' => '2026-001',
            'date_facture' => '2026-01-15',
            'total_ht' => 1000,
            'taux_tva' => 20,
            'montant_ttc' => 1200,
            'statut' => true,
        ]);

        DetailFacture::create([
            'facture_id' => $facture->id,
            'designation' => 'Prestation de conseil',
            'quantite' => 1,
            'prix_unitaire' => 1000,
            'montant_ht' => 1000,
            'montant_tva' => 200,
            'montant_ttc' => 1200,
        ]);

        return $facture->fresh('details');
    }

    private function payload(Facture $facture, array $overrides = []): array
    {
        return array_merge([
            'facture_id' => $facture->id,
            'numero_avoir' => 'AV-'.date('Y').'-0001',
            'date_avoir' => '2026-02-01',
            'type' => 'total',
            'motif' => 'Erreur de facturation',
            'taux_tva' => 20,
            'details' => [
                ['designation' => 'Prestation de conseil', 'quantite' => 1, 'prix_unitaire' => 1000],
            ],
        ], $overrides);
    }

    public function test_avoirs_index_is_displayed(): void
    {
        $user = User::factory()->create();
        $facture = $this->creerFacture();
        $avoir = Avoir::create(array_merge($this->payload($facture), [
            'fournisseur_id' => $facture->fournisseur_id,
            'client_id' => $facture->client_id,
            'consultant_id' => $facture->consultant_id,
            'mission_id' => $facture->mission_id,
            'total_ht' => 1000,
            'montant_ttc' => 1200,
        ]));

        $this->actingAs($user)
            ->get('/avoirs')
            ->assertOk()
            ->assertSee('Avoirs')
            ->assertSee($avoir->numero_avoir);
    }

    public function test_avoirs_require_authentication(): void
    {
        $this->get('/avoirs')->assertRedirect('/login');
    }

    public function test_create_page_lists_invoices(): void
    {
        $user = User::factory()->create();
        $facture = $this->creerFacture();

        $this->actingAs($user)
            ->get('/avoirs/create')
            ->assertOk()
            ->assertSee('Créer un avoir')
            ->assertSee('Factures à annuler')
            ->assertSee($facture->numero_facture)
            ->assertSee('AV-'.date('Y'));
    }

    public function test_invoice_data_endpoint_returns_prefill_payload(): void
    {
        $user = User::factory()->create();
        $facture = $this->creerFacture();

        $this->actingAs($user)
            ->getJson('/avoirs/facture/'.$facture->id.'/donnees')
            ->assertOk()
            ->assertJsonPath('numero_facture', $facture->numero_facture)
            ->assertJsonPath('montant_ttc', 1200)
            ->assertJsonPath('montant_restant', 1200)
            ->assertJsonCount(1, 'details');
    }

    public function test_create_page_can_preselect_an_invoice(): void
    {
        $user = User::factory()->create();
        $facture = $this->creerFacture();

        $this->actingAs($user)
            ->get('/avoirs/create?facture_id='.$facture->id)
            ->assertOk()
            ->assertSee($facture->numero_facture)
            ->assertSee('Prestation de conseil');
    }

    public function test_avoir_edit_page_is_displayed(): void
    {
        $user = User::factory()->create();
        $facture = $this->creerFacture();

        $this->actingAs($user)->post('/avoirs', $this->payload($facture));
        $avoir = Avoir::firstOrFail();

        $this->actingAs($user)
            ->get('/avoirs/'.$avoir->id.'/edit')
            ->assertOk()
            ->assertSee('Modifier l\'avoir '.$avoir->numero_avoir, false)
            ->assertSee($avoir->numero_avoir);
    }

    public function test_avoir_can_be_created_for_the_full_invoice(): void
    {
        $user = User::factory()->create();
        $facture = $this->creerFacture();

        $response = $this->actingAs($user)->post('/avoirs', $this->payload($facture));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $avoir = Avoir::firstOrFail();
        $this->assertSame($facture->id, $avoir->facture_id);
        $this->assertEqualsWithDelta(1200, $avoir->montant_ttc, 0.01);
        $this->assertDatabaseCount('detail_avoirs', 1);
    }

    public function test_partial_avoir_is_allowed(): void
    {
        $user = User::factory()->create();
        $facture = $this->creerFacture();

        $payload = $this->payload($facture, [
            'type' => 'partiel',
            'details' => [
                ['designation' => 'Prestation de conseil', 'quantite' => 1, 'prix_unitaire' => 500],
            ],
        ]);

        $response = $this->actingAs($user)->post('/avoirs', $payload);

        $response->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(600, Avoir::firstOrFail()->montant_ttc, 0.01);
    }

    public function test_avoir_cannot_exceed_the_remaining_invoice_amount(): void
    {
        $user = User::factory()->create();
        $facture = $this->creerFacture();

        $payload = $this->payload($facture, [
            'details' => [
                ['designation' => 'Prestation de conseil', 'quantite' => 2, 'prix_unitaire' => 1000],
            ],
        ]);

        $response = $this->actingAs($user)->post('/avoirs', $payload);

        $response->assertSessionHasErrors('details');
        $this->assertDatabaseCount('avoirs', 0);
    }

    public function test_avoir_can_be_updated_without_self_blocking_the_cap(): void
    {
        $user = User::factory()->create();
        $facture = $this->creerFacture();

        $this->actingAs($user)->post('/avoirs', $this->payload($facture));
        $avoir = Avoir::firstOrFail();

        $response = $this->actingAs($user)->put('/avoirs/'.$avoir->id, $this->payload($facture, [
            'numero_avoir' => $avoir->numero_avoir,
            'type' => 'partiel',
            'details' => [
                ['designation' => 'Prestation de conseil', 'quantite' => 1, 'prix_unitaire' => 300],
            ],
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(360, $avoir->fresh()->montant_ttc, 0.01);
    }

    public function test_avoir_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $facture = $this->creerFacture();

        $this->actingAs($user)->post('/avoirs', $this->payload($facture));
        $avoir = Avoir::firstOrFail();

        $this->actingAs($user)
            ->delete('/avoirs/'.$avoir->id)
            ->assertRedirect('/avoirs');

        $this->assertSoftDeleted('avoirs', ['id' => $avoir->id]);
    }

    public function test_avoir_pdf_is_generated(): void
    {
        $user = User::factory()->create();
        $facture = $this->creerFacture();

        $this->actingAs($user)->post('/avoirs', $this->payload($facture));
        $avoir = Avoir::firstOrFail();

        $response = $this->actingAs($user)->get('/avoirs/'.$avoir->id.'/pdf');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get('/avoirs/'.$avoir->id.'/pdf?signe=0')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_next_avoir_number_is_generated(): void
    {
        $this->assertSame('AV-'.date('Y').'-0001', Avoir::genererNumeroAvoir());

        $facture = $this->creerFacture();
        Avoir::create(array_merge($this->payload($facture, ['numero_avoir' => 'AV-'.date('Y').'-0001']), [
            'fournisseur_id' => $facture->fournisseur_id,
            'client_id' => $facture->client_id,
            'total_ht' => 1000,
            'montant_ttc' => 1200,
        ]));

        $this->assertSame('AV-'.date('Y').'-0002', Avoir::genererNumeroAvoir());
    }
}
