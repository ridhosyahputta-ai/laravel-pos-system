<?php

namespace Tests\MariaDb;

use PDO;
use RuntimeException;

/** Read-only fingerprints; never returns row values, credentials or password hashes. */
final class ProductionAudit
{
    public static function snapshot(string $root): array
    {
        $env = \Dotenv\Dotenv::createArrayBacked($root)->load();
        if (($env['DB_DATABASE'] ?? '') !== 'db_rpld' || ! empty($env['DB_URL']) || ! empty($env['DB_SOCKET'])) {
            throw new RuntimeException('Unexpected production configuration; audit aborted.');
        }
        $pdo = new PDO('mysql:host='.$env['DB_HOST'].';port='.($env['DB_PORT'] ?? '3306').';dbname=db_rpld;charset=utf8mb4', $env['DB_USERNAME'], $env['DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('SET SESSION TRANSACTION READ ONLY');
        $pdo->beginTransaction();
        try {
            $tables = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'db_rpld' AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
            $result = [];
            foreach ($tables as $table) {
                $identifier = '`'.str_replace('`', '``', $table).'`';
                $ddl = $pdo->query('SHOW CREATE TABLE '.$identifier)->fetch(PDO::FETCH_NUM)[1];
                $rows = $pdo->query('SELECT * FROM '.$identifier)->fetchAll(PDO::FETCH_ASSOC);
                $hashes = array_map(fn ($row) => hash('sha256', serialize($row)), $rows);
                sort($hashes, SORT_STRING);
                $result[$table] = ['rows' => count($rows), 'data' => hash('sha256', implode('', $hashes)), 'schema' => hash('sha256', $ddl)];
            }

            return ['database' => 'db_rpld', 'tables' => $result, 'env_sha256' => hash_file('sha256', $root.'/.env')];
        } finally {
            $pdo->rollBack();
        }
    }
}
