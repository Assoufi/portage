# AGENTS.md — Portage

Laravel application for managing **portage salarial**: consultants are billed to clients through fournisseurs (partner companies), with payments, distributions to consultants, and document generation (invoices, quotes, mission certificates).

## Versions

- **Laravel**: 12.56.0 (locked in `composer.lock`, requires `^12.0`)
- **PHP**: `^8.2` (runtime: WAMP64 environment)
- **PHPUnit**: 11.5.55
- **Frontend**: Vite 6, Tailwind CSS 3, Alpine.js 3

## Architecture

Standard **MVC** on Laravel 12 with Breeze auth scaffolding, extended with:

- **Service layer** (`app/Services/`): business logic lives here, not in controllers — `FactureService`, `PaiementService`, `RepartitionService`, `CommandeService`, `DevisService`, `AttestationService`.
- **Models** (`app/Models/`): all use `SoftDeletes`, plus accessors/mutators and query scopes (`scopeParPeriode`, `scopeEnCours`, `scopeRecherche`...). Invoice/number generation is a model concern (`Facture::genererNumeroFacturePourFournisseur()`).
- **Policies** (`app/Policies/`): Client, Consultant, Fournisseur, Mission.
- **Enums** (`app/Enums/`): `ModePaiement` (virement, cheque, especes, carte).
- **Exports/Imports** (`app/Exports/`, `app/Imports/`): `FacturesExport`, `FacturesImport` via maatwebsite/excel.
- **Support** (`app/Support/`): `MontantEnLettres` — amounts spelled out in words (used on PDF documents).
- **Mail** (`app/Mail/`): notification emails (e.g. undeclared prestations).
- **Routing** (`routes/web.php`): every application route is behind `auth` middleware. Resource routes + custom actions (dispatch, clone, PDF, import). AJAX search endpoints live under the `/api` prefix (`consultants/search`, `clients/search`, `fournisseurs/search`).
- **Views**: Blade per domain — `resources/views/factures/`, `missions/`, `devis/`, etc., with Tailwind-based layouts.

## Packages

Production:

| Package | Version | Purpose |
|---|---|---|
| laravel/framework | 12.56.0 | MVC framework |
| barryvdh/laravel-dompdf | v3.1.2 | PDF generation for invoices, quotes, attestations |
| maatwebsite/excel | 3.1.70 | Excel import/export (invoices, payments, stats) |
| laravel/tinker | 2.10.1 | Interactive artisan shell |

Dev: `laravel/breeze` 2.4.1 (auth scaffolding), `laravel/pint` (code style), `laravel/pail` (live logs), `laravel/sail`, `mockery`, `fakerphp/faker`, `nunomaduro/collision`, `phpunit/phpunit` 11.5.55.

Frontend: `tailwindcss` 3 (+ `@tailwindcss/forms`), `alpinejs` 3 (+ plugins focus/intersect/mask), `axios`, `laravel-vite-plugin`, `postcss`/`autoprefixer`.

## MySQL Tables

| Table | Role |
|---|---|
| users | user accounts (Breeze) |
| consultants | consultant profiles (nom, unique email, tel, rib, mode_paiement enum, statut) |
| clients | mission clients (nom, adresse, **ice unique 15 chars**, tva default 20, devise default MAD) |
| fournisseurs | partner companies (ice unique 15 chars, rib/IBAN, taux) |
| missions | links consultant/client/fournisseur (tjm, prix_vente, delai_paiement = 30 days) |
| commandes | orders attached to a mission (unique num_commande, statut default 'ACTIF') — fournisseur is derived from the mission, not stored |
| factures | invoices (fournisseur_id, client_id, mission_id, consultant_id, unique numero_facture, amounts) |
| detail_factures | invoice line items (cascade delete with facture) |
| paiements | client payments (client_id, fournisseur_id, nullable mission_id, unique reference) |
| repartitions | distribution of a paiement to consultants (paiement_id + consultant_id) |
| devis | quotes (unique numero_devis, nullable mission) |
| attestations | mission certificates (consultant_id + mission_id, fonction, dates, client) |

