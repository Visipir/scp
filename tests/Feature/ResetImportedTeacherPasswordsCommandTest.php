<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class ResetImportedTeacherPasswordsCommandTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_reset_imported_passwords_dry_run_and_apply(): void
    {
        putenv('SEED_DEFAULT_PASSWORD=***REMOVED***');

        // Dummy teacher ID 2 (should be ignored)
        $dummyTeacher = $this->makeUser(2, ['id' => 2, 'username' => 'guru_andi', 'password' => Hash::make('old_pass')]);

        // Imported teacher
        $importedTeacher = $this->makeUser(2, [
            'id' => 1718,
            'username' => 'guru_6aa0e473a0ac6',
            'fullname' => 'RUSDIYANTO, S.Pd',
            'password' => Hash::make('***REMOVED***'),
            'must_change_password' => false,
        ]);

        // 1. Dry run
        $this->artisan('teachers:reset-imported-passwords')
            ->expectsOutputToContain('DRY-RUN completed. Database was NOT modified')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('***REMOVED***', $importedTeacher->fresh()->password));
        $this->assertFalse((bool) $importedTeacher->fresh()->must_change_password);

        // 2. Apply
        $this->artisan('teachers:reset-imported-passwords --apply')
            ->expectsOutputToContain('Successfully reset passwords and flagged must_change_password for 1 imported teachers')
            ->assertSuccessful();

        $fresh = $importedTeacher->fresh();
        $this->assertFalse(Hash::check('***REMOVED***', $fresh->password));
        $this->assertTrue(Hash::check('***REMOVED***', $fresh->password));
        $this->assertTrue((bool) $fresh->must_change_password);

        // Dummy teacher password was not modified
        $this->assertTrue(Hash::check('old_pass', $dummyTeacher->fresh()->password));
    }
}
