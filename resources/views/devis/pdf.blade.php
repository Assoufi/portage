{{-- resources/views/devis/pdf.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Devis {{ $devis->numero_devis }}</title>
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
            margin-bottom: 20px;
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
        .header td.meta .reference {
            font-weight: bold;
            color: #111827;
        }
        h1 {
            font-size: 15pt;
            text-align: center;
            text-decoration: underline;
            margin: 10px 0 20px;
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
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .total-text { font-size: 10pt; margin-top: 8px; }
        .signature-section { margin-top: 40px; }
        .signature-box {
            float: right;
            text-align: center;
            width: 240px;
        }
    </style>
</head>
<body>
    <div class="footer">
        {{ $fournisseur?->footer ?? 'Devis généré le '.now()->format('d/m/Y à H:i') }}
    </div>

    @php
        $devise = $devis->devise;
        $client = $devis->client;
        $totalHT = $devis->total_ht;
        $totalTVA = $devis->montant_tva;
        $totalTTC = $devis->montant_ttc;

        $logoBase64 = null;
        $signatureBase64 = null;

        if ($fournisseur?->logo) {
            $chemin = \Illuminate\Support\Facades\Storage::disk('public')->path($fournisseur->logo);
            if (is_file($chemin)) {
                $mime = mime_content_type($chemin) ?: 'image/png';
                $logoBase64 = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($chemin));
            }
        }

        if ($fournisseur?->signature) {
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
                    <img width="140" src="{{ $logoBase64 }}">
                @endif
            </td>
            <td class="meta">
                @if($fournisseur?->nom)
                    <span class="bold">{{ $fournisseur->nom }}</span><br>
                @endif
                @if($fournisseur?->adresse)
                    {{ $fournisseur->adresse }}<br>
                @endif
                @if($fournisseur?->ville)
                    {{ $fournisseur->ville }}<br>
                @endif
                @if($fournisseur?->ice)
                    ICE : {{ $fournisseur->ice }}<br>
                @endif
                Référence : <span class="reference">{{ $devis->numero_devis }}</span>
            </td>
        </tr>
    </table>

    @include('devis.quote_content')

    <div class="signature-section">
        <p>
            Fait à <span class="bold">{{ $fournisseur?->ville ?? '______' }}</span>,
            le <span class="bold">{{ $devis->date_devis_formatee }}</span>
        </p>

        <div class="signature-box">
            <p class="bold" style="margin-bottom: 10px;">Signature et Cachet</p>
            @if($signatureBase64)
                <img width="160" src="{{ $signatureBase64 }}">
            @endif
        </div>
    </div>
</body>
</html>
