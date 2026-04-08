<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/main-dashboard', function () {
    return view('main-dashboard');
});

// New Session - Reset to fresh dashboard
Route::get('/new-session', function () {
    return redirect('/main-dashboard');
});
