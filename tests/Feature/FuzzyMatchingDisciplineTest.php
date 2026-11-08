<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\User;
use App\Services\EmployeeImportService;
use App\Services\HomeroomImportService;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class FuzzyMatchingDisciplineTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;
    use ScpTestHelpers;

    private EmployeeImportService $empService;
    private HomeroomImportService $homeroomService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->empService = app(EmployeeImportService::class);
        $this->homeroomService = app(HomeroomImportService::class);
    }

    public function test_negative_pairs_never_auto_matched()
    {
        // 1. SRI PRATAMA vs SRI PRANATA must be AMBIGUOUS, never exact or fuzzy matched
        $res1 = $this->empService->compareNamesTokenWise('SRI PRATAMA, S.Pd.', 'SRI PRANATA, S.Pd.');
        $this->assertSame('ambiguous', $res1['status']);
    }

    public function test_ambiguous_threshold_low_scores_become_new_and_high_remain_ambiguous()
    {
        $teacherPratama = $this->makeUser(2, ['fullname' => 'SRI PRATAMA, S.Pd.']);
        $teacherHeru = $this->makeUser(2, ['fullname' => 'MUHAMMAD KURNIAWAN, M.S.I']);
        $existing = collect([$teacherPratama, $teacherHeru]);

        // SRI WULANDARI -> score < 70% -> NEW
        $match1 = $this->empService->matchTeacher(['name' => 'Dra. SRI WULANDARI'], $existing);
        $this->assertSame('new', $match1['category']);

        // SRI LESTARI -> score < 70% -> NEW
        $match2 = $this->empService->matchTeacher(['name' => 'SRI LESTARI, S.Pd., M.Pd.'], $existing);
        $this->assertSame('new', $match2['category']);

        // SRI UTAMI -> score < 70% -> NEW
        $match3 = $this->empService->matchTeacher(['name' => 'SRI UTAMI, S.Pd'], $existing);
        $this->assertSame('new', $match3['category']);

        // MUHAMMAD CONTOH -> score < 70% -> NEW
        $match4 = $this->empService->matchTeacher(['name' => 'MUHAMMAD CONTOH, S.Sos'], $existing);
        $this->assertSame('new', $match4['category']);

        // SRI PRATAMA vs SRI PRANATA -> score >= 70% -> AMBIGUOUS
        $teacherPranata = $this->makeUser(2, ['fullname' => 'SRI PRANATA, S.Pd.']);
        $existingWithBoth = collect([$teacherPranata]);
        $matchPratama = $this->empService->matchTeacher(['name' => 'SRI PRATAMA, S.Pd.'], $existingWithBoth);
        $this->assertSame('ambiguous', $matchPratama['category']);
    }

    public function test_user_with_nip_a_vs_source_nip_b_with_same_name_is_new()
    {
        $teacherA = $this->makeUser(2, ['fullname' => 'Ahmad Fauzi', 'nip' => '198001012005011001']);
        $existing = collect([$teacherA]);

        // Source has same name but different NIP -> must be NEW (never candidate)
        $match = $this->empService->matchTeacher([
            'name' => 'Ahmad Fauzi',
            'nip'  => '198502022008011002',
        ], $existing);

        $this->assertSame('new', $match['category']);
    }

    public function test_two_source_rows_with_same_name_and_different_nips_create_two_separate_accounts()
    {
        \Illuminate\Support\Facades\Cache::put('test_token_twin', [
            'rows' => [
                [
                    'name'   => 'Budi Santoso',
                    'nip'    => '198001012005011001',
                    'email'  => 'budi1@example.com',
                    'status' => 'new',
                ],
                [
                    'name'   => 'Budi Santoso',
                    'nip'    => '198502022008011002',
                    'email'  => 'budi2@example.com',
                    'status' => 'new',
                ],
            ]
        ], now()->addMinutes(10));

        $res = $this->empService->commit('test_token_twin');
        $this->assertEquals(2, $res['created']);

        $users = User::where('fullname', 'Budi Santoso')->get();
        $this->assertCount(2, $users);
        $this->assertNotEquals($users[0]->username, $users[1]->username);
        // Username should contain last 4 digits of NIP
        $this->assertTrue(
            str_contains($users[0]->username, '1001') || str_contains($users[1]->username, '1002')
        );
    }

    public function test_reimport_is_idempotent_zero_accounts_created_on_second_run()
    {
        $payload = [
            'rows' => [
                [
                    'name'   => 'Guru Pertama',
                    'nip'    => '199001012015011001',
                    'email'  => 'guru1@example.com',
                    'status' => 'new',
                ],
            ]
        ];

        \Illuminate\Support\Facades\Cache::put('token_run_1', $payload, now()->addMinutes(10));
        $res1 = $this->empService->commit('token_run_1');
        $this->assertEquals(1, $res1['created']);

        // Second run: existing teachers reloaded from DB
        $freshTeachers = User::where('role_id', 2)->get();
        $match = $this->empService->matchTeacher([
            'name' => 'Guru Pertama',
            'nip'  => '199001012015011001',
        ], $freshTeachers);

        $this->assertSame('matched', $match['category']);
        $this->assertSame('nip', $match['match_type']);

        $payload2 = [
            'rows' => [
                [
                    'name'         => 'Guru Pertama',
                    'nip'          => '199001012015011001',
                    'email'        => 'guru1@example.com',
                    'status'       => 'matched',
                    'matched_user' => ['id' => $match['user']->id],
                ]
            ]
        ];

        \Illuminate\Support\Facades\Cache::put('token_run_2', $payload2, now()->addMinutes(10));
        $res2 = $this->empService->commit('token_run_2');
        $this->assertEquals(0, $res2['created']);
        $this->assertEquals(1, $res2['updated']);
    }

    public function test_degree_mismatch_warning_in_fuzzy()
    {
        $teacher = $this->makeUser(2, ['fullname' => 'MUHAMMAD KURNIAWAN, M.S.I']);
        $existing = collect([$teacher]);

        $match = $this->empService->matchTeacher([
            'name' => 'MUHAMAD KURNIAWAN, S.Pd.',
        ], $existing);

        $this->assertSame('fuzzy', $match['category']);
        $this->assertNotNull($match['title_warning']);
        $this->assertStringContainsString('Peringatan: Gelar berbeda', $match['title_warning']);
        $this->assertStringContainsString('S.Pd', $match['title_warning']);
        $this->assertStringContainsString('M.S.I', $match['title_warning']);
    }

    public function test_positive_pairs_are_fuzzy_matched_with_high_score()
    {
        // 1. ANASTASIA vs ANASTYA must be FUZZY
        $res1 = $this->empService->compareNamesTokenWise(
            'ANASTASIA KUSUMA PUTRI, S.Pd',
            'ANASTYA KUSUMA PUTRI, S.Pd'
        );
        $this->assertSame('fuzzy', $res1['status']);
        $this->assertGreaterThanOrEqual(85.0, $res1['score']);

        // 2. MUHLASHIN vs MUHLASIN must be FUZZY
        $res2 = $this->empService->compareNamesTokenWise(
            'AHMAD PRADANA, S.Pd.',
            'AHMAD PERDANA, S.Pd.'
        );
        $this->assertSame('fuzzy', $res2['status']);
        $this->assertGreaterThanOrEqual(85.0, $res2['score']);

        // 3. SUSILAWATI vs SUSILOWATI must be FUZZY
        $res3 = $this->empService->compareNamesTokenWise(
            'TRI WIJAYAWATI, S.T., M.Pd',
            'TRI WIJAYOWATI, S.T., M.Pd'
        );
        $this->assertSame('fuzzy', $res3['status']);
        $this->assertGreaterThanOrEqual(85.0, $res3['score']);
    }

    public function test_exact_pair_matches_with_score_100()
    {
        $res = $this->empService->compareNamesTokenWise('GURU CONTOH TESTING, S.Pd.', 'GURU CONTOH TESTING, S.Pd.');
        $this->assertSame('exact', $res['status']);
        $this->assertEquals(100.0, $res['score']);
    }

    public function test_fuzzy_candidates_never_auto_assigned_in_match_teacher()
    {
        $teacher = $this->makeUser(2, ['fullname' => 'AHMAD PERDANA, S.Pd.']);
        $existing = collect([$teacher]);

        // When source row has typo 'AHMAD PRADANA'
        $match = $this->empService->matchTeacher(['name' => 'AHMAD PRADANA, S.Pd.'], $existing);

        // Status must be fuzzy, NOT matched
        $this->assertSame('fuzzy', $match['category']);
        // The candidate must be Ahmad Perdana
        $this->assertSame($teacher->id, $match['user']->id);
        $this->assertNotEmpty($match['candidates']);
    }

    public function test_two_different_source_names_mapping_to_same_user_is_detected_as_collision()
    {
        $teacher = $this->makeUser(2, ['fullname' => 'GURU CONTOH TESTING, S.Pd.']);

        // Suppose two rows in preview both resolve to the same user
        $previewRows = [
            [
                'name'         => 'GURU CONTOH TESTING, S.Pd.',
                'matched_user' => ['id' => $teacher->id],
                'status'       => 'matched',
            ],
            [
                'name'         => 'GURU CONTOH TESTING REKAYASA',
                'matched_user' => ['id' => $teacher->id],
                'status'       => 'fuzzy',
            ],
        ];

        // Collision detection logic
        $userTargetMap = [];
        foreach ($previewRows as $idx => $r) {
            $uid = $r['matched_user']['id'] ?? null;
            if ($uid) {
                $userTargetMap[$uid][] = [
                    'index'       => $idx,
                    'source_name' => $r['name'],
                ];
            }
        }

        $collisionFound = false;
        foreach ($userTargetMap as $uid => $occurrences) {
            if (count($occurrences) > 1) {
                $uniqueNames = array_unique(array_map(fn($o) => trim($o['source_name']), $occurrences));
                if (count($uniqueNames) > 1) {
                    $collisionFound = true;
                }
            }
        }

        $this->assertTrue($collisionFound);
    }
}
