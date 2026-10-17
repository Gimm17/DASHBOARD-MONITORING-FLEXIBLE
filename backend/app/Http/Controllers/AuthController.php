<?php

namespace App\Http\Controllers;

use App\Domain\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function me(Request $request, Access $access): array
    {
        return ['user' => ['id' => $request->user()->id, 'name' => $request->user()->name, 'email' => $request->user()->email, 'assignments' => $access->assignments($request->user()), 'is_admin' => $access->admin($request->user())]];
    }

    public function login(Request $request, Access $access): array
    {
        $data = $request->validate(['email' => 'required|email|max:255', 'password' => 'required|string|max:255']);
        $key = 'login:'.Str::lower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            abort(429, 'Terlalu banyak percobaan. Coba lagi nanti.');
        }
        if (! Auth::attempt($data + ['is_active' => true])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi tidak sesuai.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return $this->me($request, $access) + ['csrf_token' => csrf_token()];
    }

    public function logout(Request $request): mixed
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
