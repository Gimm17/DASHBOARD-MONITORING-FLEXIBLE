<?php

namespace App\Domain;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class Dashboard
{
    public function __construct(private Access $access) {}

    public function units(User $user, array $filters): Builder
    {
        $a = $this->access->activities($user)->select('id');
        $q = DB::table('periods as p')->join('activities as a', 'a.id', '=', 'p.activity_id')->join('activity_regions as ar', 'ar.activity_id', '=', 'a.id')->join('regions as r', 'r.id', '=', 'ar.region_id')
            ->join('target_values as t', fn ($j) => $j->on('t.target_set_id', '=', 'a.current_target_set_id')->on('t.period_id', '=', 'p.id')->on('t.region_id', '=', 'r.id'))
            ->leftJoin('realizations as v', fn ($j) => $j->on('v.period_id', '=', 'p.id')->on('v.region_id', '=', 'r.id'))
            ->leftJoin('evaluation_items as e', fn ($j) => $j->on('e.revision_id', '=', 'p.current_revision_id')->on('e.region_id', '=', 'r.id'))
            ->whereIn('a.id', $a)->where('a.status', '!=', 'DRAF')->where('p.position_date', '<=', now('Asia/Makassar')->toDateString())
            ->where(fn ($q) => $q->whereNotNull('e.id')->orWhere(function ($live) {
                $live->whereColumn('p.position_date', '>=', 'ar.joined_on')->where(fn ($p) => $p->whereNull('ar.left_on')->orWhereColumn('p.position_date', '<', 'ar.left_on'))->where(fn ($p) => $p->whereNull('a.cancelled_on')->orWhereColumn('p.position_date', '<', 'a.cancelled_on'));
            }))
            ->where(fn ($q) => $q->whereNull('p.current_revision_id')->orWhereNotNull('e.id'));
        if (! $this->access->admin($user)) {
            $q->where(function ($scope) use ($user) {
                $scope->whereRaw('1=0');
                foreach ($this->access->assignments($user) as $grant) {
                    if ($grant->role === 'pimpinan' && $grant->scope_type === 'province') {
                        $scope->orWhereRaw('1=1');
                    } elseif (in_array($grant->role, ['pjk', 'operator'], true) && in_array($grant->scope_type, ['team', 'activity'], true)) {
                        $scope->orWhere($grant->scope_type === 'team' ? 'a.team_id' : 'a.id', $grant->scope_id);
                    } elseif ($grant->role === 'wilayah' && $grant->scope_type === 'region') {
                        $scope->orWhere('r.id', $grant->scope_id);
                    }
                }
            });
        }
        foreach (['year' => 'a.year', 'team_id' => 'a.team_id', 'activity_id' => 'a.id', 'region_id' => 'r.id', 'period_id' => 'p.id'] as $key => $column) {
            if (isset($filters[$key])) {
                $q->where($column, $filters[$key]);
            }
        }
        $value = 'CASE WHEN e.id IS NOT NULL THEN e.value ELSE v.value END';
        $target = 'CASE WHEN e.id IS NOT NULL THEN e.target ELSE t.target END';
        $status = "CASE WHEN e.id IS NOT NULL THEN e.status WHEN v.value IS NULL THEN 'BELUM_ADA_DATA' WHEN v.value >= t.target THEN 'TERCAPAI' ELSE 'DI_BAWAH_TARGET' END";
        $q->whereRaw("($status) <> 'TIDAK_DINILAI'");
        if (! empty($filters['status'])) {
            $q->whereRaw("($status) = ?", [$filters['status']]);
        }

        return $q->select('a.id as activity_id', 'a.title as activity_title', 'r.id as region_id', 'r.name as region', 'p.id as period_id', 'p.label as period', 'p.position_date', 'p.state', 'v.first_submitted_at')->selectRaw("$value as value, $target as target, $status as status");
    }

