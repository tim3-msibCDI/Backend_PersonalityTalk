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
use App\Http\Controllers\API\ArticleCategoryController;
use App\Http\Controllers\API\PsikologCategoryController;
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

Route::controller(PsikologCategoryController::class)->group(function () {
    Route::get('/piskolog/categories', 'index')->name('psikolog.categories.index'); 
    Route::get('/piskolog/categories/{id}', 'show')->name('psikolog.categories.show'); 
    Route::post('/piskolog/categories', 'store')->name('psikolog.categories.store'); 
    Route::put('/piskolog/categories/{id}', 'update')->name('psikolog.categories.update'); 
    Route::delete('/piskolog/categories/{id}','destroy')->name('psikolog.categories.destroy'); 
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

Route::controller(ArticleCategoryController::class)->group(function () {
    Route::get('/article/categories', 'index')->name('article.categories.index'); 
    Route::get('/article/categories/{id}', 'show')->name('article.categories.show'); 
    Route::post('/article/categories', 'store')->name('article.categories.store'); 
    Route::put('/article/categories/{id}', 'update')->name('article.categories.update'); 
    Route::delete('/article/categories/{id}','destroy')->name('article.categories.destroy'); 
});

Route::controller(ArticleController::class)->group(function () {
    Route::get('/admin/articles', 'indexAdmin')->name('articles.index'); 
    Route::get('/admin/articles/{id}', 'show')->name('articles.show'); 
    Route::post('/admin/articles', 'store')->name('articles.store'); 
    Route::post('/admin/articles/{id}', 'update')->name('articles.update'); 
    Route::delete('/admin/articles/{id}','destroy')->name('articles.destroy'); 
});

Route::controller(ArticleController::class)->group(function () {
    Route::get('/articles', 'indexUser')->name('user.articles.index'); 
    Route::get('/articles/{id}', 'showArticleWithRelated')->name('user.articles.show');
    Route::get('/writer/{id}/articles', 'getArticlesByAdmin');

});