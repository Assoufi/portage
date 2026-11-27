<?php

namespace App\Services;

use App\Models\Consultant;
use App\Models\Facture;
use App\Models\Mission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Rattache les factures importées à un consultant (via le bénéficiaire) puis
 * à une mission (via consultant/client/fournisseur et la date de facture).
 *
 * Les traitements sont limités aux identifiants fournis par l'appelant : les
 * factures historiques dont consultant_id/mission_id sont NULL ne sont jamais
 * modifiées par un import.
 */
class FactureRattachementService
{
    private const TAILLE_LOT = 500;

    /**
     * Rattache les factures persistées. En mode simulation, aucun écrit n'est
     * effectué mais les compteurs reflètent ce qui serait fait.
     *
     * @param  array<int, int|string>  $factureIds
     * @return array{consultants: int, missions: int, non_rattachees: array<int, array<string, mixed>>, ambigus: array<int, string>}
     */
    public function rattacher(array $factureIds, bool $simulation = false): array
    {
        $ids = $this->normaliserIds($factureIds);

        if ($ids === []) {
            return $this->rapportVide();
        }

        $executer = function () use ($ids, $simulation): array {
            $rapport = $this->rapportVide();
            [$indexConsultants, $ambigus] = $this->indexConsultants();
            $rapport['ambigus'] = $ambigus;

            foreach (array_chunk($ids, self::TAILLE_LOT) as $lot) {
                $factures = Facture::query()
                    ->whereIn('id', $lot)
                    ->get([
                        'id',
                        'consultant_id',
                        'mission_id',
                        'beneficiaire',
                        'client_id',
                        'fournisseur_id',
                        'date_facture',
                    ]);

                $this->traiterLot($factures, $indexConsultants, $simulation, $rapport);
                $this->collecterNonRattachees($factures, $rapport);
            }

            if (! $simulation && ($rapport['consultants'] > 0 || $rapport['missions'] > 0)) {
                Cache::forget('factures.stats');
            }

            return $rapport;
        };

        return $simulation ? $executer() : DB::transaction($executer);
    }

    /**
     * Prévisualise le rattachement de lignes d'import non encore persistées.
     * Chaque ligne accepte : ligne, beneficiaire, client_id, fournisseur_id,
     * date_facture. Aucun écrit en base n'est effectué.
     *
     * @param  array<int, array<string, mixed>>  $lignes
     * @return array{consultants: int, missions: int, non_rattachees: array<int, array<string, mixed>>, ambigus: array<int, string>}
     */
    public function previsualiser(array $lignes): array
    {
        $rapport = $this->rapportVide();
        [$indexConsultants, $ambigus] = $this->indexConsultants();
        $rapport['ambigus'] = $ambigus;

        $virtuelles = [];

        foreach ($lignes as $ligne) {
            $facture = new Facture;
            $facture->forceFill([
                'beneficiaire' => $ligne['beneficiaire'] ?? null,
                'client_id' => $ligne['client_id'] ?? null,
                'fournisseur_id' => $ligne['fournisseur_id'] ?? null,
                'date_facture' => $ligne['date_facture'] ?? null,
            ]);

            $virtuelles[] = [
                'facture' => $facture,
                'ligne' => $ligne['ligne'] ?? null,
            ];
        }

        foreach (array_chunk($virtuelles, self::TAILLE_LOT) as $lot) {
            $factures = collect($lot)->pluck('facture');
            $metadonnees = [];

            foreach ($lot as $element) {
                $metadonnees[spl_object_id($element['facture'])] = [
                    'ligne' => $element['ligne'],
                ];
            }

            $this->traiterLot($factures, $indexConsultants, true, $rapport);
            $this->collecterNonRattachees($factures, $rapport, $metadonnees);
        }

        return $rapport;
    }

    /**
     * @param  Collection<int, Facture>  $factures
     * @param  array<string, int>  $indexConsultants
     * @param  array{consultants: int, missions: int, non_rattachees: array<int, array<string, mixed>>, ambigus: array<int, string>}  $rapport
     */
    private function traiterLot(Collection $factures, array $indexConsultants, bool $simulation, array &$rapport): void
    {
        foreach ($factures as $facture) {
            if ($facture->consultant_id !== null || $facture->beneficiaire === null) {
                continue;
            }

            $cle = $this->normaliserNom($facture->beneficiaire);

            if ($cle === '' || ! isset($indexConsultants[$cle])) {
                continue;
            }

            $facture->consultant_id = $indexConsultants[$cle];

            if (! $simulation) {
                Facture::query()
                    ->whereKey($facture->id)
                    ->whereNull('consultant_id')
                    ->update(['consultant_id' => $facture->consultant_id]);
            }

            $rapport['consultants']++;
        }

        $aRattacher = $factures->filter(fn (Facture $facture) => $facture->mission_id === null
            && $facture->consultant_id !== null
            && $facture->client_id !== null
            && $facture->fournisseur_id !== null
            && $facture->date_facture !== null);

        if ($aRattacher->isEmpty()) {
            return;
        }

        $missions = $this->missionsCandidates($aRattacher);

        foreach ($aRattacher as $facture) {
            $missionId = $this->choisirMission($facture, $missions);

            if ($missionId === null) {
                continue;
            }

            $facture->mission_id = $missionId;

            if (! $simulation) {
                Facture::query()
                    ->whereKey($facture->id)
                    ->whereNull('mission_id')
                    ->update(['mission_id' => $missionId]);
            }

            $rapport['missions']++;
        }
    }

