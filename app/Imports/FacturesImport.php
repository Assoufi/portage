<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class FacturesImport implements ToCollection, WithCalculatedFormulas
{
    private const CHAMPS = [
        'groupe_facture',
        'numero_facture',
        'designation',
        'quantite',
        'prix_unitaire',
        'montant_ttc',
        'client',
        'date_paiement_ben',
        'paiement_fournisseur',
        'fournisseur',
        'reference_ben',
        'manager',
        'devise',
        'date',
        'mois',
        'contact',
        'date_reception',
        'periodicite',
        'livraison',
    ];

    public array $factures = [];
    public array $ignorees = [];
    public array $invalides = [];
    public int $lignesLues = 0;

    private array $enTetes = [];

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $this->extraireEnTetes($rows->first());

        if (empty($this->enTetes)) {
            $this->invalides[] = ['ligne' => 1, 'raison' => 'Entêtes du fichier non reconnus'];
            return;
        }

        $groupes = [];

        foreach ($rows->slice(1) as $ligne) {
            $r = [];
            foreach ($this->enTetes as $champ => $index) {
                $r[$champ] = $this->texte($ligne[$index] ?? null);
            }

            if ($this->ligneVide($r)) {
                continue;
            }

            $this->lignesLues++;
            $numeroLigne = $this->lignesLues + 1;

            if ($r['numero_facture'] !== '') {
                $this->ignorees[] = ['ligne' => $numeroLigne, 'numero' => $r['numero_facture']];
                continue;
            }

            if ($r['client'] === '' || $r['fournisseur'] === '') {
                $this->invalides[] = [
                    'ligne' => $numeroLigne,
                    'raison' => $r['client'] === '' ? 'Client absent' : 'Fournisseur absent',
                ];
                continue;
            }

            $cle = $r['groupe_facture'] !== ''
                ? 'groupe:' . implode('|', [$r['groupe_facture'], $r['date'], $r['client'], $r['fournisseur'], $r['manager']])
                : 'ligne:' . $numeroLigne;

            if (isset($groupes[$cle])) {
                $groupes[$cle]['lignes'][] = $this->detail($r, $numeroLigne);
                continue;
            }

            $groupes[$cle] = $this->nouveauGroupe($r, $numeroLigne);
        }

        $this->factures = array_values($groupes);
    }

    private function nouveauGroupe(array $r, int $ligne): array
    {
        return [
            'lignes' => [$this->detail($r, $ligne)],
            'date' => self::parseDate($r['date']),
            'dateBrut' => $r['date'],
            'client' => $r['client'],
            'fournisseur' => $r['fournisseur'],
            'beneficiaire' => $r['manager'],
            'periode' => self::parsePeriode($r['mois']),
            'devise' => $r['devise'],
            'contact' => $r['contact'],
            'periodicite' => $r['periodicite'],
            'livraison' => $r['livraison'],
            'reference_ben' => $r['reference_ben'],
            'date_paiement_ben' => self::parseDate($r['date_paiement_ben']),
            'paiement_fournisseur' => self::parseDate($r['paiement_fournisseur']),
            'groupe' => $r['groupe_facture'],
            'ligne' => $ligne,
        ];
    }

    private function detail(array $r, int $ligne): array
    {
        return [
            'designation' => $r['designation'] ?: 'Prestation',
            'quantite' => $r['quantite'] !== '' ? (int) $r['quantite'] : 1,
            'prix_unitaire' => self::parseMontant($r['prix_unitaire']) ?? 0.0,
            'montant_ttc' => $r['montant_ttc'] !== '' ? self::parseMontant($r['montant_ttc']) : null,
            'ligne' => $ligne,
        ];
    }

    private function extraireEnTetes(Collection $entetes): void
    {
        foreach ($entetes as $index => $brut) {
            $h = $this->normalizer($brut);
            if ($h === '') {
                continue;
            }

            $champ = match (true) {
                str_contains($h, 'groupe') => 'groupe_facture',
                str_contains($h, 'facture') => 'numero_facture',
                str_contains($h, 'designation') => 'designation',
                str_starts_with($h, 'quant') => 'quantite',
                str_contains($h, 'prix') => 'prix_unitaire',
                str_contains($h, 'montant') => 'montant_ttc',
                str_contains($h, 'paiement ben') => 'date_paiement_ben',
                str_contains($h, 'paiement fournisseur') => 'paiement_fournisseur',
                str_contains($h, 'fournisseur') => 'fournisseur',
                str_contains($h, 'reference ben') => 'reference_ben',
                str_contains($h, 'reception') => 'date_reception',
                $h === 'client' => 'client',
                $h === 'manager' => 'manager',
                $h === 'devise' => 'devise',
                $h === 'date' => 'date',
                $h === 'mois' => 'mois',
                $h === 'contact' => 'contact',
                str_contains($h, 'periodic') => 'periodicite',
                str_contains($h, 'livraison') => 'livraison',
                default => null,
            };

            if ($champ !== null && !isset($this->enTetes[$champ])) {
                $this->enTetes[$champ] = $index;
            }
        }
    }

    private function normalizer($valeur): string
    {
        $s = mb_strtolower(trim((string) $valeur));
        $s = (string) preg_replace('/\s+/', ' ', $s);
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t === false) {
            return $s;
        }

        return (string) preg_replace('/[^a-z0-9 _-]/', '', $t);
    }

    private function ligneVide(array $r): bool
    {
        foreach (self::CHAMPS as $champ) {
            if (($r[$champ] ?? '') !== '') {
                return false;
            }
        }

        return true;
    }

    private function texte($valeur): string
    {
        return trim(str_replace("\n", ' ', (string) $valeur));
    }

    public static function parseMontant($valeur): ?float
    {
        if ($valeur === null) {
            return null;
        }

        $s = (string) preg_replace('/[^\d,.\-]/', '', str_replace(' ', '', (string) $valeur));

        if ($s === '' || $s === '-') {
            return null;
        }

        $virgules = substr_count($s, ',');
        $points = substr_count($s, '.');

        if ($virgules > 0 && $points > 0) {
            $s = str_replace(',', '', $s);
        } elseif ($virgules > 1) {
            $s = str_replace(',', '', $s);
        } elseif ($virgules === 1) {
            $decimales = strlen(substr($s, strrpos($s, ',') + 1));
            $s = $decimales === 3 ? str_replace(',', '', $s) : str_replace(',', '.', $s);
        }

        return (float) $s;
    }

    public static function parseDate($valeur): ?Carbon
    {
        $s = trim((string) $valeur);

        if ($s === '' || $s === '-') {
            return null;
        }

        if (is_numeric($s)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $s))->startOfDay();
        }

        foreach (['m/d/Y', 'd/m/Y', 'Y-m-d'] as $format) {
            if (Carbon::hasFormat($s, $format)) {
                return Carbon::createFromFormat($format, $s)->startOfDay();
            }
        }

        try {
            return Carbon::parse($s)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function parsePeriode($valeur): ?Carbon
    {
        $s = trim((string) $valeur);

        if ($s === '' || $s === '-') {
            return null;
        }

        if (is_numeric($s)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $s))->startOfMonth();
        }

        foreach (['M-y', 'M-Y', 'Y-m'] as $format) {
            if (Carbon::hasFormat($s, $format)) {
                return Carbon::createFromFormat($format, $s)->startOfMonth();
            }
        }

        $d = self::parseDate($s);

        return $d?->startOfMonth();
    }
}
