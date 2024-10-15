<?php

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\TopicController;
use App\Http\Controllers\API\ArticleController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\PsikologController;
use App\Http\Controllers\API\ConsultationController;
use App\Http\Controllers\API\PsikologPriceController;
use App\Http\Controllers\API\PsikologScheduleController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(AuthController::class)->group(function () {
    Route::post('/register/user', 'userRegisterSave')->name('register.user.save');
    Route::post('/register/mahasiswa', 'mahasiswaRegisterSave')->name('register.mahasiswa.save');
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

Route::controller(PsikologPriceController::class)->group(function () {
    Route::get('/psikolog-price', 'index')->name('psikolog.price.index'); 
    Route::get('/psikolog-price/{id}', 'show')->name('psikolog.price.show'); 
    Route::post('/psikolog-price', 'store')->name('psikolog.price.store'); 
    Route::put('/psikolog-price/{id}', 'update')->name('psikolog.price.update'); 
    Route::delete('/psikolog-price/{id}','destroy')->name('psikolog.price.destroy'); 
});

Route::controller(PsikologController::class)->group(function () {
    Route::post('/psikolog/register', 'psikologRegister')->name('psikolog.register'); 
});

Route::controller(PsikologScheduleController::class)->group(function () {
    Route::post('/psikolog/schedule/generate', 'generatePsikologSchedule');
});

Route::controller(ConsultationController::class)->group(function () {
    Route::get('/consultation/psikolog/topics', 'getPsikologTopics');
    Route::get('/consultation/psikolog/available', 'getAvailablePsikolog');
    Route::get('/consultation/psikolog/{id}/details-and-schedules', 'getPsikologDetailsAndSchedules');
});

Route::controller(AdminController::class)->group(function () {
    Route::post('/approve-psikolog/{id}', 'approvePsikolog')->name('approve.psikolog'); 
    Route::post('/reject-psikolog/{id}', 'rejectPsikolog')->name('reject.psikolog'); 
});

Route::controller(ArticleController::class)->group(function () {
    Route::get('/articles', 'index')->name('articles.index'); 
    Route::get('/articles/{id}', 'show')->name('articles.show'); 
    Route::post('/articles', 'store')->name('articles.store'); 
    Route::put('/articles/{id}', 'update')->name('articles.update'); 
    Route::delete('/articles/{id}','destroy')->name('articles.destroy'); 
});