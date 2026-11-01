<?php

// app/Support/NumeroRetry.php

namespace App\Support;

use Illuminate\Database\QueryException;

/**
 * Sauvegarde avec régénération du numéro en cas de collision UNIQUE.
 *
 * La génération des numéros (facture, devis, référence paiement) repose sur la
 * relecture du maximum existant : deux écritures concurrentes peuvent obtenir
 * le même numéro. La contrainte UNIQUE reste l'arbitre ; ici, on régénère le
 * numéro (le concurrent commité est alors visible dans le MAX → pas de trou)
 * et on relance la sauvegarde, jusqu'à $essaisMax fois.
 */
class NumeroRetry
{
    /**
     * @param  callable(): string  $genererNumero  Produit un numéro frais à chaque essai
     * @param  callable(string): mixed  $sauvegarde  Effectue l'INSERT ; reçoit le numéro
     * @return mixed Résultat du callable $sauvegarde
     */
    public static function sauvegarder(callable $genererNumero, callable $sauvegarde, int $essaisMax = 5): mixed
    {
        $attempt = 0;

        while (true) {
            $numero = $genererNumero();

            try {
                return $sauvegarde($numero);
            } catch (QueryException $e) {
                // SQLSTATE 23000 : violation d'intégrité (contrainte UNIQUE)
                if ((int) ($e->errorInfo[1] ?? 0) === 23000 && ++$attempt < $essaisMax) {
                    continue;
                }

                throw $e;
            }
        }
    }
}
