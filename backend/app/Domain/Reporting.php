<?php

namespace App\Domain;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Reporting
{
    public function __construct(private Activities $activities, private Access $access) {}

    public function batch(User $user, int $periodId, array $data, ?string $key): array
    {
        $period = DB::table('periods')->find($periodId);
        abort_unless($period, 404);
        $a = $this->activities->find($user, $period->activity_id, 'write');
        $data = Validator::make($data, ['rows' => 'required|array|min:1|max:20', 'rows.*.region_id' => 'required|integer|distinct', 'rows.*.version' => 'required|integer|min:0', 'rows.*.position_date' => 'required|date_format:Y-m-d', 'rows.*.value' => 'present', 'rows.*.notes' => 'nullable|string|max:4000', 'rows.*.reason' => 'nullable|string|max:2000', 'rows.*.document_ref' => 'nullable|string|max:160'])->validate();
        abort_unless($key && preg_match('/^[A-Za-z0-9_-]{8,100}$/D', $key), 422, 'Kunci idempotensi wajib.');
        $hash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
        DB::table('batch_requests')->insertOrIgnore(['user_id' => $user->id, 'period_id' => $periodId, 'request_key' => $key, 'payload_hash' => $hash, 'created_at' => now(), 'updated_at' => now()]);
        $batch = DB::table('batch_requests')->where(['user_id' => $user->id, 'period_id' => $periodId, 'request_key' => $key])->first();
        abort_unless(hash_equals($batch->payload_hash, $hash), 409, 'Kunci telah digunakan untuk data berbeda.');
        $results = [];
        foreach ($data['rows'] as $index => $row) {
            $results[] = DB::transaction(function () use ($a, $periodId, $row, $batch, $index, $user) {
                $activity = DB::table('activities')->lockForUpdate()->find($a->id);
                $period = DB::table('periods')->lockForUpdate()->find($periodId);
                $existingResult = DB::table('batch_results')->where(['batch_request_id' => $batch->id, 'row_index' => $index])->first();
                if ($existingResult) {
                    return json_decode($existingResult->result, true);
                }
                abort_if($period->state === 'final' || ($activity->status !== 'AKTIF' && ! $period->current_revision_id), 423, 'Periode atau kegiatan terkunci.');
                abort_if($period->position_date > now('Asia/Makassar')->toDateString(), 422, 'Periode evaluasi belum tiba.');
                abort_unless($this->access->allowed($user, $activity, 'write'), 403);
                try {
                    $result = $this->saveRow($user, $activity, $period, $row);
                } catch (ValidationException $e) {
                    $result = ['region_id' => $row['region_id'], 'status' => 'invalid', 'errors' => $e->errors()];
                }
                DB::table('batch_results')->insert(['batch_request_id' => $batch->id, 'row_index' => $index, 'result' => json_encode($result, JSON_THROW_ON_ERROR)]);

                return $result;
            }, 3);
        }

        return ['batch_id' => $batch->id, 'results' => $results];
    }

