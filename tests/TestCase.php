<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Hard safety guard: Never allow tests or RefreshDatabase to touch the physical database.sqlite file
        if (config('database.default') === 'sqlite' && config('database.connections.sqlite.database') !== ':memory:') {
            config(['database.connections.sqlite.database' => ':memory:']);
            \Illuminate\Support\Facades\DB::purge('sqlite');
        }
    }
}
