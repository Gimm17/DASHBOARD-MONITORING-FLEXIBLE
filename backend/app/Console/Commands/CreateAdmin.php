<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'dmf:admin {email} {--name=Administrator Provinsi}';

    protected $description = 'Provision an administrator without a default or printed password';

    public function handle(): int
    {
        $password = getenv('DMF_ADMIN_PASSWORD') ?: $this->secret('Kata sandi admin (minimal 12 karakter, kombinasi huruf, angka, simbol)');
        $v = Validator::make(['email' => $this->argument('email'), 'name' => $this->option('name'), 'password' => $password], ['email' => 'required|email|max:255|unique:users,email', 'name' => 'required|string|max:160', 'password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()->symbols()]]);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) {
                $this->error($e);
            }

            return self::FAILURE;
        }
        DB::transaction(function () use ($password) {
            $u = User::create(['name' => $this->option('name'), 'email' => $this->argument('email'), 'password' => $password]);
            DB::table('role_assignments')->insert(['user_id' => $u->id, 'role' => 'admin', 'scope_type' => 'province', 'scope_id' => 0]);
        });
        $this->info('Admin berhasil dibuat. Kata sandi tidak dicetak.');

        return self::SUCCESS;
    }
}
