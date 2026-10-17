<?php

use App\Domain\Access;
use App\Http\Controllers\AdminController;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (config('database.default') !== 'mysql' || ! str_contains(config('database.connections.mysql.database'), '_admin_test_')) {
    throw new RuntimeException('Isolated _admin_test_ MySQL database required.');
}
if (($argv[1] ?? '') === 'worker') {
    $actor = User::findOrFail((int) $argv[2]);
    $subject = User::findOrFail((int) $argv[3]);
    $r = Request::create('/api/users', 'POST', ['id' => $subject->id, 'email' => $subject->email, 'name' => $subject->name, 'is_active' => true, 'assignments' => [['role' => 'pimpinan', 'scope_type' => 'province', 'scope_id' => 0]]]);
    $r->setUserResolver(fn () => $actor);
    file_put_contents(getenv('DMF_ADMIN_MARKER'), 'ready');
    try {
        app(AdminController::class)->saveUser($r, app(Access::class));
        echo 'SAVED';
    } catch (HttpException $e) {
        echo 'HTTP'.$e->getStatusCode();
    }exit;
}
if (DB::table('users')->count()) {
    throw new RuntimeException('Use an empty isolated admin test database.');
}
$ids = [];
foreach (['A', 'B'] as $label) {
    $u = User::create(['name' => 'Admin '.$label, 'email' => strtolower($label).'@example.test', 'password' => bin2hex(random_bytes(20))]);
    $ids[] = $u->id;
    DB::table('role_assignments')->insert(['user_id' => $u->id, 'role' => 'admin', 'scope_type' => 'province', 'scope_id' => 0]);
}
DB::beginTransaction();
DB::table('users')->where('id', $ids[0])->lockForUpdate()->first();
$workers = [];
$markers = [];
foreach ($ids as $id) {
    $marker = tempnam(sys_get_temp_dir(), 'dmf-admin-');
    unlink($marker);
    $markers[] = $marker;
    $p = new Process([PHP_BINARY, __FILE__, 'worker', (string) $ids[0], (string) $id], null, ['DMF_ADMIN_MARKER' => $marker]);
    $p->start();
    $workers[] = $p;
}
$until = microtime(true) + 15;
do {
    clearstatcache();
    $ready = count(array_filter($markers, 'is_file'));
    if ($ready === 2) {
        break;
    }usleep(10000);
} while (microtime(true) < $until);
usleep(300000);
DB::commit();
$outputs = [];
foreach ($workers as $p) {
    $p->wait();
    $outputs[] = $p->getOutput();
    if ($p->getErrorOutput()) {
        throw new RuntimeException($p->getErrorOutput());
    }
}
sort($outputs);
$count = DB::table('role_assignments')->join('users', 'users.id', '=', 'role_assignments.user_id')->where('role', 'admin')->where('users.is_active', true)->count();
foreach ($markers as $m) {
    if (is_file($m)) {
        unlink($m);
    }
}
if ($outputs !== ['HTTP422', 'SAVED'] || $count !== 1) {
    throw new RuntimeException('Admin invariant failed: '.json_encode($outputs).' active admins='.$count);
}
echo "PASS: concurrent demotions preserve one active administrator; one change is rejected with HTTP422.\n";
