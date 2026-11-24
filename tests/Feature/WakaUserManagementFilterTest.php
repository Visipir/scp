<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class WakaUserManagementFilterTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    private User $waka;
    private User $guru;
    private User $siswaA;
    private User $siswaB;
    private SchoolClass $classA;
    private SchoolClass $classB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->makeConfig();

        $this->classA = $this->makeClass('XI RPL 1');
        $this->classB = $this->makeClass('XI RPL 2');

        $this->waka = $this->makeUser(1, ['fullname' => 'Waka Kurikulum Test', 'username' => 'waka_mgr']);
        $this->guru = $this->makeUser(2, ['fullname' => 'Budi Pengajar', 'username' => 'guru_budi', 'email' => 'budi@school.test']);
        $this->siswaA = $this->makeUser(4, ['fullname' => 'Andi Siswa', 'username' => 'siswa_andi', 'class_id' => $this->classA->id]);
        $this->siswaB = $this->makeUser(4, ['fullname' => 'Citra Siswa', 'username' => 'siswa_citra', 'class_id' => $this->classB->id]);
    }

    public function test_waka_sees_manajemen_pengguna_navigation_and_dashboard_card(): void
    {
        $response = $this->actingAs($this->waka)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Manajemen Pengguna')
            ->assertSee(route('users.index'), false);
    }

    public function test_waka_can_filter_guru_and_siswa_and_search_by_email(): void
    {
        // All users + quick filter links present
        $this->actingAs($this->waka)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Budi Pengajar')
            ->assertSee('Andi Siswa')
            ->assertSee('Citra Siswa')
            ->assertSee(route('users.index', ['role_id' => 2]), false)
            ->assertSee(route('users.index', ['role_id' => 4]), false);

        // Filter Guru (role_id = 2)
        $this->actingAs($this->waka)
            ->get(route('users.index', ['role_id' => 2]))
            ->assertOk()
            ->assertSee('Budi Pengajar')
            ->assertDontSee('Andi Siswa')
            ->assertDontSee('Citra Siswa');

        // Filter Siswa (role_id = 4) + class_id
        $this->actingAs($this->waka)
            ->get(route('users.index', ['role_id' => 4]))
            ->assertOk()
            ->assertSee('Andi Siswa')
            ->assertSee('Citra Siswa')
            ->assertDontSee('Budi Pengajar');

        $this->actingAs($this->waka)
            ->get(route('users.index', ['role_id' => 4, 'class_id' => $this->classA->id]))
            ->assertOk()
            ->assertSee('Andi Siswa')
            ->assertDontSee('Citra Siswa')
            ->assertDontSee('Budi Pengajar');

        // Search by email
        $this->actingAs($this->waka)
            ->get(route('users.index', ['search' => 'budi@school.test']))
            ->assertOk()
            ->assertSee('Budi Pengajar')
            ->assertDontSee('Andi Siswa');
    }
}
