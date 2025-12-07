<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Ruta requerida por Sanctum
Route::get('/web-login', function () {
    return response()->json(['message' => 'Unauthenticated. Use /api/login for API authentication.'], 401);
})->name('login');
