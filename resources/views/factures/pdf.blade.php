{{-- resources/views/factures/pdf.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $facture->numero_facture }}</title>
    <style>
        @page {
            margin: 1.2cm;
            size: A4 portrait;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5pt;
            color: #111827;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .footer {
            position: fixed;
            bottom: -0.6cm;
            left: 0;
            right: 0;
            font-size: 8pt;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 4px;
            color: #555;
        }
        .header {
            width: 100%;
            margin-bottom: 18px;
        }
        .header td.logo {
            width: 45%;
            vertical-align: top;
        }
        .header td.meta {
            width: 55%;
            text-align: right;
            vertical-align: top;
            font-size: 9pt;
            color: #4b5563;
        }
        .header td.meta .nom {
            font-weight: bold;
            font-size: 11pt;
            color: #111827;
        }
        h1 {
            font-size: 15pt;
            text-align: center;
            text-decoration: underline;
            margin: 10px 0 18px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-box {
            border: 1px solid #d1d5db;
            padding: 8px;
            vertical-align: top;
            font-size: 9.5pt;
        }
        .info-box .titre {
            font-weight: bold;
            margin-bottom: 4px;
            text-transform: uppercase;
            font-size: 9pt;
            color: #374151;
        }
        table.lignes {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
            margin-bottom: 12px;
        }
        table.lignes th, table.lignes td {
            border: 1px solid #d1d5db;
            padding: 6px;
        }
        table.lignes th {
            background-color: #f3f4f6;
            font-weight: bold;
            text-align: center;
        }
        table.lignes tfoot td {
            background-color: #f9fafb;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .total-text {
            font-size: 10pt;
            margin-top: 8px;
            border: 1px solid #d1d5db;
            background-color: #f9fafb;
            padding: 8px;
        }
        .signature-section { margin-top: 35px; }
        .signature-box {
            float: right;
            text-align: center;
            width: 240px;
        }
    </style>
</head>
<body>
    <div class="footer">
        {{ $fournisseur?->footer ?? 'Facture générée le '.now()->format('d/m/Y à H:i') }}
    </div>

    @php $signe = $signe ?? true; @endphp
    @php
        $client = $facture->client;
        $consultant = $facture->consultant;
        $devise = $client?->devise ?? 'MAD';
        $totalHT = (float) ($facture->total_ht ?? 0);
        $totalTVA = (float) $facture->details->sum('montant_tva');
        $totalTTC = (float) ($facture->montant_ttc ?? 0);

        $logoBase64 = null;
        $signatureBase64 = null;

        if ($fournisseur?->logo) {
            $chemin = \Illuminate\Support\Facades\Storage::disk('public')->path($fournisseur->logo);
            if (is_file($chemin)) {
                $mime = mime_content_type($chemin) ?: 'image/png';
                $logoBase64 = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($chemin));
            }
        }

        // Signature intégrée uniquement pour la version signée
        // (cachée pour les livraisons "Papier" via $signe = false).
        if (($signe ?? true) && $fournisseur?->signature) {
            $chemin = \Illuminate\Support\Facades\Storage::disk('public')->path($fournisseur->signature);
            if (is_file($chemin)) {
                $mime = mime_content_type($chemin) ?: 'image/png';
                $signatureBase64 = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($chemin));
            }
        }
    @endphp

    <table class="header">
        <tr>
            <td class="logo">
                @if($logoBase64)
                    <img width="140" src="{{ $logoBase64 }}" alt="Logo">
                @endif
            </td>
            <td class="meta">
                @if($fournisseur?->nom)
                    <span class="nom">{{ $fournisseur->nom }}</span><br>
                @endif                
                @if($fournisseur?->ice)
                    ICE : {{ $fournisseur->ice }}<br>
                @endif
                @if($fournisseur?->rib)
                    RIB : {{ $fournisseur->rib }}<br>
                @endif
                @if($fournisseur?->iban)
                    IBAN : {{ $fournisseur->iban }}<br>
                @endif
            </td>
        </tr>
    </table>

    <h1>FACTURE N° {{ $facture->numero_facture }}</h1>

    <table class="info-table">
        <tr>
            <td class="info-box" style="width: 48%;">
                <p class="titre">Client</p>
                <b>Nom :</b> {{ $client?->nom ?? '—' }}<br>
                @if($client?->adresse)
                    <b>Adresse :</b> {{ $client->adresse }}<br>
                @endif
                @if($client?->type_identification && $client?->num_identification)
                    <b>{{ $client->type_identification }} :</b> {{ $client->num_identification }}<br>
                @endif
                @if($consultant?->nom)
                    <b>Consultant :</b> {{ $consultant->nom }}<br>
                @endif
            </td>
            <td style="width: 4%;"></td>
            <td class="info-box" style="width: 48%;">
                <p class="titre">Détails de la facture</p>
                <b>N° Facture :</b> {{ $facture->numero_facture }}<br>
                @if($facture->numero_bcm)
                    <b>N° BCM :</b> {{ $facture->numero_bcm }}<br>
                @endif
                <b>Date :</b> {{ $facture->date_facture_formatee }}<br>
                @if($facture->date_echeance)
                    <b>Échéance :</b> {{ $facture->date_echeance_formatee }}<br>
                @endif                
            </td>
        </tr>
    </table>

    <table class="lignes">
        <thead>
            <tr>
                <th scope="col">Désignation</th>
                <th scope="col" style="width: 12%;">Quantité</th>
                <th scope="col" style="width: 22%;">Prix unitaire HT ({{ $devise }})</th>
                <th scope="col" style="width: 22%;">Total HT ({{ $devise }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($facture->details as $detail)
                <tr>
                    <td>{{ $detail->designation }}</td>
                    <td class="text-right">{{ $detail->quantite }}</td>
                    <td class="text-right">{{ number_format($detail->prix_unitaire, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($detail->montant_ht, 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Aucune ligne</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"></td>
                <td class="bold">Total HT</td>
                <td class="text-right">{{ number_format($totalHT, 2, ',', ' ') }} {{ $devise }}</td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <td class="bold">Total TVA</td>
                <td class="text-right">{{ number_format($totalTVA, 2, ',', ' ') }} {{ $devise }}</td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <td class="bold">Total TTC</td>
                <td class="text-right bold">{{ number_format($totalTTC, 2, ',', ' ') }} {{ $devise }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="total-text">
        <p>Arrêtée la présente facture à la somme de :</p>
        <p><b>{{ \App\Support\MontantEnLettres::convertir($totalTTC, $devise) }}</b> Toutes Taxes Comprises</p>
    </div>

    <div class="signature-section">
       

        <div class="signature-box">
            <p class="bold" style="margin-bottom: 10px;">Signature et Cachet</p>
            @if($signatureBase64)
                <img width="160" src="{{ $signatureBase64 }}" alt="Signature">
            @endif
        </div>
    </div>
</body>
</html>
