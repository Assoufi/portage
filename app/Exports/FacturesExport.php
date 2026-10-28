<?php

namespace App\Exports;

use App\Models\Facture;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class FacturesExport implements FromCollection, WithHeadings, WithColumnFormatting, ShouldAutoSize
{
    public function __construct(private readonly Builder $query) {}

    /**
     * Colonnes de l'export.
     */
    public function headings(): array
    {
        return [
            'N° Facture',
            'Date',
            'Désignation',
            'Quantité',
            'Prix unitaire',
            'Montant TTC',
            'Client',
            'Fournisseur',
            'Manager',
            'Devise',
            'Date Paiement BEN',
            'Reference BEN',
            'Paiement Fournisseur',
            'Reference Fournisseur',
            'Mois',
            'Contact',
            'Date reception',
            'Periodicite',
            'Livraison',
        ];
    }

    /**
     * Formats par colonne : dates réelles au format dd/mm/yyyy (B, K, M, Q)
     * et valeurs numériques (E, F).
     * La période (O) est exportée en texte au format mm/yyyy.
     */
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'M' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'Q' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }

    /**
     * Une ligne par détail de facture (une ligne même sans détail),
     * mappé selon la structure demandée. Chargement par lots pour
     * limiter la mémoire sur de grands volumes.
     *
     * Les dates sont converties en séries Excel via Date::dateTimeToExcel()
     * de façon à obtenir de vraies cellules de type Date au format
     * dd/mm/yyyy (voir columnFormats) : si elles restaient en Carbon,
     * Maatwebsite les écrirait sous forme de chaînes "2026-01-15 00:00:00".
     */
    public function collection(): \Illuminate\Support\Collection
    {
        $dateExcel = fn ($d) => $d ? Date::dateTimeToExcel($d) : null;

        $rows = [];

        // Export de reporting : certains volumes dépassent la limite par défaut
        ini_set('memory_limit', '512M');

        $this->query
            ->with(['client', 'fournisseur', 'details'])
            ->orderBy('id')
            ->chunk(500, function ($factures) use (&$rows, $dateExcel) {
                foreach ($factures as $facture) {
                    $details = $facture->details->isNotEmpty()
                        ? $facture->details
                        : collect([null]);

                    foreach ($details as $detail) {
                        $rows[] = [
                            $facture->numero_facture,
                            $dateExcel($facture->date_facture),
                            $detail?->designation,
                            $detail?->quantite,
                            $detail?->prix_unitaire ?? 0,
                            $detail?->montant_ttc ?? 0,
                            $facture->client?->nom,
                            $facture->fournisseur?->nom,
                            $facture->beneficiaire,
                            $facture->client?->devise,
                            $dateExcel($facture->date_reglement),
                            $facture->reference_reglement,
                            $dateExcel($facture->date_paiement),
                            $facture->reference_paiement,
                            // Période (mois) : format mm/yyyy (ex. 01/2026)
                            $facture->periode?->format('m/Y'),
                            null,
                            $dateExcel($facture->date_reception),
                            null,
                            null,
                        ];
                    }

                    // Libération de la mémoire des modèles chargés
                    unset($facture);
                }
            });

        return collect($rows);
    }
}
