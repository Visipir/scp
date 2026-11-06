<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserNameAlias;
use App\Services\EmployeeImportService;
use App\Services\FetScheduleParserService;
use App\Services\HomeroomImportService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class UserNameAliasTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_unique_normalized_alias_constraint()
    {
        $user1 = $this->makeUser(2, ['fullname' => 'Anastasia Ratna S, S.Pd.']);
        $user2 = $this->makeUser(2, ['fullname' => 'Guru Lain, S.Pd.']);

        UserNameAlias::create([
            'user_id'          => $user1->id,
            'alias_raw'        => 'Anastya Ratna S',
            'alias_normalized' => UserNameAlias::normalize('Anastya Ratna S'),
        ]);

        $this->expectException(QueryException::class);

        // Attempt duplicate alias normalized
        UserNameAlias::create([
            'user_id'          => $user2->id,
            'alias_raw'        => 'ANASTYA RATNA S.',
            'alias_normalized' => UserNameAlias::normalize('ANASTYA RATNA S.'),
        ]);
    }

    public function test_alias_matching_in_fet_schedule_parser()
    {
        $teacher = $this->makeUser(2, ['fullname' => 'Anastasia Ratna Sulistiyani, S.Pd.']);

        // Before alias: ANASTYA does not resolve
        $parser = app(FetScheduleParserService::class);
        $teachers = User::where('role_id', 2)->get();
        $resolvedBefore = $parser->resolveTeacher('ANASTYA RATNA SULISTIYANI, S.Pd', null, $teachers);
        $this->assertNull($resolvedBefore);

        // Save confirmed fuzzy variant as alias
        UserNameAlias::create([
            'user_id'          => $teacher->id,
            'alias_raw'        => 'ANASTYA RATNA SULISTIYANI, S.Pd',
            'alias_normalized' => UserNameAlias::normalize('ANASTYA RATNA SULISTIYANI, S.Pd'),
        ]);

        // After alias: ANASTYA resolves to Anastasia!
        $resolvedAfter = $parser->resolveTeacher('ANASTYA RATNA SULISTIYANI, S.Pd', null, $teachers);
        $this->assertNotNull($resolvedAfter);
        $this->assertSame($teacher->id, $resolvedAfter->id);
    }

    public function test_alias_matching_in_employee_and_homeroom_services()
    {
        $teacher = $this->makeUser(2, ['fullname' => 'Anastasia Ratna Sulistiyani, S.Pd.']);

        UserNameAlias::create([
            'user_id'          => $teacher->id,
            'alias_raw'        => 'Anastya Ratna',
            'alias_normalized' => UserNameAlias::normalize('Anastya Ratna'),
        ]);

        $empService = app(EmployeeImportService::class);
        $teachers = User::where('role_id', 2)->get();

        $empMatch = $empService->matchTeacher([
            'name' => 'Anastya Ratna',
            'nip'  => null,
        ], $teachers, ['ANASTYA RATNA' => 1], ['ANASTYA RATNA' => 0]);

        $this->assertSame('matched', $empMatch['category']);
        $this->assertSame('alias', $empMatch['match_type']);
        $this->assertSame($teacher->id, $empMatch['user']->id);

        $hrService = app(HomeroomImportService::class);
        $hrMatch = $hrService->matchTeacher('Anastya Ratna', $teachers);

        $this->assertSame('matched', $hrMatch['category']);
        $this->assertSame($teacher->id, $hrMatch['user']->id);
    }
}