Framework tables: `users`, `cache`, `jobs` (Laravel defaults).

**Relationships:**

- `Mission` **belongsTo** Consultant, Client, Fournisseur; **hasMany** Factures.
- `Facture` **belongsTo** Fournisseur, Client, Mission, Consultant; **hasMany** DetailFactures (the only `cascade` delete).
- `Paiement` **belongsTo** Client, Fournisseur, Mission (nullable); **hasMany** Repartitions.
- `Repartition` **belongsTo** Paiement + Consultant.
- `Commande`, `Devis`, `Attestation` **belongsTo** Mission (devis also belongsTo Fournisseur/Client; mission nullable).

All foreign keys are `onDelete('restrict') / onUpdate('cascade')` — hard deletion of referenced parents is impossible. Archiving is done via **SoftDeletes** (`deleted_at`) on nearly every table. Recent migrations (`devis`, `attestations`, `commandes`) convert referenced tables from MyISAM to InnoDB at runtime if needed.

## Naming Conventions

- **Domain vocabulary is French**, code identifiers in English where possible: plural snake_case tables (`factures`), singular PascalCase models (`Facture`), `XxxController` classes.
- Methods/scopes in camelCase, often French: `genererNumeroFacture`, `dupliquer`, `marquerReglee`, `calculateTotals`.
- Route names in kebab-case French: `factures.marquer-reglee`, `stats.prestations-non-declarees`.
- Blade directories per domain in French plural (`resources/views/factures/`, `paiements/`, `repartitions/`).
- Comments and business terms (devis, attestation, repartition) are kept in French throughout.

## Migration & Test Commands

```bash
php artisan migrate          # run migrations (MySQL; some convert MyISAM -> InnoDB as needed)
php artisan migrate:rollback # reverse the last batch
php artisan test             # Unit + Feature suites (PHPUnit 11)
php artisan test --filter=FactureTest   # run a single suite
composer dev                 # parallel: artisan serve + queue:listen + pail + vite
vendor/bin/pint              # auto-fix code style
npm run dev / npm run build  # Vite assets
```

Tests live in `tests/Unit` (model logic: Devis, Commande, Client, Attestation, MontantEnLettres) and `tests/Feature` (controllers: Facture, Devis, Commande, Client, Fournisseur, Attestation, Missions duplicate, auth). Feature tests run against the MySQL database `portage_test` (see `phpunit.xml`); mail/queue/cache/session drivers are replaced with inert drivers in tests.

## Business Constraints

- **ICE**: exactly 15 characters, unique, on both clients and fournisseurs; a uniqueness pre-check endpoint exists (`clients/check-identification`).
- **TVA** defaults to 20%, **devise** to MAD; all amounts are `decimal(12,2)`.
- **Invoice numbering**: format `yyyy-ddd`, counter **per fournisseur**, unique — soft-deleted rows are included in the counter because of the UNIQUE constraint.
- **Date validation**: `date_fin >= date_debut` is enforced in application logic (`Mission::validateDates`), not at DB level.
- **Payment terms**: mission `delai_paiement` defaults to 30 days, computed from `date_fin`.
- **No hard deletes**: restrictive FKs + SoftDeletes preserve history; rows are disabled/inactivated via the boolean `statut` flag instead of removed.
- Payments received (`montant_recu`) must be **distributed to consultants** through `repartitions` (each carries its own mode_paiement/rib/banque for the transfer).

## Files Not to Modify

- **`.env`** — secrets and environment-specific config; never commit or edit in production.
- **`database/migrations/*` already applied** — always create a new migration instead of editing existing ones (others' databases depend on them).
- **`composer.lock` / `package-lock.json`** — only touched via `composer install|update` / `npm install|update`.
- **`vendor/` and `node_modules/`** — generated by dependency managers.
- **Breeze scaffold** — `routes/auth.php`, `bootstrap/app.php`, `public/index.php`, `app/Http/Controllers/Auth/*`: generated by laravel/breeze; leave stable unless explicitly required.
