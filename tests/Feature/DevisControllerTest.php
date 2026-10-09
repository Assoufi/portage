<?php

namespace Tests\Feature;

use App\Models\Devis;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DevisControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(?Mission $mission = null): array
    {
        $mission = $mission ?? Mission::factory()->create();

        return [
            'mission_id' => $mission->id,
            'client_id' => $mission->client_id,
            'fournisseur_id' => $mission->fournisseur_id,
            'numero_devis' => 'DEV-'.date('Y').'-0001',
            'date_devis' => '2026-01-15',
            'description' => 'Prestation de conseil',
            'quantite' => 2,
            'prix_unitaire' => 1500.50,
        ];
    }

    public function test_devis_index_is_displayed(): void
    {
        $user = User::factory()->create();
        $devis = Devis::factory()->create();

        $response = $this->actingAs($user)->get('/devis');

        $response->assertOk();
        $response->assertSee('Devis');
        $response->assertSee('Documents');
        $response->assertSee($devis->numero_devis);
    }

    public function test_devis_index_requires_authentication(): void
    {
        $this->get('/devis')->assertRedirect('/login');
    }

    public function test_devis_create_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/devis/create')
            ->assertOk()
            ->assertSee('Créer un nouveau devis')
            ->assertSee('DEV-'.date('Y'));
    }

    public function test_devis_store_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/devis', [
            'mission_id' => null,
            'client_id' => null,
            'fournisseur_id' => null,
            'numero_devis' => null,
            'date_devis' => null,
            'quantite' => null,
            'prix_unitaire' => null,
        ]);

        $response->assertSessionHasErrors([
            'client_id',
            'fournisseur_id',
            'numero_devis',
            'date_devis',
            'quantite',
            'prix_unitaire',
        ]);

        $this->assertDatabaseCount('devis', 0);
    }

    public function test_devis_store_validates_foreign_keys_exist(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $payload['client_id'] = 999999;
        $payload['fournisseur_id'] = 999999;

        $response = $this->actingAs($user)->post('/devis', $payload);

        $response->assertSessionHasErrors(['client_id', 'fournisseur_id']);
    }

    public function test_devis_store_validates_unique_numero(): void
    {
        $user = User::factory()->create();
        $devis = Devis::factory()->create(['numero_devis' => 'DEV-'.date('Y').'-0001']);
        $payload = $this->validPayload($devis->mission);
        $payload['numero_devis'] = 'DEV-'.date('Y').'-0001';

        $response = $this->actingAs($user)->post('/devis', $payload);

        $response->assertSessionHasErrors('numero_devis');
    }

    public function test_devis_can_be_created(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();

        $response = $this->actingAs($user)->post('/devis', $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('devis', [
            'mission_id' => $payload['mission_id'],
            'client_id' => $payload['client_id'],
            'fournisseur_id' => $payload['fournisseur_id'],
            'numero_devis' => strtoupper($payload['numero_devis']),
        ]);
    }

    public function test_devis_can_be_created_without_mission(): void
    {
        $user = User::factory()->create();
        $devis = Devis::factory()->create();

        $payload = [
            'mission_id' => null,
            'client_id' => $devis->client_id,
            'fournisseur_id' => $devis->fournisseur_id,
            'numero_devis' => 'DEV-'.date('Y').'-0099',
            'date_devis' => '2026-02-01',
            'description' => 'Devis sans mission',
            'quantite' => 1,
            'prix_unitaire' => 500,
        ];

        $response = $this->actingAs($user)->post('/devis', $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('devis', [
            'numero_devis' => 'DEV-'.date('Y').'-0099',
            'mission_id' => null,
        ]);
    }

    public function test_devis_generates_next_numero(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();
        Devis::factory()->create([
            'numero_devis' => 'DEV-'.date('Y').'-0001',
            'mission_id' => $mission->id,
            'client_id' => $mission->client_id,
            'fournisseur_id' => $mission->fournisseur_id,
        ]);

        $this->assertSame('DEV-'.date('Y').'-0002', Devis::genererNumeroDevis());
    }

    public function test_devis_edit_page_is_displayed(): void
    {
        $user = User::factory()->create();
        $devis = Devis::factory()->create();

        $this->actingAs($user)
            ->get('/devis/'.$devis->id.'/edit')
            ->assertOk()
            ->assertSee('Modifier le devis '.$devis->numero_devis, false);
    }

    public function test_devis_can_be_updated(): void
    {
        $user = User::factory()->create();
        $devis = Devis::factory()->create();
        $payload = $this->validPayload($devis->mission);
        $payload['numero_devis'] = $devis->numero_devis;
        $payload['quantite'] = 5;
        $payload['prix_unitaire'] = 2000;

        $response = $this->actingAs($user)->put('/devis/'.$devis->id, $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/devis');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('devis', [
            'id' => $devis->id,
            'quantite' => 5,
        ]);
    }

    public function test_devis_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $devis = Devis::factory()->create();

        $response = $this->actingAs($user)->delete('/devis/'.$devis->id);

        $response->assertRedirect('/devis');
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('devis', ['id' => $devis->id]);
    }

    public function test_devis_show_page_is_displayed(): void
    {
        $user = User::factory()->create();
        $devis = Devis::factory()->create();

        $this->actingAs($user)
            ->get('/devis/'.$devis->id)
            ->assertOk()
            ->assertSee($devis->numero_devis)
            ->assertSee($devis->client->nom);
    }

    public function test_devis_pdf_can_be_downloaded(): void
    {
        $user = User::factory()->create();
        $devis = Devis::factory()->create();

        $response = $this->actingAs($user)->get('/devis/'.$devis->id.'/pdf');

        $response->assertOk();
        $response->assertDownload('devis-'.$devis->numero_devis.'.pdf');
    }

    public function test_devis_pdf_uses_fournisseur_branding(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $devis = Devis::factory()->create();
        $fournisseur = $devis->fournisseur;

        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
        Storage::disk('public')->put('fournisseurs/logo.png', $png);
        Storage::disk('public')->put('fournisseurs/signature.png', $png);

        $fournisseur->update([
            'logo' => 'fournisseurs/logo.png',
            'signature' => 'fournisseurs/signature.png',
            'footer' => 'Footer du fournisseur',
        ]);

        $response = $this->actingAs($user)->get('/devis/'.$devis->id.'/pdf');

        $response->assertOk();
        $response->assertDownload('devis-'.$devis->numero_devis.'.pdf');
    }
}
