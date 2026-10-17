<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $config = $app['config'];
        $driver = $config->get('database.default');
        $connection = $config->get('database.connections.'.$driver, []);
        $safe = $app->environment('testing') && empty($connection['url']) && (($driver === 'sqlite' && ($connection['database'] ?? '') === ':memory:') || ($driver === 'mysql' && str_contains((string) ($connection['database'] ?? ''), '_test_')));
        if (! $safe) {
            throw new \RuntimeException('Tests require in-memory SQLite or an isolated MySQL database named _test_; refusing to reset any other database.');
        }

        return $app;
    }
}
