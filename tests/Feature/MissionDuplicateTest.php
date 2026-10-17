<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionDuplicateTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    public function test_duplicate_requires_authentication(): void
    {
        $mission = Mission::factory()->create();

        $this->get('/missions/'.$mission->id.'/dupliquer')->assertRedirect('/login');
    }

    public function test_duplicate_redirects_to_create_with_clone_parameter(): void
    {
        $user = $this->user();
        $mission = Mission::factory()->create();

        $this->actingAs($user)
            ->get('/missions/'.$mission->id.'/dupliquer')
            ->assertRedirect(route('missions.create', ['clone' => $mission->id]));
    }

    public function test_duplicate_of_unknown_mission_returns_404(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get('/missions/999999/dupliquer')->assertNotFound();
    }

    public function test_create_with_clone_prefills_mission_data(): void
    {
        $user = $this->user();
        $mission = Mission::factory()->create([
            'titre' => 'Mission à dupliquer',
            'formule' => 'TJM',
            'taux' => 25.50,
            'tjm' => 800.00,
            'prix_vente' => 40000.00,
            'delai_paiement' => 45,
            'remarques' => 'Remarques de la mission source',
        ]);

        $response = $this->actingAs($user)->get('/missions/create?clone='.$mission->id);

        $response->assertOk();
        $response->assertViewHas('clone', function (array $clone) use ($mission) {
            return $clone['consultant_id'] === $mission->consultant_id
                && $clone['client_id'] === $mission->client_id
                && $clone['fournisseur_id'] === $mission->fournisseur_id
                && $clone['titre'] === 'Mission à dupliquer'
                && $clone['formule'] === 'TJM'
                && (float) $clone['taux'] === 25.50
                && (float) $clone['tjm'] === 800.00
                && (float) $clone['prix_vente'] === 40000.00
                && $clone['delai_paiement'] === 45
                && $clone['remarques'] === 'Remarques de la mission source'
                && $clone['date_debut'] === $mission->date_debut->format('Y-m-d')
                && $clone['date_fin'] === $mission->date_fin?->format('Y-m-d')
                && !array_key_exists('id', $clone)
                && !array_key_exists('created_at', $clone)
                && !array_key_exists('updated_at', $clone);
        });
    }

    public function test_create_without_clone_has_no_clone_data(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->get('/missions/create')
            ->assertOk()
            ->assertViewHas('clone', null);
    }

    public function test_create_with_unknown_clone_id_has_no_clone_data(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->get('/missions/create?clone=999999')
            ->assertOk()
            ->assertViewHas('clone', null);
    }

    public function test_duplicated_data_can_be_stored_as_new_mission(): void
    {
        $user = $this->user();
        $mission = Mission::factory()->create([
            'titre' => 'Mission à dupliquer',
            'formule' => 'TJM',
            'taux' => 25.50,
            'tjm' => 800.00,
            'prix_vente' => 40000.00,
            'delai_paiement' => 45,
            'remarques' => 'Remarques de la mission source',
        ]);

        $response = $this->actingAs($user)->post('/missions', [
            'consultant_id' => $mission->consultant_id,
            'client_id' => $mission->client_id,
            'fournisseur_id' => $mission->fournisseur_id,
            'titre' => 'Mission à dupliquer',
            'formule' => 'TJM',
            'taux' => 25.50,
            'tjm' => 800.00,
            'prix_vente' => 40000.00,
            'date_debut' => $mission->date_debut->format('Y-m-d'),
            'date_fin' => $mission->date_fin?->format('Y-m-d'),
            'delai_paiement' => 45,
            'remarques' => 'Remarques de la mission source',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/missions');
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('missions', 2);
        $this->assertDatabaseHas('missions', [
            'consultant_id' => $mission->consultant_id,
            'client_id' => $mission->client_id,
            'fournisseur_id' => $mission->fournisseur_id,
            'titre' => 'Mission à dupliquer',
            'formule' => 'TJM',
            'prix_vente' => 40000.00,
            'delai_paiement' => 45,
            'remarques' => 'Remarques de la mission source',
            'deleted_at' => null,
        ]);
    }

    public function test_duplicate_fails_validation_when_consultant_is_inactive(): void
    {
        $user = $this->user();
        $mission = Mission::factory()->create();
        $mission->consultant->update(['statut' => false]);

        $response = $this->actingAs($user)->post('/missions', [
            'consultant_id' => $mission->consultant_id,
            'client_id' => $mission->client_id,
            'fournisseur_id' => $mission->fournisseur_id,
            'titre' => $mission->titre,
            'formule' => $mission->formule,
            'taux' => $mission->taux,
            'tjm' => $mission->tjm,
            'prix_vente' => $mission->prix_vente,
            'date_debut' => $mission->date_debut->format('Y-m-d'),
            'date_fin' => $mission->date_fin?->format('Y-m-d'),
            'delai_paiement' => $mission->delai_paiement,
            'remarques' => $mission->remarques,
        ]);

        $response->assertSessionHasErrors('consultant_id');
        $this->assertDatabaseCount('missions', 1);
    }

    public function test_index_shows_duplicate_button(): void
    {
        $user = $this->user();
        $mission = Mission::factory()->create();

        $this->actingAs($user)
            ->get('/missions')
            ->assertOk()
            ->assertSee(route('missions.dupliquer', $mission), false);
    }

    public function test_show_shows_duplicate_button(): void
    {
        $user = $this->user();
        $mission = Mission::factory()->create();

        $this->actingAs($user)
            ->get('/missions/'.$mission->id)
            ->assertOk()
            ->assertSee(route('missions.dupliquer', $mission), false)
            ->assertSee('Dupliquer');
    }

    public function test_edit_shows_duplicate_button(): void
    {
        $user = $this->user();
        $mission = Mission::factory()->create();

        $this->actingAs($user)
            ->get('/missions/'.$mission->id.'/edit')
            ->assertOk()
            ->assertSee(route('missions.dupliquer', $mission), false)
            ->assertSee('Dupliquer');
    }

    public function test_create_page_shows_duplication_banner_when_cloning(): void
    {
        $user = $this->user();
        $mission = Mission::factory()->create(['titre' => 'Mission à dupliquer']);

        $this->actingAs($user)
            ->get('/missions/create?clone='.$mission->id)
            ->assertOk()
            ->assertSee('Duplication de la mission')
            ->assertSee('Mission à dupliquer');
    }
}
