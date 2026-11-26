-- ============================================================================
-- Portage — Index de performance pour le filtrage des factures
-- Fichier   : database/sql/2026_11_24_add_performance_indexes_to_factures.sql
-- Cible     : MySQL 5.7+ / MariaDB 10.x
-- Effet     : AJOUTE uniquement des index (aucune donnée modifiée/supprimée)
-- Idempotent: oui (chaque index n'est créé que s'il n'existe pas déjà)
--
-- PRÉREQUIS : sauvegarde préalable de la base (obligatoire)
--   mysqldump -u root -p portage > D:\backups-portage\portage_before_indexes.sql
--
-- EXÉCUTION :
--   mysql -u root -p portage < database/sql/2026_11_24_add_performance_indexes_to_factures.sql
--   (ou coller le contenu dans phpMyAdmin / HeidiSQL)
--
-- ATTENTION : la création d'index verrouille brièvement la table `factures`.
--             À exécuter hors heures de pointe sur un volume important.
-- ============================================================================

-- Procédure utilitaire : ajoute un index seulement s'il n'existe pas déjà.
DROP PROCEDURE IF EXISTS add_index_if_not_exists;

DELIMITER $$

CREATE PROCEDURE add_index_if_not_exists(
    IN p_table   VARCHAR(64),
    IN p_index   VARCHAR(64),
    IN p_columns VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = p_table
          AND INDEX_NAME   = p_index
    ) THEN
        SET @ddl = CONCAT(
            'ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')'
        );
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- 1) Filtre par consultant (colonne totalement non indexée auparavant)
CALL add_index_if_not_exists(
    'factures', 'factures_consultant_id_index', '`consultant_id`'
);

-- 2) Filtre client + tri par date (couvre aussi le filtre client seul)
CALL add_index_if_not_exists(
    'factures', 'factures_client_id_date_facture_index', '`client_id`, `date_facture`'
);

-- 3) Tri par défaut de la liste : date_facture DESC, id DESC (pagination stable)
CALL add_index_if_not_exists(
    'factures', 'factures_date_facture_id_index', '`date_facture`, `id`'
);

-- 4) Statut « en retard » et statistiques : date_echeance < now()
CALL add_index_if_not_exists(
    'factures', 'factures_date_echeance_index', '`date_echeance`'
);

-- 5) Filtre date de réception (égalité exacte)
CALL add_index_if_not_exists(
    'factures', 'factures_date_reception_index', '`date_reception`'
);

-- 6) Filtre période (intervalle de mois sur une colonne DATE)
CALL add_index_if_not_exists(
    'factures', 'factures_periode_index', '`periode`'
);

-- 7) Filtre statut actif / inactif
CALL add_index_if_not_exists(
    'factures', 'factures_statut_index', '`statut`'
);

-- 8) Statuts réglée / non réglée (date_reglement IS [NOT] NULL)
CALL add_index_if_not_exists(
    'factures', 'factures_date_reglement_index', '`date_reglement`'
);

-- 9) Filtre « facture réglée » basé sur mode_reglement
CALL add_index_if_not_exists(
    'factures', 'factures_mode_reglement_index', '`mode_reglement`'
);

DROP PROCEDURE IF EXISTS add_index_if_not_exists;

-- ============================================================================
-- ROLLBACK (à exécuter manuellement si nécessaire — supprime uniquement les
-- index ci-dessus, aucune donnée) :
--
-- ALTER TABLE `factures` DROP INDEX `factures_consultant_id_index`;
-- ALTER TABLE `factures` DROP INDEX `factures_client_id_date_facture_index`;
-- ALTER TABLE `factures` DROP INDEX `factures_date_facture_id_index`;
-- ALTER TABLE `factures` DROP INDEX `factures_date_echeance_index`;
-- ALTER TABLE `factures` DROP INDEX `factures_date_reception_index`;
-- ALTER TABLE `factures` DROP INDEX `factures_periode_index`;
-- ALTER TABLE `factures` DROP INDEX `factures_statut_index`;
-- ALTER TABLE `factures` DROP INDEX `factures_date_reglement_index`;
-- ALTER TABLE `factures` DROP INDEX `factures_mode_reglement_index`;
-- ============================================================================

-- Vérification :
-- SHOW INDEX FROM `factures`;
