<?php

namespace App\Services;

use App\Imports\ClientsImport;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Import des clients depuis un fichier Excel déjà lu par ClientsImport.
 *
 * Les clients existants sont rapprochés par nom normalisé (casse et espaces
 * ignorés). Les cellules vides du fichier n'écrasent jamais les valeurs en base.
 */
class ClientImportService
{
    private const DEVISES = ['MAD', 'EUR', 'USD', 'GBP', 'CAD'];

    private const LIVRAISONS = ['Papier', 'Email', 'Whatsapp'];

    public function importer(ClientsImport $import): array
    {
        // Chargement unique de tous les clients, archives comprises.
        $clients = Client::withTrashed()->get();
        $parNom = [];
        $parIdentification = [];

        foreach ($clients as $client) {
            $this->indexer($client, null, $parNom, $parIdentification);
        }

        $details = [];
        $creees = 0;
        $misesAJour = 0;
        $restaurees = 0;
        $invalides = 0;

        // Lignes déjà invalides lors de la lecture (entêtes, feuille vide...).
        foreach ($import->invalides as $invalide) {
            $invalides++;
            $details[] = $this->detail(
                $invalide['ligne'] ?? null,
                $invalide['nom'] ?? null,
                'invalide',
                $invalide['raison'] ?? 'Ligne invalide.'
            );
        }

        foreach ($import->lignes as $ligne) {
            $numero = $ligne['ligne'] ?? null;
            $nom = (string) ($ligne['nom'] ?? '');
            $erreurs = $this->valider($ligne);

            if ($erreurs !== []) {
                $invalides++;
                $details[] = $this->detail($numero, $nom, 'invalide', implode(' ', $erreurs));

                continue;
            }

            $cleNom = $this->normaliserNom($nom);
            $correspondances = $parNom[$cleNom] ?? [];

            if (count($correspondances) > 1) {
                $invalides++;
                $details[] = $this->detail(
                    $numero,
                    $nom,
                    'invalide',
                    'Plusieurs clients portent ce nom ; la ligne ne peut pas être rapprochée.'
                );

                continue;
            }

            $client = $correspondances[0] ?? null;
            // Un numéro d'identification ne peut appartenir qu'à un seul client,
            // y compris parmi les clients archivés.
            $cleIdentification = $this->normaliserIdentification($ligne['num_identification'] ?? null);

            if ($cleIdentification !== '') {
                foreach ($parIdentification[$cleIdentification] ?? [] as $candidat) {
                    if ($client === null || $candidat->id !== $client->id) {
                        $invalides++;
                        $details[] = $this->detail(
                            $numero,
                            $nom,
                            'invalide',
                            "Le numéro d'identification appartient déjà à un autre client."
                        );

                        continue 2;
                    }
                }
            }

            try {
                $resultat = DB::transaction(function () use ($client, $ligne, &$parNom, &$parIdentification) {
                    if ($client === null) {
                        $nouveau = Client::create($this->donnees($ligne, true));
                        $this->indexer($nouveau, null, $parNom, $parIdentification);

                        return ['statut' => 'creee', 'client' => $nouveau];
                    }

                    $ancienneIdentification = $client->num_identification;
                    $restaure = $client->trashed();

                    if ($restaure) {
                        $client->restore();
                    }

                    $donnees = $this->donnees($ligne);

                    if ($donnees !== []) {
                        $client->update($donnees);
                    }

                    $client->refresh();
                    $this->indexer($client, $ancienneIdentification, $parNom, $parIdentification);

                    return [
                        'statut' => $restaure ? 'restauree' : 'mise_a_jour',
                        'client' => $client,
                    ];
                });
            } catch (Throwable $e) {
                $invalides++;
                $details[] = $this->detail(
                    $numero,
                    $nom,
                    'invalide',
                    'Erreur lors de l\'enregistrement : '.$e->getMessage()
                );

                continue;
            }

            if ($resultat['statut'] === 'creee') {
                $creees++;
                $message = 'Client créé.';
            } elseif ($resultat['statut'] === 'restauree') {
                $restaurees++;
                $message = 'Client restauré et mis à jour.';
            } else {
                $misesAJour++;
                $message = 'Client mis à jour.';
            }

            $details[] = $this->detail($numero, $nom, $resultat['statut'], $message);
        }

        return [
            'lignes' => $import->lignesLues,
            'creees' => $creees,
            'mises_a_jour' => $misesAJour,
            'restaurees' => $restaurees,
            'ignorees' => count($import->ignorees),
            'invalides' => $invalides,
            'details' => $details,
            'colonnes_ignorees' => $import->ignorees,
        ];
    }

