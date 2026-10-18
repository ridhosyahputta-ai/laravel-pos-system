<?php

namespace Tests\MariaDb;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use RuntimeException;

final class Environment
{
    public static function prepare(): void
    {
        $root = dirname(__DIR__, 2);
        if (is_file($root.'/bootstrap/cache/config.php')
            || (($cache = getenv('APP_CONFIG_CACHE')) && is_file($cache))
            || ! empty(getenv('DB_URL'))
            || (($database = getenv('DB_DATABASE')) && $database !== 'db_runway_testing')) {
            throw new RuntimeException('Unsafe inherited configuration; MariaDB testing aborted.');
        }

        $env = \Dotenv\Dotenv::createArrayBacked($root, '.env.mariadb-testing')->load();
        foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mariadb', 'DB_HOST' => '127.0.0.1', 'DB_DATABASE' => 'db_runway_testing', 'DB_USERNAME' => 'runway_testing', 'RUNWAY_MARIADB_TESTS' => '1'] as $key => $expected) {
            if (($env[$key] ?? null) !== $expected) {
                throw new RuntimeException('Private testing configuration does not match the allowlist.');
            }
        }
        if (empty($env['DB_PASSWORD']) || ! empty($env['DB_URL']) || ! empty($env['DB_SOCKET']) || ! ctype_digit($env['DB_PORT'] ?? '')) {
            throw new RuntimeException('Unsafe testing credentials or connection overrides.');
        }
        $env = array_replace($env, [
            'APP_NAME' => 'Runway', 'APP_DEBUG' => 'false', 'DB_URL' => '', 'DB_SOCKET' => '',
            'DB_CHARSET' => 'utf8mb4', 'DB_COLLATION' => 'utf8mb4_unicode_ci',
            'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array', 'LOG_CHANNEL' => 'null', 'BCRYPT_ROUNDS' => '4',
        ]);
        foreach ($env as $key => $value) {
            putenv($key.'='.$value);
            $_ENV[$key] = $_SERVER[$key] = $value;
        }
    }

    public static function boot(): Application
    {
        self::prepare();
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        // Never load the main .env, even during console boot or child processes.
        $app->loadEnvironmentFrom('.env.mariadb-testing');
        $app->make(Kernel::class)->bootstrap();
        $connection = $app['config']->get('database.connections.mariadb');
        if (! $app->environment('testing') || $app->configurationIsCached()
            || $app['config']->get('database.default') !== 'mariadb'
            || ($connection['database'] ?? '') !== 'db_runway_testing'
            || ($connection['username'] ?? '') !== 'runway_testing'
            || ($connection['host'] ?? '') !== '127.0.0.1'
            || ! empty($connection['url']) || ! empty($connection['unix_socket'])
            || isset($connection['read']) || isset($connection['write'])) {
            throw new RuntimeException('Resolved Laravel database configuration is unsafe.');
        }
        // No secondary connection may fall back to the production environment.
        $app['config']->set('database.connections', ['mariadb' => $connection]);
        self::assertIsolated($app);

        return $app;
    }

    public static function assertIsolated(Application $app): void
    {
        $connection = $app['db']->connection();
        $identity = $connection->selectOne('SELECT DATABASE() AS db, CURRENT_USER() AS account, VERSION() AS version');
        if (! $app->environment('testing') || $connection->getDatabaseName() !== 'db_runway_testing'
            || $identity->db !== 'db_runway_testing' || $identity->account !== 'runway_testing@127.0.0.1'
            || ! str_contains($identity->version, 'MariaDB')) {
            throw new RuntimeException('Active database/account is not the isolated MariaDB target.');
        }
        try {
            $connection->getPdo()->query('SELECT id FROM db_rpld.users LIMIT 0');
        } catch (\PDOException $exception) {
            if (in_array($exception->errorInfo[1] ?? 0, [1044, 1142], true)) {
                return;
            }
            throw new RuntimeException('Cannot prove production access is denied.');
        }
        throw new RuntimeException('Testing account unexpectedly has production access.');
    }
}