    private function saveRow(User $user, object $activity, object $period, array $row, bool $approved = false): array
    {
        $member = DB::table('activity_regions')->where(['activity_id' => $activity->id, 'region_id' => $row['region_id']])->first();
        $officialStatus = $period->current_revision_id ? DB::table('evaluation_items')->where('revision_id', $period->current_revision_id)->where('region_id', $row['region_id'])->value('status') : null;
        $eligible = $period->current_revision_id ? $officialStatus && $officialStatus !== 'TIDAK_DINILAI' : $member && $period->position_date >= $member->joined_on && (! $member->left_on || $period->position_date < $member->left_on) && (! $activity->cancelled_on || $period->position_date < $activity->cancelled_on);
        if (! $eligible) {
            return ['region_id' => $row['region_id'], 'status' => 'forbidden', 'errors' => ['Wilayah bukan peserta periode ini.']];
        }
        if ($row['position_date'] !== $period->position_date) {
            throw ValidationException::withMessages(['position_date' => 'Tanggal posisi harus sama dengan jadwal.']);
        }
        $value = $row['value'] === null || $row['value'] === '' ? null : Activities::decimal($row['value'], $activity->precision, false, $activity->value_type === 'percentage');
        $old = DB::table('realizations')->where(['period_id' => $period->id, 'region_id' => $row['region_id']])->lockForUpdate()->first();
        if (($old->version ?? 0) !== (int) $row['version']) {
            return ['region_id' => $row['region_id'], 'status' => 'conflict', 'errors' => ['Data sudah diubah pengguna lain. Muat ulang.']];
        }
        $oldValue = $old?->value === null ? null : bcadd((string) $old->value, '0', 4);
        $notes = $row['notes'] ?? '';
        if ($old && $value === $oldValue && $notes === ($old->notes ?? '')) {
            return ['region_id' => $row['region_id'], 'status' => 'saved', 'version' => $old->version, 'value' => $oldValue];
        }
        $reason = trim($row['reason'] ?? '');
        $previous = DB::table('realizations')->join('periods', 'periods.id', '=', 'realizations.period_id')->where('periods.activity_id', $activity->id)->where('periods.position_date', '<', $period->position_date)->where('region_id', $row['region_id'])->whereNotNull('value')->orderByDesc('periods.position_date')->value('value');
        $decrease = ($value === null && $oldValue !== null) || ($value !== null && (($previous !== null && bccomp($value, (string) $previous, 4) < 0) || ($oldValue !== null && bccomp($value, $oldValue, 4) < 0)));
        if (($old || $decrease) && strlen($reason) < 5) {
            throw ValidationException::withMessages(['reason' => 'Perubahan/koreksi membutuhkan alasan minimal 5 karakter.']);
        }
        if ($decrease && ! $approved) {
            $ticket = DB::table('correction_requests')->insertGetId(['period_id' => $period->id, 'region_id' => $row['region_id'], 'actor_id' => $user->id, 'old_value' => $oldValue, 'new_value' => $value, 'expected_version' => $old->version ?? 0, 'reason' => $reason, 'document_ref' => $row['document_ref'] ?? null, 'status' => 'MENUNGGU_REVIEW', 'created_at' => now(), 'updated_at' => now()]);
            Audit::record($user, 'correction.requested', $activity->id, $period->id, $row['region_id'], ['value' => $oldValue], ['value' => $value, 'ticket_id' => $ticket], $reason);

            return ['region_id' => $row['region_id'], 'status' => 'pending', 'ticket_id' => $ticket, 'version' => $old->version ?? 0, 'value' => $oldValue];
        }
        $fields = ['value' => $value, 'notes' => $notes, 'actor_id' => $user->id, 'version' => ($old->version ?? 0) + 1, 'updated_at' => now(), 'first_submitted_at' => $old?->first_submitted_at ?? ($value !== null ? now() : null)];
        if ($old) {
            DB::table('realizations')->where('id', $old->id)->update($fields);
        } else {
            DB::table('realizations')->insert($fields + ['period_id' => $period->id, 'region_id' => $row['region_id'], 'created_at' => now()]);
        }
        Audit::record($user, 'realization.saved', $activity->id, $period->id, $row['region_id'], ['value' => $oldValue], ['value' => $value, 'notes' => $notes], $reason ?: null);

        return ['region_id' => $row['region_id'], 'status' => 'saved', 'version' => $fields['version'], 'value' => $value];
    }

