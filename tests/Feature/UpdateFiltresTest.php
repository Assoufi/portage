<?php

namespace Tests\Feature;

use App\Models\Attestation;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Consultant;
use App\Models\Devis;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Models\Mission;
use App\Models\Paiement;
use App\Models\Repartition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateFiltresTest extends TestCase
{
    use RefreshDatabase;

    public static function provider(): array
    {
        return [
            'attestations' => ['attestations', fn () => Attestation::factory()->create()],
            'commandes' => ['commandes', fn () => Commande::factory()->create()],
            'consultants' => ['consultants', fn () => Consultant::factory()->create()],
            'devis' => ['devis', fn () => Devis::factory()->create()],
            'factures' => [
                'factures',
                fn () => Facture::create([
                    'fournisseur_id' => Fournisseur::factory()->create()->id,
                    'client_id' => Client::factory()->create()->id,
                    'numero_facture' => '2026-'.random_int(100, 999),
                    'date_facture' => '2026-01-15',
                    'total_ht' => 100,
                    'montant_ttc' => 120,
                ]),
            ],
            'missions' => ['missions', fn () => Mission::factory()->create()],
            'paiements' => [
                'paiements',
                fn () => Paiement::create([
                    'client_id' => Client::factory()->create()->id,
                    'fournisseur_id' => Fournisseur::factory()->create()->id,
                    'reference' => 'PAY-'.Str::upper(Str::random(8)),
                    'montant' => 1500,
                    'date_paiement' => '2026-01-15',
                ]),
            ],
            'repartitions' => [
                'repartitions',
                fn () => Repartition::create([
                    'paiement_id' => Paiement::create([
                        'client_id' => Client::factory()->create()->id,
                        'fournisseur_id' => Fournisseur::factory()->create()->id,
                        'reference' => 'PAY-'.Str::upper(Str::random(8)),
                        'montant' => 1500,
                        'date_paiement' => '2026-01-15',
                    ])->id,
                    'consultant_id' => Consultant::factory()->create()->id,
                    'montant' => 750,
                    'date_paiement' => '2026-01-20',
                ]),
            ],
        ];
    }

    #[DataProvider('provider')]
    public function test_update_redirige_vers_index_en_conservant_les_filtres(string $uri, callable $creer): void
    {
        $user = User::factory()->create();
        $element = $creer();

        $payload = $element->toArray();

        // Faker peut produire un nom contenant un point, refusé par ConsultantRequest.
        if ($uri === 'consultants') {
            $payload['nom'] = 'Consultant Test';
        }

        $response = $this->actingAs($user)
            ->put("/{$uri}/{$element->id}?foo=abc&page=2", $payload);

        $response->assertSessionHasNoErrors();

        $location = $response->assertRedirect()->headers->get('Location');

        $this->assertStringContainsString("/{$uri}?foo=abc", $location);
        $this->assertStringNotContainsString('page=2', $location);
    }
}
