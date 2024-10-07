<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\TopicController;
use App\Http\Controllers\API\CategoryController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(AuthController::class)->group(function () {
    Route::post('/register-user', 'userRegisterSave')->name('register.user.save');
    Route::post('/register-mahasiswa', 'mahasiswaRegisterSave')->name('register.mahasiswa.save');
    Route::post('/login', 'userloginAction')->name('user.login');
    // 
    // Route::get('/auth/google', 'redirectToGoogle')->name('auth.google.redirect');
    // Route::get('/auth/google/callback', 'handleGoogleCallback')->name('auth.google.callback');
});

Route::controller(CategoryController::class)->group(function () {
    Route::get('/categories', 'index')->name('categories.index'); 
    Route::get('/categories/{id}', 'show')->name('categories.show'); 
    Route::post('/categories', 'store')->name('categories.store'); 
    Route::put('/categories/{id}', 'update')->name('categories.update'); 
    Route::delete('/categories/{id}','destroy')->name('categories.destroy'); 
});

Route::controller(TopicController::class)->group(function () {
    Route::get('/topics', 'index')->name('topics.index'); 
    Route::get('/topics/{id}', 'show')->name('topics.show'); 
    Route::post('/topics', 'store')->name('topics.store'); 
    Route::put('/topics/{id}', 'update')->name('topics.update'); 
    Route::delete('/topics/{id}','destroy')->name('topics.destroy'); 
});
