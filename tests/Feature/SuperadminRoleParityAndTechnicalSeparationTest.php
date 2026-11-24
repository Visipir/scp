<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class SuperadminRoleParityAndTechnicalSeparationTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    private User $superadmin;
    private User $waka;
    private User $teacher;
    private User $student;
    private SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        $this->class = SchoolClass::create([
            'class_name'    => 'XII RPL 1',
            'academic_year' => '2025/2026',
        ]);

        $this->superadmin = $this->makeUser(5, ['username' => 'superadmin_test']);
        $this->waka = $this->makeUser(1, ['username' => 'waka_test']);
        $this->teacher = $this->makeUser(2, ['username' => 'teacher_test']);
        $this->student = $this->makeUser(4, [
            'username' => 'student_test',
            'class_id' => $this->class->id,
        ]);

        $this->makeConfig();
    }

    public function test_technical_routes_forbidden_for_waka(): void
    {
        // 1. Email settings
        $this->actingAs($this->waka)
            ->get('/system-configuration/email')
            ->assertForbidden();

        $this->actingAs($this->waka)
            ->post('/system-configuration/email', ['mail_host' => 'smtp.evil.test'])
            ->assertForbidden();

        // 2. Gemini configuration
        $this->actingAs($this->waka)
            ->post('/waka/settings/gemini', ['gemini_api_key' => 'AIzaSyFakeKey'])
            ->assertForbidden();

        // 3. Login background management
        $this->actingAs($this->waka)
            ->get('/waka/settings/login-background')
            ->assertForbidden();

        $this->actingAs($this->waka)
            ->delete('/waka/settings/login-background')
            ->assertForbidden();

        // 4. API email settings
        $this->apiAs($this->waka)
            ->getJson('/api/v1/system/email-settings')
            ->assertForbidden();
    }

    public function test_technical_routes_accessible_for_superadmin(): void
    {
        // 1. Email settings
        $this->actingAs($this->superadmin)
            ->get('/system-configuration/email')
            ->assertOk();

        // 2. Gemini configuration
        $this->actingAs($this->superadmin)
            ->post('/waka/settings/gemini', [
                'gemini_api_key'        => 'AIzaSyTestValidKey123',
                'gemini_schedule_model' => 'gemini-3.5-flash',
                'gemini_chat_model'     => 'gemini-3.5-flash-lite',
            ])
            ->assertRedirect(route('waka.settings'));

        $this->assertDatabaseHas('system_configurations', [
            'gemini_schedule_model' => 'gemini-3.5-flash',
            'gemini_chat_model'     => 'gemini-3.5-flash-lite',
        ]);

        // 3. Login background settings page
        $this->actingAs($this->superadmin)
            ->get('/waka/settings/login-background')
            ->assertOk();

        // 4. API email settings
        $this->apiAs($this->superadmin)
            ->getJson('/api/v1/system/email-settings')
            ->assertOk();
    }

    public function test_shared_curriculum_settings_accessible_for_both_waka_and_superadmin(): void
    {
        // Both can view /waka/settings
        $this->actingAs($this->waka)->get('/waka/settings')->assertOk();
        $this->actingAs($this->superadmin)->get('/waka/settings')->assertOk();

        // Both can toggle cycle
        $this->actingAs($this->waka)->patch('/system-configuration/toggle-cycle')->assertRedirect(route('waka.settings'));
        $this->actingAs($this->superadmin)->patch('/system-configuration/toggle-cycle')->assertRedirect(route('waka.settings'));

        // Both can update journal time rules
        $this->actingAs($this->waka)->post('/waka/settings/journal-time', [
            'journal_fill_rule'            => 'during_and_after',
            'journal_early_buffer_minutes' => 15,
        ])->assertRedirect(route('waka.settings'));

        $this->actingAs($this->superadmin)->post('/waka/settings/journal-time', [
            'journal_fill_rule'            => 'anytime',
            'journal_early_buffer_minutes' => 0,
        ])->assertRedirect(route('waka.settings'));
    }

    public function test_superadmin_can_create_edit_update_and_delete_exams_without_403(): void
    {
        // 1. Create form
        $this->actingAs($this->superadmin)
            ->get(route('exams.create'))
            ->assertOk();

        // 2. Store new exam
        $response = $this->actingAs($this->superadmin)->post(route('exams.store'), [
            'category'    => 'materi',
            'title'       => 'Materi Jaringan Komputer',
            'subject'     => 'Produktif RPL',
            'class_ids'   => [$this->class->id],
            'exam_date'   => now()->toDateString(),
            'description' => 'Pendalaman TCP/IP',
        ]);
        $response->assertRedirect(route('exams.index'));

        $exam = Exam::where('title', 'Materi Jaringan Komputer')->first();
        $this->assertNotNull($exam);

        // 3. Edit form
        $this->actingAs($this->superadmin)
            ->get(route('exams.edit', $exam))
            ->assertOk();

        // 4. Update exam
        $updateResponse = $this->actingAs($this->superadmin)->put(route('exams.update', $exam), [
            'category'    => 'ujian',
            'title'       => 'Ujian Jaringan Komputer Final',
            'subject'     => 'Produktif RPL',
            'class_id'    => $this->class->id,
            'exam_date'   => now()->addDays(2)->toDateString(),
            'description' => 'Ujian akhir semester',
        ]);
        $updateResponse->assertRedirect(route('exams.index'));
        $this->assertSame('Ujian Jaringan Komputer Final', $exam->fresh()->title);

        // 5. Delete exam
        $deleteResponse = $this->actingAs($this->superadmin)->delete(route('exams.destroy', $exam));
        $deleteResponse->assertRedirect(route('exams.index'));
        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }

    public function test_superadmin_can_manage_all_users_without_403(): void
    {
        // 1. List users (sees all roles)
        $listRes = $this->actingAs($this->superadmin)->get(route('users.index'));
        $listRes->assertOk();
        $listRes->assertSee($this->teacher->fullname);
        $listRes->assertSee($this->student->fullname);

        // 2. Create teacher account
        $createRes = $this->actingAs($this->superadmin)->post(route('users.store'), [
            'fullname' => 'Guru Baru Superadmin',
            'username' => 'guru_baru_sa',
            'email'    => 'guru_baru_sa@example.test',
            'role_id'  => 2,
            'password' => 'password123',
        ]);
        $createRes->assertRedirect(route('users.index'));
        $newTeacher = User::where('username', 'guru_baru_sa')->first();
        $this->assertNotNull($newTeacher);
        $this->assertSame(2, (int) $newTeacher->role_id);

        // 3. Edit teacher account
        $editRes = $this->actingAs($this->superadmin)
            ->get(route('users.edit', $newTeacher));
        if ($editRes->status() !== 302 || $editRes->headers->get('Location') !== route('users.index')) {
            dump([
                'status' => $editRes->status(),
                'location' => $editRes->headers->get('Location'),
                'content' => substr($editRes->getContent(), 0, 500),
            ]);
        }
        $editRes->assertRedirect(route('users.index'));

        // 4. Update teacher account
        $updateRes = $this->actingAs($this->superadmin)->put(route('users.update', $newTeacher), [
            'fullname' => 'Guru Baru Updated',
            'username' => 'guru_baru_sa',
            'email'    => 'guru_baru_updated@example.test',
            'role_id'  => 2,
        ]);
        if ($updateRes->status() !== 302 || $updateRes->headers->get('Location') !== route('users.index')) {
            dump([
                'status' => $updateRes->status(),
                'location' => $updateRes->headers->get('Location'),
                'errors' => session('errors') ? session('errors')->all() : null,
                'session_error' => session('error'),
            ]);
        }
        $updateRes->assertRedirect(route('users.index'));
        $this->assertSame('Guru Baru Updated', $newTeacher->fresh()->fullname);

        // 5. Bulk reset teacher passwords
        $bulkRes = $this->actingAs($this->superadmin)->post(route('users.bulk-reset-passwords'));
        $bulkRes->assertRedirect(route('users.index'));
        $bulkRes->assertSessionHas('reset_credentials');
    }

    public function test_superadmin_can_view_swap_reports_and_logs(): void
    {
        // Web report
        $this->actingAs($this->superadmin)
            ->get(route('schedule-swaps.index'))
            ->assertOk();

        // API report
        $this->apiAs($this->superadmin)
            ->getJson('/api/v1/waka/swap-report')
            ->assertOk();

        // API logs
        $this->apiAs($this->superadmin)
            ->getJson('/api/v1/waka/swap-logs')
            ->assertOk();
    }
}
