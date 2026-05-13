<?php

declare(strict_types=1);

use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/scan/{ulid}', [ScanController::class, 'show'])
    ->where('ulid', '[0-9A-Za-z]{26}')
    ->name('scan.asset');
