<x-mail::message>

Bonjour,

Veuillez trouver ci-joint la facture **N° {{ $facture->numero_facture }}** émise par {{ $facture->fournisseur?->nom ?? '—' }} le {{ $facture->date_facture_formatee }}.

- Montant TTC : {{ $facture->montant_ttc_formate }}
- Échéance de paiement : {{ $facture->date_echeance_formatee }}

Nous vous prions de bien vouloir procéder à son règlement avant l'échéance indiquée.

Cordialement

</x-mail::message>
