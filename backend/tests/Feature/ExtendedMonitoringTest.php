<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExtendedMonitoringTest extends MonitoringTest
{
    public function test_malformed_number_is_a_row_validation_error_and_partial_batch_is_retained(): void
    {
        [$a,$region] = $this->activity();
        $p = $a['periods'][0]['id'];
        $this->withHeader('Idempotency-Key', 'malformed-001')->postJson("/api/periods/$p/realizations", ['rows' => [['region_id' => $region, 'value' => ['bad'], 'version' => 0, 'position_date' => '2026-10-05']]])->assertOk()->assertJsonPath('results.0.status', 'invalid');
        $this->withHeader('Idempotency-Key', 'partial-001')->postJson("/api/periods/$p/realizations", ['rows' => [['region_id' => $region, 'value' => '0', 'version' => 0, 'position_date' => '2026-10-05'], ['region_id' => 9999, 'value' => '20', 'version' => 0, 'position_date' => '2026-10-05']]])->assertOk()->assertJsonPath('results.0.status', 'saved')->assertJsonPath('results.1.status', 'forbidden');
        $this->assertDatabaseHas('realizations', ['period_id' => $p, 'region_id' => $region, 'value' => 0]);
    }

    public function test_production_headers_and_regional_revision_scope(): void
    {
        [$a,$region,$admin,$data] = $this->activity();
        $other = DB::table('regions')->insertGetId(['code' => 'OTHER', 'name' => 'Wilayah Lain', 'is_active' => true]);
        $data['region_targets'][] = ['region_id' => $other, 'final_target' => '140', 'targets' => ['40', '100', '140']];
        $data['version'] = 2;
        $data['reason'] = 'Menambahkan wilayah kedua';
        $this->putJson('/api/activities/'.$a['id'], $data)->assertOk();
        DB::table('activity_regions')->where('activity_id', $a['id'])->update(['joined_on' => '2026-10-01']);
        $p = $a['periods'][0]['id'];
        $this->postJson("/api/periods/$p/finalize", ['version' => 0])->assertCreated();
        $viewer = User::factory()->create();
        DB::table('role_assignments')->insert(['user_id' => $viewer->id, 'role' => 'wilayah', 'scope_type' => 'region', 'scope_id' => $region]);
        $this->app['env'] = 'production';
        $this->actingAs($viewer)->getJson("/api/periods/$p/revisions")->assertOk()->assertJsonCount(1, 'revisions.0.snapshot.cells')->assertJsonMissingPath('revisions.0.snapshot.cells.'.$other)->assertHeader('x-frame-options', 'DENY')->assertHeader('content-security-policy');
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('summary.total', 2)->assertJsonPath('summary.missing', 2);
        $csv = $this->get('/api/export')->assertOk()->streamedContent();
        $this->assertStringNotContainsString('Wilayah Lain', $csv);
        $this->app['env'] = 'testing';
    }

    public function test_clearing_an_approved_value_requires_correction_review(): void
    {
        [$a,$region] = $this->activity();
        $p = $a['periods'][0]['id'];
        $url = "/api/periods/$p/realizations";
        $this->withHeader('Idempotency-Key', 'null-review-001')->postJson($url, ['rows' => [['region_id' => $region, 'value' => '35', 'version' => 0, 'position_date' => '2026-10-05']]])->assertOk();
        $this->withHeader('Idempotency-Key', 'null-review-002')->postJson($url, ['rows' => [['region_id' => $region, 'value' => null, 'version' => 1, 'position_date' => '2026-10-05', 'reason' => 'Membatalkan angka yang salah']]])->assertJsonPath('results.0.status', 'pending');
        $this->assertDatabaseHas('realizations', ['period_id' => $p, 'value' => 35, 'version' => 1]);
    }

    public function test_same_day_participant_removal_does_not_erase_final_snapshot(): void
    {
        [$a,$region,$admin,$data] = $this->activity();
        $this->travelTo(now()->setDate(2026, 10, 5));
        $p = $a['periods'][0]['id'];
        $this->postJson("/api/periods/$p/finalize", ['version' => 0])->assertCreated();
        $data['version'] = 2;
        $data['reason'] = 'Peserta tidak ikut berikutnya';
        $data['region_targets'] = [];
        $this->putJson('/api/activities/'.$a['id'], $data)->assertOk();
        $this->getJson('/api/dashboard')->assertJsonPath('summary.total', 1)->assertJsonPath('units.0.region_id', $region);
        $this->postJson("/api/periods/$p/reopen", ['reason' => 'Koreksi setelah peserta keluar'])->assertOk();
        $this->withHeader('Idempotency-Key', 'removed-correction-001')->postJson("/api/periods/$p/realizations", ['rows' => [['region_id' => $region, 'value' => '40', 'version' => 0, 'position_date' => '2026-10-05']]])->assertJsonPath('results.0.status', 'saved');
        $this->postJson("/api/periods/$p/finalize", ['version' => 2])->assertCreated();
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('summary.total', 1)->assertJsonPath('summary.attained', 1);
    }

    public function test_cancel_then_archive_preserves_past_but_does_not_resurrect_future(): void
    {
        [$a] = $this->activity();
        $p = $a['periods'][0]['id'];
        $this->postJson("/api/periods/$p/finalize", ['version' => 0])->assertCreated();
        $this->postJson('/api/activities/'.$a['id'].'/status', ['status' => 'BATAL', 'version' => 2, 'reason' => 'Kegiatan dihentikan resmi'])->assertOk();
        $this->getJson('/api/dashboard')->assertJsonPath('summary.total', 2);
        $this->postJson('/api/activities/'.$a['id'].'/status', ['status' => 'ARSIP', 'version' => 3, 'reason' => 'Arsip kegiatan batal'])->assertOk();
        $this->travelTo(now()->setDate(2026, 11, 2));
        $this->getJson('/api/dashboard')->assertJsonPath('summary.total', 2);
    }

    public function test_completed_activity_can_be_corrected_without_reopening_routine_input(): void
    {
        [$a,$region] = $this->activity();
        $this->travelTo(now()->setDate(2026, 11, 2));
        foreach ($a['periods'] as $p) {
            $this->postJson('/api/periods/'.$p['id'].'/finalize', ['version' => 0])->assertCreated();
        }
        $this->postJson('/api/activities/'.$a['id'].'/status', ['status' => 'SELESAI', 'version' => 2])->assertOk();
        $p = $a['periods'][0]['id'];
        $this->postJson("/api/periods/$p/reopen", ['reason' => 'Koreksi hasil setelah penutupan'])->assertOk();
        $this->withHeader('Idempotency-Key', 'closed-correction-001')->postJson("/api/periods/$p/realizations", ['rows' => [['region_id' => $region, 'value' => '40', 'version' => 0, 'position_date' => '2026-10-05', 'reason' => 'Koreksi setelah tutup']]])->assertJsonPath('results.0.status', 'saved');
        $this->postJson("/api/periods/$p/finalize", ['version' => 2])->assertCreated()->assertJsonPath('revision', 2);
        $this->getJson("/api/periods/$p/revisions")->assertOk()->assertJsonCount(2, 'revisions');
        $this->assertDatabaseHas('activities', ['id' => $a['id'], 'status' => 'SELESAI']);
    }

    public function test_csv_escapes_formula_with_leading_whitespace(): void
    {
        [$a] = $this->activity();
        DB::table('activities')->where('id', $a['id'])->update(['title' => " \t=1+1"]);
        $csv = $this->get('/api/export')->assertOk()->streamedContent();
        $this->assertStringContainsString("' \t=1+1", $csv);
    }

    public function test_dashboard_details_are_paginated(): void
    {
        [$a,$region,$admin,$data] = $this->activity();
        for ($i = 0; $i < 30; $i++) {
            $id = DB::table('regions')->insertGetId(['code' => 'EXTRA'.$i, 'name' => 'Extra '.$i, 'is_active' => true]);
            $data['region_targets'][] = ['region_id' => $id, 'final_target' => '140', 'targets' => ['40', '100', '140']];
        }
        $data['version'] = 2;
        $data['reason'] = 'Menambah peserta uji';
        $this->putJson('/api/activities/'.$a['id'], $data)->assertOk();
        DB::table('activity_regions')->where('activity_id', $a['id'])->update(['joined_on' => '2026-10-01']);
        $this->getJson('/api/dashboard')->assertOk()->assertJsonCount(50, 'units')->assertJsonPath('unit_total', 62);
    }

    public function test_decrease_creates_ticket_without_mutating_official_value(): void
    {
        [$a,$region] = $this->activity();
        $p = $a['periods'][0]['id'];
        $url = "/api/periods/$p/realizations";
        $this->withHeader('Idempotency-Key', 'decrease-001')->postJson($url, ['rows' => [['region_id' => $region, 'value' => '35', 'version' => 0, 'position_date' => '2026-10-05']]])->assertJsonPath('results.0.status', 'saved');
        $this->withHeader('Idempotency-Key', 'decrease-002')->postJson($url, ['rows' => [['region_id' => $region, 'value' => '30', 'version' => 1, 'position_date' => '2026-10-05', 'reason' => 'Koreksi dokumen duplikat']]])->assertJsonPath('results.0.status', 'pending');
        $this->assertDatabaseHas('realizations', ['period_id' => $p, 'value' => 35, 'version' => 1]);
        $ticket = DB::table('correction_requests')->first();
        $this->postJson('/api/tickets/'.$ticket->id.'/review', ['action' => 'approve', 'message' => 'Disetujui berdasarkan verifikasi'])->assertOk();
        $this->assertDatabaseHas('realizations', ['period_id' => $p, 'value' => 30, 'version' => 2]);
        $this->postJson('/api/tickets/'.$ticket->id.'/review', ['action' => 'approve', 'message' => 'Disetujui ulang'])->assertConflict();
    }

    public function test_dashboard_excludes_future_and_counts_missing_in_denominator(): void
    {
        [$a,$region] = $this->activity();
        $p = $a['periods'][1]['id'];
        $this->withHeader('Idempotency-Key', 'dashboard-001')->postJson("/api/periods/$p/realizations", ['rows' => [['region_id' => $region, 'value' => '105', 'version' => 0, 'position_date' => '2026-10-12']]])->assertOk();
        $this->getJson('/api/dashboard')->assertJsonPath('summary.total', 2)->assertJsonPath('summary.missing', 1)->assertJsonPath('summary.attained', 1)->assertJsonPath('summary.rate', 50);
        $this->getJson('/api/export?status=BELUM_ADA_DATA')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_multiple_roles_do_not_widen_other_team_permissions(): void
    {
        [$a,$region] = $this->activity();
        $user = User::factory()->create();
        DB::table('role_assignments')->insert([['user_id' => $user->id, 'role' => 'wilayah', 'scope_type' => 'region', 'scope_id' => $region], ['user_id' => $user->id, 'role' => 'operator', 'scope_type' => 'team', 'scope_id' => 999]]);
        $this->actingAs($user)->getJson('/api/activities/'.$a['id'])->assertOk()->assertJsonPath('can_write', false);
        $this->withHeader('Idempotency-Key', 'unauthorized-001')->postJson('/api/periods/'.$a['periods'][0]['id'].'/realizations', ['rows' => []])->assertForbidden();
    }

    public function test_target_revision_keeps_final_snapshot_and_reopen_refinalizes(): void
    {
        [$a,$region,$admin,$data] = $this->activity();
        $p = $a['periods'][0]['id'];
        $this->withHeader('Idempotency-Key', 'snapshot-001')->postJson("/api/periods/$p/realizations", ['rows' => [['region_id' => $region, 'value' => '35', 'version' => 0, 'position_date' => '2026-10-05']]])->assertOk();
        $this->postJson("/api/periods/$p/finalize", ['version' => 0])->assertCreated();
        $data['version'] = 2;
        $data['reason'] = 'Target diperbarui resmi';
        $data['region_targets'][0]['targets'] = ['30', '100', '140'];
        $this->putJson('/api/activities/'.$a['id'], $data)->assertOk()->assertJsonPath('rows.0.cells.0.target', '40.0000');
        $this->postJson("/api/periods/$p/reopen", ['reason' => 'Evaluasi ulang target'])->assertOk();
        $this->postJson("/api/periods/$p/finalize", ['version' => 2])->assertCreated()->assertJsonPath('revision', 2);
        $this->getJson('/api/activities/'.$a['id'])->assertJsonPath('rows.0.cells.0.target', '30.0000')->assertJsonPath('rows.0.cells.0.status', 'TERCAPAI');
        $this->assertDatabaseCount('evaluation_revisions', 2);
    }

    public function test_last_admin_cannot_be_disabled_or_demoted(): void
    {
        [$a,$region,$admin] = $this->activity();
        $this->postJson('/api/users', ['id' => $admin->id, 'email' => $admin->email, 'name' => $admin->name, 'is_active' => false, 'assignments' => [['role' => 'pimpinan', 'scope_type' => 'province', 'scope_id' => 0]]])->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true]);
    }

    public function test_oversized_and_invalid_batches_do_not_create_reports(): void
    {
        [$a,$region] = $this->activity();
        $p = $a['periods'][0]['id'];
        $this->withHeader('Idempotency-Key', 'precision-001')->postJson("/api/periods/$p/realizations", ['rows' => [['region_id' => $region, 'value' => '1.5', 'version' => 0, 'position_date' => '2026-10-05']]])->assertJsonPath('results.0.status', 'invalid');
        $this->withHeader('Idempotency-Key', 'precision-002')->postJson("/api/periods/$p/realizations", ['rows' => [['region_id' => $region, 'value' => '35', 'version' => 0, 'position_date' => '2026-10-04']]])->assertJsonPath('results.0.status', 'invalid');
        $this->assertDatabaseCount('realizations', 0);
    }
}
