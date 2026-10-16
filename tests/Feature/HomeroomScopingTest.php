<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class HomeroomScopingTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function makeLeave(User $student): LeaveRequest
    {
        return LeaveRequest::create([
            'student_id'      => $student->id,
            'applicant_id'    => $student->id,
            'start_date'      => now()->addDay()->toDateString(),
            'end_date'        => now()->addDays(2)->toDateString(),
            'reason'          => 'Keperluan keluarga',
            'status_approval' => 'Pending',
        ]);
    }

    public function test_wali_kelas_cannot_see_leave_of_other_class(): void
    {
        $classA = $this->makeClass('Kelas A');
        $classB = $this->makeClass('Kelas B');
        $studentB = $this->makeUser(4, ['class_id' => $classB->id, 'fullname' => 'Siswa B']);
        $leaveB = $this->makeLeave($studentB);

        $waliA = $this->makeUser(3, ['class_id' => $classA->id, 'fullname' => 'Wali A']);

        $this->actingAs($waliA)->get('/leave-requests/' . $leaveB->id)->assertForbidden();
    }

    public function test_wali_kelas_can_see_leave_of_own_class(): void
    {
        $classA = $this->makeClass('Kelas A');
        $studentA = $this->makeUser(4, ['class_id' => $classA->id, 'fullname' => 'Siswa A']);
        $leaveA = $this->makeLeave($studentA);

        $waliA = $this->makeUser(3, ['class_id' => $classA->id]);

        $this->actingAs($waliA)->get('/leave-requests/' . $leaveA->id)->assertOk();
    }

    public function test_wali_kelas_cannot_approve_leave_of_other_class(): void
    {
        $classA = $this->makeClass('Kelas A');
        $classB = $this->makeClass('Kelas B');
        $studentB = $this->makeUser(4, ['class_id' => $classB->id]);
        $leaveB = $this->makeLeave($studentB);

        $waliA = $this->makeUser(3, ['class_id' => $classA->id]);

        $this->actingAs($waliA)
            ->patch('/leave-requests/' . $leaveB->id . '/status', ['status_approval' => 'Approved'])
            ->assertForbidden();

        $this->assertSame('Pending', $leaveB->fresh()->status_approval);
    }

    public function test_wali_kelas_can_approve_leave_of_own_class(): void
    {
        $classA = $this->makeClass('Kelas A');
        $studentA = $this->makeUser(4, ['class_id' => $classA->id]);
        $leaveA = $this->makeLeave($studentA);

        $waliA = $this->makeUser(3, ['class_id' => $classA->id]);

        $this->actingAs($waliA)
            ->patch('/leave-requests/' . $leaveA->id . '/status', ['status_approval' => 'Approved'])
            ->assertRedirect(route('leave-requests.show', $leaveA));

        $this->assertSame('Approved', $leaveA->fresh()->status_approval);
    }

    public function test_bk_without_class_can_see_and_approve_any_leave(): void
    {
        $classB = $this->makeClass('Kelas B');
        $studentB = $this->makeUser(4, ['class_id' => $classB->id]);
        $leaveB = $this->makeLeave($studentB);

        $bk = $this->makeUser(3, ['class_id' => null, 'fullname' => 'Guru BK']);

        $this->actingAs($bk)->get('/leave-requests/' . $leaveB->id)->assertOk();
        $this->actingAs($bk)
            ->patch('/leave-requests/' . $leaveB->id . '/status', ['status_approval' => 'Rejected'])
            ->assertRedirect(route('leave-requests.show', $leaveB));

        $this->assertSame('Rejected', $leaveB->fresh()->status_approval);
    }

    public function test_waka_still_sees_and_approves_any_leave(): void
    {
        $classB = $this->makeClass('Kelas B');
        $studentB = $this->makeUser(4, ['class_id' => $classB->id]);
        $leaveB = $this->makeLeave($studentB);

        $waka = $this->makeUser(1);

        $this->actingAs($waka)->get('/leave-requests/' . $leaveB->id)->assertOk();
        $this->actingAs($waka)
            ->patch('/leave-requests/' . $leaveB->id . '/status', ['status_approval' => 'Approved'])
            ->assertRedirect(route('leave-requests.show', $leaveB));
    }

    public function test_leave_list_is_filtered_by_homeroom_class(): void
    {
        $classA = $this->makeClass('Kelas A');
        $classB = $this->makeClass('Kelas B');
        $studentA = $this->makeUser(4, ['class_id' => $classA->id, 'fullname' => 'Anak Kelas A']);
        $studentB = $this->makeUser(4, ['class_id' => $classB->id, 'fullname' => 'Anak Kelas B']);
        $this->makeLeave($studentA);
        $this->makeLeave($studentB);

        $waliA = $this->makeUser(3, ['class_id' => $classA->id]);

        $response = $this->actingAs($waliA)->get('/leave-requests');
        $response->assertOk();
        $response->assertSee('Anak Kelas A');
        $response->assertDontSee('Anak Kelas B');
    }

    public function test_wali_kelas_cannot_open_other_class_recap_but_can_open_own(): void
    {
        $classA = $this->makeClass('Rombel Uji A');
        $classB = $this->makeClass('Rombel Uji B');
        $waliA = $this->makeUser(3, ['class_id' => $classA->id]);

        $this->actingAs($waliA)->get('/classes/' . $classB->id)->assertForbidden();
        $this->actingAs($waliA)->get('/classes/' . $classA->id)->assertOk();

        $index = $this->actingAs($waliA)->get('/classes');
        $index->assertOk();
        $index->assertSee('Rombel Uji A');
        $index->assertDontSee('Rombel Uji B');
    }

    public function test_wali_kelas_cannot_export_other_class_recap(): void
    {
        $classA = $this->makeClass('Kelas A');
        $classB = $this->makeClass('Kelas B');
        $waliA = $this->makeUser(3, ['class_id' => $classA->id]);

        $params = ['start_date' => now()->startOfMonth()->toDateString(), 'end_date' => now()->toDateString()];

        $this->actingAs($waliA)->get('/classes/' . $classB->id . '/export?' . http_build_query($params))->assertForbidden();
        $this->actingAs($waliA)->get('/classes/' . $classA->id . '/export?' . http_build_query($params))->assertOk();
    }

    public function test_user_store_allows_class_for_role_3(): void
    {
        $classA = $this->makeClass('Kelas A');
        $waka = $this->makeUser(1);

        $this->actingAs($waka)->post('/users', [
            'fullname' => 'Wali Baru',
            'username' => 'wali_baru',
            'role_id'  => 3,
            'class_id' => $classA->id,
            'password' => 'password',
        ])->assertRedirect(route('users.index'));

        $this->assertSame($classA->id, User::where('username', 'wali_baru')->first()->class_id);
    }

    public function test_user_update_allows_class_for_role_3(): void
    {
        $classA = $this->makeClass('Kelas A');
        $classB = $this->makeClass('Kelas B');
        $waka = $this->makeUser(1);
        $wali = $this->makeUser(3, ['class_id' => $classA->id, 'username' => 'wali_x']);

        $this->actingAs($waka)->put('/users/' . $wali->id, [
            'fullname' => $wali->fullname,
            'username' => 'wali_x',
            'role_id'  => 3,
            'class_id' => $classB->id,
        ])->assertRedirect(route('users.index'));

        $this->assertSame($classB->id, $wali->fresh()->class_id);
    }
}
