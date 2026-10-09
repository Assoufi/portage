{{-- resources/views/attestations/pdf.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Attestation de mission #{{ $attestation->id }}</title>
    <style>
        @page {
            margin: 1.5cm;
            size: A4 portrait;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11pt;
            color: #111827;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .footer {
            position: fixed;
            bottom: -0.5cm;
            left: 0;
            right: 0;
            font-size: 8pt;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            color: #555;
            height: 30px;
        }
        .header {
            width: 100%;
            margin-bottom: 30px;
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
            font-size: 16pt;
            text-align: center;
            text-decoration: underline;
            margin: 20px 0 40px;
        }
        .paragraphe {
            text-align: justify;
            margin-bottom: 22px;
        }
        .titre-section {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 8px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        td {
            padding: 5px 8px;
            vertical-align: top;
        }
        td.label {
            width: 35%;
            color: #6b7280;
        }
        td.valeur {
            font-weight: bold;
        }
        .signature-section {
            width: 100%;
            margin-top: 50px;
        }
        .signature-box {
            float: right;
            text-align: center;
            width: 250px;
            min-height: 140px;
        }
        .bold {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="footer">
        {{ $fournisseur?->footer ?? 'Attestation générée le '.now()->format('d/m/Y à H:i') }}
    </div>

    @php
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
                    <img width="150" src="{{ $logoBase64 }}">
                @endif
            </td>
            <td class="meta">
                @if($fournisseur?->nom)
                    <span class="bold">{{ $fournisseur->nom }}</span><br>
                @endif
                @if($fournisseur?->ville)
                    {{ $fournisseur->ville }}<br>
                @endif
                Référence : <span class="reference">ATT-{{ str_pad($attestation->id, 5, '0', STR_PAD_LEFT) }}</span><br>                
            </td>
        </tr>
    </table>

    <h1>Attestation de mission</h1>

    <p class="paragraphe">
        @if($fournisseur?->nom)
            Je soussigné, au nom de la société <span class="bold">{{ $fournisseur->nom }}</span>,
            certifie par la présente que :
        @else
            Je soussigné certifie par la présente que :
        @endif
    </p>

    <p class="paragraphe">
        <span class="bold">{{ $attestation->consultant?->nom ?? 'N/A' }}</span>, titulaire du CIN N° {{ $attestation->consultant?->cin ?? 'N/A' }},
        exerçant la fonction de <span class="bold">{{ $attestation->fonction }}</span>,
        a réalisé une mission pour le client <span class="bold">{{ $attestation->client }}</span>        
        @if($attestation->date_fin)
            laquelle s'est déroulée du <span class="bold">{{ $attestation->date_debut_formattee }}</span>
            au <span class="bold">{{ $attestation->date_fin_formattee }}</span>.
        @else
            laquelle a débuté le <span class="bold">{{ $attestation->date_debut_formattee }}</span>
            et se poursuit à ce jour.
        @endif
    </p>

    <p class="paragraphe">
        Cette attestation est délivrée à l'intéressé pour servir et valoir ce que de droit.
    </p>
    

    <div class="signature-section">
        <p>
            Fait à <span class="bold">{{ $fournisseur?->ville ?? '______' }}</span>,
            le <span class="bold">{{ $attestation->date_signature_formattee ?? $attestation->date_attestation_formattee }}</span>
        </p>

        <div class="signature-box">
            <p class="bold" style="margin-bottom: 10px;">La Direction</p>
            @if($signatureBase64)
                <img width="180" src="{{ $signatureBase64 }}">
            @endif
        </div>
    </div>
</body>
</html>
