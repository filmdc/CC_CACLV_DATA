<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $this->configureDatabaseConnection();

        $this->app['config']->set('cache.default', 'array');
        Cache::setDefaultDriver('array');
    }

    private function configureDatabaseConnection(): void
    {
        // Use MySQL for testing to match production environment
        // This ensures tests accurately reflect production behavior with:
        // - MySQL-specific features (JSON queries, ENUM types)
        // - Foreign key constraints behavior
        // - Complex sync logic queries
        $defaultConnection = $this->app['config']->get('database.default');
        DB::setDefaultConnection($defaultConnection);
    }
}
