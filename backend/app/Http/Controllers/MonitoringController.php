<?php

namespace App\Http\Controllers;

use App\Domain\Access;
use App\Domain\Activities;
use App\Domain\Dashboard;
use App\Domain\Reporting;
use App\Domain\ScheduleGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function __construct(private Activities $activities, private Access $access) {}

    public function dashboard(Request $r, Dashboard $dashboard): array
    {
        return $dashboard->get($r->user(), $this->filters($r));
    }

    private function filters(Request $r): array
    {
        return $r->validate(['year' => 'sometimes|integer|between:2020,2100', 'team_id' => 'sometimes|integer', 'activity_id' => 'sometimes|integer', 'region_id' => 'sometimes|integer', 'period_id' => 'sometimes|integer', 'status' => 'sometimes|in:TERCAPAI,DI_BAWAH_TARGET,BELUM_ADA_DATA', 'page' => 'sometimes|integer|between:1,100000']);
    }

    public function show(Request $r, int $id): array
    {
        return $this->activities->detail($r->user(), $id);
    }

    public function revisions(Request $r, int $id): array
    {
        $period = DB::table('periods')->find($id);
        abort_unless($period, 404);
        $a = $this->activities->find($r->user(), $period->activity_id);
        $regions = $this->access->regionIds($r->user(), $a);
        $rows = DB::table('evaluation_revisions')->join('users', 'users.id', '=', 'evaluation_revisions.actor_id')->where('period_id', $id)->select('evaluation_revisions.*', 'users.name as actor')->orderBy('revision')->get()->map(function ($row) use ($regions) {
            $data = (array) $row;
            $snapshot = json_decode($row->snapshot, true);
            if ($regions !== null) {
                $snapshot['cells'] = array_intersect_key($snapshot['cells'], array_flip($regions));
            }$data['snapshot'] = $snapshot;

            return $data;
        });

        return ['revisions' => $rows];
    }

    public function create(Request $r): mixed
    {
        return response()->json($this->activities->save($r->user(), $r->all()), 201);
    }

    public function update(Request $r, int $id): array
    {
        return $this->activities->save($r->user(), $r->all(), $id);
    }

    public function transition(Request $r, int $id): array
    {
        $d = $r->validate(['status' => 'required|in:AKTIF,SELESAI,ARSIP,BATAL', 'version' => 'required|integer|min:1', 'reason' => 'nullable|string|max:2000']);

        return $this->activities->transition($r->user(), $id, $d['status'], $d['version'], $d['reason'] ?? null);
    }

    public function batch(Request $r, int $id, Reporting $reporting): array
    {
        return $reporting->batch($r->user(), $id, $r->all(), $r->header('Idempotency-Key'));
    }

    public function finalize(Request $r, int $id, Reporting $reporting): mixed
    {
        $d = $r->validate(['version' => 'required|integer|min:0']);

        return response()->json($reporting->finalize($r->user(), $id, $d['version']), 201);
    }

    public function reopen(Request $r, int $id, Reporting $reporting): array
    {
        $d = $r->validate(['reason' => 'required|string|min:5|max:2000']);
        $reporting->reopen($r->user(), $id, $d['reason']);

        return ['ok' => true];
    }

    public function schedule(Request $r, ScheduleGenerator $g): array
    {
        $d = $r->validate(['start' => 'required|date_format:Y-m-d', 'end' => 'required|date_format:Y-m-d|after_or_equal:start', 'frequency' => 'required|in:daily,weekly,monthly,quarterly', 'day' => 'sometimes|integer|between:1,31', 'weekdays_only' => 'sometimes|boolean', 'excluded' => 'sometimes|array', 'excluded.*' => 'date_format:Y-m-d']);

        return ['dates' => $g->generate($d['start'], $d['end'], $d['frequency'], $d['day'] ?? 1, $d['weekdays_only'] ?? false, $d['excluded'] ?? [])];
    }

    public function logs(Request $r): array
    {
        $ids = $this->access->activities($r->user())->pluck('id');
        $q = DB::table('audit_logs')->join('users', 'users.id', '=', 'audit_logs.actor_id')->leftJoin('activities', 'activities.id', '=', 'audit_logs.activity_id')->leftJoin('regions', 'regions.id', '=', 'audit_logs.region_id')->select('audit_logs.*', 'users.name as operator', 'activities.title as activity_title', 'regions.name as region_name');
        $q->where(function ($q) use ($ids, $r) {
            $q->whereIn('audit_logs.activity_id', $ids);
            if ($this->access->admin($r->user())) {
                $q->orWhereNull('audit_logs.activity_id');
            }
        });
        // Filter each activity separately: a team grant must not widen an unrelated regional grant.
        if (! $this->access->admin($r->user())) {
            $q->where(function ($q) use ($r) {
                $q->whereRaw('1=0');
                foreach ($this->access->activities($r->user())->get() as $a) {
                    $regions = $this->access->regionIds($r->user(), $a);
                    $q->orWhere(function ($part) use ($a, $regions) {
                        $part->where('audit_logs.activity_id', $a->id);
                        if ($regions !== null) {
                            $part->whereIn('audit_logs.region_id', $regions);
                        }
                    });
                }
            });
        }

        return ['logs' => $q->orderByDesc('audit_logs.id')->paginate(50)];
    }

    public function tickets(Request $r): array
    {
        $records = [];
        foreach ($this->access->activities($r->user())->get() as $a) {
            $regions = $this->access->regionIds($r->user(), $a);
            $q = DB::table('correction_requests as c')->join('periods as p', 'p.id', '=', 'c.period_id')->join('regions as r', 'r.id', '=', 'c.region_id')->join('users as u', 'u.id', '=', 'c.actor_id')->where('p.activity_id', $a->id)->select('c.*', 'p.label as period_label', 'r.name as region_name', 'u.name as operator');
            if ($regions !== null) {
                $q->whereIn('c.region_id', $regions);
            }
            foreach ($q->orderByDesc('c.id')->get() as $t) {
                $records[] = (array) $t + ['activity_title' => $a->title, 'can_review' => $this->access->allowed($r->user(), $a, 'manage')];
            }
        }

        return ['tickets' => collect($records)->sortByDesc('id')->values()->take(500)->all()];
    }

    public function review(Request $r, int $id, Reporting $reporting): array
    {
        $d = $r->validate(['action' => 'required|in:approve,clarify,reject', 'message' => 'required|string|min:5|max:4000']);

        return $reporting->review($r->user(), $id, $d['action'], $d['message']);
    }

    public function export(Request $r, Dashboard $dashboard): mixed
    {
        $query = $dashboard->units($r->user(), $this->filters($r));

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Kegiatan', 'Wilayah', 'Periode', 'Posisi', 'Target', 'Realisasi', 'Status', 'Final'], ',', '"', '');
            foreach ($query->orderBy('p.position_date')->orderBy('a.id')->orderBy('r.id')->cursor() as $record) {
                $u = (array) $record;
                $safe = fn ($v) => preg_match('/^(?:[\x00-\x20]*[=+\-@]|[\t\r\n])/u', (string) $v) ? "'".$v : $v;
                fputcsv($out, array_map($safe, [$u['activity_title'], $u['region'], $u['period'], $u['position_date'], $u['target'], $u['value'], $u['status'], $u['state']]), ',', '"', '');
            }fclose($out);
        }, 'monitoring.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
