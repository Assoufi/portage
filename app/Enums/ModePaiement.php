<?php
// app/Enums/ModePaiement.php

namespace App\Enums;

enum ModePaiement: string
{
    case VIREMENT = 'virement';
    case CHEQUE = 'cheque';
    case ESPECES = 'especes';
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    
    public function label(): string
    {
        return match($this) {
            self::VIREMENT => 'Virement bancaire',
            self::CHEQUE => 'Chèque',
            self::ESPECES => 'Espèces',
        };
    }
}