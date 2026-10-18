<?php

namespace Tests\MariaDb;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EnvironmentSafetyTest extends TestCase
{
    public static function unsafeOverrides(): array
    {
        return [
            ['DB_DATABASE', 'db_rpld'],
            ['DB_URL', 'mysql://invalid.example/forbidden'],
            ['APP_CONFIG_CACHE', __DIR__.'/Environment.php'],
        ];
    }

    #[DataProvider('unsafeOverrides')]
    public function testUnsafeConfigurationStopsBeforeApplicationBoot(string $key, string $value): void
    {
        $original = getenv($key);
        try {
            putenv($key.'='.$value);
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Unsafe inherited configuration');
            Environment::prepare();
        } finally {
            putenv($original === false ? $key : $key.'='.$original);
        }
    }
}
