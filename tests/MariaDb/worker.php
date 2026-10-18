<?php

use App\Actions\CreateSale;
use App\Models\Produk;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\MariaDb\Environment;

require __DIR__.'/bootstrap.php';

$input = json_decode($argv[1], true, flags: JSON_THROW_ON_ERROR);
$directory = $input['directory'];
$label = $input['label'];
$signal = static fn (string $name) => file_put_contents($directory.'/'.$name, 'ready');
$wait = static function (string $name) use ($directory): void {
    $deadline = microtime(true) + 25;
    while (! is_file($directory.'/'.$name)) {
        if (microtime(true) >= $deadline) {
            throw new RuntimeException('Worker synchronization timed out.');
        }
        usleep(5000);
    }
};

try {
    $app = Environment::boot();
    Environment::assertIsolated($app);
    $connectionId = (int) DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
    $signal('ready-'.$label);
    $wait('start-'.$label);
    $attempts = 0;
    $held = false;
    $lockTarget = $input['lock_target'] ?? null;
    DB::connection()->beforeExecuting(function ($sql, $bindings) use (&$attempts, $signal, $label, $lockTarget): void {
        if (str_starts_with($sql, 'insert into `transaksis`')) {
            $attempts++;
            $signal('attempting-claim-'.$label);
        }
        if ($lockTarget !== null && str_ends_with($sql, 'for update') && in_array($lockTarget, $bindings)) {
            $signal('attempting-lock-'.$label);
        }
    });

    if (($input['mode'] ?? 'sale') === 'blocker') {
        DB::transaction(function () use ($input, $signal, $wait): void {
            // Increase this transaction's InnoDB weight so the smaller sale is
            // selected as the real deadlock victim, exercising CreateSale retry.
            // Point updates avoid a range/table scan locking the sale's rows
            // before the intended two-row deadlock cycle has been established.
            foreach ($input['weight_ids'] as $id) {
                DB::table('produks')->where('id', $id)->increment('stok');
            }
            Produk::lockForUpdate()->findOrFail($input['first_id']);
            $signal('held-blocker');
            $wait('release-blocker');
            Produk::lockForUpdate()->findOrFail($input['second_id']);
        });
        echo json_encode(['status' => 'ok', 'connection_id' => $connectionId, 'database' => 'db_runway_testing']), PHP_EOL;
        exit(0);
    }

    DB::listen(function ($query) use ($input, $signal, $wait, $label, &$held): void {
        $holdClaim = ($input['hold'] ?? '') === 'claim' && str_starts_with($query->sql, 'insert into `transaksis`');
        $holdStock = ($input['hold'] ?? '') === 'stock' && str_ends_with($query->sql, 'for update');
        if (! $held && ($holdClaim || $holdStock)) {
            $held = true;
            $signal('held-'.$label);
            $wait('release-'.$label);
        }
    });
    \Illuminate\Support\Carbon::setTestNow('2026-10-18 12:00:00 UTC');
    $sale = $app->make(CreateSale::class)->handle($input['products'], $input['quantities'], $input['token'], $input['user_id']);
    echo json_encode([
        'status' => 'ok', 'id' => $sale->id, 'replayed' => ! $sale->wasRecentlyCreated,
        'attempts' => $attempts, 'connection_id' => $connectionId, 'database' => 'db_runway_testing',
    ]), PHP_EOL;
} catch (ValidationException $exception) {
    echo json_encode(['status' => 'validation', 'fields' => array_keys($exception->errors()), 'connection_id' => $connectionId ?? null]), PHP_EOL;
} catch (Throwable $exception) {
    // Never print connection arguments or private configuration from child errors.
    echo json_encode(['status' => 'error', 'class' => $exception::class, 'code' => $exception->getCode()]), PHP_EOL;
    exit(1);
}
