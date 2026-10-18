<?php

namespace App\Domain;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Activities
{
    public function __construct(private Access $access, private EvaluationCalculator $calculator) {}

    public function find(User $user, int $id, string $operation = 'read'): object
    {
        $activity = DB::table('activities')->find($id);
        abort_unless($activity, 404);
        abort_unless($this->access->allowed($user, $activity, $operation), 403);

        return $activity;
    }

    public static function decimal(mixed $value, int $precision, bool $positive = false, bool $percent = false): string
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            throw ValidationException::withMessages(['value' => 'Nilai harus berupa angka.']);
        }
        $s = (string) $value;
        if (! preg_match('/^\d{1,14}(?:\.\d{1,4})?$/D', $s) || strlen(rtrim(explode('.', $s)[1] ?? '', '0')) > $precision || ($positive && bccomp($s, '0', 4) <= 0) || ($percent && bccomp($s, '100', 4) > 0)) {
            throw ValidationException::withMessages(['value' => 'Angka tidak valid, melebihi presisi/satuan, atau di luar rentang.']);
        }

        return bcadd($s, '0', 4);
    }

    public function save(User $user, array $input, ?int $id = null): array
    {
        if ($id) {
            $this->find($user, $id, 'manage');
        } else {
            $fake = (object) ['id' => 0, 'team_id' => (int) ($input['team_id'] ?? 0)];
            abort_unless($this->access->allowed($user, $fake, 'manage'), 403);
        }
        $data = Validator::make($input, [
            'code' => 'required|string|max:60', 'title' => 'required|string|max:220', 'team_id' => 'required|integer|exists:teams,id',
            'owner_id' => 'required|integer|exists:users,id', 'year' => 'required|integer|between:2020,2100',
            'start_date' => 'required|date_format:Y-m-d', 'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'indicator' => 'required|string|max:200', 'unit' => 'required|string|max:80', 'value_type' => 'required|in:count,percentage', 'precision' => 'required|integer|between:0,4',
            'periods' => 'present|array|max:366', 'periods.*.label' => 'required|string|max:100', 'periods.*.position_date' => 'required|date_format:Y-m-d|distinct',
            'periods.*.due_at' => 'required|date', 'region_targets' => 'present|array|max:1000', 'region_targets.*.region_id' => 'required|integer|exists:regions,id|distinct',
            'region_targets.*.final_target' => 'required', 'region_targets.*.targets' => 'required|array',
            'version' => $id ? 'required|integer|min:1' : 'sometimes|integer', 'reason' => $id ? 'required|string|min:5|max:2000' : 'nullable|string|max:2000',
        ])->validate();
        abort_unless($this->access->allowed($user, (object) ['id' => $id ?? 0, 'team_id' => (int) $data['team_id']], 'manage'), 403);
        if (substr($data['start_date'], 0, 4) !== (string) $data['year'] || substr($data['end_date'], 0, 4) !== (string) $data['year']) {
            throw ValidationException::withMessages(['year' => 'Tanggal kegiatan harus berada dalam tahun pelaporan.']);
        }
        if (! DB::table('teams')->where('id', $data['team_id'])->where('is_active', true)->exists() || ! DB::table('users')->where('id', $data['owner_id'])->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['team_id' => 'Tim dan PJK harus aktif.']);
        }
        $lastDate = '';
        foreach ($data['periods'] as $period) {
            if ($period['position_date'] <= $lastDate || $period['position_date'] < $data['start_date'] || $period['position_date'] > $data['end_date'] || CarbonImmutable::parse($period['due_at'])->lt(CarbonImmutable::parse($period['position_date'], 'Asia/Makassar'))) {
                throw ValidationException::withMessages(['periods' => 'Jadwal harus berurutan, unik, dalam rentang, dan batas laporan tidak mendahului posisi data.']);
            }
            $lastDate = $period['position_date'];
        }
        foreach ($data['region_targets'] as &$r) {
            if (! DB::table('regions')->where('id', $r['region_id'])->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['region_targets' => 'Wilayah tidak aktif.']);
            }
            $r['final_target'] = self::decimal($r['final_target'], $data['precision'], true, $data['value_type'] === 'percentage');
            if (count($r['targets']) !== count($data['periods'])) {
                throw ValidationException::withMessages(['region_targets' => 'Jumlah target harus sama dengan jumlah periode.']);
            }
            $previous = '0';
            foreach ($r['targets'] as &$target) {
                $target = self::decimal($target, $data['precision'], true, $data['value_type'] === 'percentage');
                if (bccomp($target, $previous, 4) < 0 || bccomp($target, $r['final_target'], 4) > 0) {
                    throw ValidationException::withMessages(['region_targets' => 'Target kumulatif tidak boleh menurun atau melebihi target akhir.']);
                }
                $previous = $target;
            } unset($target);
            if (count($r['targets']) && bccomp($previous, $r['final_target'], 4) !== 0) {
                throw ValidationException::withMessages(['region_targets' => 'Target periode terakhir harus sama dengan target akhir.']);
            }
        } unset($r);
        $id = DB::transaction(function () use ($data, $user, $id) {
            $old = $id ? DB::table('activities')->lockForUpdate()->find($id) : null;
            if ($old && $old->version !== (int) $data['version']) {
                abort(409, 'Konfigurasi telah diubah. Muat ulang.');
            }
            if ($old?->status === 'AKTIF') {
                $errors = [];
                if (empty($data['periods'])) {
                    $errors['periods'] = 'Kegiatan aktif harus memiliki minimal satu periode evaluasi.';
                }
                if (empty($data['region_targets'])) {
                    $errors['region_targets'] = 'Kegiatan aktif harus memiliki minimal satu peserta.';
                }
                if ($errors) {
                    throw ValidationException::withMessages($errors);
                }
            }
            if ($old && in_array($old->status, ['SELESAI', 'ARSIP', 'BATAL'], true)) {
                $correctionOpen = DB::table('periods')->where('activity_id', $id)->whereNotNull('current_revision_id')->where('state', 'open')->exists();
                abort_unless($this->access->admin($user) && $correctionOpen, 423, 'Kegiatan ditutup. Admin dapat membuka evaluasi final untuk koreksi.');
            }
            if (DB::table('activities')->where('code', $data['code'])->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
                throw ValidationException::withMessages(['code' => 'Kode kegiatan sudah digunakan.']);
            }
            $fields = collect($data)->only(['code', 'title', 'team_id', 'owner_id', 'year', 'start_date', 'end_date', 'indicator', 'unit', 'value_type', 'precision'])->all();
            $fields['version'] = ($old->version ?? 0) + 1;
            $fields['updated_at'] = now();
            if ($old) {
                DB::table('activities')->where('id', $id)->update($fields);
            } else {
                $id = DB::table('activities')->insertGetId($fields + ['status' => 'DRAF', 'created_at' => now()]);
            }
            $oldPeriods = DB::table('periods')->where('activity_id', $id)->orderBy('position_date')->get();
            $hasReports = DB::table('realizations')->whereIn('period_id', $oldPeriods->pluck('id'))->exists() || DB::table('evaluation_revisions')->whereIn('period_id', $oldPeriods->pluck('id'))->exists();
            if ($hasReports && $oldPeriods->pluck('position_date')->all() !== array_column($data['periods'], 'position_date')) {
                throw ValidationException::withMessages(['periods' => 'Jadwal yang sudah memiliki data tidak dapat diganti.']);
            }
            if ($hasReports && ($old->value_type !== $data['value_type'] || $old->precision !== $data['precision'] || $old->unit !== $data['unit'])) {
                throw ValidationException::withMessages(['indicator' => 'Tipe, presisi, dan satuan tidak dapat diubah setelah input.']);
            }
            if (! $hasReports) {
                DB::table('periods')->where('activity_id', $id)->whereNotIn('position_date', array_column($data['periods'], 'position_date'))->delete();
            }
            $periodIds = [];
            foreach ($data['periods'] as $p) {
                $existing = $oldPeriods->firstWhere('position_date', $p['position_date']);
                $values = ['label' => $p['label'], 'due_at' => CarbonImmutable::parse($p['due_at'], 'Asia/Makassar')->utc()->format('Y-m-d H:i:s')];
                if ($existing) {
                    if ($existing->current_revision_id && ($values['due_at'] !== $existing->due_at || $values['label'] !== $existing->label)) {
                        throw ValidationException::withMessages(['periods' => 'Metadata evaluasi final tidak dapat diubah.']);
                    }
                    DB::table('periods')->where('id', $existing->id)->update($values);
                    $periodIds[] = $existing->id;
                } else {
                    $periodIds[] = DB::table('periods')->insertGetId($values + ['activity_id' => $id, 'position_date' => $p['position_date']]);
                }
            }
            $previousSet = DB::table('target_sets')->where('activity_id', $id)->orderByDesc('version')->first();
            $targets = $previousSet ? json_decode($previousSet->data, true) : [];
            $selected = array_column($data['region_targets'], 'region_id');
            DB::table('activity_regions')->where('activity_id', $id)->whereNotIn('region_id', $selected)->whereNull('left_on')->update(['left_on' => now('Asia/Makassar')->toDateString()]);
            foreach ($data['region_targets'] as $r) {
                $member = DB::table('activity_regions')->where('activity_id', $id)->where('region_id', $r['region_id'])->first();
                if ($member?->left_on) {
                    throw ValidationException::withMessages(['region_targets' => 'Peserta yang sudah keluar tidak dapat ditambahkan ulang.']);
                }
                if (! $member) {
                    DB::table('activity_regions')->insert(['activity_id' => $id, 'region_id' => $r['region_id'], 'joined_on' => $old && $old->status === 'AKTIF' ? now('Asia/Makassar')->toDateString() : $data['start_date']]);
                } elseif ($old?->status === 'DRAF' && ! $hasReports) {
                    DB::table('activity_regions')->where('id', $member->id)->update(['joined_on' => $data['start_date']]);
                }
                $targets[$r['region_id']] = ['final' => $r['final_target'], 'periods' => array_combine($periodIds, $r['targets']) ?: []];
            }
            $targetSetId = DB::table('target_sets')->insertGetId(['activity_id' => $id, 'version' => $fields['version'], 'data' => json_encode($targets, JSON_THROW_ON_ERROR), 'actor_id' => $user->id, 'reason' => $data['reason'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
            $projections = [];
            foreach ($targets as $regionId => $targetData) {
                foreach ($targetData['periods'] as $periodId => $value) {
                    if (in_array((int) $periodId, $periodIds, true)) {
                        $projections[] = ['target_set_id' => $targetSetId, 'period_id' => $periodId, 'region_id' => $regionId, 'target' => $value, 'final_target' => $targetData['final']];
                    }
                }
            }
            foreach (array_chunk($projections, 500) as $chunk) {
                DB::table('target_values')->insert($chunk);
            }
            DB::table('activities')->where('id', $id)->update(['current_target_set_id' => $targetSetId]);
            Audit::record($user, $old ? 'activity.updated' : 'activity.created', $id, null, null, $old, $fields, $data['reason'] ?? null);

            return $id;
        }, 3);

        return $this->detail($user, $id);
    }

    public function detail(User $user, int $id, bool $official = true): array
    {
        $activity = $this->find($user, $id);
        $periods = DB::table('periods')->where('activity_id', $id)->orderBy('position_date')->get();
        $set = DB::table('target_sets')->where('activity_id', $id)->orderByDesc('version')->first();
        $targets = $set ? json_decode($set->data, true) : [];
        $members = DB::table('activity_regions')->join('regions', 'regions.id', '=', 'activity_regions.region_id')->where('activity_id', $id)->select('regions.*', 'activity_regions.joined_on', 'activity_regions.left_on')->get();
        $regionScope = $this->access->regionIds($user, $activity);
        if ($regionScope !== null) {
            $members = $members->whereIn('id', $regionScope);
        }
        $reports = DB::table('realizations')->whereIn('period_id', $periods->pluck('id'))->get()->keyBy(fn ($r) => $r->period_id.':'.$r->region_id);
        $revisions = DB::table('evaluation_revisions')->whereIn('id', $periods->pluck('current_revision_id')->filter())->get()->keyBy('id');
        $rows = [];
        $today = now('Asia/Makassar')->toDateString();
        foreach ($members as $member) {
            $target = $targets[$member->id] ?? null;
            if (! $target) {
                continue;
            }
            $cells = [];
            $latestValue = null;
            foreach ($periods as $period) {
                $report = $reports->get($period->id.':'.$member->id);
                $value = $report?->value === null ? null : bcadd((string) $report->value, '0', 4);
                $eligible = $period->position_date >= $member->joined_on && (! $member->left_on || $period->position_date < $member->left_on) && (! $activity->cancelled_on || $period->position_date < $activity->cancelled_on);
                $snapshot = $period->current_revision_id ? json_decode($revisions[$period->current_revision_id]->snapshot, true) : null;
                if ($snapshot) {
                    $eligible = isset($snapshot['cells'][$member->id]) && $snapshot['cells'][$member->id]['status'] !== 'TIDAK_DINILAI';
                }
                $cell = $this->calculator->calculate($value, $target['periods'][$period->id] ?? '0', $target['final'], $period->position_date <= $today, $eligible);
                $cell += ['period_id' => $period->id, 'version' => $report?->version ?? 0, 'notes' => $report?->notes ?? '', 'isLate' => $report?->first_submitted_at ? $report->first_submitted_at > $period->due_at : false, 'updated_at' => $report?->updated_at];
                if ($official && $period->current_revision_id) {
                    if (isset($snapshot['cells'][$member->id])) {
                        $officialCell = $snapshot['cells'][$member->id];
                        $cell['provisional'] = $cell;
                        $cell = array_merge($cell, $officialCell);
                    } else {
                        $cell['status'] = 'TIDAK_DINILAI';
                    }
                }
                if ($eligible && $period->position_date <= $today && $cell['value'] !== null) {
                    $latestValue = $cell['value'];
                }
                $cells[] = $cell;
            }
            $progress = $this->calculator->calculate($latestValue, '1', $target['final'], true)['progress'];
            $rows[] = ['id' => $member->id, 'code' => $member->code, 'name' => $member->name, 'final_target' => $target['final'], 'cells' => $cells, 'progress' => $progress, 'joined_on' => $member->joined_on, 'left_on' => $member->left_on];
        }
        $periodData = $periods->map(function ($p) use ($revisions) {
            $r = $p->current_revision_id ? $revisions->get($p->current_revision_id) : null;

            return (array) $p + ['revision' => $r?->revision];
        })->all();
        $activityData = (array) $activity;
        $activityData['team'] = DB::table('teams')->where('id', $activity->team_id)->value('name');
        $activityData['pjk'] = DB::table('users')->where('id', $activity->owner_id)->value('name');
        $activityData['periods'] = $periodData;

        return ['activity' => $activityData, 'periods' => $periodData, 'rows' => $rows, 'can_write' => $this->access->allowed($user, $activity, 'write'), 'can_manage' => $this->access->allowed($user, $activity, 'manage'), 'can_reopen' => $this->access->admin($user)];
    }

    public function transition(User $user, int $id, string $status, int $version, ?string $reason): array
    {
        DB::transaction(function () use ($user, $id, $status, $version, $reason) {
            $a = DB::table('activities')->lockForUpdate()->find($id);
            abort_unless($a, 404);
            abort_unless($this->access->allowed($user, $a, 'manage'), 403);
            abort_if($a->version !== $version, 409, 'Konfigurasi telah berubah.');
            $allowed = ['DRAF' => ['AKTIF', 'BATAL'], 'AKTIF' => ['SELESAI', 'BATAL'], 'SELESAI' => ['ARSIP'], 'BATAL' => ['ARSIP'], 'ARSIP' => []];
            abort_unless(in_array($status, $allowed[$a->status], true), 422, 'Transisi status tidak valid.');
            if ($status === 'AKTIF') {
                abort_unless(DB::table('periods')->where('activity_id', $id)->exists() && DB::table('activity_regions')->where('activity_id', $id)->whereNull('left_on')->exists(), 422, 'Lengkapi periode dan peserta.');
            }
            if ($status === 'SELESAI') {
                abort_if(DB::table('periods')->where('activity_id', $id)->where('state', '!=', 'final')->exists(), 422, 'Finalkan semua periode sebelum menutup kegiatan.');
            }
            if ($status === 'BATAL') {
                abort_unless(strlen(trim($reason ?? '')) >= 5, 422, 'Alasan pembatalan wajib.');
                DB::table('activities')->where('id', $id)->update(['cancelled_on' => $a->status === 'DRAF' ? $a->start_date : now('Asia/Makassar')->toDateString()]);
            }
            DB::table('activities')->where('id', $id)->update(['status' => $status, 'version' => $version + 1, 'updated_at' => now()]);
            Audit::record($user, 'activity.status', $id, null, null, ['status' => $a->status], ['status' => $status], $reason);
        }, 3);

        return $this->detail($user, $id);
    }
}
