<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('main-dashboard');
});

Route::get('/main-dashboard', function () {
    return view('main-dashboard');
});

// New Session - Reset to fresh dashboard
Route::get('/new-session', function () {
    return redirect('/main-dashboard');
});

// Generation downloads for frontend dashboard
use App\Http\Controllers\GeneratorController;
Route::get('generations/download/{runId}/{type}', [GeneratorController::class, 'download'])->name('generations.download');
