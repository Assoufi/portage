<?php

namespace Tests\Feature;

use App\Models\ExecutedScript;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseScriptControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disque isolé par test : les fichiers téléversés ne persistent pas.
        Storage::fake('local');
    }

    public function test_database_index_exige_authentification(): void
    {
        $this->get('/database')->assertRedirect('/login');
    }

    public function test_database_index_interdit_aux_non_admins(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/database')->assertForbidden();
    }

    public function test_database_index_est_affiche_pour_un_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/database')
            ->assertOk()
            ->assertSee('Database — Scripts SQL');
    }

    public function test_preview_rejette_un_fichier_non_sql(): void
    {
        $admin = User::factory()->admin()->create();
        $fichier = UploadedFile::fake()->createWithContent('script.txt', 'INSERT INTO users (name) VALUES (X);');

        $reponse = $this->actingAs($admin)
            ->post('/database/preview', ['script' => $fichier]);

        $reponse->assertSessionHasErrors('script');
        $this->assertDatabaseCount('executed_scripts', 0);
        Storage::disk('local')->assertDirectoryEmpty('scripts-sql');
    }

    public function test_preview_rejette_un_fichier_trop_volumineux(): void
    {
        $admin = User::factory()->admin()->create();
        $contenu = "SELECT 1;\n".str_repeat('x', 3 * 1024 * 1024);
        $fichier = UploadedFile::fake()->createWithContent('gros.sql', $contenu);

        $reponse = $this->actingAs($admin)
            ->post('/database/preview', ['script' => $fichier]);

        $reponse->assertSessionHasErrors('script');
        $this->assertDatabaseCount('executed_scripts', 0);
    }

    public function test_preview_bloque_un_script_avec_requete_dangereuse(): void
    {
        $admin = User::factory()->admin()->create();
        $fichier = UploadedFile::fake()->createWithContent('drop.sql', "SELECT 1;\nDROP TABLE users;\nSELECT 2;");

        $reponse = $this->actingAs($admin)
            ->post('/database/preview', ['script' => $fichier]);

        $script = ExecutedScript::query()->sole();
        $reponse->assertRedirect(route('database.show', $script));

        $this->assertSame('blocked', $script->fresh()->status);
        $this->assertNotEmpty($script->fresh()->detected_risks);
        $this->assertNull($script->fresh()->executed_at);
        $this->assertDatabaseCount('users', 1); // aucune exécution : seul l'admin du test existe
    }

    public function test_preview_accepte_un_script_propre_et_propose_la_confirmation(): void
    {
        $admin = User::factory()->admin()->create();
        $fichier = UploadedFile::fake()->createWithContent(
            'prop.sql',
            "INSERT INTO users (name, email, password) VALUES ('SQL', 'sql-prop@example.com', 'x');"
        );

        $reponse = $this->actingAs($admin)
            ->post('/database/preview', ['script' => $fichier]);

        $script = ExecutedScript::query()->sole();
        $reponse->assertRedirect(route('database.confirm', $script));
        $this->assertSame('pending', $script->status);

        $this->actingAs($admin)
            ->get(route('database.confirm', $script))
            ->assertOk()
            ->assertSee($script->checksum);
    }

    public function test_execute_exige_la_ressaisie_du_checksum_et_la_confirmation_de_sauvegarde(): void
    {
        $admin = User::factory()->admin()->create();
        $script = $this->televerserScriptPropre('sql-exec@example.com', $admin);

        $this->actingAs($admin)
            ->post(route('database.execute', $script), [])
            ->assertSessionHasErrors(['checksum', 'backup_confirmed']);

        $this->assertSame('pending', $script->fresh()->status);
    }

    public function test_execute_rejette_un_checksum_incorrect(): void
    {
        $admin = User::factory()->admin()->create();
        $script = $this->televerserScriptPropre('sql-wrong@example.com', $admin);

        $this->actingAs($admin)
            ->post(route('database.execute', $script), [
                'checksum' => str_repeat('0', 64),
                'backup_confirmed' => '1',
            ])
            ->assertSessionHasErrors('checksum');

        $this->assertSame('pending', $script->fresh()->status);
    }

    public function test_execute_refsationne_le_script_et_le_trace(): void
    {
        $admin = User::factory()->admin()->create();
        $script = $this->televerserScriptPropre('sql-ok@example.com', $admin);

        $reponse = $this->actingAs($admin)
            ->post(route('database.execute', $script), [
                'checksum' => $script->checksum,
                'backup_confirmed' => '1',
            ]);

        $reponse->assertRedirect(route('database.show', $script));

        $script = $script->fresh();
        $this->assertSame('completed', $script->status);
        $this->assertEquals(1, $script->affected_rows);
        $this->assertNotNull($script->executed_at);
        $this->assertDatabaseHas('users', ['email' => 'sql-ok@example.com']);
    }

    public function test_execute_est_refuse_pour_un_script_bloque(): void
    {
        $admin = User::factory()->admin()->create();
        $fichier = UploadedFile::fake()->createWithContent('suppression.sql', 'DELETE FROM users;');

        $this->actingAs($admin)
            ->post('/database/preview', ['script' => $fichier]);

        $script = ExecutedScript::query()->sole();

        $this->actingAs($admin)
            ->get(route('database.confirm', $script))
            ->assertRedirect(route('database.show', $script));

        $this->actingAs($admin)
            ->post(route('database.execute', $script), [
                'checksum' => $script->checksum,
                'backup_confirmed' => '1',
            ])
            ->assertForbidden();

        $this->assertSame('blocked', $script->fresh()->status);
    }

    public function test_execute_est_refuse_pour_un_autre_admin_que_televerseur(): void
    {
        $televerseur = User::factory()->admin()->create();
        $autreAdmin = User::factory()->admin()->create();
        $script = $this->televerserScriptPropre('sql-autre@example.com', $televerseur);

        $this->actingAs($autreAdmin)
            ->post(route('database.execute', $script), [
                'checksum' => $script->checksum,
                'backup_confirmed' => '1',
            ])
            ->assertForbidden();

        $this->assertSame('pending', $script->fresh()->status);
    }

    /**
     * Téléverse un script propre en base et retourne sa trace d'audit.
     */
    private function televerserScriptPropre(string $email, User $admin): ExecutedScript
    {
        $sql = "INSERT INTO users (name, email, password) VALUES ('Auto', '{$email}', 'x');";
        $fichier = UploadedFile::fake()->createWithContent('execution.sql', $sql);

        $this->actingAs($admin)->post('/database/preview', ['script' => $fichier]);

        return ExecutedScript::query()->sole();
    }
}