    private function valider(array $ligne): array
    {
        $donnees = array_intersect_key($ligne, array_flip([
            'nom', 'adresse', 'type_identification', 'num_identification',
            'delai_paiement', 'devise', 'mode_livraison', 'remarques',
        ]));

        $regles = [
            'nom' => ['required', 'string', 'max:50'],
            'adresse' => ['nullable', 'string', 'max:1000'],
            'type_identification' => ['nullable', 'string', 'max:50'],
            'num_identification' => ['nullable', 'string', 'max:50'],
            'delai_paiement' => ['nullable', 'integer', 'between:0,365'],
            'devise' => ['nullable', 'string', Rule::in(self::DEVISES)],
            'mode_livraison' => ['nullable', 'array'],
            'mode_livraison.*' => [Rule::in(self::LIVRAISONS)],
            'remarques' => ['nullable', 'string', 'max:2000'],
        ];

        $messages = [
            'nom.required' => 'Le nom du client est obligatoire.',
            'nom.max' => 'Le nom du client ne doit pas dépasser 50 caractères.',
            'adresse.max' => "L'adresse ne doit pas dépasser 1000 caractères.",
            'type_identification.max' => "Le type d'identification ne doit pas dépasser 50 caractères.",
            'num_identification.max' => "Le numéro d'identification ne doit pas dépasser 50 caractères.",
            'delai_paiement.integer' => 'Le délai de paiement doit être un nombre entier de jours.',
            'delai_paiement.between' => 'Le délai de paiement doit être compris entre 0 et 365 jours.',
            'devise.in' => "La devise n'est pas supportée (MAD, EUR, USD, GBP, CAD).",
            'mode_livraison.array' => 'Le mode de livraison est invalide.',
            'mode_livraison.*.in' => 'Le mode de livraison doit être « Papier », « Email » ou « Whatsapp ».',
            'remarques.max' => 'Les remarques ne doivent pas dépasser 2000 caractères.',
        ];

        $validator = Validator::make($donnees, $regles, $messages);

        return $validator->fails() ? $validator->errors()->all() : [];
    }

    private function donnees(array $ligne, bool $creation = false): array
    {
        $donnees = [];

        foreach (['adresse', 'type_identification', 'num_identification', 'delai_paiement', 'devise', 'mode_livraison', 'remarques'] as $champ) {
            if ($this->estRenseigne($ligne[$champ] ?? null)) {
                $donnees[$champ] = $ligne[$champ];
            }
        }

        if ($creation) {
            $donnees['nom'] = $ligne['nom'];
            // L'adresse est obligatoire en base : on reprend le nom si absente.
            $donnees['adresse'] = $this->valeurNonVide($ligne['adresse'] ?? null) ?? $ligne['nom'];
        }

        return $donnees;
    }

    private function estRenseigne($valeur): bool
    {
        if (is_array($valeur)) {
            return $valeur !== [];
        }

        return $valeur !== null && trim((string) $valeur) !== '';
    }

    private function valeurNonVide($valeur): ?string
    {
        if (is_array($valeur)) {
            return null;
        }

        $texte = trim((string) $valeur);

        return $texte === '' ? null : $texte;
    }

    private function normaliserNom(?string $nom): string
    {
        $nom = mb_strtolower(trim((string) $nom));

        return trim((string) preg_replace('/\s+/u', ' ', $nom));
    }

    private function normaliserIdentification($valeur): string
    {
        return strtoupper((string) preg_replace('/\s+/u', '', trim((string) $valeur)));
    }

    /**
     * Maintient les index en mémoire après une création ou une mise à jour.
     */
    private function indexer(Client $client, ?string $ancienneIdentification, array &$parNom, array &$parIdentification): void
    {
        $cleNom = $this->normaliserNom($client->nom);
        $parNom[$cleNom] ??= [];

        if (! collect($parNom[$cleNom])->contains(fn (Client $candidat) => $candidat->id === $client->id)) {
            $parNom[$cleNom][] = $client;
        }

        $ancienneCle = $this->normaliserIdentification($ancienneIdentification);

        if ($ancienneCle !== '') {
            $parIdentification[$ancienneCle] = array_values(array_filter(
                $parIdentification[$ancienneCle] ?? [],
                fn (Client $candidat) => $candidat->id !== $client->id
            ));

            if ($parIdentification[$ancienneCle] === []) {
                unset($parIdentification[$ancienneCle]);
            }
        }

        $cleIdentification = $this->normaliserIdentification($client->num_identification);

        if ($cleIdentification !== '') {
            $parIdentification[$cleIdentification] ??= [];

            if (! collect($parIdentification[$cleIdentification])->contains(fn (Client $candidat) => $candidat->id === $client->id)) {
                $parIdentification[$cleIdentification][] = $client;
            }
        }
    }

    private function detail($ligne, $nom, string $statut, string $message): array
    {
        return [
            'ligne' => $ligne,
            'nom' => $nom,
            'statut' => $statut,
            'message' => $message,
        ];
    }
}