    public function finalize(User $user, int $periodId, int $expectedVersion): array
    {
        $ref = DB::table('periods')->find($periodId);
        abort_unless($ref, 404);

        return DB::transaction(function () use ($user, $periodId, $expectedVersion, $ref) {
            $a = DB::table('activities')->lockForUpdate()->find($ref->activity_id);
            $p = DB::table('periods')->lockForUpdate()->find($periodId);
            abort_unless($this->access->allowed($user, $a, 'manage'), 403);
            abort_if($p->version !== $expectedVersion, 409, 'Evaluasi telah berubah. Muat ulang.');
            abort_if($p->state === 'final' || ($a->status !== 'AKTIF' && ! $p->current_revision_id), 423, 'Evaluasi atau kegiatan terkunci.');
            abort_if($p->position_date > now('Asia/Makassar')->toDateString(), 422, 'Evaluasi belum tiba.');
            abort_if(DB::table('correction_requests')->where('period_id', $periodId)->whereIn('status', ['MENUNGGU_REVIEW', 'BUTUH_KLARIFIKASI'])->exists(), 422, 'Selesaikan tiket koreksi terlebih dahulu.');
            $detail = $this->activities->detail($user, $a->id, false);
            $cells = [];
            foreach ($detail['rows'] as $r) {
                foreach ($r['cells'] as $cell) {
                    if ($cell['period_id'] === $periodId) {
                        $cells[$r['id']] = $cell;
                    }
                }
            }
            $revision = (DB::table('evaluation_revisions')->where('period_id', $periodId)->max('revision') ?? 0) + 1;
            $targetVersion = DB::table('target_sets')->where('id', $a->current_target_set_id)->value('version');
            $id = DB::table('evaluation_revisions')->insertGetId(['period_id' => $periodId, 'revision' => $revision, 'snapshot' => json_encode(['cells' => $cells, 'target_version' => $targetVersion, 'target_set_id' => $a->current_target_set_id, 'activity_version' => $a->version, 'rule_version' => '1.0', 'due_at' => $p->due_at], JSON_THROW_ON_ERROR), 'actor_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
            foreach ($cells as $regionId => $cell) {
                DB::table('evaluation_items')->insert(['revision_id' => $id, 'region_id' => $regionId, 'value' => $cell['value'], 'target' => $cell['target'], 'final_target' => $cell['final_target'], 'status' => $cell['status'], 'is_late' => $cell['isLate']]);
            }
            DB::table('periods')->where('id', $periodId)->update(['state' => 'final', 'version' => $p->version + 1, 'current_revision_id' => $id]);
            Audit::record($user, 'evaluation.finalized', $a->id, $periodId, null, null, ['revision' => $revision], null);

            return ['revision' => $revision, 'id' => $id];
        }, 3);
    }

    public function reopen(User $user, int $periodId, string $reason): void
    {
        abort_unless($this->access->admin($user), 403);
        $ref = DB::table('periods')->find($periodId);
        abort_unless($ref, 404);
        DB::transaction(function () use ($user, $periodId, $reason, $ref) {
            $a = DB::table('activities')->lockForUpdate()->find($ref->activity_id);
            $p = DB::table('periods')->lockForUpdate()->find($periodId);
            abort_unless($p->state === 'final', 422, 'Hanya evaluasi final dapat dibuka untuk koreksi.');
            DB::table('periods')->where('id', $periodId)->update(['state' => 'open', 'version' => $p->version + 1]);
            Audit::record($user, 'evaluation.reopened', $a->id, $periodId, null, ['revision' => $p->current_revision_id], ['state' => 'open'], $reason);
        }, 3);
    }

    public function review(User $user, int $ticketId, string $action, string $message): array
    {
        $ref = DB::table('correction_requests')->find($ticketId);
        abort_unless($ref, 404);
        $period = DB::table('periods')->find($ref->period_id);

        return DB::transaction(function () use ($user, $ticketId, $action, $message, $period) {
            $a = DB::table('activities')->lockForUpdate()->find($period->activity_id);
            $period = DB::table('periods')->lockForUpdate()->find($period->id);
            $ticket = DB::table('correction_requests')->lockForUpdate()->find($ticketId);
            abort_unless($this->access->allowed($user, $a, 'manage'), 403);
            abort_if(in_array($ticket->status, ['DISETUJUI', 'DITOLAK'], true), 409, 'Tiket sudah ditinjau.');
            $status = ['approve' => 'DISETUJUI', 'clarify' => 'BUTUH_KLARIFIKASI', 'reject' => 'DITOLAK'][$action];
            if ($action === 'approve') {
                abort_if($period->state === 'final' || ($a->status !== 'AKTIF' && ! $period->current_revision_id), 423, 'Periode terkunci.');
                $result = $this->saveRow($user, $a, $period, ['region_id' => $ticket->region_id, 'value' => $ticket->new_value, 'version' => $ticket->expected_version, 'position_date' => $period->position_date, 'reason' => $ticket->reason, 'notes' => $ticket->reason], true);
                abort_unless($result['status'] === 'saved', 409, 'Data sumber berubah sejak pengajuan. Ajukan koreksi baru.');
            }
            DB::table('correction_requests')->where('id', $ticketId)->update(['status' => $status, 'response' => $message, 'reviewer_id' => $user->id, 'updated_at' => now()]);
            Audit::record($user, 'correction.'.$action, $a->id, $period->id, $ticket->region_id, ['status' => $ticket->status], ['status' => $status], $message);

            return ['status' => $status];
        }, 3);
    }
}
