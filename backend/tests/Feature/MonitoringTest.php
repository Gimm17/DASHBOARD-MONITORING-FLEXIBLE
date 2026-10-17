<?php

namespace Tests\Feature;

use App\Domain\EvaluationCalculator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create();
        DB::table('role_assignments')->insert(['user_id' => $user->id, 'role' => 'admin', 'scope_type' => 'province', 'scope_id' => 0]);

        return $user;
    }

    protected function activity(): array
    {
        $this->travelTo(now()->setDate(2026, 10, 17));
        $admin = $this->admin();
        $this->actingAs($admin);
        $team = DB::table('teams')->insertGetId(['code' => 'SOC', 'name' => 'Sosial', 'is_active' => true]);
        $region = DB::table('regions')->insertGetId(['code' => 'TEST01', 'name' => 'Wilayah Uji', 'is_active' => true]);
        $data = [
            'code' => 'SURVEI-01', 'title' => 'Survei Uji', 'team_id' => $team, 'owner_id' => $admin->id,
            'year' => 2026, 'start_date' => '2026-10-01', 'end_date' => '2026-10-31',
            'indicator' => 'Dokumen selesai', 'unit' => 'Dokumen', 'value_type' => 'count', 'precision' => 0,
            'periods' => [
                ['label' => 'Pertama', 'position_date' => '2026-10-05', 'due_at' => '2026-10-06T17:00:00+08:00'],
                ['label' => 'Kedua', 'position_date' => '2026-10-12', 'due_at' => '2026-10-13T17:00:00+08:00'],
                ['label' => 'Terakhir', 'position_date' => '2026-10-31', 'due_at' => '2026-11-01T17:00:00+08:00'],
            ],
            'region_targets' => [['region_id' => $region, 'final_target' => '140', 'targets' => ['40', '100', '140']]],
        ];
        $response = $this->postJson('/api/activities', $data)->assertCreated();
        $response = $this->postJson('/api/activities/'.$response->json('activity.id').'/status', ['status' => 'AKTIF', 'version' => 1])->assertOk();

        return [$response->json('activity'), $region, $admin, $data];
    }

    public function test_monitoring_schema_exists(): void
    {
        $this->assertTrue(Schema::hasTable('activities'), 'Monitoring schema must be migrated');
    }

    public function test_activity_selection_and_target_validation(): void
    {
        [$activity, $region, $admin, $data] = $this->activity();
        $this->getJson('/api/activities/'.$activity['id'])->assertOk()->assertJsonCount(1, 'rows')->assertJsonCount(3, 'periods');
        $data['code'] = 'BAD';
        $data['region_targets'][0]['targets'] = ['40', '30', '140'];
        $this->postJson('/api/activities', $data)->assertUnprocessable();
        $this->assertDatabaseCount('activities', 1);
    }

    public function test_operator_cannot_create_or_access_another_team(): void
    {
        [$a] = $this->activity();
        $operator = User::factory()->create();
        DB::table('role_assignments')->insert(['user_id' => $operator->id, 'role' => 'operator', 'scope_type' => 'team', 'scope_id' => 999]);
        $this->actingAs($operator)->getJson('/api/activities/'.$a['id'])->assertForbidden();
        $this->postJson('/api/activities', [])->assertForbidden();
        $this->getJson('/api/dashboard')->assertJsonCount(0, 'activities');
    }

    public function test_null_and_zero_and_retry_and_conflict(): void
    {
        [$a, $region] = $this->activity();
        $p = $a['periods'][0]['id'];
        $payload = ['rows' => [['region_id' => $region, 'value' => '0', 'version' => 0, 'position_date' => '2026-10-05']]];
        $url = "/api/periods/$p/realizations";
        $this->withHeader('Idempotency-Key', 'batch-test-001')->postJson($url, $payload)->assertOk()->assertJsonPath('results.0.status', 'saved');
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('results.0.status', 'saved');
        $this->assertDatabaseCount('realizations', 1);
        $payload['rows'][0]['value'] = '10';
        $this->postJson($url, $payload)->assertConflict();
        $this->withHeader('Idempotency-Key', 'batch-test-002')->postJson($url, $payload)->assertJsonPath('results.0.status', 'conflict');
        $this->getJson('/api/activities/'.$a['id'])->assertJsonPath('rows.0.cells.0.value', '0.0000')->assertJsonPath('rows.0.cells.1.value', null);
    }

    public function test_partial_batch_negative_and_future_and_wrong_date_rejected(): void
    {
        [$a, $region] = $this->activity();
        $p = $a['periods'][0]['id'];
        $this->withHeader('Idempotency-Key', 'invalid-row-001')->postJson("/api/periods/$p/realizations", ['rows' => [
            ['region_id' => $region, 'value' => '-1', 'version' => 0, 'position_date' => '2026-10-05'],
        ]])->assertOk()->assertJsonPath('results.0.status', 'invalid');
        $future = $a['periods'][2]['id'];
        $this->withHeader('Idempotency-Key', 'future-row-001')->postJson("/api/periods/$future/realizations", ['rows' => [
            ['region_id' => $region, 'value' => '1', 'version' => 0, 'position_date' => '2026-10-31'],
        ]])->assertUnprocessable();
        $this->assertDatabaseCount('realizations', 0);
    }

    public function test_cumulative_calculation_and_final_lock_and_reopen_history(): void
    {
        [$a, $region, $admin] = $this->activity();
        $p = $a['periods'][1]['id'];
        $url = "/api/periods/$p/realizations";
        $this->withHeader('Idempotency-Key', 'calc-row-001')->postJson($url, ['rows' => [
            ['region_id' => $region, 'value' => '105', 'version' => 0, 'position_date' => '2026-10-12'],
        ]])->assertOk()->assertJsonPath('results.0.status', 'saved');
        $this->getJson('/api/activities/'.$a['id'])->assertJsonPath('rows.0.cells.1.attainment', 105)->assertJsonPath('rows.0.progress', 75);
        $this->postJson("/api/periods/$p/finalize", ['version' => 0])->assertCreated();
        $this->withHeader('Idempotency-Key', 'locked-row-001')->postJson($url, ['rows' => [
            ['region_id' => $region, 'value' => '106', 'version' => 1, 'position_date' => '2026-10-12', 'reason' => 'Dokumen susulan'],
        ]])->assertStatus(423);
        $this->postJson("/api/periods/$p/reopen", ['reason' => 'Koreksi resmi'])->assertOk();
        $this->assertDatabaseCount('evaluation_revisions', 1);
        $this->getJson('/api/activities/'.$a['id'])->assertJsonPath('periods.1.revision', 1)->assertJsonPath('periods.1.state', 'open');
    }

    public function test_precision_is_not_rounded_before_status(): void
    {
        $this->assertTrue(class_exists(EvaluationCalculator::class), 'Domain calculator is required');
        $r = app(EvaluationCalculator::class)->calculate('99.9999', '100', '140', true);
        $this->assertSame('DI_BAWAH_TARGET', $r['status']);
    }

    public function test_region_access_is_filtered_before_aggregation(): void
    {
        [$a, $region] = $this->activity();
        $viewer = User::factory()->create();
        DB::table('role_assignments')->insert(['user_id' => $viewer->id, 'role' => 'wilayah', 'scope_type' => 'region', 'scope_id' => $region]);
        $this->actingAs($viewer)->getJson('/api/activities/'.$a['id'])->assertJsonCount(1, 'rows');
        $this->postJson('/api/periods/'.$a['periods'][0]['id'].'/finalize', ['version' => 0])->assertForbidden();
    }

    public function test_inactive_account_cannot_use_existing_session(): void
    {
        [$a, $region, $admin] = $this->activity();
        $admin->forceFill(['is_active' => false])->save();
        $this->getJson('/api/dashboard')->assertForbidden();
    }
}
