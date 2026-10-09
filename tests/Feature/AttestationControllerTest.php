<?php

namespace Tests\Feature;

use App\Models\Attestation;
use App\Models\Consultant;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttestationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(?Mission $mission = null): array
    {
        $mission = $mission ?? Mission::factory()->create();

        return [
            'consultant_id' => $mission->consultant_id,
            'mission_id' => $mission->id,
            'fonction' => 'Consultant Senior',
            'date_attestation' => '2026-01-15',
            'date_signature' => '2026-01-15',
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-01-31',
            'client' => 'ACME SARL',
        ];
    }

    public function test_attestation_index_is_displayed(): void
    {
        $user = User::factory()->create();
        $attestation = Attestation::factory()->create();

        $response = $this->actingAs($user)->get('/attestations');

        $response->assertOk();
        $response->assertSee('Attestations de mission');
        $response->assertSee('Documents');
        $response->assertSee($attestation->client);
    }

    public function test_attestation_index_requires_authentication(): void
    {
        $this->get('/attestations')->assertRedirect('/login');
    }

    public function test_attestation_create_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/attestations/create')
            ->assertOk()
            ->assertSee('Créer une nouvelle attestation');
    }

    public function test_create_form_only_shows_consultants_having_missions(): void
    {
        $user = User::factory()->create();
        $consultantAvecMission = Mission::factory()->create()->consultant;
        $consultantSansMission = Consultant::factory()
            ->create(['nom' => 'Xavier Sans Mission']);

        $this->actingAs($user)
            ->get('/attestations/create')
            ->assertOk()
            ->assertSee($consultantAvecMission->nom)
            ->assertDontSee('Xavier Sans Mission');
    }

    public function test_edit_form_shows_consultant_and_missions_of_attestation(): void
    {
        $user = User::factory()->create();
        $attestation = Attestation::factory()->create();
        $autreConsultant = Consultant::factory()
            ->create(['nom' => 'Yves Sans Mission']);

        $response = $this->actingAs($user)
            ->get('/attestations/'.$attestation->id.'/edit')
            ->assertOk()
            ->assertSee($attestation->consultant->nom);

        $this->assertStringContainsString(
            '\\u0022consultantId\\u0022:'.$attestation->consultant_id,
            $response->getContent()
        );
        $this->assertStringNotContainsString('Yves Sans Mission', $response->getContent());
    }

    public function test_attestation_can_be_created(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();

        $response = $this->actingAs($user)->post('/attestations', $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attestations', [
            'consultant_id' => $payload['consultant_id'],
            'mission_id' => $payload['mission_id'],
            'fonction' => $payload['fonction'],
            'client' => $payload['client'],
        ]);
    }

    public function test_attestation_store_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/attestations', [
            'consultant_id' => null,
            'mission_id' => null,
            'fonction' => null,
            'date_attestation' => null,
            'date_signature' => null,
            'date_debut' => null,
            'date_fin' => null,
            'client' => null,
        ]);

        $response->assertSessionHasErrors([
            'consultant_id',
            'mission_id',
            'fonction',
            'date_attestation',
            'date_signature',
            'date_debut',
            'client',
        ]);

        $this->assertDatabaseCount('attestations', 0);
    }

    public function test_attestation_can_be_created_without_date_fin(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['date_fin']);

        $response = $this->actingAs($user)->post('/attestations', $payload);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attestations', [
            'mission_id' => $payload['mission_id'],
            'date_fin' => null,
        ]);
    }

    public function test_attestation_store_validates_date_fin_after_or_equal_date_debut(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $payload['date_debut'] = '2026-02-01';
        $payload['date_fin'] = '2026-01-01';

        $response = $this->actingAs($user)->post('/attestations', $payload);

        $response->assertSessionHasErrors('date_fin');
        $this->assertDatabaseCount('attestations', 0);
    }

    public function test_attestation_store_validates_foreign_keys_exist(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $payload['consultant_id'] = 999999;
        $payload['mission_id'] = 999999;

        $response = $this->actingAs($user)->post('/attestations', $payload);

        $response->assertSessionHasErrors(['consultant_id', 'mission_id']);
    }

    public function test_attestation_show_page_is_displayed(): void
    {
        $user = User::factory()->create();
        $attestation = Attestation::factory()->create();

        $this->actingAs($user)
            ->get('/attestations/'.$attestation->id)
            ->assertOk()
            ->assertSee($attestation->client)
            ->assertSee($attestation->fonction);
    }

    public function test_attestation_edit_page_is_displayed(): void
    {
        $user = User::factory()->create();
        $attestation = Attestation::factory()->create();

        $this->actingAs($user)
            ->get('/attestations/'.$attestation->id.'/edit')
            ->assertOk()
            ->assertSee('Modifier l\'attestation', false);
    }

    public function test_attestation_can_be_updated(): void
    {
        $user = User::factory()->create();
        $attestation = Attestation::factory()->create();
        $payload = $this->validPayload($attestation->mission);
        $payload['fonction'] = 'Lead Consultant';
        $payload['client'] = 'Nouveau Client SARL';

        $response = $this->actingAs($user)
            ->put('/attestations/'.$attestation->id, $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/attestations');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attestations', [
            'id' => $attestation->id,
            'fonction' => 'Lead Consultant',
            'client' => 'Nouveau Client SARL',
        ]);
    }

    public function test_attestation_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $attestation = Attestation::factory()->create();

        $response = $this->actingAs($user)
            ->delete('/attestations/'.$attestation->id);

        $response->assertRedirect('/attestations');
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('attestations', ['id' => $attestation->id]);
    }

    public function test_attestation_pdf_can_be_downloaded(): void
    {
        $user = User::factory()->create();
        $attestation = Attestation::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attestations/'.$attestation->id.'/pdf');

        $response->assertOk();
        $response->assertDownload('attestation-mission-'.$attestation->id.'.pdf');
    }

    public function test_attestation_pdf_uses_fournisseur_branding(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $attestation = Attestation::factory()->create();
        $fournisseur = $attestation->mission->fournisseur;

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

        $response = $this->actingAs($user)
            ->get('/attestations/'.$attestation->id.'/pdf');

        $response->assertOk();
        $response->assertDownload('attestation-mission-'.$attestation->id.'.pdf');
    }
}
