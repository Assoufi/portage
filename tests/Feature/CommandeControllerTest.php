<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommandeControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(?Mission $mission = null): array
    {
        $mission = $mission ?? Mission::factory()->create();

        return [
            'mission_id' => $mission->id,
            'num_commande' => 'CMD-2026-0001',
            'date_commande' => '2026-01-15',
            'date_debut' => '2026-01-16',
            'date_fin' => '2026-02-15',
            'quantite' => 10,
            'tjm' => $mission->tjm,
            'statut' => 'ACTIF',
        ];
    }

    public function test_commandes_index_is_displayed(): void
    {
        $user = User::factory()->create();
        $commande = Commande::factory()->create();

        $response = $this->actingAs($user)->get('/commandes');

        $response->assertOk();
        $response->assertSee('Commandes');
        $response->assertSee($commande->num_commande);
    }

    public function test_commandes_index_requires_authentication(): void
    {
        $this->get('/commandes')->assertRedirect('/login');
    }

    public function test_commandes_index_filters_by_statut(): void
    {
        $user = User::factory()->create();
        $active = Commande::factory()->create(['num_commande' => 'CMD-2026-1001']);
        $inactive = Commande::factory()->inactif()->create(['num_commande' => 'CMD-2026-1002']);

        $response = $this->actingAs($user)->get('/commandes?statut=ACTIF');

        $response->assertOk();
        $response->assertSee($active->num_commande);
        $response->assertDontSee($inactive->num_commande);
    }

    public function test_commandes_index_filters_by_mission(): void
    {
        $user = User::factory()->create();
        $commande = Commande::factory()->create(['num_commande' => 'CMD-2026-2001']);
        $autreCommande = Commande::factory()->create(['num_commande' => 'CMD-2026-2002']);

        $response = $this->actingAs($user)->get('/commandes?mission_id='.$commande->mission_id);

        $response->assertOk();
        $response->assertSee($commande->num_commande);
        $response->assertDontSee($autreCommande->num_commande);
    }

    public function test_commandes_create_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/commandes/create')
            ->assertOk()
            ->assertSee('Créer une nouvelle commande');
    }

    public function test_commandes_create_form_only_lists_ongoing_missions(): void
    {
        $user = User::factory()->create();
        $enCours = Mission::factory()->enCours()->create(['titre' => 'Mission en cours']);
        $terminee = Mission::factory()->create([
            'titre' => 'Mission terminee',
            'date_debut' => now()->subYear(),
            'date_fin' => now()->subMonths(6),
        ]);

        $response = $this->actingAs($user)->get('/commandes/create');

        $response->assertOk();
        $response->assertSee('Mission en cours');
        $response->assertDontSee('Mission terminee');
    }

    public function test_commandes_create_form_displays_fournisseur_from_selected_mission(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->enCours()->create();

        $response = $this->actingAs($user)->get('/commandes/create');

        $response->assertOk();
        $response->assertSee($mission->fournisseur->nom);
    }

    public function test_commandes_store_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/commandes', [
            'mission_id' => null,
            'num_commande' => null,
            'date_commande' => null,
            'quantite' => null,
            'tjm' => null,
            'statut' => null,
        ]);

        $response->assertSessionHasErrors([
            'mission_id',
            'num_commande',
            'date_commande',
            'quantite',
            'tjm',
            'statut',
        ]);

        $this->assertDatabaseCount('commandes', 0);
    }

    public function test_commandes_store_does_not_accept_fournisseur_id(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        $response = $this->actingAs($user)->post('/commandes', [
            'mission_id' => $mission->id,
            'fournisseur_id' => $mission->fournisseur_id,
            'num_commande' => 'CMD-2026-0009',
            'date_commande' => '2026-01-01',
            'quantite' => 1,
            'tjm' => 500,
            'statut' => 'ACTIF',
        ]);

        $response->assertSessionHasNoErrors();
        $commande = Commande::where('num_commande', 'CMD-2026-0009')->first();
        $this->assertNotNull($commande);
        $this->assertSame($mission->id, $commande->mission_id);
        $this->assertArrayNotHasKey('fournisseur_id', $commande->getAttributes());
    }

    public function test_commandes_store_validates_unique_num_commande(): void
    {
        $user = User::factory()->create();
        $commande = Commande::factory()->create(['num_commande' => 'CMD-2026-0001']);

        $payload = $this->validPayload($commande->mission);
        $payload['num_commande'] = 'CMD-2026-0001';

        $response = $this->actingAs($user)->post('/commandes', $payload);

        $response->assertSessionHasErrors('num_commande');
    }

    public function test_commandes_store_validates_date_fin_after_date_debut(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $payload['date_debut'] = '2026-03-01';
        $payload['date_fin'] = '2026-02-01';

        $response = $this->actingAs($user)->post('/commandes', $payload);

        $response->assertSessionHasErrors('date_fin');
    }

    public function test_commandes_store_validates_statut_is_in_list(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $payload['statut'] = 'INVALIDE';

        $response = $this->actingAs($user)->post('/commandes', $payload);

        $response->assertSessionHasErrors('statut');
    }

    public function test_commande_can_be_created(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();

        $response = $this->actingAs($user)->post('/commandes', $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('commandes', [
            'mission_id' => $payload['mission_id'],
            'num_commande' => $payload['num_commande'],
            'statut' => 'ACTIF',
        ]);
    }

    public function test_commande_edit_page_is_displayed(): void
    {
        $user = User::factory()->create();
        $commande = Commande::factory()->create();

        $this->actingAs($user)
            ->get('/commandes/'.$commande->id.'/edit')
            ->assertOk()
            ->assertSee('Modifier la commande '.$commande->num_commande, false);
    }

    public function test_commande_edit_form_keeps_mission_even_if_no_longer_ongoing(): void
    {
        $user = User::factory()->create();
        $commande = Commande::factory()->create();
        // La mission n'est plus en cours (terminée depuis 6 mois)
        $commande->mission->update([
            'date_debut' => now()->subYear(),
            'date_fin' => now()->subMonths(6),
        ]);

        $response = $this->actingAs($user)->get('/commandes/'.$commande->id.'/edit');

        $response->assertOk();
        $response->assertSee($commande->mission->titre);
    }

    public function test_commande_can_be_updated(): void
    {
        $user = User::factory()->create();
        $commande = Commande::factory()->create();
        $payload = $this->validPayload($commande->mission);
        $payload['num_commande'] = $commande->num_commande;
        $payload['quantite'] = 20;
        $payload['statut'] = 'INACTIF';

        $response = $this->actingAs($user)->put('/commandes/'.$commande->id, $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/commandes');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('commandes', [
            'id' => $commande->id,
            'quantite' => 20,
            'statut' => 'INACTIF',
        ]);
    }

    public function test_commande_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $commande = Commande::factory()->create();

        $response = $this->actingAs($user)->delete('/commandes/'.$commande->id);

        $response->assertRedirect('/commandes');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('commandes', ['id' => $commande->id]);
    }

    public function test_commande_show_page_is_displayed(): void
    {
        $user = User::factory()->create();
        $commande = Commande::factory()->create();

        $this->actingAs($user)
            ->get('/commandes/'.$commande->id)
            ->assertOk()
            ->assertSee($commande->num_commande)
            ->assertSee($commande->mission->titre);
    }

    public function test_commandes_index_shows_commandes_menu(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/commandes');

        $response->assertOk();
        $response->assertSee('Missions');
    }
}
