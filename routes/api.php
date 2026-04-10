<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GeneratorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/generate', [GeneratorController::class, 'generate'])->name('api.generate');

// Return generation history for the authenticated user
Route::get('/generations/history', [GeneratorController::class, 'history'])->name('api.generations.history');

Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    // Legacy/non-OAuth Google endpoint (kept for backward compatibility)
    Route::post('/google/mock', [AuthController::class, 'google'])->name('google.mock');
    Route::post('/google', [AuthController::class, 'google'])->name('google.legacy');

    // OAuth flow endpoints
    Route::get('/google/start', [AuthController::class, 'googleRedirect'])->name('google.start');
    Route::get('/google/callback', [AuthController::class, 'googleCallback'])->name('google.callback');

    // Backward-compatible alias
    Route::get('/google/redirect', [AuthController::class, 'googleRedirect'])->name('google.redirect');
});
