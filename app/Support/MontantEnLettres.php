<?php

namespace App\Support;

/**
 * Conversion d'un montant numérique en toutes lettres (français),
 * utilisée pour la mention "Arrêté le présent devis à la somme de".
 */
class MontantEnLettres
{
    private const UNITES = [
        'zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
        'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize',
        'dix-sept', 'dix-huit', 'dix-neuf',
    ];

    private const DIZAINES = [
        2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante',
    ];

    private const DEVISES = [
        'MAD' => ['dirham', 'dirhams'],
        'EUR' => ['euro', 'euros'],
        'USD' => ['dollar', 'dollars'],
        'GBP' => ['livre sterling', 'livres sterling'],
        'CAD' => ['dollar canadien', 'dollars canadiens'],
    ];

    public static function convertir(float $montant, string $devise = 'MAD'): string
    {
        $montant = round($montant, 2);
        $entier = (int) floor($montant);
        $centimes = (int) round(($montant - $entier) * 100);

        $resultat = self::enLettres($entier).' '.self::nomDevise($devise, $entier);

        if ($centimes > 0) {
            $resultat .= ' et '.self::enLettres($centimes).' '.($centimes > 1 ? 'centimes' : 'centime');
        }

        return ucfirst($resultat);
    }

    public static function enLettres(int $nombre): string
    {
        if ($nombre === 0) {
            return 'zéro';
        }

        $parties = [];

        $milliards = intdiv($nombre, 1_000_000_000);
        $millions = intdiv($nombre, 1_000_000) % 1000;
        $milliers = intdiv($nombre, 1_000) % 1000;
        $reste = $nombre % 1000;

        if ($milliards > 0) {
            $parties[] = self::troisChiffres($milliards).' '.($milliards > 1 ? 'milliards' : 'milliard');
        }

        if ($millions > 0) {
            $parties[] = self::troisChiffres($millions).' '.($millions > 1 ? 'millions' : 'million');
        }

        if ($milliers > 0) {
            if ($milliers === 1) {
                $parties[] = 'mille';
            } else {
                $parties[] = self::accorderUniteDevantNumeral(self::troisChiffres($milliers)).' mille';
            }
        }

        if ($reste > 0) {
            $parties[] = self::troisChiffres($reste);
        }

        return implode(' ', $parties);
    }

    private static function troisChiffres(int $nombre): string
    {
        $centaines = intdiv($nombre, 100);
        $reste = $nombre % 100;
        $sortie = '';

        if ($centaines > 0) {
            if ($centaines === 1) {
                $sortie = 'cent';
            } else {
                $sortie = self::UNITES[$centaines].' cent'.($reste === 0 ? 's' : '');
            }
        }

        if ($reste > 0) {
            if ($sortie !== '') {
                $sortie .= ' ';
            }
            $sortie .= self::deuxChiffres($reste);
        }

        return $sortie;
    }

    private static function deuxChiffres(int $nombre): string
    {
        if ($nombre < 20) {
            return self::UNITES[$nombre];
        }

        $dizaine = intdiv($nombre, 10);
        $unite = $nombre % 10;

        if ($dizaine === 7 || $dizaine === 9) {
            if ($dizaine === 7 && $unite === 1) {
                return 'soixante et onze';
            }

            $base = $dizaine === 7 ? 'soixante' : 'quatre-vingt';

            return $base.'-'.self::UNITES[10 + $unite];
        }

        if ($dizaine === 8) {
            return $unite === 0 ? 'quatre-vingts' : 'quatre-vingt-'.self::UNITES[$unite];
        }

        $mot = self::DIZAINES[$dizaine];

        if ($unite === 0) {
            return $mot;
        }

        if ($unite === 1) {
            return $mot.' et un';
        }

        return $mot.'-'.self::UNITES[$unite];
    }

    /**
     * "deux cents" / "quatre-vingts" perdent leur "s" devant "mille".
     */
    private static function accorderUniteDevantNumeral(string $mot): string
    {
        foreach (['cents', 'vingts'] as $suffixe) {
            if (str_ends_with($mot, $suffixe)) {
                return substr($mot, 0, -1);
            }
        }

        return $mot;
    }

    private static function nomDevise(string $devise, int $nombre): string
    {
        [$singulier, $pluriel] = self::DEVISES[$devise] ?? ['unité monétaire', 'unités monétaires'];

        return $nombre > 1 ? $pluriel : $singulier;
    }
}
