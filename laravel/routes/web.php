<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleController;

Route::get('/', function () {
    return view('welcome');
});

// Google OAuth routes
Route::get('auth/google/redirect', [GoogleController::class, 'redirect']);
Route::get('auth/google/callback', [GoogleController::class, 'callback']);
