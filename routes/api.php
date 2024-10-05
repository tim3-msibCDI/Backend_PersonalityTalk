<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'registerSave')->name('register-save');
    Route::post('/login', 'loginAction')->name('login-action');
    Route::get('/auth/google', 'redirectToGoogle')->name('auth.google.redirect');
    Route::get('/auth/google/callback', 'handleGoogleCallback')->name('auth.google.callback');
});