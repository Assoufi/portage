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

class FiltresNavigationTest extends TestCase
{
    use RefreshDatabase;

    public static function provider(): array
    {
        return [
            'attestations' => [
                'attestations',
                fn () => Attestation::factory()->create(),
            ],
            'commandes' => [
                'commandes',
                fn () => Commande::factory()->create(),
            ],
            'consultants' => [
                'consultants',
                fn () => Consultant::factory()->create(),
            ],
            'devis' => [
                'devis',
                fn () => Devis::factory()->create(),
            ],
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
            'missions' => [
                'missions',
                fn () => Mission::factory()->create(),
            ],
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
    public function test_les_liens_de_l_index_conservent_les_filtres(string $uri, callable $creer): void
    {
        $user = User::factory()->create();
        $element = $creer();

        $this->actingAs($user)
            ->get("/{$uri}?foo=abc")
            ->assertOk()
            ->assertSee('href="'.route($uri.'.show', $element).'?foo=abc"', false)
            ->assertSee('href="'.route($uri.'.edit', $element).'?foo=abc"', false);
    }

    #[DataProvider('provider')]
    public function test_le_formulaire_et_le_lien_annuler_de_la_page_edition_conservent_les_filtres(string $uri, callable $creer): void
    {
        $user = User::factory()->create();
        $element = $creer();

        $this->actingAs($user)
            ->get(route($uri.'.edit', $element).'?foo=abc')
            ->assertOk()
            ->assertSee('action="'.route($uri.'.update', $element).'?foo=abc"', false)
            ->assertSee('href="'.route($uri.'.index').'?foo=abc"', false);
    }

    #[DataProvider('provider')]
    public function test_les_liens_de_la_page_show_conservent_les_filtres(string $uri, callable $creer): void
    {
        $user = User::factory()->create();
        $element = $creer();

        $this->actingAs($user)
            ->get(route($uri.'.show', $element).'?foo=abc')
            ->assertOk()
            ->assertSee('href="'.route($uri.'.edit', $element).'?foo=abc"', false)
            ->assertSee('href="'.route($uri.'.index').'?foo=abc"', false);
    }
}
