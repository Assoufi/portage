<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientUpdateFilterTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nom' => 'Client Test',
            'adresse' => '1 rue de Test, Casablanca',
            'email' => 'contact@testclient.ma',
            'type_identification' => 'ICE',
            'num_identification' => '123456789012345',
            'tva' => 20,
            'devise' => 'MAD',
            'statut' => '1',
        ], $overrides);
    }

    public function test_update_redirige_vers_index_avec_les_criteres_conserves(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $response = $this->actingAs($user)
            ->put('/clients/'.$client->id.'?search=test&statut=actif&devise=MAD', $this->payload([
                'num_identification' => $client->num_identification,
            ]));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('clients.index', [
            'search' => 'test',
            'statut' => 'actif',
            'devise' => 'MAD',
        ]));
    }

    public function test_update_supprime_le_parametre_page_de_la_redirection(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $response = $this->actingAs($user)
            ->put('/clients/'.$client->id.'?statut=actif&page=3&sort=nom&direction=asc', $this->payload([
                'num_identification' => $client->num_identification,
            ]));

        $response->assertSessionHasNoErrors();

        $location = $response->assertRedirect()->headers->get('Location');

        $this->assertStringContainsString('statut=actif', $location);
        $this->assertStringContainsString('sort=nom', $location);
        $this->assertStringContainsString('direction=asc', $location);
        $this->assertStringNotContainsString('page=3', $location);
    }

    public function test_update_sans_filtres_redirige_vers_index_simple(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $response = $this->actingAs($user)
            ->put('/clients/'.$client->id, $this->payload([
                'num_identification' => $client->num_identification,
            ]));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/clients');
    }

    public function test_le_lien_modifier_de_l_index_transmet_la_query_string(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $response = $this->actingAs($user)
            ->get('/clients?statut=actif&devise=MAD');

        $response->assertOk()
            ->assertSee('/clients/'.$client->id.'/edit?devise=MAD&amp;statut=actif', false);
    }

    public function test_le_formulaire_d_edition_transmet_la_query_string(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user)
            ->get('/clients/'.$client->id.'/edit?search=test&statut=actif')
            ->assertOk()
            ->assertSee('/clients/'.$client->id.'?search=test&amp;statut=actif', false);
    }

    public function test_le_lien_voir_de_l_index_transmet_la_query_string(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $expected = 'href="'.route('clients.show', $client).'?statut=actif"';

        $this->actingAs($user)
            ->get('/clients?statut=actif')
            ->assertOk()
            ->assertSee($expected, false);
    }

    public function test_annuler_dans_la_page_edition_transmet_la_query_string(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $expected = 'href="'.str_replace('&', '&amp;', route('clients.index', [
            'search' => 'test',
            'statut' => 'actif',
        ])).'"';

        $this->actingAs($user)
            ->get('/clients/'.$client->id.'/edit?search=test&statut=actif')
            ->assertOk()
            ->assertSee($expected, false);
    }

    public function test_les_liens_de_la_page_show_transmettent_la_query_string(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $response = $this->actingAs($user)
            ->get('/clients/'.$client->id.'?search=test&statut=actif');

        $response->assertOk()
            ->assertSee('href="'.str_replace('&', '&amp;', route('clients.index', [
                'search' => 'test',
                'statut' => 'actif',
            ])).'"', false)
            ->assertSee('href="'.str_replace('&', '&amp;', route('clients.edit', [
                'client' => $client,
                'search' => 'test',
                'statut' => 'actif',
            ])).'"', false);
    }
}
