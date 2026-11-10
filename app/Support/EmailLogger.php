<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Journalisation des envois d'emails : succès (expéditeur / destinataires
 * uniquement, jamais le corps du message) et échecs (avec la cause).
 */
class EmailLogger
{
    public static function succes(string $mailable, array $destinataires, array $copie = []): void
    {
        Log::info('Email envoyé', [
            'mailable' => class_basename($mailable),
            'de' => config('mail.from.address'),
            'a' => self::normaliser($destinataires),
            'copie' => self::normaliser($copie),
        ]);
    }

    public static function echec(string $mailable, array $destinataires, Throwable $e): void
    {
        Log::error('Email non envoyé', [
            'mailable' => class_basename($mailable),
            'de' => config('mail.from.address'),
            'a' => self::normaliser($destinataires),
            'erreur' => $e->getMessage(),
        ]);
    }

    private static function normaliser(array $destinataires): string
    {
        return implode(', ', array_filter(array_map('strval', $destinataires)));
    }
}
