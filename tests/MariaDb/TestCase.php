<?php

namespace Tests\MariaDb;

use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends LaravelTestCase
{
    public function createApplication()
    {
        return Environment::boot();
    }

    protected function setUp(): void
    {
        parent::setUp();
        Environment::assertIsolated($this->app);
        // This explicit suite owns db_runway_testing. No RefreshDatabase,
        // migrate:fresh, or enclosing transaction: child processes must see commits.
        $tables = DB::select('SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = \'BASE TABLE\'');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($tables as $table) {
                if ($table->name !== 'migrations') {
                    DB::table($table->name)->delete();
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
