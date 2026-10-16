<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\User;
use App\Services\AttendanceSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class StudentAttendanceTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function makeAttendance(User $student, string $date, string $status, ?string $notes = null): Attendance
    {
        $schedule = $this->makeSchedule(['class_id' => $student->class_id ?: $this->makeClass()->id]);

        $journal = Journal::create([
            'schedule_id'       => $schedule->id,
            'date'              => $date,
            'topic_description' => 'Materi uji',
        ]);

        return Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $student->id,
            'status'     => $status,
            'date'       => $date,
            'notes'      => $notes,
        ]);
    }

    private function makeChild(User $parent, string $name): User
    {
        $child = $this->makeUser(4, [
            'fullname' => $name,
            'class_id' => $this->makeClass($name . ' Kelas')->id,
        ]);
        $child->update(['parent_id' => $parent->id]);

        return $child;
    }

    public function test_student_sees_only_own_records(): void
    {
        $student = $this->makeUser(4, ['class_id' => $this->makeClass('XII RPL 1')->id]);
        $other = $this->makeUser(4, ['class_id' => $student->class_id]);
        $date = now()->toDateString();

        $this->makeAttendance($student, $date, 'Sakit', 'NOTA-SAYA');
        $this->makeAttendance($other, $date, 'Alpa', 'NOTA-ORANG');

        $response = $this->actingAs($student)->get('/attendance');
        $response->assertOk();
        $response->assertSee('NOTA-SAYA');
        $response->assertDontSee('NOTA-ORANG');
    }

    public function test_parent_sees_only_their_children(): void
    {
        $parent = $this->makeUser(4);
        $childA = $this->makeChild($parent, 'Aaa Anak');
        $childB = $this->makeChild($parent, 'Zzz Anak');
        $date = now()->toDateString();

        $this->makeAttendance($childA, $date, 'Sakit', 'NOTA-ANAK-A');
        $this->makeAttendance($childB, $date, 'Alpa', 'NOTA-ANAK-B');

        // Default selection = first child (sorted by fullname)
        $response = $this->actingAs($parent)->get('/attendance');
        $response->assertOk();
        $response->assertSee('NOTA-ANAK-A');
        $response->assertDontSee('NOTA-ANAK-B');

        $second = $this->actingAs($parent)->get('/attendance?student_id=' . $childB->id);
        $second->assertOk();
        $second->assertSee('NOTA-ANAK-B');
        $second->assertDontSee('NOTA-ANAK-A');
    }

    public function test_parent_requesting_another_student_is_forbidden(): void
    {
        $parent = $this->makeUser(4);
        $this->makeChild($parent, 'Anak Sendiri');
        $stranger = $this->makeUser(4, ['class_id' => $this->makeClass('XII RPL 9')->id]);

        $this->actingAs($parent)->get('/attendance?student_id=' . $stranger->id)->assertForbidden();
    }

    public function test_summary_counts_are_correct(): void
    {
        $student = $this->makeUser(4, ['class_id' => $this->makeClass('XII RPL 1')->id]);
        $month = now()->format('Y-m');
        $day = now()->startOfMonth();

        foreach (['Hadir', 'Hadir', 'Sakit', 'Izin', 'Alpa', 'Terlambat'] as $i => $status) {
            $this->makeAttendance($student, $day->copy()->addDays($i)->toDateString(), $status);
        }

        $summary = (new AttendanceSummaryService)->forStudent($student->id, $month)['summary'];

        $this->assertSame([
            'Hadir'     => 2,
            'Sakit'     => 1,
            'Izin'      => 1,
            'Alpa'      => 1,
            'Terlambat' => 1,
        ], $summary);
    }

    public function test_month_filter_limits_records(): void
    {
        $student = $this->makeUser(4, ['class_id' => $this->makeClass('XII RPL 1')->id]);

        $this->makeAttendance($student, now()->toDateString(), 'Hadir', 'NOTA-BULAN-INI');
        $previous = now()->subMonthNoOverflow();
        $this->makeAttendance($student, $previous->startOfMonth()->toDateString(), 'Hadir', 'NOTA-BULAN-LALU');

        $current = $this->actingAs($student)->get('/attendance');
        $current->assertOk();
        $current->assertSee('NOTA-BULAN-INI');
        $current->assertDontSee('NOTA-BULAN-LALU');

        $past = $this->actingAs($student)->get('/attendance?month=' . $previous->format('Y-m'));
        $past->assertOk();
        $past->assertSee('NOTA-BULAN-LALU');
        $past->assertDontSee('NOTA-BULAN-INI');
    }

    public function test_non_role_four_cannot_access(): void
    {
        $this->actingAs($this->makeUser(2))->get('/attendance')->assertForbidden();
        $this->actingAs($this->makeUser(1))->get('/attendance')->assertForbidden();
        $this->actingAs($this->makeUser(3))->get('/attendance')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/attendance')->assertRedirect('/login');
    }
}
