<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\User;
use Database\Seeders\RealAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class RealAccountsSeederTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_real_accounts_seeder_creates_production_accounts_and_requires_password_change(): void
    {
        $classA = $this->makeClass('X RPL 1');
        putenv('SEED_DEFAULT_PASSWORD=***REMOVED***');

        $this->seed(RealAccountsSeeder::class);

        $waka = User::where('username', 'waka.kurikulum')->first();
        $this->assertNotNull($waka);
        $this->assertSame(1, (int) $waka->role_id);
        $this->assertNull($waka->class_id);
        $this->assertTrue((bool) $waka->must_change_password);

        $bk = User::where('username', 'guru.bk')->first();
        $this->assertNotNull($bk);
        $this->assertSame(3, (int) $bk->role_id);
        $this->assertNull($bk->class_id);
        $this->assertTrue((bool) $bk->must_change_password);

        $wali = User::where('username', 'wali.x.rpl.1')->first();
        $this->assertNotNull($wali);
        $this->assertSame(3, (int) $wali->role_id);
        $this->assertSame($classA->id, $wali->class_id);
        $this->assertTrue((bool) $wali->must_change_password);
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
