<?php

// Runs only on a dedicated MySQL test database; never changes an existing production database.
use App\Domain\Activities;
use App\Domain\Reporting;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (config('database.default') !== 'mysql' || ! str_contains(config('database.connections.mysql.database'), '_test_')) {
    throw new RuntimeException('Dedicated MySQL _test_ database required.');
}
if (($argv[1] ?? '') === 'worker') {
    $u = User::findOrFail((int) $argv[2]);
    $period = (int) $argv[3];
    $region = (int) $argv[4];
    if (($argv[5] ?? '') === 'finalize') {
        DB::transaction(function () use ($u, $period) {
            $ref = DB::table('periods')->find($period);
            DB::table('activities')->where('id', $ref->activity_id)->lockForUpdate()->first();
            file_put_contents($argvMarker = getenv('DMF_RACE_MARKER'), 'locked');
            usleep(300000);
            app(Reporting::class)->finalize($u, $period, 0);
        });
        echo 'FINAL';
    } elseif (($argv[5] ?? '') === 'write_hold') {
        DB::transaction(function () use ($u, $period, $region) {
            $ref = DB::table('periods')->find($period);
            DB::table('activities')->where('id', $ref->activity_id)->lockForUpdate()->first();
            app(Reporting::class)->batch($u, $period, ['rows' => [['region_id' => $region, 'value' => '40', 'version' => 0, 'position_date' => $ref->position_date]]], 'writer-first-'.bin2hex(random_bytes(6)));
            $marker = getenv('DMF_RACE_MARKER');
            file_put_contents($marker, 'writer ready');
            $until = microtime(true) + 15;
            while (! is_file($marker.'.attempt') && microtime(true) < $until) {
                clearstatcache();
                usleep(10000);
            }usleep(300000);
        });
        echo 'SAVED';
    } elseif (($argv[5] ?? '') === 'finalize_now') {
        file_put_contents(getenv('DMF_RACE_MARKER').'.attempt', 'ready');
        app(Reporting::class)->finalize($u, $period, 0);
        echo 'FINAL';
    } else {
        try {
            app(Reporting::class)->batch($u, $period, ['rows' => [['region_id' => $region, 'value' => '105', 'version' => 0, 'position_date' => DB::table('periods')->where('id', $period)->value('position_date')]]], 'race-request-'.bin2hex(random_bytes(8)));
            echo 'SAVED';
        } catch (HttpException $e) {
            echo 'HTTP'.$e->getStatusCode();
        }
    }exit;
}
$email = 'race-'.bin2hex(random_bytes(4)).'@example.test';
$u = User::create(['name' => 'Race Test', 'email' => $email, 'password' => bin2hex(random_bytes(24))]);
DB::table('role_assignments')->insert(['user_id' => $u->id, 'role' => 'admin', 'scope_type' => 'province', 'scope_id' => 0]);
$code = 'RACE'.bin2hex(random_bytes(3));
$team = DB::table('teams')->insertGetId(['code' => $code, 'name' => 'Race Test', 'is_active' => true]);
$region = DB::table('regions')->insertGetId(['code' => $code, 'name' => 'Race Test', 'is_active' => true]);
$today = now('Asia/Makassar')->toDateString();
$d = app(Activities::class)->save($u, ['code' => $code, 'title' => 'Concurrency Test', 'team_id' => $team, 'owner_id' => $u->id, 'year' => (int) substr($today, 0, 4), 'start_date' => $today, 'end_date' => $today, 'indicator' => 'Test', 'unit' => 'Dokumen', 'value_type' => 'count', 'precision' => 0, 'periods' => [['label' => 'Test', 'position_date' => $today, 'due_at' => $today.'T17:00:00+08:00']], 'region_targets' => [['region_id' => $region, 'final_target' => '100', 'targets' => ['100']]]]);
app(Activities::class)->transition($u, $d['activity']['id'], 'AKTIF', 1, null);
$p = $d['periods'][0]['id'];
$marker = tempnam(sys_get_temp_dir(), 'dmf-race-');
unlink($marker);
putenv('DMF_RACE_MARKER='.$marker);
$first = new Process([PHP_BINARY, __FILE__, 'worker', (string) $u->id, (string) $p, (string) $region, 'finalize'], null, ['DMF_RACE_MARKER' => $marker]);
$first->start();
$until = microtime(true) + 10;
while (! is_file($marker) && microtime(true) < $until) {
    usleep(10000);
    clearstatcache(true, $marker);
    if (! $first->isRunning()) {
        throw new RuntimeException($first->getOutput().' '.$first->getErrorOutput());
    }
}
if (! is_file($marker)) {
    throw new RuntimeException('Worker lock was not acquired.');
}
$second = new Process([PHP_BINARY, __FILE__, 'worker', (string) $u->id, (string) $p, (string) $region, 'save']);
$second->start();
$first->wait();
$second->wait();
unlink($marker);
$count = DB::table('realizations')->where('period_id', $p)->count();
$snap = DB::table('evaluation_revisions')->where('period_id', $p)->count();
if ($first->getOutput() !== 'FINAL' || $second->getOutput() !== 'HTTP423' || $count !== 0 || $snap !== 1) {
    throw new RuntimeException('Race invariant failed: '.$first->getOutput().' '.$second->getOutput().' '.$first->getErrorOutput().' '.$second->getErrorOutput());
}
echo "PASS: concurrent write waited for finalization and was rejected with HTTP423; one immutable revision and zero late writes.\n";
$copy = collect($d['activity'])->only(['title', 'team_id', 'owner_id', 'year', 'start_date', 'end_date', 'indicator', 'unit', 'value_type', 'precision'])->all();
$copy['code'] = 'WRITE-FIRST-'.bin2hex(random_bytes(3));
$copy['periods'] = [['label' => 'Writer first', 'position_date' => $today, 'due_at' => $today.'T17:00:00+08:00']];
$copy['region_targets'] = [['region_id' => $region, 'final_target' => '100', 'targets' => ['100']]];
$next = app(Activities::class)->save($u, $copy);
app(Activities::class)->transition($u, $next['activity']['id'], 'AKTIF', 1, null);
$p = $next['periods'][0]['id'];
$marker = tempnam(sys_get_temp_dir(), 'dmf-write-');
unlink($marker);
$writer = new Process([PHP_BINARY, __FILE__, 'worker', (string) $u->id, (string) $p, (string) $region, 'write_hold'], null, ['DMF_RACE_MARKER' => $marker]);
$writer->start();
$until = microtime(true) + 15;
while (! is_file($marker) && microtime(true) < $until) {
    clearstatcache();
    usleep(10000);
    if (! $writer->isRunning()) {
        throw new RuntimeException($writer->getOutput().$writer->getErrorOutput());
    }
}
$finalizer = new Process([PHP_BINARY, __FILE__, 'worker', (string) $u->id, (string) $p, (string) $region, 'finalize_now'], null, ['DMF_RACE_MARKER' => $marker]);
$finalizer->start();
$writer->wait();
$finalizer->wait();
$snapshot = json_decode(DB::table('evaluation_revisions')->where('period_id', $p)->value('snapshot'), true);
if (($snapshot['cells'][$region]['value'] ?? null) !== '40.0000') {
    throw new RuntimeException('Writer-first finalizer missed the committed report: '.$finalizer->getOutput().$finalizer->getErrorOutput());
}
unlink($marker);
unlink($marker.'.attempt');
echo "PASS: writer-first finalization includes the report committed while it waited for the activity lock.\n";
