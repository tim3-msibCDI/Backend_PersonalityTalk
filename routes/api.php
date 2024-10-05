<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(AuthController::class)->group(function () {
    Route::post('/register-user', 'userRegisterSave')->name('register-user-save');
    Route::post('/register-mahasiswa', 'mahasiswaRegisterSave')->name('register-mahasiswa-save');
    Route::post('/login', 'userloginAction')->name('login-action');
    Route::get('/auth/google', 'redirectToGoogle')->name('auth.google.redirect');
    Route::get('/auth/google/callback', 'handleGoogleCallback')->name('auth.google.callback');
});