<?php

namespace Tests\Feature;

use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FournisseurTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(): array
    {
        return [
            'nom' => 'Fournisseur Images SARL',
            'adresse' => '12 Rue des Tests',
            'ville' => 'Casablanca',
            'email' => 'contact@fournisseur-images.ma',
            'ice' => 'ABC123456789012',
            'rib' => 'FR7630006000011234567890189',
            'iban' => 'FR763000600001123456',
            'taux' => 10,
            'statut' => 1,
        ];
    }

    public function test_fournisseur_can_be_created_with_signature_logo_and_footer(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $payload = $this->validPayload();
        $payload['signature'] = UploadedFile::fake()->image('signature.png');
        $payload['logo'] = UploadedFile::fake()->image('logo.png', 200, 100);
        $payload['footer'] = '  Pied de page optionnel  ';

        $response = $this->actingAs($user)->post('/fournisseurs', $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/fournisseurs');
        $response->assertSessionHas('success');

        $fournisseur = Fournisseur::where('nom', 'Fournisseur Images SARL')->firstOrFail();

        $this->assertNotNull($fournisseur->signature);
        $this->assertNotNull($fournisseur->logo);
        $this->assertSame('Pied de page optionnel', $fournisseur->footer);

        Storage::disk('public')->assertExists($fournisseur->signature);
        Storage::disk('public')->assertExists($fournisseur->logo);
    }

    public function test_fournisseur_can_be_created_without_images_and_footer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/fournisseurs', $this->validPayload());

        $response->assertSessionHasNoErrors();

        $fournisseur = Fournisseur::where('nom', 'Fournisseur Images SARL')->firstOrFail();

        $this->assertNull($fournisseur->signature);
        $this->assertNull($fournisseur->logo);
        $this->assertNull($fournisseur->footer);
        $this->assertNull($fournisseur->signature_url);
        $this->assertNull($fournisseur->logo_url);
    }

    public function test_fournisseur_store_rejects_non_image_file(): void
    {
        $user = User::factory()->create();

        $payload = $this->validPayload();
        $payload['signature'] = UploadedFile::fake()->create('contrat.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post('/fournisseurs', $payload);

        $response->assertSessionHasErrors('signature');
        $this->assertDatabaseCount('fournisseurs', 0);
    }

    public function test_fournisseur_footer_max_length_is_validated(): void
    {
        $user = User::factory()->create();

        $payload = $this->validPayload();
        $payload['footer'] = str_repeat('a', 1001);

        $response = $this->actingAs($user)->post('/fournisseurs', $payload);

        $response->assertSessionHasErrors('footer');
        $this->assertDatabaseCount('fournisseurs', 0);
    }

    public function test_fournisseur_update_replaces_signature_and_keeps_existing_logo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $fournisseur = Fournisseur::factory()->create([
            'signature' => 'fournisseurs/ancienne-signature.png',
            'logo' => 'fournisseurs/existant-logo.png',
        ]);
        Storage::disk('public')->put('fournisseurs/ancienne-signature.png', 'ancienne');
        Storage::disk('public')->put('fournisseurs/existant-logo.png', 'logo');

        $payload = $this->validPayload();
        $payload['nom'] = $fournisseur->nom;
        $payload['signature'] = UploadedFile::fake()->image('nouvelle-signature.png');

        $response = $this->actingAs($user)
            ->put('/fournisseurs/'.$fournisseur->id, $payload);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $fournisseur->refresh();

        Storage::disk('public')->assertMissing('fournisseurs/ancienne-signature.png');
        Storage::disk('public')->assertExists($fournisseur->signature);
        Storage::disk('public')->assertExists('fournisseurs/existant-logo.png');
        $this->assertSame('fournisseurs/existant-logo.png', $fournisseur->logo);
    }

    public function test_fournisseur_update_without_file_keeps_existing_images(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $fournisseur = Fournisseur::factory()->create([
            'signature' => 'fournisseurs/signature-actuelle.png',
            'logo' => 'fournisseurs/logo-actuel.png',
            'footer' => 'Ancien footer',
        ]);
        Storage::disk('public')->put('fournisseurs/signature-actuelle.png', 'sig');
        Storage::disk('public')->put('fournisseurs/logo-actuel.png', 'logo');

        $payload = $this->validPayload();
        $payload['nom'] = $fournisseur->nom;
        $payload['footer'] = 'Nouveau footer';

        $response = $this->actingAs($user)
            ->put('/fournisseurs/'.$fournisseur->id, $payload);

        $response->assertSessionHasNoErrors();

        $fournisseur->refresh();

        $this->assertSame('fournisseurs/signature-actuelle.png', $fournisseur->signature);
        $this->assertSame('fournisseurs/logo-actuel.png', $fournisseur->logo);
        $this->assertSame('Nouveau footer', $fournisseur->footer);
        Storage::disk('public')->assertExists('fournisseurs/signature-actuelle.png');
        Storage::disk('public')->assertExists('fournisseurs/logo-actuel.png');
    }

    public function test_fournisseur_create_form_displays_new_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/fournisseurs/create')
            ->assertOk()
            ->assertSee('Signature (image)')
            ->assertSee('Logo (image)')
            ->assertSee('Footer');
    }

    public function test_fournisseur_show_displays_footer_and_visuels(): void
    {
        $user = User::factory()->create();
        $fournisseur = Fournisseur::factory()->create(['footer' => 'Mentions légales du fournisseur']);

        $this->actingAs($user)
            ->get('/fournisseurs/'.$fournisseur->id)
            ->assertOk()
            ->assertSee('Mentions légales du fournisseur')
            ->assertSee('Visuels');
    }
}
