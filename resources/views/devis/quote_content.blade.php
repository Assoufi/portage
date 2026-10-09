<h1>DEVIS N° {{ $devis->numero_devis }}</h1>

<table class="info-table">
    <tr>
        <td class="info-box">
            <p class="titre">Destinataire</p>
            <b>Nom :</b> {{ $client?->nom }}<br>
            <b>Adresse :</b> {{ $client?->adresse }}<br>
            @if($client?->type_identification && $client?->num_identification)
                <b>{{ $client->type_identification }} :</b> {{ $client->num_identification }}<br>
            @endif
            <b>Date :</b> {{ $devis->date_devis_formatee }}
        </td>
        <td style="width: 4%;"></td>
        <td class="info-box">
            <p class="titre">Émetteur</p>
            <b>Société :</b> {{ $fournisseur?->nom ?? '—' }}<br>
            @if($fournisseur?->adresse)
                <b>Adresse :</b> {{ $fournisseur->adresse }}<br>
            @endif
            @if($fournisseur?->ice)
                <b>ICE :</b> {{ $fournisseur->ice }}<br>
            @endif
        </td>
    </tr>
</table>

<table class="lignes">
    <thead>
        <tr>
            <th scope="col">Désignation</th>
            <th scope="col">Quantité</th>
            <th scope="col">Prix unitaire HT ({{ $devise }})</th>
            <th scope="col">Total HT ({{ $devise }})</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                {!! nl2br(e($devis->description)) !!}
                @if($devis->mission?->consultant)
                    <br>
                    <b>Consultant :</b> {{ $devis->mission->consultant->nom }}
                @endif
            </td>
            <td class="text-right">{{ number_format($devis->quantite, 2, ',', ' ') }}</td>
            <td class="text-right">{{ number_format($devis->prix_unitaire, 2, ',', ' ') }}</td>
            <td class="text-right">{{ number_format($totalHT, 2, ',', ' ') }}</td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2"></td>
            <td class="bold">Total HT</td>
            <td class="text-right">{{ number_format($totalHT, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <td colspan="2"></td>
            <td class="bold">TVA</td>
            <td class="text-right">{{ number_format($totalTVA, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <td colspan="2"></td>
            <td class="bold">Total TTC</td>
            <td class="text-right bold">{{ number_format($totalTTC, 2, ',', ' ') }}</td>
        </tr>
    </tfoot>
</table>

<div class="total-text">
    <p>Arrêté le présent devis à la somme de :</p>
    <p><b>{{ \App\Support\MontantEnLettres::convertir($totalTTC, $devise) }}</b> Toutes Taxes Comprises</p>
</div>
