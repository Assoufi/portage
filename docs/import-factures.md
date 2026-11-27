# Import des factures — rattachement consultants / missions

L'import Excel des factures est exécuté par la commande `factures:importer`,
appelée aussi bien en CLI que par l'écran `Factures > Importer`
(`FactureController::importer`). Depuis l'ajout du rattachement, la commande
suit les étapes suivantes :

1. Lecture du fichier et création des clients/fournisseurs manquants.
2. Création des factures et de leurs lignes de détail.
3. **Rattachement** des factures créées à un consultant puis à une mission.
4. Commit de la transaction globale.

## Périmètre

Le rattachement ne traite **que les factures créées pendant l'import courant**.
Les factures historiques dont `consultant_id` ou `mission_id` est `NULL` ne sont
jamais modifiées par un import. Les identifiants créés sont collectés pendant la
boucle d'import et transmis à `FactureRattachementService::rattacher()`.

## Règles de correspondance

### Consultant

- Source : `factures.beneficiaire` (colonne « manager » du fichier Excel).
- Comparaison avec `consultants.nom` après normalisation identique aux mutateurs
  Eloquent (`trim`, espaces internes compactés, `strtolower` puis `ucwords`).
- Les consultants soft-deleted sont ignorés.
- Si plusieurs consultants partagent le même nom normalisé, l'identifiant le
  plus petit est retenu et le nom est signalé comme ambigu dans le rapport.
- Aucun consultant n'est créé automatiquement : un bénéficiaire inconnu reste
  non rattaché et est reporté.

### Mission

- Conditions : `consultant_id`, `client_id` et `fournisseur_id` identiques, et
  date de facture comprise dans l'intervalle de la mission.
- Une mission sans `date_fin` est considérée comme en cours (fin ouverte).
- Les missions soft-deleted sont ignorées.
- Si plusieurs missions se chevauchent, la plus récente (`date_debut` puis `id`
  décroissants) est retenue.
- Sans mission correspondante, `mission_id` reste `NULL` et la facture est
  reportée comme « mission introuvable ».

## Comportement en cas d'échec

Le rattachement s'exécute dans un savepoint de la transaction d'import. En cas
d'exception :

- seules les écritures de rattachement sont annulées ;
- l'erreur est journalisée (`ImportFactures.rattachement_echec`) ;
- l'erreur apparaît dans la section « Échecs » du rapport ;
- les factures déjà créées sont conservées et le commit est effectué.

Un import n'est donc jamais perdu à cause du rattachement.

## Mode simulation

Sans `--execute`, aucune facture n'est créée. La commande exécute malgré tout
`FactureRattachementService::previsualiser()` sur les lignes du fichier afin
d'afficher les compteurs de rattachement prévisionnels. Les clients ou
fournisseurs qui n'existent pas encore n'ont pas d'identifiant : les missions
correspondantes ne peuvent pas être prévisualisées pour ces lignes.

## Rapport

La section « Rattachement consultants / missions » affiche :

- le nombre de factures rattachées à un consultant ;
- le nombre de factures rattachées à une mission ;
- les noms de consultants ambigus ;
- les factures non rattachées (identifiant ou ligne Excel, bénéficiaire, raison).

## Maintenance

- Les index nécessaires sont créés par
  `database/migrations/2026_11_27_000001_add_rattachement_indexes.php`.
- Toute modification des règles doit être couverte par
  `tests/Feature/FactureRattachementTest.php`.
- Ne jamais remplacer ce traitement par un `UPDATE` global sur les factures
  `NULL` : cela modifierait l'historique hors import.
