<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', fn (Blueprint $t) => $t->date('cancelled_on')->nullable());
        Schema::create('system_locks', function (Blueprint $t) {
            $t->string('name', 40)->primary();
        });
        DB::table('system_locks')->insert(['name' => 'admin-membership']);
        foreach (DB::table('audit_logs')->where('action', 'activity.status')->orderBy('id')->get() as $log) {
            $after = json_decode($log->after_data, true);
            if (($after['status'] ?? null) === 'BATAL') {
                DB::table('activities')->where('id', $log->activity_id)->whereNull('cancelled_on')->update(['cancelled_on' => CarbonImmutable::parse($log->created_at, 'UTC')->setTimezone('Asia/Makassar')->toDateString()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_locks');
        Schema::table('activities', fn (Blueprint $t) => $t->dropColumn('cancelled_on'));
    }
};
