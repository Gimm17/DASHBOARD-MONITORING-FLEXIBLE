<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$source = $argv[1] ?? '';
$target = $argv[2] ?? '';
foreach ([$source, $target] as $name) {
    if (! preg_match('/^dmf_(?:restore_)?test_[0-9]{8}$/D', $name)) {
        throw new RuntimeException('Only named isolated test databases are accepted.');
    }
}
if (DB::getDriverName() !== 'mysql') {
    throw new RuntimeException('MySQL required.');
}
$tables = DB::select("SHOW TABLES FROM `$source`");
$checked = 0;
foreach ($tables as $table) {
    $name = array_values((array) $table)[0];
    if (! preg_match('/^[a-z0-9_]+$/D', $name)) {
        throw new RuntimeException('Unexpected table name');
    }$rows = DB::select("CHECKSUM TABLE `$source`.`$name`, `$target`.`$name` EXTENDED");
    if (count($rows) !== 2 || $rows[0]->Checksum === null || $rows[0]->Checksum !== $rows[1]->Checksum) {
        throw new RuntimeException('Restore checksum mismatch: '.$name);
    }$checked++;
}
echo "PASS: all $checked tables have matching extended checksums between source and restored database.\n";
