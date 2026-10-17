<?php

namespace App\Domain;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class Audit
{
    public static function record(User $user, string $action, ?int $activity, ?int $period, ?int $region, mixed $before, mixed $after, ?string $reason = null): void
    {
        DB::table('audit_logs')->insert([
            'actor_id' => $user->id, 'activity_id' => $activity, 'period_id' => $period, 'region_id' => $region,
            'action' => $action, 'before_data' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
            'after_data' => $after === null ? null : json_encode($after, JSON_THROW_ON_ERROR), 'reason' => $reason,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
