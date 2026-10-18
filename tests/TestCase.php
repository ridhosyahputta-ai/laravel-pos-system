<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        if (($_ENV['APP_ENV'] ?? getenv('APP_ENV')) !== 'testing'
            || ($_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION')) !== 'sqlite'
            || ($_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE')) !== ':memory:'
            || ! empty($_ENV['DB_URL'] ?? getenv('DB_URL'))) {
            throw new \RuntimeException('Test bootstrap requires SQLite :memory: and APP_ENV=testing.');
        }

        $cachePath = $_ENV['APP_CONFIG_CACHE'] ?? getenv('APP_CONFIG_CACHE');
        if (is_file(dirname(__DIR__).'/bootstrap/cache/config.php') || ($cachePath && is_file($cachePath))) {
            throw new \RuntimeException('Cached application configuration must not be loaded by database tests.');
        }

        $app = parent::createApplication();
        // Runs before PHPUnit/Pest sets up RefreshDatabase or any other test traits.
        $this->assertTestDatabaseSafety($app);

        return $app;
    }

    protected function assertTestDatabaseSafety($app): void
    {
        if (! $app->environment('testing') || $app->configurationIsCached()) {
            throw new \RuntimeException('Database tests require an uncached testing environment.');
        }

        $connection = $app['db']->connection();

        // This suite is intentionally restricted to an ephemeral database, never db_rpld.
        if ($connection->getDriverName() !== 'sqlite'
            || $connection->getDatabaseName() !== ':memory:'
            || $connection->getConfig('url')) {
            throw new \RuntimeException('Database tests are allowed only on SQLite :memory:.');
        }
    }
}
