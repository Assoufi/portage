<?php

namespace Tests\Unit;

use App\Services\SqlRiskDetector;
use Tests\TestCase;

class SqlRiskDetectorTest extends TestCase
{
    private SqlRiskDetector $detecteur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detecteur = new SqlRiskDetector;
    }

    private function analyse(string $sql): array
    {
        return $this->detecteur->analyze($sql);
    }

    public function test_script_propre_avec_select_insert_update_create_passe(): void
    {
        $sql = <<<'SQL'
SELECT id, nom FROM clients WHERE id = 1;
INSERT INTO repartitions (paiement_id, consultant_id) VALUES (1, 2);
UPDATE users SET name = 'X' WHERE id = 3;
CREATE TABLE table_temporaire (id BIGINT UNSIGNED NOT NULL, nom VARCHAR(50));
CREATE UNIQUE INDEX index_nom ON table_temporaire (nom);
SQL;

        $resultat = $this->analyse($sql);

        $this->assertSame([], $resultat['risks']);
        $this->assertCount(5, $resultat['statements']);
        $this->assertTrue($resultat['has_ddl']);
    }

    public function test_drop_table_est_bloque(): void
    {
        $resultat = $this->analyse('DROP TABLE users;');

        $this->assertContains('DROP', array_column($resultat['risks'], 'rule'));
    }

    public function test_delete_est_toujours_bloque(): void
    {
        $resultat = $this->analyse('DELETE FROM paiements WHERE id = 1;');

        $rules = array_column($resultat['risks'], 'rule');
        $this->assertContains('DELETE', $rules);
        $this->assertContains('Type de requête non autorisé', $rules);
    }

    public function test_alter_table_est_bloque(): void
    {
        $resultat = $this->analyse('ALTER TABLE users ADD COLUMN note VARCHAR(255);');

        $this->assertContains('ALTER', array_column($resultat['risks'], 'rule'));
    }

    public function test_truncate_rename_exec_call_et_create_or_replace_sont_bloques(): void
    {
        $sql = "TRUNCATE TABLE clients;\nRENAME TABLE clients TO c2;\nEXECUTE ma_requete;\nCALL ma_procedure();\nCREATE OR REPLACE VIEW v AS SELECT 1;";
        $resultat = $this->analyse($sql);

        $rules = array_column($resultat['risks'], 'rule');
        $this->assertContains('TRUNCATE', $rules);
        $this->assertContains('RENAME', $rules);
        $this->assertContains('EXEC/EXECUTE', $rules);
        $this->assertContains('CALL', $rules);
        $this->assertContains('CREATE OR REPLACE', $rules);
    }

    public function test_update_sans_where_est_bloque(): void
    {
        $resultat = $this->analyse('UPDATE users SET name = X;');

        $this->assertContains('UPDATE sans WHERE', array_column($resultat['risks'], 'rule'));
    }

    public function test_set_foreign_key_checks_grant_revoke_use_sont_bloques(): void
    {
        $sql = "SET FOREIGN_KEY_CHECKS = 0;\nGRANT ALL ON *.* TO 'u'@'h';\nREVOKE SELECT ON portage FROM u;\nUSE portage;";
        $resultat = $this->analyse($sql);

        $rules = array_column($resultat['risks'], 'rule');
        $this->assertContains('SET FOREIGN_KEY_CHECKS', $rules);
        $this->assertContains('GRANT/REVOKE', $rules);
        $this->assertContains('USE (changement de base)', $rules);
    }

    public function test_select_into_outfile_est_bloque(): void
    {
        $resultat = $this->analyse("SELECT * FROM clients INTO OUTFILE '/tmp/dump.csv';");

        $this->assertContains('INTO OUTFILE/DUMPFILE', array_column($resultat['risks'], 'rule'));
    }

    public function test_les_commentaires_ne_provoquent_pas_de_faux_positif(): void
    {
        $sql = "-- Recuperation du fichier, DROP TABLE serait au milieu du commentaire\n# DROP TABLE deuxieme commentaire\nINSERT INTO missions (tjm, prix_vente) VALUES (1, 2);\n/* DROP TABLE IGNORED ICI */ SELECT 1;";
        $resultat = $this->analyse($sql);

        $this->assertSame([], $resultat['risks']);
        $this->assertCount(2, $resultat['statements']);
    }

    public function test_les_regles_sont_insensibles_a_la_casse(): void
    {
        $resultat = $this->analyse('drop table users;');

        $this->assertContains('DROP', array_column($resultat['risks'], 'rule'));
    }

    public function test_un_statement_hors_whitelist_est_bloque(): void
    {
        $resultat = $this->analyse('COMMIT;');

        $this->assertContains('Type de requête non autorisé', array_column($resultat['risks'], 'rule'));
    }

    public function test_delimiter_pour_procedures_est_bloque(): void
    {
        $resultat = $this->analyse("DELIMITER //\nCREATE TABLE t (id INT)\n//\nDELIMITER ;");

        $this->assertContains('DELIMITER (routine)', array_column($resultat['risks'], 'rule'));
    }

    public function test_point_virgule_dans_une_chaine_ne_casse_pas_le_decoupage(): void
    {
        $resultat = $this->analyse("INSERT INTO missions (titre) VALUES ('fait; fin; par');\nSELECT 1;");

        $this->assertSame([], $resultat['risks']);
        $this->assertCount(2, $resultat['statements']);
    }

    public function test_le_numero_de_ligne_est_renseigne_pour_chaque_risque(): void
    {
        $sql = "SELECT 1;\nSELECT 2;\nDROP TABLE users;\nDROP TABLE clients;";
        $resultat = $this->analyse($sql);

        $lignes = array_column($resultat['risks'], 'line');
        $this->assertContains(3, $lignes);
        $this->assertContains(4, $lignes);
    }

    public function test_fichier_vide_ou_uniquement_des_commentaires_ne_donne_rien(): void
    {
        $resultat = $this->analyse("  \n-- juste un commentaire\n");

        $this->assertSame([], $resultat['statements']);
        $this->assertSame([], $resultat['risks']);
        $this->assertFalse($resultat['has_ddl']);
    }

    public function test_load_data_et_load_file_sont_bloques(): void
    {
        $resultat = $this->analyse("SELECT LOAD_FILE('/etc/passwd');\nLOAD DATA INFILE 'x.txt' INTO TABLE t;");

        $this->assertContains('LOAD DATA/LOAD_FILE', array_column($resultat['risks'], 'rule'));
    }

    public function test_update_avec_clause_where_passe(): void
    {
        $resultat = $this->analyse('update users set name = \'x\' where id = 1;');

        $this->assertSame([], $resultat['risks']);
    }

    public function test_mots_cles_sql_dans_les_donnees_ne_provoquent_pas_de_faux_positif(): void
    {
        $sql = "INSERT INTO users (name, email, password) VALUES ('Auto', 'sql-exec@example.com', 'x');\nUPDATE users SET name = 'je vais deleter et DROP TRUC et ALTER plus tard' WHERE id = 1;";

        $this->assertSame([], $this->analyse($sql)['risks']);
    }

    public function test_un_keyword_dans_un_identifiant_backtick_reste_suspect(): void
    {
        $resultat = $this->analyse('SELECT `use` FROM t;');

        $rules = array_column($resultat['risks'], 'rule');
        $this->assertContains('USE (changement de base)', $rules);
    }
}
