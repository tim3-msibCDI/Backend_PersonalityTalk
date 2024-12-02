<?php

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MitraController;
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
use App\Http\Controllers\API\ActivityHistoryController;
use App\Http\Controllers\API\ArticleCategoryController;
use App\Http\Controllers\API\PsikologCategoryController;
use App\Http\Controllers\API\PsikologScheduleController;
use App\Http\Controllers\API\ConsultationTransactionController;

/**
 * Authentication biasa
 */
Route::controller(AuthController::class)->group(function () {
    Route::post('/user/register', 'registerUser')->name('register.save');
    Route::post('/user/login', 'userloginAction')->name('user.login');
});

// Handle login dengan google
Route::middleware('web')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('/oauth/google', 'redirectToGoogle')->name('auth.google.redirect');
        Route::get('/oauth/google/callback', 'handleGoogleCallback')->name('auth.google.callback');
    });
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

    // User Profile 
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

    // User Consultation API
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

    // Psikolog Review
    Route::controller(PsikologReviewController::class)->group(function () {
        Route::get('/consultation/detail-psikolog-before-review', 'detailPsikologBeforeReview');
        Route::post('/consultation/submit-review', 'submitReview');
    });

    // Kelola Transaksi Konsultasi
    Route::controller(ConsultationController::class)->group(function () {
        Route::post('/transactions/{transactionId}/approve-payment', 'approvePaymentProof');
        Route::post('/transactions/{transactionId}/disapprove-payment', 'disapprovePaymentProof');    
    });

    // Riwayat Konsultasi dan Transaksi Pengguna
    Route::controller(ActivityHistoryController::class)->group(function () {
        Route::get('/history/consultation', 'listConsultationHistory');
        Route::get('/history/consultation/transaction', 'listConsulTransactionHistory');
        Route::get('/history/consultation/transaction/{transactionId}', 'detailConsulTransaction');
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

/*
 * Bisa diakses oleh Psikolog dan User Biasa
 *
 */
Route::middleware(['auth:sanctum', 'role:P,U,M'])->group(function () {
    
}); 

/**
 * Bisa diakses oleh Admin 
 */
Route::middleware('auth:sanctum', 'admin')->group(function () {
    Route::post('/admin/logout', [AdminAuthController::class, 'logoutAdmin']);

    // Admin Profile
    Route::controller(AdminProfileController::class)->group(function () {
        Route::get('/admin/info', 'getAdminInfo');
        Route::get('/admin/profile/detail', 'getAdminProfile');
        Route::put('/admin/profile/update', 'updateAdminProfile');
        Route::put('/admin/profile/updatePassword', 'updateAdminPassword');
    });

    // Manage User Umum
    Route::controller(ManageUserController::class)->group(function () {
        Route::get('/admin/users', 'listUserUmum');
        Route::get('/admin/users/{id}', 'detailUserUmum');
        Route::post('/admin/users', 'storeUserUmum');
        Route::post('/admin/users/{id}', 'updateUserUmum');
        Route::delete('/admin/users/{id}', 'destroyUserUmum');    
    });

    // Manage User Mahasiswa
    Route::controller(ManageUserController::class)->group(function () {
        Route::get('/admin/mahasiswa', 'listUserMahasiswa');
        Route::get('/admin/mahasiswa/{id}', 'detailUserMahasiswa');
        Route::post('/admin/mahasiswa', 'storeUserMahasiswa');
        Route::post('/admin/mahasiswa/{id}', 'updateUserMahasiswa');
        Route::delete('/admin/mahasiswa/{id}', 'destroyUserMahasiswa');    
    });

    // Manage User Psikolog
    Route::controller(ManageUserController::class)->group(function () {
        Route::get('/admin/psikolog', 'listUserPsikolog');
        Route::get('/admin/psikolog/{id}', 'detailUserPsikolog');
        Route::post('/admin/psikolog/{id}', 'updateUserPsikolog');
        Route::delete('/admin/psikolog/{id}', 'destroyUserPsikolog');    
    });

    // Manage User Konselor
    Route::controller(ManageUserController::class)->group(function () {
        Route::get('/admin/konselor', 'listUserKonselor');
        Route::get('/admin/konselor/{id}', 'detailUserKonselor');
        Route::post('/admin/konselor/{id}', 'updateUserPsikolog');
        Route::delete('/admin/konselor/{id}', 'destroyUserPsikolog');    
    }); 

    // Manage Pendaftaran Psikolog
    Route::controller(ManagePsikologController::class)->group(function () {
        Route::get('/admin/psikolog-regis', 'listPsikologRegistrant');
        Route::get('/admin/psikolog-regis/{id_psikolog}', 'detailPsikolog');
        Route::post('/admin/psikolog-regis/{id_psikolog}/approve', 'approvePsikolog'); 
        Route::post('/admin/psikolog-regis/{id_psikolog}/reject', 'rejectPsikolog');
    });

    // Kelola Topik Konsultasi
    Route::controller(ConsulTopicController::class)->group(function () {
        Route::get('/admin/topics', 'index')->name('topics.index'); 
        Route::get('/admin/topics/{id}', 'show')->name('topics.show'); 
        Route::post('/admin/topics', 'store')->name('topics.store'); 
        Route::put('/admin/topics/{id}', 'update')->name('topics.update'); 
        Route::delete('/admin/topics/{id}','destroy')->name('topics.destroy'); 
    });

    // Kelola Jadwal Konsultasi
    Route::controller(PsikologScheduleController::class)->group(function () {
        Route::get('/admin/consul-schedules/psikolog', 'listPsikolog');
        Route::get('/admin/consul-schedules/psikolog/{psikologId}', 'detailPsikologSchedule');
        Route::put('/admin/consul-schedules/{scheduleId}/update-availability', 'updateAvailability');

    });

    //Kelola riwayat konsultasi
    Route::controller(ConsultationController::class)->group(function () {
        Route::get('/admin/consultation/history', 'consultationHistory');
        Route::get('/admin/consultation/{consultationId}/rating', 'detailConsultationRating');

    });

    // Kelola Transaksi Konsultasi
    Route::controller(ConsultationTransactionController::class)->group(function () {
        Route::get('/admin/consultation/transactions', 'listConsulTransaction'); 
        Route::post('/admin/consultation/transactions/approve/{transactionId}', 'approvePaymentProof'); 
        Route::post('/admin/consultation/transactions/reject/{transactionId}', 'rejectPaymentProof');
    });

    // Kelola Kategori Artikel
    Route::controller(ArticleCategoryController::class)->group(function () {
        Route::get('/admin/article/categories', 'index'); 
        Route::post('/admin/article/categories', 'store'); 
        Route::delete('/admin/article/categories/{id}','destroy'); 
    });

    // Kelola Artikel
    Route::controller(ArticleController::class)->group(function () {
        Route::get('/admin/articles', 'listAdminArticle')->name('articles.index'); 
        Route::get('/admin/articles/{id}', 'show')->name('articles.show'); 
        Route::post('/admin/articles', 'store')->name('articles.store'); 
        Route::post('/admin/articles/{id}', 'update')->name('articles.update'); 
        Route::delete('/admin/articles/{id}','destroy')->name('articles.destroy'); 
    });

    // Kelola Informasi Kesehatan Mental
    Route::controller(DiseaseController::class)->group(function () {
        Route::get('/admin/diseases', 'listAdminDisease')->name('diseases.index'); 
        Route::get('/admin/diseases/{id}', 'show')->name('diseases.show'); 
        Route::post('/admin/diseases', 'store')->name('diseases.store'); 
        Route::post('/admin/diseases/{id}', 'update')->name('diseases.update'); 
        Route::delete('/admin/diseases/{id}','destroy')->name('diseases.destroy'); 
    }); 

    // Kelola Voucher
    Route::controller(VoucherController::class)->group(function () {
        Route::get('/admin/vouchers', 'index'); 
        Route::get('/admin/vouchers/{id}', 'show'); 
        Route::post('/admin/vouchers', 'store'); 
        Route::post('/admin/vouchers/{id}', 'update'); 
        Route::delete('/admin/vouchers/{id}','destroy'); 
    });

    Route::controller(MitraController::class)->group(function () {
        Route::get('/admin/mitra', 'index');
        Route::get('/admin/mitra/{id}', 'show');
        Route::post('/admin/mitra', 'store');
        Route::post('/admin/mitra/{id}', 'update');
        Route::delete('/admin/mitra/{id}', 'destroy');
    });

    // Kelola Metode Pembayaran
    Route::controller(PaymentMethodController::class)->group(function () {
        Route::get('/admin/payment-methods', 'index'); 
        Route::get('/admin/payment-methods/{id}', 'show'); 
        Route::post('/admin/payment-methods', 'store'); 
        Route::post('/admin/payment-methods/{id}', 'update'); 
        Route::delete('/admin/payment-methods/{id}','destroy');
    });

    // Kelola Psikolog Price
    Route::controller(PsikologPriceController::class)->group(function () {
        Route::get('/admin/psikolog-price', 'index')->name('psikolog.price.index'); 
        Route::get('/admin/psikolog-price/{id}', 'show')->name('psikolog.price.show'); 
        Route::post('/admin/psikolog-price', 'store')->name('psikolog.price.store'); 
        Route::put('/admin/psikolog-price/{id}', 'update')->name('psikolog.price.update'); 
        Route::delete('/admin/psikolog-price/{id}','destroy')->name('psikolog.price.destroy'); 
    });
    Route::controller(PsikologCategoryController::class)->group(function () {
        Route::get('/admin/piskolog/categories', 'index')->name('psikolog.categories.index'); 
        Route::get('/admin/piskolog/categories/{id}', 'show')->name('psikolog.categories.show'); 
        Route::post('/admin/piskolog/categories', 'store')->name('psikolog.categories.store'); 
        Route::put('/admin/piskolog/categories/{id}', 'update')->name('psikolog.categories.update'); 
        Route::delete('/admin/piskolog/categories/{id}','destroy')->name('psikolog.categories.destroy'); 
    });
    
});

/**
 * Tidak perlu Login
 */
Route::controller(ArticleController::class)->group(function () {
    Route::get('/articles/categories', 'listCategoryArticle'); 
    Route::get('/articles', 'listUserArticle'); 
    Route::get('/articles/{id}', 'showArticleWithRelated');
    Route::get('/writer/{id}/articles', 'getArticlesByAdmin');
});

Route::controller(DiseaseController::class)->group(function () {
    Route::get('/diseases', 'listUserDisease')->name('user.diseases.index'); 
    Route::get('/diseases/{id}', 'showDiseaseDetail')->name('user.diseases.show');
});


