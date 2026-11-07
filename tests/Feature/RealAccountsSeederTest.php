<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RealAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class RealAccountsSeederTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    private ?string $originalCsvContent = null;
    private string $csvPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        $seedDir = storage_path('app/seed');
        if (!File::exists($seedDir)) {
            File::makeDirectory($seedDir, 0755, true);
        }
        $this->csvPath = $seedDir . '/accounts.csv';
        if (File::exists($this->csvPath)) {
            $this->originalCsvContent = File::get($this->csvPath);
        }
    }

    protected function tearDown(): void
    {
        if ($this->originalCsvContent !== null) {
            File::put($this->csvPath, $this->originalCsvContent);
        }
        parent::tearDown();
    }

    public function test_real_accounts_seeder_creates_only_waka_and_kesiswaan_and_ignores_wali_placeholders(): void
    {
        $classA = $this->makeClass('X RPL 1');
        putenv('SEED_DEFAULT_PASSWORD=' . \Illuminate\Support\Str::random(16));

        File::put(
            $this->csvPath,
            "username,name,role,class_id,email\n" .
            "waka.kurikulum,Waka Kurikulum,1,,waka@sekolah.sch.id\n" .
            "tim.kesiswaan,Tim Kesiswaan,3,,kesiswaan@sekolah.sch.id\n" .
            "wali.x.rpl.1,Wali X RPL 1,3,{$classA->id},wali@sekolah.sch.id\n"
        );

        $this->seed(RealAccountsSeeder::class);

        $waka = User::where('username', 'waka.kurikulum')->first();
        $this->assertNotNull($waka);
        $this->assertSame(1, (int) $waka->role_id);
        $this->assertNull($waka->class_id);
        $this->assertTrue((bool) $waka->must_change_password);

        $kesiswaan = User::where('username', 'tim.kesiswaan')->first();
        $this->assertNotNull($kesiswaan);
        $this->assertSame(3, (int) $kesiswaan->role_id);
        $this->assertNull($kesiswaan->class_id);
        $this->assertTrue((bool) $kesiswaan->must_change_password);

        // wali.* placeholder row MUST be ignored
        $this->assertNull(User::where('username', 'wali.x.rpl.1')->first());

        // DatabaseSeeder MUST NOT reference RealAccountsSeeder
        $dbSeederSource = File::get(database_path('seeders/DatabaseSeeder.php'));
        $this->assertStringNotContainsString('RealAccountsSeeder', $dbSeederSource);
    }

    public function test_real_accounts_seeder_throws_when_password_env_missing(): void
    {
        putenv('SEED_DEFAULT_PASSWORD=');
        unset($_ENV['SEED_DEFAULT_PASSWORD'], $_SERVER['SEED_DEFAULT_PASSWORD']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SEED_DEFAULT_PASSWORD');

        $this->seed(RealAccountsSeeder::class);
    }
}
