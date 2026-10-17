<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MonitoringController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::get('csrf', fn () => response()->json(['csrf_token' => csrf_token()]));
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:30,1');
    Route::middleware('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('dashboard', [MonitoringController::class, 'dashboard']);
        Route::get('export', [MonitoringController::class, 'export']);
        Route::get('references', [AdminController::class, 'references']);
        Route::post('masters/{kind}', [AdminController::class, 'master']);
        Route::get('users', [AdminController::class, 'users']);
        Route::post('users', [AdminController::class, 'saveUser']);
        Route::post('schedule', [MonitoringController::class, 'schedule']);
        Route::get('activities/{id}', [MonitoringController::class, 'show'])->whereNumber('id');
        Route::post('activities', [MonitoringController::class, 'create']);
        Route::put('activities/{id}', [MonitoringController::class, 'update'])->whereNumber('id');
        Route::post('activities/{id}/status', [MonitoringController::class, 'transition'])->whereNumber('id');
        Route::post('periods/{id}/realizations', [MonitoringController::class, 'batch'])->whereNumber('id');
        Route::post('periods/{id}/finalize', [MonitoringController::class, 'finalize'])->whereNumber('id');
        Route::post('periods/{id}/reopen', [MonitoringController::class, 'reopen'])->whereNumber('id');
        Route::get('periods/{id}/revisions', [MonitoringController::class, 'revisions'])->whereNumber('id');
        Route::get('tickets', [MonitoringController::class, 'tickets']);
        Route::post('tickets/{id}/review', [MonitoringController::class, 'review'])->whereNumber('id');
        Route::get('audit', [MonitoringController::class, 'logs']);
    });
    Route::any('{path}', fn () => abort(404))->where('path', '.*');
});
Route::get('/{path?}', function () {
    $file = public_path('spa/index.html');

    return is_file($file) ? response()->file($file, ['Cache-Control' => 'no-cache']) : response('Frontend belum dibangun. Jalankan npm run build dari akar proyek.', 503);
})->where('path', '^(?!api(?:/|$)).*');
