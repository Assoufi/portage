<?php

namespace Tests\Feature;

use App\Models\Attestation;
use App\Models\Client;
use App\Models\Consultant;
use App\Models\Devis;
use App\Models\Fournisseur;
use App\Models\Mission;
use App\Models\Paiement;
use App\Models\Repartition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DestroyFiltresTest extends TestCase
{
    use RefreshDatabase;

    public static function provider(): array
    {
        return [
            'clients' => [
                'uri' => 'clients',
                'creer' => fn () => Client::factory()->create(),
                'table' => 'clients',
            ],
            'fournisseurs' => [
                'uri' => 'fournisseurs',
                'creer' => fn () => Fournisseur::factory()->create(),
                'table' => 'fournisseurs',
            ],
            'consultants' => [
                'uri' => 'consultants',
                'creer' => fn () => Consultant::factory()->create(),
                'table' => 'consultants',
            ],
            'missions' => [
                'uri' => 'missions',
                'creer' => fn () => Mission::factory()->create(),
                'table' => 'missions',
            ],
            'devis' => [
                'uri' => 'devis',
                'creer' => fn () => Devis::factory()->create(),
                'table' => 'devis',
            ],
            'attestations' => [
                'uri' => 'attestations',
                'creer' => fn () => Attestation::factory()->create(),
                'table' => 'attestations',
            ],
            'paiements' => [
                'uri' => 'paiements',
                'creer' => fn () => Paiement::create([
                    'client_id'      => Client::factory()->create()->id,
                    'fournisseur_id' => Fournisseur::factory()->create()->id,
                    'reference'      => 'PAY-' . Str::upper(Str::random(8)),
                    'montant'        => 1500,
                    'date_paiement'  => '2026-01-15',
                ]),
                'table' => 'paiements',
            ],
            'repartitions' => [
                'uri' => 'repartitions',
                'creer' => function () {
                    $paiement = Paiement::create([
                        'client_id'      => Client::factory()->create()->id,
                        'fournisseur_id' => Fournisseur::factory()->create()->id,
                        'reference'      => 'PAY-' . Str::upper(Str::random(8)),
                        'montant'        => 1500,
                        'date_paiement'  => '2026-01-15',
                    ]);

                    return Repartition::create([
                        'paiement_id'           => $paiement->id,
                        'consultant_id'         => Consultant::factory()->create()->id,
                        'montant'               => 750,
                        'date_paiement'         => '2026-01-20',
                    ]);
                },
                'table' => 'repartitions',
            ],
        ];
    }

    #[DataProvider('provider')]
    public function test_destroy_redirige_vers_index_avec_les_criteres_conserves(string $uri, callable $creer, string $table): void
    {
        $user    = User::factory()->create();
        $element = $creer();

        $response = $this->actingAs($user)
            ->delete("/{$uri}/" . $element->id . '?recherche=abc&sort=id&page=2');

        $location = $response->assertRedirect()->headers->get('Location');

        $this->assertStringContainsString("/{$uri}", $location);
        $this->assertStringContainsString('recherche=abc', $location);
        $this->assertStringContainsString('sort=id', $location);
        $this->assertStringNotContainsString('page=2', $location);

        $this->assertSoftDeleted($table, ['id' => $element->id]);
    }
}
