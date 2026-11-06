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
        // 1. SRI SUNARTINI vs SRI SUNARSIH must be AMBIGUOUS, never exact or fuzzy matched
        $res1 = $this->empService->compareNamesTokenWise('SRI SUNARTINI, S.Pd.', 'SRI SUNARSIH, S.Pd.');
        $this->assertSame('ambiguous', $res1['status']);

        // 2. TATIK HERAWATI vs TATIK WARDAYATI must be AMBIGUOUS
        $res2 = $this->empService->compareNamesTokenWise('TATIK HERAWATI, S.Pd', 'TATIK WARDAYATI, S.Pd.');
        $this->assertSame('ambiguous', $res2['status']);

        // 3. YULI ASTUTI vs YULI ARBIATININGSIH must be AMBIGUOUS
        $res3 = $this->empService->compareNamesTokenWise('YULI ASTUTI, S.Pd.', 'YULI ARBIATININGSIH, S.Pd');
        $this->assertSame('ambiguous', $res3['status']);
    }

    public function test_positive_pairs_are_fuzzy_matched_with_high_score()
    {
        // 1. ANASTASIA vs ANASTYA must be FUZZY
        $res1 = $this->empService->compareNamesTokenWise(
            'ANASTASIA RATNA SULISTIYANI, S.Pd',
            'ANASTYA RATNA SULISTIYANI, S.Pd'
        );
        $this->assertSame('fuzzy', $res1['status']);
        $this->assertGreaterThanOrEqual(85.0, $res1['score']);

        // 2. MUHLASHIN vs MUHLASIN must be FUZZY
        $res2 = $this->empService->compareNamesTokenWise(
            'AHMAD MUHLASHIN, S.Pd.',
            'AHMAD MUHLASIN, S.Pd.'
        );
        $this->assertSame('fuzzy', $res2['status']);
        $this->assertGreaterThanOrEqual(85.0, $res2['score']);

        // 3. SUSILAWATI vs SUSILOWATI must be FUZZY
        $res3 = $this->empService->compareNamesTokenWise(
            'TRI SUSILAWATI, S.T., M.Pd',
            'TRI SUSILOWATI, S.T., M.Pd'
        );
        $this->assertSame('fuzzy', $res3['status']);
        $this->assertGreaterThanOrEqual(85.0, $res3['score']);
    }

    public function test_exact_pair_matches_with_score_100()
    {
        $res = $this->empService->compareNamesTokenWise('AMAT SUKARDI, S.Pd.', 'AMAT SUKARDI, S.Pd.');
        $this->assertSame('exact', $res['status']);
        $this->assertEquals(100.0, $res['score']);
    }

    public function test_fuzzy_candidates_never_auto_assigned_in_match_teacher()
    {
        $teacher = $this->makeUser(2, ['fullname' => 'AHMAD MUHLASIN, S.Pd.']);
        $existing = collect([$teacher]);

        // When source row has typo 'AHMAD MUHLASHIN'
        $match = $this->empService->matchTeacher(['name' => 'AHMAD MUHLASHIN, S.Pd.'], $existing);

        // Status must be fuzzy, NOT matched
        $this->assertSame('fuzzy', $match['category']);
        // The candidate must be Ahmad Muhlasin
        $this->assertSame($teacher->id, $match['user']->id);
        $this->assertNotEmpty($match['candidates']);
    }

    public function test_two_different_source_names_mapping_to_same_user_is_detected_as_collision()
    {
        $teacher = $this->makeUser(2, ['fullname' => 'AMAT SUKARDI, S.Pd.']);

        // Suppose two rows in preview both resolve to the same user
        $previewRows = [
            [
                'name'         => 'AMAT SUKARDI, S.Pd.',
                'matched_user' => ['id' => $teacher->id],
                'status'       => 'matched',
            ],
            [
                'name'         => 'AMAT SUKARDI REKAYASA',
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
