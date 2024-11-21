<?php

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ArticleController;
use App\Http\Controllers\API\DiseaseController;
use App\Http\Controllers\API\VoucherController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\PsikologController;
use App\Http\Controllers\API\AdminAuthController;
use App\Http\Controllers\API\ManageUserController;
use App\Http\Controllers\API\ConsulTopicController;
use App\Http\Controllers\API\UserProfileController;
use App\Http\Controllers\API\AdminProfileController;
use App\Http\Controllers\API\ConsultationController;
use App\Http\Controllers\API\PaymentMethodController;
use App\Http\Controllers\API\PsikologPriceController;
use App\Http\Controllers\API\ForgotPasswordController;
use App\Http\Controllers\API\ManagePsikologController;
use App\Http\Controllers\API\PsikologReviewController;
use App\Http\Controllers\API\ArticleCategoryController;
use App\Http\Controllers\API\PsikologCategoryController;
use App\Http\Controllers\API\PsikologScheduleController;
use App\Http\Controllers\API\ConsultationTransactionController;

/**
 * Authentication
 */
Route::controller(AuthController::class)->group(function () {
    Route::post('/user/register', 'registerUser')->name('register.save');
    Route::post('/user/login', 'userloginAction')->name('user.login');
    // Route::get('/auth/google', 'redirectToGoogle')->name('auth.google.redirect');
    // Route::get('/auth/google/callback', 'handleGoogleCallback')->name('auth.google.callback');
});
Route::controller(PsikologController::class)->group(function () {
    Route::post('/psikolog/register', 'psikologRegister')->name('psikolog.register'); 
});
Route::controller(AdminAuthController::class)->group(function () {
    Route::post('/admin/login', 'loginAdmin')->name('admin.login');
});
Route::controller(ForgotPasswordController::class)->group(function () {
    Route::post('/password/reset/request', 'requestReset');
    Route::get('/password/reset/confirm', 'confirmReset');
    Route::post('/password/reset/change', 'resetAndChangePassword');
});

Route::middleware(['auth:sanctum', 'role:M,U,P'])->group(function () {
    Route::post('/user/logout', [AuthController::class, 'logoutAction'])->name('user.logout');

    Route::controller(UserProfileController::class)->group(function(){
        Route::get('/user/info', 'getUserInfo');
        Route::get('/user/profile/detail', 'getUserProfile');
        Route::put('/user/profile/update', 'updateProfile');
        Route::put('/user/profile/updatePassword', 'updatePassword');
        Route::put('/user/profile/updateMahasiswa', 'updateToMahasiswa');
    });
});

/**
 * Bisa diakses oleh Mahasiswa dan Umum
 */
Route::middleware(['auth:sanctum', 'role:M,U'])->group(function () {
    Route::controller(ConsultationController::class)->group(function () {
        Route::get('/consultation/psikolog/topics', 'getPsikologTopics');
        Route::get('/consultation/psikolog/available', 'getAvailablePsikologV2');
        Route::get('/consultation/psikolog/{id}/details-and-schedules', 'getPsikologDetailsAndSchedulesV2');
        Route::get('/consultation/payment-list', 'listUserPaymentMethod');
        Route::get('/consultation/preview-before-payment', 'getPreviewConsultation');
        Route::post('/consultation/create-transaction', 'createConsultationAndTransaction');
        Route::post('/consultation/submit-complaint', 'submitComplaint');
        Route::post('/consultation/upload-payment-proof', 'uploadPaymentProof');
    });

    Route::controller(PsikologReviewController::class)->group(function () {
        Route::get('/consultation/detail-psikolog-before-review', 'detailPsikologBeforeReview');
        Route::post('/consultation/submit-review', 'submitReview');
    });

    Route::controller(ConsultationController::class)->group(function () {
        Route::post('/transactions/{transactionId}/approve-payment', 'approvePaymentProof');
        Route::post('/transactions/{transactionId}/disapprove-payment', 'disapprovePaymentProof');    
    });

    Route::get('/consultation/voucher-redeem', [VoucherController::class, 'redeemConsultationVoucher']);
});

