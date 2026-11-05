<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class NormalizeTeacherUsernamesCommandTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_dry_run_does_not_modify_database(): void
    {
        $teacher = $this->makeUser(2, [
            'username' => 'guru_6aa0e473a0ac6',
            'fullname' => 'RUSDIYANTO, S.Pd',
        ]);

        $this->artisan('teachers:normalize-usernames')
            ->expectsOutputToContain('DRY-RUN completed. Database was NOT modified')
            ->assertSuccessful();

        $this->assertSame('guru_6aa0e473a0ac6', $teacher->fresh()->username);
    }

    public function test_apply_mode_updates_usernames_and_creates_backup(): void
    {
        $teacher1 = $this->makeUser(2, [
            'username' => 'guru_6aa0e473a0ac6',
            'fullname' => 'RUSDIYANTO, S.Pd',
        ]);
        $teacher2 = $this->makeUser(2, [
            'username' => 'guru_6aa0e473df9dd',
            'fullname' => 'RUSDIYANTO, M.Pd', // Duplicate name
        ]);

        $token = $teacher1->createToken('test')->plainTextToken;
        $this->assertCount(1, $teacher1->tokens);

        $this->artisan('teachers:normalize-usernames --apply')
            ->expectsOutputToContain('Successfully normalized 2 teacher usernames')
            ->assertSuccessful();

        $this->assertSame('rusdiyanto', $teacher1->fresh()->username);
        $this->assertSame('rusdiyanto-2', $teacher2->fresh()->username);

        // Tokens should be revoked
        $this->assertCount(0, $teacher1->fresh()->tokens);

        // Backup file should exist
        $backups = File::glob(storage_path('app/backups/users_table_backup_*.json'));
        $this->assertNotEmpty($backups);

        // Clean up backup file
        foreach ($backups as $b) {
            File::delete($b);
        }
    }
}
