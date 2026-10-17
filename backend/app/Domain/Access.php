<?php

namespace App\Domain;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Access
{
    public function assignments(User $user): Collection
    {
        return DB::table('role_assignments')->where('user_id', $user->id)->get();
    }

    public function admin(User $user): bool
    {
        return $this->assignments($user)->contains(fn ($r) => $r->role === 'admin' && $r->scope_type === 'province' && $r->scope_id === 0);
    }

    public function allowed(User $user, object $activity, string $operation = 'read'): bool
    {
        if (! $user->is_active) {
            return false;
        }
        if ($this->admin($user)) {
            return true;
        }

        return $this->assignments($user)->contains(function ($r) use ($activity, $operation) {
            if ($r->role === 'pimpinan' && $r->scope_type === 'province') {
                return $operation === 'read';
            }
            if ($r->role === 'wilayah' && $r->scope_type === 'region') {
                return $operation === 'read' && DB::table('activity_regions')->where('activity_id', $activity->id)->where('region_id', $r->scope_id)->exists();
            }
            $matches = ($r->scope_type === 'team' && $r->scope_id === $activity->team_id) || ($r->scope_type === 'activity' && $r->scope_id === $activity->id);

            return $matches && in_array($r->role, $operation === 'manage' ? ['pjk'] : ['pjk', 'operator'], true);
        });
    }

    public function activities(User $user): Builder
    {
        $query = DB::table('activities');
        if ($this->admin($user)) {
            return $query;
        }
        $roles = $this->assignments($user);
        if ($roles->contains(fn ($r) => $r->role === 'pimpinan' && $r->scope_type === 'province')) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($roles) {
            $q->whereRaw('1 = 0');
            foreach ($roles as $r) {
                if (in_array($r->role, ['operator', 'pjk'], true) && in_array($r->scope_type, ['team', 'activity'], true)) {
                    $q->orWhere($r->scope_type === 'team' ? 'activities.team_id' : 'activities.id', $r->scope_id);
                } elseif ($r->role === 'wilayah' && $r->scope_type === 'region') {
                    $q->orWhereIn('activities.id', DB::table('activity_regions')->select('activity_id')->where('region_id', $r->scope_id));
                }
            }
        });
    }

    public function regionIds(User $user, object $activity): ?array
    {
        if ($this->admin($user)) {
            return null;
        }
        $roles = $this->assignments($user);
        foreach ($roles as $r) {
            if ($r->role === 'pimpinan' && $r->scope_type === 'province') {
                return null;
            }
            if (in_array($r->role, ['operator', 'pjk'], true) && (($r->scope_type === 'team' && $r->scope_id === $activity->team_id) || ($r->scope_type === 'activity' && $r->scope_id === $activity->id))) {
                return null;
            }
        }

        return $roles->where('role', 'wilayah')->where('scope_type', 'region')->pluck('scope_id')->all();
    }
}
