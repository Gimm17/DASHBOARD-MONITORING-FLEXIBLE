<?php

namespace App\Console\Commands;

use App\Domain\Activities;
use App\Domain\Reporting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrepareDemo extends Command
{
    protected $signature = 'dmf:demo';

    protected $description = 'Create fictional monitoring data in a local or testing environment only';

    public function handle(Activities $activities, Reporting $reporting): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Data demo hanya boleh dibuat pada local/testing.');

            return self::FAILURE;
        }
        $adminId = DB::table('role_assignments')->where('role', 'admin')->where('scope_type', 'province')->value('user_id');
        $user = $adminId ? User::find($adminId) : null;
        if (! $user) {
            $this->error('Buat admin dengan dmf:admin terlebih dahulu.');

            return self::FAILURE;
        }
        if (DB::table('activities')->where('code', 'DEMO-MONITOR')->exists()) {
            $this->info('Demo sudah tersedia.');

            return self::SUCCESS;
        }
        foreach (['teams' => [['code' => 'DEMO', 'name' => 'Tim Contoh']], 'regions' => [['code' => 'REG01', 'name' => 'Wilayah Contoh A'], ['code' => 'REG02', 'name' => 'Wilayah Contoh B']], 'units' => [['code' => 'DOK', 'name' => 'Dokumen']]] as $table => $rows) {
            foreach ($rows as $row) {
                DB::table($table)->insertOrIgnore($row + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        $date = now('Asia/Makassar');
        $year = $date->year;
        $start = $date->copy()->startOfYear()->toDateString();
        $end = $date->copy()->endOfYear()->toDateString();
        $dates = collect([$date->copy()->subDays(7)->max($date->copy()->startOfYear())->toDateString(), $date->toDateString(), $end])->unique()->values();
        $regions = DB::table('regions')->whereIn('code', ['REG01', 'REG02'])->pluck('id')->all();
        $data = ['code' => 'DEMO-MONITOR', 'title' => 'Monitoring Contoh', 'team_id' => DB::table('teams')->where('code', 'DEMO')->value('id'), 'owner_id' => $user->id, 'year' => $year, 'start_date' => $start, 'end_date' => $end, 'indicator' => 'Dokumen selesai', 'unit' => 'Dokumen', 'value_type' => 'count', 'precision' => 0, 'periods' => $dates->map(fn ($d, $i) => ['label' => 'Evaluasi '.($i + 1), 'position_date' => $d, 'due_at' => $d.'T17:00:00+08:00'])->all(), 'region_targets' => array_map(fn ($r) => ['region_id' => $r, 'final_target' => '140', 'targets' => $dates->map(fn ($d, $i) => $i === $dates->count() - 1 ? '140' : (string) (40 + $i * 60))->all()], $regions)];
        $detail = $activities->save($user, $data);
        $activities->transition($user, $detail['activity']['id'], 'AKTIF', 1, null);
        $p = $detail['periods'][0];
        $reporting->batch($user, $p['id'], ['rows' => [['region_id' => $regions[0], 'value' => '35', 'position_date' => $p['position_date'], 'version' => 0]]], 'demo-initial-0001');
        $this->info('Monitoring fiktif dibuat. Jangan gunakan sebagai data organisasi.');

        return self::SUCCESS;
    }
}
