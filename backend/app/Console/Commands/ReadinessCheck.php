<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReadinessCheck extends Command
{
    protected $signature = 'dmf:check {--production}';

    protected $description = 'Verify technical release prerequisites without changing data';

    public function handle(): int
    {
        $checks = ['PHP >= 8.4.1' => version_compare(PHP_VERSION, '8.4.1', '>='), 'BCMath' => extension_loaded('bcmath'), 'Application key' => strlen((string) config('app.key')) > 20, 'Frontend build' => is_file(public_path('spa/index.html')), 'Writable storage' => is_writable(storage_path())];
        try {
            DB::select('select 1');
            $checks['Database connectivity'] = true;
            $checks['Monitoring schema'] = Schema::hasTable('system_locks') && Schema::hasTable('evaluation_items') && Schema::hasColumn('activities', 'cancelled_on');
            $checks['Administrator provisioned'] = ($checks['Monitoring schema']) && DB::table('users')->join('role_assignments', 'users.id', '=', 'role_assignments.user_id')->where('users.is_active', true)->where('role', 'admin')->where('scope_type', 'province')->where('scope_id', 0)->exists();
        } catch (\Throwable) {
            $checks['Database connectivity'] = false;
            $checks['Monitoring schema'] = false;
            $checks['Administrator provisioned'] = false;
        }
        if ($this->option('production')) {
            $checks += ['Production environment' => app()->isProduction(), 'Debug disabled' => ! config('app.debug'), 'HTTPS URL' => str_starts_with(config('app.url'), 'https://'), 'Secure session cookie' => (bool) config('session.secure'), 'Encrypted session' => (bool) config('session.encrypt'), 'MySQL connection' => config('database.default') === 'mysql'];
        }
        foreach ($checks as $name => $ok) {
            $this->line(($ok ? 'PASS ' : 'FAIL ').$name);
        }
        $this->comment('Operational UAT, restore drills and target-server load tests require separate evidence.');

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
