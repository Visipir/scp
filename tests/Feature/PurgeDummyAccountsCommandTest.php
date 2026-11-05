<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class PurgeDummyAccountsCommandTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_dry_run_does_not_delete_accounts(): void
    {
        $dummy = $this->makeUser(2, ['username' => 'guru_siti']);

        $this->artisan('accounts:purge-dummy')
            ->expectsOutputToContain('DRY-RUN completed. Database was NOT modified')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $dummy->id]);
    }

    public function test_apply_blocks_deletion_when_relations_exist(): void
    {
        $dummyTeacher = $this->makeUser(2, ['username' => 'guru_andi']);
        $this->makeSchedule(['teacher_id' => $dummyTeacher->id]);

        $this->artisan('accounts:purge-dummy --apply')
            ->expectsOutputToContain('BLOCKED (has relations)')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $dummyTeacher->id]);
    }

    public function test_apply_deletes_only_eligible_dummy_accounts_without_relations(): void
    {
        $dummyTeacherWithRelation = $this->makeUser(2, ['username' => 'guru_andi']);
        $this->makeSchedule(['teacher_id' => $dummyTeacherWithRelation->id]);

        $dummyTeacherClean = $this->makeUser(2, ['username' => 'guru_siti']);

        $this->artisan('accounts:purge-dummy --apply')
            ->expectsOutputToContain('Successfully purged 1 dummy accounts')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $dummyTeacherWithRelation->id]);
        $this->assertDatabaseMissing('users', ['id' => $dummyTeacherClean->id]);
    }
}
