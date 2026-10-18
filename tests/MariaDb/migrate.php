<?php

require __DIR__.'/bootstrap.php';

$app = \Tests\MariaDb\Environment::boot();
\Tests\MariaDb\Environment::assertIsolated($app);
echo "Verified active database: db_runway_testing; isolated account; production access denied.\n";
$exit = $app->make(\Illuminate\Contracts\Console\Kernel::class)->call('migrate', ['--database' => 'mariadb', '--force' => true]);
echo $app->make(\Illuminate\Contracts\Console\Kernel::class)->output();
exit($exit);
