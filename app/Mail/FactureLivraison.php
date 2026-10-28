<?php

namespace App\Mail;

use App\Models\Facture;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Envoi de la facture signée (PDF) par email au client,
 * depuis le Dispatcher de la liste des factures.
 */
class FactureLivraison extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Facture $facture,
        public string $pdfData,
    ) {}

    public function build(): static
    {
        $fournisseur = $this->facture->fournisseur?->nom ?? 'Facturation';

        return $this->from('portage@homsys.ma', $fournisseur)
            ->subject("Facture {$this->facture->numero_facture} — {$fournisseur}")
            ->markdown('emails.facture-livraison')
            ->attachData($this->pdfData, 'facture-' . $this->facture->numero_facture . '.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
