<?php

namespace Tests\Feature;

use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FournisseurUpdateFilterTest extends TestCase
{
    use RefreshDatabase;

    private function creerFournisseur(array $attributes = []): Fournisseur
    {
        return Fournisseur::factory()->create(array_merge([
            'nom' => 'Fournisseur Novancy SARL',
        ], $attributes));
    }

    public function test_update_redirige_vers_index_avec_les_criteres_conserves(): void
    {
        $user = User::factory()->create();
        $fournisseur = $this->creerFournisseur();

        $response = $this->actingAs($user)
            ->put('/fournisseurs/'.$fournisseur->id.'?search=novancy&statut=actif', [
                'nom' => 'Fournisseur Novancy SARL',
                'statut' => 1,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('fournisseurs.index', [
            'search' => 'novancy',
            'statut' => 'actif',
        ]));
    }

    public function test_update_supprime_le_parametre_page_de_la_redirection(): void
    {
        $user = User::factory()->create();
        $fournisseur = $this->creerFournisseur();

        $response = $this->actingAs($user)
            ->put('/fournisseurs/'.$fournisseur->id.'?search=novancy&page=3&direction=asc', [
                'nom' => 'Fournisseur Novancy SARL',
                'statut' => 1,
            ]);

        $response->assertSessionHasNoErrors();

        $location = $response->assertRedirect()->headers->get('Location');

        $this->assertStringContainsString('search=novancy', $location);
        $this->assertStringContainsString('direction=asc', $location);
        $this->assertStringNotContainsString('page=3', $location);
    }

    public function test_update_sans_filtres_redirige_vers_index_simple(): void
    {
        $user = User::factory()->create();
        $fournisseur = $this->creerFournisseur();

        $response = $this->actingAs($user)
            ->put('/fournisseurs/'.$fournisseur->id, [
                'nom' => 'Fournisseur Novancy SARL',
                'statut' => 1,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/fournisseurs');
    }

    public function test_le_lien_modifier_de_l_index_transmet_la_query_string(): void
    {
        $user = User::factory()->create();
        $fournisseur = $this->creerFournisseur();

        $response = $this->actingAs($user)
            ->get('/fournisseurs?search=novancy');

        $response->assertOk()
            ->assertSee('/fournisseurs/'.$fournisseur->id.'/edit?search=novancy', false);
    }

    public function test_le_formulaire_d_edition_transmet_la_query_string(): void
    {
        $user = User::factory()->create();
        $fournisseur = $this->creerFournisseur();

        $this->actingAs($user)
            ->get('/fournisseurs/'.$fournisseur->id.'/edit?search=novancy&statut=actif')
            ->assertOk()
            ->assertSee('/fournisseurs/'.$fournisseur->id.'?search=novancy&amp;statut=actif', false);
    }
}