    /**
     * @param  Collection<int, Facture>  $factures
     * @param  array{consultants: int, missions: int, non_rattachees: array<int, array<string, mixed>>, ambigus: array<int, string>}  $rapport
     * @param  array<int, array<string, mixed>>  $metadonnees
     */
    private function collecterNonRattachees(Collection $factures, array &$rapport, array $metadonnees = []): void
    {
        foreach ($factures as $facture) {
            $identifiant = $metadonnees[spl_object_id($facture)] ?? ['id' => $facture->id];

            if ($facture->consultant_id === null) {
                $rapport['non_rattachees'][] = $identifiant + [
                    'beneficiaire' => $facture->beneficiaire,
                    'raison' => $facture->beneficiaire === null ? 'bénéficiaire absent' : 'consultant introuvable',
                ];

                continue;
            }

            if ($facture->mission_id === null) {
                $rapport['non_rattachees'][] = $identifiant + [
                    'beneficiaire' => $facture->beneficiaire,
                    'raison' => 'mission introuvable',
                ];
            }
        }
    }

    /**
     * @return array{0: array<string, int>, 1: array<int, string>}
     */
    private function indexConsultants(): array
    {
        $index = [];
        $ambigus = [];

        foreach (Consultant::query()->orderBy('id')->get(['id', 'nom']) as $consultant) {
            $cle = $this->normaliserNom($consultant->nom);

            if ($cle === '') {
                continue;
            }

            if (isset($index[$cle])) {
                $ambigus[$cle] = $consultant->nom;

                continue;
            }

            $index[$cle] = (int) $consultant->id;
        }

        return [$index, array_values($ambigus)];
    }

    /**
     * @param  Collection<int, Facture>  $factures
     * @return Collection<int, Mission>
     */
    private function missionsCandidates(Collection $factures): Collection
    {
        $dates = $factures->pluck('date_facture')
            ->filter()
            ->map(fn ($date) => $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date);

        if ($dates->isEmpty()) {
            return collect();
        }

        return Mission::query()
            ->whereIn('consultant_id', $factures->pluck('consultant_id')->unique())
            ->whereIn('client_id', $factures->pluck('client_id')->unique())
            ->whereIn('fournisseur_id', $factures->pluck('fournisseur_id')->unique())
            ->where('date_debut', '<=', $dates->max())
            ->where(function ($query) use ($dates) {
                $query->whereNull('date_fin')
                    ->orWhere('date_fin', '>=', $dates->min());
            })
            ->orderByDesc('date_debut')
            ->orderByDesc('id')
            ->get([
                'id',
                'consultant_id',
                'client_id',
                'fournisseur_id',
                'date_debut',
                'date_fin',
            ]);
    }

    /**
     * @param  Collection<int, Mission>  $missions
     */
    private function choisirMission(Facture $facture, Collection $missions): ?int
    {
        $dateFacture = $facture->date_facture;

        foreach ($missions as $mission) {
            if ((int) $mission->consultant_id !== (int) $facture->consultant_id
                || (int) $mission->client_id !== (int) $facture->client_id
                || (int) $mission->fournisseur_id !== (int) $facture->fournisseur_id) {
                continue;
            }

            if ($mission->date_debut !== null && $mission->date_debut->gt($dateFacture)) {
                continue;
            }

            if ($mission->date_fin !== null && $mission->date_fin->lt($dateFacture)) {
                continue;
            }

            return (int) $mission->id;
        }

        return null;
    }

    private function normaliserNom(?string $nom): string
    {
        $nom = preg_replace('/\s+/u', ' ', trim((string) $nom)) ?? '';

        if ($nom === '') {
            return '';
        }

        return mb_convert_case(mb_strtolower($nom, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * @param  array<int, int|string>  $factureIds
     * @return array<int, int>
     */
    private function normaliserIds(array $factureIds): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', $factureIds),
            fn (int $id) => $id > 0
        )));
    }

    /**
     * @return array{consultants: int, missions: int, non_rattachees: array<int, array<string, mixed>>, ambigus: array<int, string>}
     */
    private function rapportVide(): array
    {
        return [
            'consultants' => 0,
            'missions' => 0,
            'non_rattachees' => [],
            'ambigus' => [],
        ];
    }
}