/**
 * Bisa diakses oleh Mahasiswa 
 */
Route::middleware(['auth:sanctum', 'role:M'])->group(function () {

});

/**
 * Bisa diakses oleh Psikolog 
 */
Route::middleware(['auth:sanctum', 'role:P'])->group(function () {
    Route::controller(PsikologScheduleController::class)->group(function () {
        Route::get('/psikolog/schedule/main-schedules', 'getMainSchedules');
        Route::get('/psikolog/schedule/selected-by-date', 'getSchedulesByDate');
        Route::post('/psikolog/schedule/generate', 'generatePsikologSchedule');
        Route::get('/psikolog/schedule/update', 'bulkUpdatePsikologSchedule');


    });
});

/**
 * Bisa diakses oleh Admin 
 */
Route::middleware('auth:sanctum', 'admin')->group(function () {
    Route::post('/admin/logout', [AdminAuthController::class, 'logoutAdmin']);

    Route::controller(AdminProfileController::class)->group(function () {
        Route::get('/admin/info', 'getAdminInfo');
        Route::get('/admin/profile/detail', 'getAdminProfile');
        Route::put('/admin/profile/update', 'updateAdminProfile');
        Route::put('/admin/profile/updatePassword', 'updateAdminPassword');
    });

    Route::controller(ManageUserController::class)->group(function () {
        Route::get('/admin/users', 'listUserUmum');
        Route::get('/admin/users/{id}', 'detailUserUmum');
        Route::post('/admin/users', 'storeUserUmum');
        Route::post('/admin/users/{id}', 'updateUserUmum');
        Route::delete('/admin/users/{id}', 'destroyUserUmum');    
    });

    Route::controller(ManageUserController::class)->group(function () {
        Route::get('/admin/mahasiswa', 'listUserMahasiswa');
        Route::get('/admin/mahasiswa/{id}', 'detailUserMahasiswa');
        Route::post('/admin/mahasiswa', 'storeUserMahasiswa');
        Route::post('/admin/mahasiswa/{id}', 'updateUserMahasiswa');
        Route::delete('/admin/mahasiswa/{id}', 'destroyUserMahasiswa');    
    });

    Route::controller(ManageUserController::class)->group(function () {
        Route::get('/admin/psikolog', 'listUserPsikolog');
        Route::get('/admin/psikolog/{id}', 'detailUserPsikolog');
        Route::post('/admin/psikolog/{id}', 'updateUserPsikolog');
        Route::delete('/admin/psikolog/{id}', 'destroyUserPsikolog');    
    });

    Route::controller(ManageUserController::class)->group(function () {
        Route::get('/admin/konselor', 'listUserKonselor');
        Route::get('/admin/konselor/{id}', 'detailUserKonselor');
        Route::post('/admin/konselor/{id}', 'updateUserPsikolog');
        Route::delete('/admin/konselor/{id}', 'destroyUserKonselor');    
    });

    Route::controller(ConsulTopicController::class)->group(function () {
        Route::get('/topics', 'index')->name('topics.index'); 
        Route::get('/topics/{id}', 'show')->name('topics.show'); 
        Route::post('/topics', 'store')->name('topics.store'); 
        Route::put('/topics/{id}', 'update')->name('topics.update'); 
        Route::delete('/topics/{id}','destroy')->name('topics.destroy'); 
    });

    Route::controller(ManagePsikologController::class)->group(function () {
        Route::get('/admin/psikolog-regis', 'listPsikologRegistrant');
        Route::get('/admin/psikolog-regis/{id}', 'detailPsikolog');
        Route::post('/admin/psikolog-regis/{id}/approve', 'approvePsikolog'); 
        Route::post('/admin/psikolog-regis/{id}/reject', 'rejectPsikolog');
    });

    Route::controller(ConsultationTransactionController::class)->group(function () {
        Route::get('consultation/transactions', 'listConsulTransaction'); 
        Route::post('consultation/transactions/approve/{transactionId}', 'approvePaymentProof'); 
        Route::post('consultation/transactions/reject/{transactionId}', 'rejectPaymentProof');
    });
    Route::controller(ArticleCategoryController::class)->group(function () {
        Route::get('/article/categories', 'index')->name('article.categories.index'); 
        Route::get('/article/categories/{id}', 'show')->name('article.categories.show'); 
        Route::post('/article/categories', 'store')->name('article.categories.store'); 
        Route::put('/article/categories/{id}', 'update')->name('article.categories.update'); 
        Route::delete('/article/categories/{id}','destroy')->name('article.categories.destroy'); 
    });
    Route::controller(ArticleController::class)->group(function () {
        Route::get('/admin/articles', 'listAdminArticle')->name('articles.index'); 
        Route::get('/admin/articles/{id}', 'show')->name('articles.show'); 
        Route::post('/admin/articles', 'store')->name('articles.store'); 
        Route::post('/admin/articles/{id}', 'update')->name('articles.update'); 
        Route::delete('/admin/articles/{id}','destroy')->name('articles.destroy'); 
    });
    Route::controller(DiseaseController::class)->group(function () {
        Route::get('/admin/diseases', 'listAdminDisease')->name('diseases.index'); 
        Route::get('/admin/diseases/{id}', 'show')->name('diseases.show'); 
        Route::post('/admin/diseases', 'store')->name('diseases.store'); 
        Route::post('/admin/diseases/{id}', 'update')->name('diseases.update'); 
        Route::delete('/admin/diseases/{id}','destroy')->name('diseases.destroy'); 
    });

    Route::controller(VoucherController::class)->group(function () {
        Route::get('/admin/vouchers', 'index'); 
        Route::get('/admin/vouchers/{id}', 'show'); 
        Route::post('/admin/vouchers', 'store'); 
        Route::post('/admin/vouchers/{id}', 'update'); 
        Route::delete('/admin/vouchers/{id}','destroy'); 
    });

    Route::controller(PaymentMethodController::class)->group(function () {
        Route::get('/admin/payment-methods', 'index'); 
        Route::get('/admin/payment-methods/{id}', 'show'); 
        Route::post('/admin/payment-methods', 'store'); 
        Route::post('/admin/payment-methods/{id}', 'update'); 
        Route::delete('/admin/payment-methods/{id}','destroy');
    });

    // Masukkan langsung ke atribut / Nggak perlu tabel
    Route::controller(PsikologPriceController::class)->group(function () {
        Route::get('/psikolog-price', 'index')->name('psikolog.price.index'); 
        Route::get('/psikolog-price/{id}', 'show')->name('psikolog.price.show'); 
        Route::post('/psikolog-price', 'store')->name('psikolog.price.store'); 
        Route::put('/psikolog-price/{id}', 'update')->name('psikolog.price.update'); 
        Route::delete('/psikolog-price/{id}','destroy')->name('psikolog.price.destroy'); 
    });
    Route::controller(PsikologCategoryController::class)->group(function () {
        Route::get('/piskolog/categories', 'index')->name('psikolog.categories.index'); 
        Route::get('/piskolog/categories/{id}', 'show')->name('psikolog.categories.show'); 
        Route::post('/piskolog/categories', 'store')->name('psikolog.categories.store'); 
        Route::put('/piskolog/categories/{id}', 'update')->name('psikolog.categories.update'); 
        Route::delete('/piskolog/categories/{id}','destroy')->name('psikolog.categories.destroy'); 
    });
    
});

/**
 * Tidak perlu Login
 */
Route::controller(ArticleController::class)->group(function () {
    Route::get('/articles/categories', 'listCategoryArticle')->name('user.articles.categories'); 
    Route::get('/articles', 'listUserArticle')->name('user.articles.index'); 
    Route::get('/articles/{id}', 'showArticleWithRelated')->name('user.articles.show');
    Route::get('/writer/{id}/articles', 'getArticlesByAdmin');
});

Route::controller(DiseaseController::class)->group(function () {
    Route::get('/diseases', 'listUserDisease')->name('user.diseases.index'); 
    Route::get('/diseases/{id}', 'showDiseaseDetail')->name('user.diseases.show');
});


