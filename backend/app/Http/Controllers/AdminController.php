<?php

namespace App\Http\Controllers;

use App\Domain\Access;
use App\Domain\Audit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    public function references(Request $r, Access $access): array
    {
        $data = [];
        foreach (['teams', 'regions', 'units'] as $kind) {
            $data[$kind] = DB::table($kind)->orderBy('name')->get();
        }
        $data['users'] = DB::table('users')->where('is_active', true)->select('id', 'name')->get();
        $data['assignments'] = $access->assignments($r->user());

        return $data;
    }

    public function master(Request $r, string $kind, Access $access): array
    {
        abort_unless($access->admin($r->user()), 403);
        abort_unless(in_array($kind, ['teams', 'regions', 'units'], true), 404);
        $d = $r->validate(['id' => 'nullable|integer', 'code' => ['required', 'string', 'max:40', Rule::unique($kind, 'code')->ignore($r->input('id'))], 'name' => 'required|string|max:160', 'is_active' => 'required|boolean']);
        DB::transaction(function () use ($r, $kind, $d) {
            $old = isset($d['id']) ? DB::table($kind)->find($d['id']) : null;
            if (isset($d['id'])) {
                abort_unless($old, 404);
            }$f = collect($d)->except('id')->all() + ['updated_at' => now()];
            if ($old) {
                DB::table($kind)->where('id', $old->id)->update($f);
            } else {
                DB::table($kind)->insert($f + ['created_at' => now()]);
            }Audit::record($r->user(), 'master.'.$kind, null, null, null, $old, $f);
        });

        return ['ok' => true];
    }

    public function users(Request $r, Access $access): array
    {
        abort_unless($access->admin($r->user()), 403);

        return ['users' => User::orderBy('name')->get()->map(fn ($u) => $u->toArray() + ['assignments' => $access->assignments($u)])];
    }

    public function saveUser(Request $r, Access $access): array
    {
        abort_unless($access->admin($r->user()), 403);
        $d = $r->validate(['id' => 'nullable|integer|exists:users,id', 'name' => 'required|string|max:160', 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($r->input('id'))], 'password' => [$r->input('id') ? 'nullable' : 'required', Password::min(12)->letters()->mixedCase()->numbers()->symbols()], 'is_active' => 'required|boolean', 'assignments' => 'required|array|min:1|max:50', 'assignments.*.role' => 'required|in:admin,pjk,operator,pimpinan,wilayah', 'assignments.*.scope_type' => 'required|in:province,team,activity,region', 'assignments.*.scope_id' => 'required|integer|min:0']);
        foreach ($d['assignments'] as $a) {
            $allowed = match ($a['role']) {
                'admin','pimpinan' => ['province'],'pjk','operator' => ['team', 'activity'],'wilayah' => ['region']
            };
            abort_unless(in_array($a['scope_type'], $allowed, true), 422, 'Peran dan cakupan tidak sesuai.');
            if ($a['scope_type'] === 'province') {
                abort_unless($a['scope_id'] === 0, 422);
            } else {
                abort_unless(DB::table(['team' => 'teams', 'activity' => 'activities', 'region' => 'regions'][$a['scope_type']])->where('id', $a['scope_id'])->exists(), 422, 'Cakupan tidak ditemukan.');
            }
        }
        $u = DB::transaction(function () use ($d, $r) {
            DB::table('system_locks')->where('name', 'admin-membership')->lockForUpdate()->first();
            $id = $d['id'] ?? null;
            $adminIds = DB::table('role_assignments')->where('role', 'admin')->where('scope_type', 'province')->where('scope_id', 0)->pluck('user_id');
            $admins = User::whereIn('id', $adminIds)->orderBy('id')->lockForUpdate()->get();
            $user = $id ? User::lockForUpdate()->findOrFail($id) : new User;
            $willBeAdmin = $d['is_active'] && collect($d['assignments'])->contains(fn ($a) => $a['role'] === 'admin');
            if ($id && $admins->contains('id', $id) && ! $willBeAdmin && $admins->where('is_active', true)->where('id', '!=', $id)->isEmpty()) {
                abort(422, 'Minimal satu admin aktif harus tersedia.');
            }
            $before = ['name' => $user->name, 'email' => $user->email, 'is_active' => $user->is_active];
            $user->forceFill(collect($d)->only(['name', 'email', 'is_active'])->all());
            if (! empty($d['password'])) {
                $user->password = $d['password'];
            }$user->save();
            DB::table('role_assignments')->where('user_id', $user->id)->delete();
            foreach (collect($d['assignments'])->unique(fn ($a) => implode(':', $a)) as $a) {
                DB::table('role_assignments')->insert($a + ['user_id' => $user->id]);
            }
            if (! $d['is_active'] || ! empty($d['password'])) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            Audit::record($r->user(), 'user.updated', null, null, null, $before, ['user_id' => $user->id, 'assignments' => $d['assignments'], 'is_active' => $d['is_active']]);

            return $user;
        });

        return ['user' => $u];
    }
}
