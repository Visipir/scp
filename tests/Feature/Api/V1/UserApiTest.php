<?php

namespace Tests\Feature\Api\V1;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_user_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/users')->assertStatus(401);
        $this->postJson('/api/v1/users', [])->assertStatus(401);
        $this->getJson('/api/v1/users/1')->assertStatus(401);
        $this->putJson('/api/v1/users/1', [])->assertStatus(401);
        $this->deleteJson('/api/v1/users/1')->assertStatus(401);
        $this->patchJson('/api/v1/users/1/reset-password')->assertStatus(401);
    }

    public function test_guru_and_siswa_cannot_access_user_endpoints(): void
    {
        $guru = $this->makeUser(2);
        $siswa = $this->makeUser(4);

        $this->apiAs($guru)->getJson('/api/v1/users')->assertStatus(403);
        $this->apiAs($siswa)->getJson('/api/v1/users')->assertStatus(403);
    }

    public function test_waka_can_list_users_with_filters(): void
    {
        $waka = $this->makeUser(1);
        $guru = $this->makeUser(2, ['fullname' => 'Budi Santoso', 'username' => 'budi123']);
        $siswa = $this->makeUser(4, ['fullname' => 'Siti Rahma', 'username' => 'siti456']);

        $resAll = $this->apiAs($waka)->getJson('/api/v1/users');
        $resAll->assertOk()
            ->assertJsonStructure(['data' => [['id', 'username', 'fullname', 'role_id']]]);

        // Filter by role
        $resGuru = $this->apiAs($waka)->getJson('/api/v1/users?role_id=2');
        $resGuru->assertOk();
        $this->assertTrue(collect($resGuru->json('data'))->every(fn ($u) => $u['role_id'] === 2));

        // Filter by search
        $resSearch = $this->apiAs($waka)->getJson('/api/v1/users?search=budi');
        $resSearch->assertOk();
        $this->assertTrue(collect($resSearch->json('data'))->contains('username', 'budi123'));
    }

    public function test_waka_can_create_user_with_must_change_password_and_no_password_exposed(): void
    {
        $waka = $this->makeUser(1);
        $cls = $this->makeClass('X RPL 1');

        $payload = [
            'fullname'          => 'Ahmad Dani',
            'username'          => 'ahmaddani',
            'role_id'           => 4,
            'class_id'          => $cls->id,
            'gender'            => 'L',
            'attendance_number' => 1,
            'password'          => 'secret123',
        ];

        $res = $this->apiAs($waka)->postJson('/api/v1/users', $payload);
        $res->assertStatus(201);
        $res->assertJsonPath('data.username', 'ahmaddani');
        $res->assertJsonPath('data.must_change_password', true);
        $res->assertJsonMissing(['password']);

        $created = User::where('username', 'ahmaddani')->first();
        $this->assertNotNull($created);
        $this->assertTrue(Hash::check('secret123', $created->password));
        $this->assertTrue((bool) $created->must_change_password);
    }

    public function test_validation_fails_on_missing_required_fields_and_duplicate_username(): void
    {
        $waka = $this->makeUser(1);
        $this->makeUser(2, ['username' => 'existing_user']);

        $res = $this->apiAs($waka)->postJson('/api/v1/users', [
            'username' => 'existing_user',
            'role_id'  => 4,
            // missing fullname, class_id (for student), password
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['fullname', 'username', 'class_id', 'password']);
    }

    public function test_waka_can_show_and_update_user(): void
    {
        $waka = $this->makeUser(1);
        $target = $this->makeUser(2, ['fullname' => 'Old Name', 'username' => 'olduser']);

        $showRes = $this->apiAs($waka)->getJson("/api/v1/users/{$target->id}");
        $showRes->assertOk()->assertJsonPath('data.fullname', 'Old Name');

        $updateRes = $this->apiAs($waka)->putJson("/api/v1/users/{$target->id}", [
            'fullname' => 'New Name',
            'username' => 'olduser',
            'role_id'  => 2,
        ]);
        $updateRes->assertOk()->assertJsonPath('data.fullname', 'New Name');

        $this->assertEquals('New Name', $target->fresh()->fullname);
    }

    public function test_cannot_delete_oneself(): void
    {
        $waka = $this->makeUser(1);

        $res = $this->apiAs($waka)->deleteJson("/api/v1/users/{$waka->id}");
        $res->assertStatus(422)
            ->assertJsonPath('message', 'Anda tidak dapat menghapus akun Anda sendiri.');
    }

    public function test_cannot_delete_user_with_attendance_dependencies(): void
    {
        $waka = $this->makeUser(1);
        $siswa = $this->makeUser(4);
        $schedule = $this->makeSchedule();

        $journal = Journal::create([
            'schedule_id'       => $schedule->id,
            'date'              => now()->toDateString(),
            'topic_description' => 'Materi Array',
        ]);

        Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $siswa->id,
            'status'     => 'Hadir',
            'date'       => now()->toDateString(),
        ]);

        $res = $this->apiAs($waka)->deleteJson("/api/v1/users/{$siswa->id}");
        $res->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $siswa->id]);
    }

    public function test_waka_can_delete_unconstrained_user(): void
    {
        $waka = $this->makeUser(1);
        $target = $this->makeUser(4);

        $res = $this->apiAs($waka)->deleteJson("/api/v1/users/{$target->id}");
        $res->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_reset_password_sets_password_to_username(): void
    {
        $waka = $this->makeUser(1);
        $target = $this->makeUser(4, [
            'username'             => 'student99',
            'password'             => Hash::make('custompass'),
            'must_change_password' => false,
        ]);

        $res = $this->apiAs($waka)->patchJson("/api/v1/users/{$target->id}/reset-password");
        $res->assertOk();
        $res->assertJsonPath('data.must_change_password', true);

        $target->refresh();
        $this->assertTrue(Hash::check('student99', $target->password));
        $this->assertTrue((bool) $target->must_change_password);
    }

    // MARK: - Role 3 Scoping & Restriction Tests

    public function test_role_3_only_sees_students(): void
    {
        $bk = $this->makeUser(3);
        $guru = $this->makeUser(2);
        $siswa = $this->makeUser(4);

        $res = $this->apiAs($bk)->getJson('/api/v1/users');
        $res->assertOk();
        $userIds = collect($res->json('data'))->pluck('id');

        $this->assertTrue($userIds->contains($siswa->id));
        $this->assertFalse($userIds->contains($guru->id));
        $this->assertFalse($userIds->contains($bk->id));
    }

    public function test_role_3_with_scoped_class_only_sees_homeroom_students(): void
    {
        $classA = $this->makeClass('X RPL 1');
        $classB = $this->makeClass('X RPL 2');

        $waliA = $this->makeUser(3, ['class_id' => $classA->id]);
        $siswaA = $this->makeUser(4, ['class_id' => $classA->id]);
        $siswaB = $this->makeUser(4, ['class_id' => $classB->id]);

        $res = $this->apiAs($waliA)->getJson('/api/v1/users');
        $res->assertOk();
        $userIds = collect($res->json('data'))->pluck('id');

        $this->assertTrue($userIds->contains($siswaA->id));
        $this->assertFalse($userIds->contains($siswaB->id));
    }

    public function test_role_3_cannot_create_or_modify_non_student_users(): void
    {
        $bk = $this->makeUser(3);
        $guru = $this->makeUser(2);

        // Attempt create teacher (role 2) -> 403
        $createRes = $this->apiAs($bk)->postJson('/api/v1/users', [
            'fullname' => 'Pak Guru Baru',
            'username' => 'gurubaru',
            'role_id'  => 2,
            'password' => '***REMOVED***',
        ]);
        $createRes->assertStatus(403);

        // Attempt update teacher -> 403
        $updateRes = $this->apiAs($bk)->putJson("/api/v1/users/{$guru->id}", [
            'fullname' => 'Hacked Name',
            'username' => $guru->username,
            'role_id'  => 2,
        ]);
        $updateRes->assertStatus(403);

        // Attempt delete teacher -> 403
        $delRes = $this->apiAs($bk)->deleteJson("/api/v1/users/{$guru->id}");
        $delRes->assertStatus(403);

        // Attempt reset password of teacher -> 403
        $resetRes = $this->apiAs($bk)->patchJson("/api/v1/users/{$guru->id}/reset-password");
        $resetRes->assertStatus(403);
    }

    public function test_authorization_runs_before_validation_for_role_3_against_roles_1_to_3(): void
    {
        $bk = $this->makeUser(3);
        $waka = $this->makeUser(1);
        $guru = $this->makeUser(2);
        $otherBk = $this->makeUser(3);

        $validPayload = [
            'fullname' => 'Updated Name',
            'username' => 'updateduser',
            'role_id'  => 4,
            'class_id' => $this->makeClass('X PPLG 1')->id,
        ];

        foreach ([$waka, $guru, $otherBk] as $target) {
            // PUT /users/{user} - empty body must be 403 (not 422 validation failure)
            $this->apiAs($bk)->putJson("/api/v1/users/{$target->id}", [])
                ->assertStatus(403);

            // PUT /users/{user} - valid body must be 403
            $this->apiAs($bk)->putJson("/api/v1/users/{$target->id}", $validPayload)
                ->assertStatus(403);

            // PATCH /users/{user}/reset-password - empty body must be 403
            $this->apiAs($bk)->patchJson("/api/v1/users/{$target->id}/reset-password", [])
                ->assertStatus(403);

            // PATCH /users/{user}/reset-password - non-empty body must be 403
            $this->apiAs($bk)->patchJson("/api/v1/users/{$target->id}/reset-password", ['dummy' => 'value'])
                ->assertStatus(403);

            // DELETE /users/{user} - empty body must be 403
            $this->apiAs($bk)->deleteJson("/api/v1/users/{$target->id}", [])
                ->assertStatus(403);

            // DELETE /users/{user} - non-empty body must be 403
            $this->apiAs($bk)->deleteJson("/api/v1/users/{$target->id}", ['reason' => 'test'])
                ->assertStatus(403);
        }
    }
}
