<?php

use App\Domain\Dashboard;
use App\Domain\Reporting;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (config('database.default') !== 'mysql' || ! str_contains(config('database.connections.mysql.database'), '_test_')) {
    throw new RuntimeException('Dedicated MySQL _test_ database required.');
}
$u = User::firstOrCreate(['email' => 'benchmark@example.test'], ['name' => 'Benchmark', 'password' => bin2hex(random_bytes(24))]);
DB::table('role_assignments')->insertOrIgnore(['user_id' => $u->id, 'role' => 'admin', 'scope_type' => 'province', 'scope_id' => 0]);
if (! DB::table('activities')->where('code', 'BENCH-0')->exists()) {
    $team = DB::table('teams')->insertGetId(['code' => 'BENCH', 'name' => 'Benchmark', 'is_active' => true]);
    $regions = [];
    for ($i = 0; $i < 20; $i++) {
        $regions[] = DB::table('regions')->insertGetId(['code' => 'BENCH-R'.$i, 'name' => 'Region '.$i, 'is_active' => true]);
    }
    $today = now('Asia/Makassar');
    $start = $today->copy()->subDays(49);
    $stamp = now();
    for ($i = 0; $i < 100; $i++) {
        $aid = DB::table('activities')->insertGetId(['code' => 'BENCH-'.$i, 'title' => 'Benchmark '.$i, 'team_id' => $team, 'owner_id' => $u->id, 'year' => $today->year, 'start_date' => $start->toDateString(), 'end_date' => $today->toDateString(), 'indicator' => 'Dokumen', 'unit' => 'Dokumen', 'value_type' => 'count', 'precision' => 0, 'status' => 'AKTIF', 'version' => 1, 'created_at' => $stamp, 'updated_at' => $stamp]);
        $set = DB::table('target_sets')->insertGetId(['activity_id' => $aid, 'version' => 1, 'data' => '{}', 'actor_id' => $u->id, 'created_at' => $stamp, 'updated_at' => $stamp]);
        DB::table('activities')->where('id', $aid)->update(['current_target_set_id' => $set]);
        foreach ($regions as $rid) {
            DB::table('activity_regions')->insert(['activity_id' => $aid, 'region_id' => $rid, 'joined_on' => $start->toDateString()]);
        }
        $targets = [];
        $reports = [];
        $json = [];
        for ($p = 0; $p < 50; $p++) {
            $date = $start->copy()->addDays($p)->toDateString();
            $pid = DB::table('periods')->insertGetId(['activity_id' => $aid, 'label' => 'Hari '.($p + 1), 'position_date' => $date, 'due_at' => $date.' 09:00:00']);
            foreach ($regions as $rid) {
                $target = ($p + 1) * 2;
                $targets[] = ['target_set_id' => $set, 'period_id' => $pid, 'region_id' => $rid, 'target' => $target, 'final_target' => 100];
                $json[$rid]['final'] = '100.0000';
                $json[$rid]['periods'][$pid] = (string) $target;
                $reports[] = ['period_id' => $pid, 'region_id' => $rid, 'value' => $target, 'actor_id' => $u->id, 'version' => 1, 'first_submitted_at' => $stamp, 'created_at' => $stamp, 'updated_at' => $stamp];
            }
        }
        foreach (array_chunk($targets, 500) as $chunk) {
            DB::table('target_values')->insert($chunk);
        }foreach (array_chunk($reports, 500) as $chunk) {
            DB::table('realizations')->insert($chunk);
        }DB::table('target_sets')->where('id', $set)->update(['data' => json_encode($json)]);
    }
}
$times = [];
$result = null;
for ($i = 0; $i < 12; $i++) {
    $start = microtime(true);
    $result = app(Dashboard::class)->get($u, []);
    $elapsed = (microtime(true) - $start) * 1000;
    if ($i > 1) {
        $times[] = $elapsed;
    }
}
sort($times);
$report = ['database' => DB::selectOne('select version() as version')->version, 'activities' => DB::table('activities')->where('code', 'like', 'BENCH-%')->count(), 'realizations' => DB::table('realizations')->count(), 'runs' => count($times), 'p95_ms' => round($times[(int) ceil(count($times) * .95) - 1], 2), 'peak_memory_mb' => round(memory_get_peak_usage(true) / 1048576, 2), 'detail_page_size' => count($result['units']), 'note' => 'Local sequential service benchmark, not a 30-user target-server load test.'];
$aid = DB::table('activities')->where('code', 'BENCH-0')->value('id');
$p = DB::table('periods')->where('activity_id', $aid)->orderByDesc('position_date')->first();
$batchTimes = [];
for ($i = 0; $i < 12; $i++) {
    $rows = DB::table('realizations')->where('period_id', $p->id)->get()->map(fn ($r) => ['region_id' => $r->region_id, 'version' => $r->version, 'position_date' => $p->position_date, 'value' => bcadd((string) $r->value, '1', 0), 'reason' => 'Sequential batch benchmark'])->all();
    $start = microtime(true);
    $saved = app(Reporting::class)->batch($u, $p->id, ['rows' => $rows], 'benchmark-'.bin2hex(random_bytes(12)));
    $ms = (microtime(true) - $start) * 1000;
    if (count($rows) !== 20 || collect($saved['results'])->where('status', 'saved')->count() !== 20) {
        throw new RuntimeException('20-row batch failed');
    }if ($i > 1) {
        $batchTimes[] = $ms;
    }
}
sort($batchTimes);
$report['batch_20_p95_ms'] = round($batchTimes[(int) ceil(count($batchTimes) * .95) - 1], 2);
echo json_encode($report, JSON_PRETTY_PRINT)."\n";
if ($report['p95_ms'] > 3000 || $report['batch_20_p95_ms'] > 3000) {
    exit(1);
}