    public function get(User $user, array $filters): array
    {
        $units = $this->units($user, $filters);
        $dateExpression = DB::getDriverName() === 'sqlite' ? "date(first_submitted_at, '+8 hours')" : 'DATE(DATE_ADD(first_submitted_at, INTERVAL 8 HOUR))';
        $groups = DB::query()->fromSub(clone $units, 'u')->select('activity_id', 'position_date')->selectRaw("$dateExpression as submitted_date, COUNT(first_submitted_at) as submissions, COUNT(*) as total, SUM(CASE WHEN status = 'TERCAPAI' THEN 1 ELSE 0 END) as attained, SUM(CASE WHEN value IS NULL THEN 1 ELSE 0 END) as missing, SUM(CASE WHEN state <> 'final' THEN 1 ELSE 0 END) as provisional")->groupBy('activity_id', 'position_date')->groupByRaw($dateExpression)->get();
        $n = (int) $groups->sum('total');
        $met = (int) $groups->sum('attained');
        $missing = (int) $groups->sum('missing');
        $perActivity = $groups->groupBy('activity_id')->map(fn ($g) => (object) ['total' => $g->sum('total'), 'attained' => $g->sum('attained'), 'reported' => $g->sum('total') - $g->sum('missing')]);
        $activities = $this->access->activities($user)->join('teams', 'teams.id', '=', 'activities.team_id')->join('users', 'users.id', '=', 'activities.owner_id')->select('activities.*', 'teams.name as team', 'users.name as pjk');
        foreach (['year' => 'activities.year', 'team_id' => 'activities.team_id', 'activity_id' => 'activities.id'] as $key => $column) {
            if (isset($filters[$key])) {
                $activities->where($column, $filters[$key]);
            }
        }
        if (isset($filters['region_id'])) {
            $activities->whereIn('activities.id', DB::table('activity_regions')->select('activity_id')->where('region_id', $filters['region_id']));
        }
        if (! empty($filters['status'])) {
            $activities->whereIn('activities.id', $perActivity->keys());
        }
        $activities = $activities->orderByDesc('activities.id')->get()->map(function ($a) use ($perActivity) {
            $s = $perActivity->get($a->id);
            $total = (int) ($s->total ?? 0);

            return (array) $a + ['evaluation_count' => $total, 'reported' => (int) ($s->reported ?? 0), 'attained' => (int) ($s->attained ?? 0), 'coverageRate' => $total ? round($s->reported / $total * 100, 2) : null, 'capaianPeriode' => $total ? round($s->attained / $total * 100, 2) : null];
        });
        $trend = $groups->groupBy('position_date')->sortKeys()->map(fn ($g, $date) => ['date' => $date, 'total' => $g->sum('total'), 'reported' => $g->sum('total') - $g->sum('missing'), 'attained' => $g->sum('attained')])->values();
        $counts = $groups->whereNotNull('submitted_date')->groupBy('submitted_date')->map(fn ($g) => $g->sum('submissions'));
        $weekly = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now('Asia/Makassar')->subDays($i)->startOfDay();
            $weekly[] = ['date' => $date->toDateString(), 'label' => $date->locale('id')->isoFormat('ddd'), 'count' => (int) ($counts[$date->toDateString()] ?? 0)];
        }
        $page = (int) ($filters['page'] ?? 1);
        $skip = ($page - 1) * 50;
        $records = collect();
        foreach ($activities->sortBy('id') as $a) {
            $count = (int) ($perActivity->get($a['id'])->total ?? 0);
            if ($skip >= $count) {
                $skip -= $count;

                continue;
            }if ($records->count() >= 50) {
                break;
            }$slice = (clone $units)->where('a.id', $a['id'])->orderBy('p.position_date')->orderBy('r.id')->offset($skip)->limit(50 - $records->count())->get();
            $records = $records->concat($slice);
            $skip = 0;
        }

        return ['activities' => $activities, 'summary' => ['active' => $activities->where('status', 'AKTIF')->count(), 'total' => $n, 'attained' => $met, 'missing' => $missing, 'below' => $n - $met - $missing, 'rate' => $n ? round($met / $n * 100, 2) : null, 'coverage' => $n ? round(($n - $missing) / $n * 100, 2) : null],
            'units' => $records->values(), 'unit_total' => $n, 'unit_page' => $page, 'unit_last_page' => max(1, (int) ceil($n / 50)),
            'trend' => $trend, 'weekly' => $weekly, 'filters' => $filters, 'calculated_at' => now()->toIso8601String(), 'timezone' => 'Asia/Makassar', 'is_provisional' => $groups->sum('provisional') > 0];
    }
}
