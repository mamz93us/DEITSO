<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| v1 routes for the mobile app and the future Windows agent live here.
| Sprint 0: only a health probe so the auto-discovery middleware groups
| have something to attach to.
|
*/

Route::middleware('auth:sanctum')->get('/user', fn (Request $request) => $request->user());

Route::prefix('v1')->group(function () {
    Route::get('/ping', fn () => response()->json(['ok' => true, 'time' => now()->toIso8601String()]));
});
