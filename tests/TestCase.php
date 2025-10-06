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
        if (extension_loaded('pdo_sqlite')) {
            $this->app['config']->set('database.default', 'sqlite');
            $this->app['config']->set('database.connections.sqlite', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]);

            DB::setDefaultConnection('sqlite');
            return;
        }

        $defaultConnection = $this->app['config']->get('database.default');
        DB::setDefaultConnection($defaultConnection);
    }
}
