<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemandeSuiviJours extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nomConsultant,
        public string $mois,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: 'portage@homsys.ma',
            subject: "Demande de suivi des jours prestés - {$this->mois}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.demande-suivi-jours',
        );
    }
}
