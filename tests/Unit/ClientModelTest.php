<?php

namespace Tests\Unit;

use App\Models\Client;
use Tests\TestCase;

class ClientModelTest extends TestCase
{
    public function test_new_notification_fields_are_fillable(): void
    {
        $client = new Client;

        foreach (['periodicite', 'mode_livraison', 'telephone', 'notifyto', 'notifycc'] as $attribut) {
            $this->assertTrue($client->isFillable($attribut), "L'attribut {$attribut} devrait être fillable.");
        }
    }

    public function test_mode_livraison_is_cast_to_array(): void
    {
        $client = new Client(['mode_livraison' => ['Papier', 'Email', 'Whatsapp']]);

        $this->assertSame(['Papier', 'Email', 'Whatsapp'], $client->mode_livraison);
    }

    public function test_mode_livraison_accepte_les_valeurs_double_encodees_legacy(): void
    {
        // Brut '["Papier"]' encodé une seule fois tel que stocké par les imports legacy
        $client = new Client;
        $client->setRawAttributes(['mode_livraison' => '"Papier"']);

        $this->assertSame(['Papier'], $client->mode_livraison);
        $this->assertIsArray($client->mode_livraison);
    }

    public function test_mode_livraison_retourne_un_tableau_vide_si_valeur_legacy_vide(): void
    {
        $client = new Client;
        $client->setRawAttributes(['mode_livraison' => '""']);

        $this->assertSame([], $client->mode_livraison);
    }

    public function test_mode_livraison_sauvegarde_un_tableau_nettoye_en_json_simple(): void
    {
        $client = new Client(['mode_livraison' => [' Papier ', '', 'Email', 42]]);

        $this->assertSame('["Papier","Email"]', $client->getAttributes()['mode_livraison']);
    }

    public function test_telephone_mutator_trims_and_nullifies_empty(): void
    {
        $this->assertSame('0612345678', (new Client(['telephone' => '  0612345678  ']))->telephone);
        $this->assertNull((new Client(['telephone' => '   ']))->telephone);
    }

    public function test_notification_email_mutators_trim_and_nullify_empty(): void
    {
        $client = new Client([
            'notifyto' => '  a@b.ma;c@d.ma  ',
            'notifycc' => '   ',
        ]);

        $this->assertSame('a@b.ma;c@d.ma', $client->notifyto);
        $this->assertNull($client->notifycc);
    }

    public function test_periodicite_is_fillable(): void
    {
        $client = new Client(['periodicite' => 'Mensuelle']);

        $this->assertSame('Mensuelle', $client->periodicite);
    }
}
