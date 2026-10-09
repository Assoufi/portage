<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nom' => 'Test Client',
            'adresse' => '1 rue de Test, Casablanca',
            'email' => 'contact@testclient.ma',
            'type_identification' => 'ICE',
            'num_identification' => '123456789012345',
            'tva' => 20,
            'devise' => 'MAD',
            'statut' => '1',
            'periodicite' => 'Mensuelle',
            'mode_livraison' => ['Papier', 'Email'],
            'telephone' => '0612345678',
            'notifyto' => 'facturation@testclient.ma;compta@testclient.ma',
            'notifycc' => 'copie@testclient.ma',
        ], $overrides);
    }

    public function test_client_index_requires_authentication(): void
    {
        $this->get('/clients')->assertRedirect('/login');
    }

    public function test_create_page_displays_new_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/clients/create')
            ->assertOk()
            ->assertSee('Périodicité')
            ->assertSee('Mode de livraison')
            ->assertSee('Notifier à')
            ->assertSee('Copie notification');
    }

    public function test_edit_page_displays_new_fields(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['mode_livraison' => ['Email', 'Whatsapp']]);

        $this->actingAs($user)
            ->get('/clients/'.$client->id.'/edit')
            ->assertOk()
            ->assertSee('Email')
            ->assertSee('Whatsapp');
    }

    public function test_client_can_be_created_with_notification_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/clients', $this->payload());

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/clients');

        $client = Client::firstWhere('num_identification', '123456789012345');

        $this->assertNotNull($client);
        $this->assertSame('Mensuelle', $client->periodicite);
        $this->assertSame(['Papier', 'Email'], $client->mode_livraison);
        $this->assertSame('0612345678', $client->telephone);
        $this->assertSame('facturation@testclient.ma;compta@testclient.ma', $client->notifyto);
        $this->assertSame('copie@testclient.ma', $client->notifycc);
    }

    public function test_periodicite_must_be_allowed_value(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/clients', $this->payload(['periodicite' => 'Quotidienne']));

        $response->assertSessionHasErrors('periodicite');
    }

    public function test_mode_livraison_must_be_allowed_values(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/clients', $this->payload(['mode_livraison' => ['Papier', 'Fax']]));

        $response->assertSessionHasErrors('mode_livraison.1');
    }

    public function test_telephone_cannot_exceed_twenty_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/clients', $this->payload(['telephone' => str_repeat('1', 21)]));

        $response->assertSessionHasErrors('telephone');
    }

    public function test_notifyto_must_contain_valid_emails(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/clients', $this->payload(['notifyto' => 'valide@test.ma;pas-un-email']));

        $response->assertSessionHasErrors('notifyto');
    }

    public function test_notifycc_must_contain_valid_emails(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/clients', $this->payload(['notifycc' => 'invalide']));

        $response->assertSessionHasErrors('notifycc');
    }

    public function test_client_can_be_updated_with_notification_fields(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $response = $this->actingAs($user)->put('/clients/'.$client->id, $this->payload([
            'num_identification' => $client->num_identification,
            'periodicite' => 'Occasionnelle',
            'mode_livraison' => ['Whatsapp'],
            'telephone' => '0700000000',
            'notifyto' => 'nouveau@test.ma',
            'notifycc' => null,
        ]));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/clients');

        $client->refresh();
        $this->assertSame('Occasionnelle', $client->periodicite);
        $this->assertSame(['Whatsapp'], $client->mode_livraison);
        $this->assertSame('0700000000', $client->telephone);
        $this->assertSame('nouveau@test.ma', $client->notifyto);
        $this->assertNull($client->notifycc);
    }

    public function test_show_page_displays_notification_fields(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'periodicite' => 'Mensuelle',
            'mode_livraison' => ['Papier', 'Whatsapp'],
            'telephone' => '0611223344',
            'notifyto' => 'a@b.ma',
            'notifycc' => 'c@d.ma',
        ]);

        $this->actingAs($user)
            ->get('/clients/'.$client->id)
            ->assertOk()
            ->assertSee('Mensuelle')
            ->assertSee('Papier, Whatsapp')
            ->assertSee('0611223344')
            ->assertSee('a@b.ma')
            ->assertSee('c@d.ma');
    }
}
