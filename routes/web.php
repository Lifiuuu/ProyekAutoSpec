<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth');
});

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/auth-test', function () {
    return view('auth');
});

Route::get('/register', function () {
    return view('auth-register');
});
