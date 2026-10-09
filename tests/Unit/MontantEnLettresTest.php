<?php

namespace Tests\Unit;

use App\Support\MontantEnLettres;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MontantEnLettresTest extends TestCase
{
    #[DataProvider('montantsProvider')]
    public function test_convertir_ecrit_le_montant_en_lettres(float $montant, string $devise, string $attendu): void
    {
        $this->assertSame($attendu, MontantEnLettres::convertir($montant, $devise));
    }

    public static function montantsProvider(): array
    {
        return [
            'zéro' => [0, 'MAD', 'Zéro dirham'],
            'unité singulier' => [1, 'MAD', 'Un dirham'],
            'unité pluriel' => [2, 'MAD', 'Deux dirhams'],
            'grand nombre' => [234, 'MAD', 'Deux cent trente-quatre dirhams'],
            'centimes' => [1.5, 'MAD', 'Un dirham et cinquante centimes'],
            'un centime' => [1.01, 'MAD', 'Un dirham et un centime'],
            'euro' => [1234, 'EUR', 'Mille deux cent trente-quatre euros'],
            'accord devant mille' => [80000, 'MAD', 'Quatre-vingt mille dirhams'],
            'dollar' => [10, 'USD', 'Dix dollars'],
            'devise inconnue' => [5, 'XYZ', 'Cinq unités monétaires'],
        ];
    }

    #[DataProvider('nombresProvider')]
    public function test_en_lettres(int $nombre, string $attendu): void
    {
        $this->assertSame($attendu, MontantEnLettres::enLettres($nombre));
    }

    public static function nombresProvider(): array
    {
        return [
            'soixante-dix' => [70, 'soixante-dix'],
            'soixante et onze' => [71, 'soixante et onze'],
            'soixante-dix-sept' => [77, 'soixante-dix-sept'],
            'quatre-vingts' => [80, 'quatre-vingts'],
            'quatre-vingt-un' => [81, 'quatre-vingt-un'],
            'quatre-vingt-onze' => [91, 'quatre-vingt-onze'],
            'quatre-vingt-dix-neuf' => [99, 'quatre-vingt-dix-neuf'],
            'cent' => [100, 'cent'],
            'deux cents' => [200, 'deux cents'],
            'deux cent un' => [201, 'deux cent un'],
            'mille' => [1000, 'mille'],
            'deux cent mille' => [200000, 'deux cent mille'],
            'un million' => [1000000, 'un million'],
            'deux millions' => [2000000, 'deux millions'],
            'quatre-vingts millions' => [80000000, 'quatre-vingts millions'],
            'un milliard' => [1000000000, 'un milliard'],
        ];
    }
}
