<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PelangganAuthController;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\TambahDayaController;
use App\Http\Controllers\PelangganProfileController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\PembayaranController;

Route::get('/', function () {
    return view('landing');
})->name('landing');

// Global login route (required by Laravel auth middleware)
// Global login route
Route::get('/login', function () {
    return redirect()->route('pelanggan.login');
})->name('login');

Route::prefix('pelanggan')->name('pelanggan.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [PelangganAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [PelangganAuthController::class, 'login'])->name('login.submit'); // Explicit name
        Route::get('/register', [PelangganAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [PelangganAuthController::class, 'register'])->name('register.submit'); // Explicit name
        Route::get('/register/success', [PelangganAuthController::class, 'showRegisterPending'])->name('register.pending');

        // Forgot Password
        Route::get('/lupa-password', [App\Http\Controllers\Auth\ForgotPasswordRequestController::class, 'show'])->name('forgot-password');
        Route::middleware('throttle:3,10')->post('/lupa-password', [App\Http\Controllers\Auth\ForgotPasswordRequestController::class, 'store'])->name('forgot-password.store');
        
        // Verifications (Rate Limit: 10 per minute)
        Route::middleware('throttle:10,1')->group(function() {
            Route::post('/lupa-password/verify-email', [App\Http\Controllers\Auth\ForgotPasswordRequestController::class, 'verifyEmail'])->name('forgot-password.verify-email');
            Route::post('/lupa-password/verify-nik', [App\Http\Controllers\Auth\ForgotPasswordRequestController::class, 'verifyNik'])->name('forgot-password.verify-nik');
            Route::post('/lupa-password/verify-nama', [App\Http\Controllers\Auth\ForgotPasswordRequestController::class, 'verifyNama'])->name('forgot-password.verify-nama');
        });
    });

    Route::middleware(['auth', CheckRole::class . ':pelanggan'])->group(function () {
        Route::post('/logout', [PelangganAuthController::class, 'logout'])->name('logout');
        Route::get('/profil', [App\Http\Controllers\PelangganProfileController::class, 'show'])->name('profile');
    });
});

// Admin Helper Routes (Simple Blade implementation for now)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/permintaan-akun-pelanggan', [App\Http\Controllers\Admin\CustomerRequestController::class, 'index'])->name('requests.index');
    Route::get('/permintaan-akun-pelanggan/{id}', [App\Http\Controllers\Admin\CustomerRequestController::class, 'show'])->name('requests.show');
    Route::post('/permintaan-akun-pelanggan/{id}/approve', [App\Http\Controllers\Admin\CustomerRequestController::class, 'approve'])->name('requests.approve');
    Route::post('/permintaan-akun-pelanggan/{id}/reject', [App\Http\Controllers\Admin\CustomerRequestController::class, 'reject'])->name('requests.reject');

    // Password Reset Requests
    Route::get('/password-reset-requests', [App\Http\Controllers\Auth\ForgotPasswordRequestController::class, 'indexAdmin'])->name('password-reset-requests.index');
    Route::post('/password-reset-requests/{id}/approve', [App\Http\Controllers\Auth\ForgotPasswordRequestController::class, 'approve'])->name('password-reset-requests.approve');
    Route::post('/password-reset-requests/{id}/reject', [App\Http\Controllers\Auth\ForgotPasswordRequestController::class, 'reject'])->name('password-reset-requests.reject');
});

// Pegawai Authentication (Single Door Login for Internal Staff)
Route::prefix('pegawai')->name('pegawai.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [App\Http\Controllers\PegawaiAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [App\Http\Controllers\PegawaiAuthController::class, 'login'])->name('login.post');
    });
    
    Route::middleware('auth')->group(function () {
        Route::post('/logout', [App\Http\Controllers\PegawaiAuthController::class, 'logout'])->name('logout');
    });
});

// Monitoring & Pembayaran (Protected)
Route::middleware(['auth', 'customer.only'])->group(function () {
    Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran');
    
    // Protected Permohonan Forms - Wizard Tambah Daya
    Route::get('/pelanggan/tambah-daya/step1', [TambahDayaController::class, 'step1'])->name('tambah-daya.step1');
    Route::post('/pelanggan/tambah-daya/step1', [TambahDayaController::class, 'storeStep1'])->name('tambah-daya.step1.store');

    Route::get('/pelanggan/tambah-daya/step2', [TambahDayaController::class, 'step2'])->name('tambah-daya.step2');
    Route::post('/pelanggan/tambah-daya/step2', [TambahDayaController::class, 'storeStep2'])->name('tambah-daya.step2.store');
    
    // Draft functionality
    Route::post('/pelanggan/permohonan/{id}/autosave', [TambahDayaController::class, 'autosave'])->name('tambah-daya.autosave');
    Route::get('/pelanggan/permohonan/{id}/resume', [TambahDayaController::class, 'resume'])->name('tambah-daya.resume');
    Route::delete('/pelanggan/permohonan/{id}/cancel', [TambahDayaController::class, 'cancel'])->name('tambah-daya.cancel');
    
    
    Route::get('/pelanggan/tambah-daya/step3', [TambahDayaController::class, 'step3'])->name('tambah-daya.step3');
    Route::post('/pelanggan/tambah-daya/step3', [TambahDayaController::class, 'storeStep3'])->name('tambah-daya.step3.store');
    Route::post('/pelanggan/tambah-daya/check-nik', [TambahDayaController::class, 'checkNik'])->name('tambah-daya.check-nik');

    // Step 4: Data SLO
    Route::get('/pelanggan/tambah-daya/step4', [TambahDayaController::class, 'step4'])->name('tambah-daya.step4');
    Route::post('/pelanggan/tambah-daya/step4', [TambahDayaController::class, 'storeStep4'])->name('tambah-daya.step4.store');
    Route::post('/pelanggan/tambah-daya/check-slo', [TambahDayaController::class, 'checkSlo'])->name('tambah-daya.check-slo');

    // Step 5: Finalisasi & Data Lengkap
    Route::get('/pelanggan/tambah-daya/step5', [TambahDayaController::class, 'step5'])->name('tambah-daya.step5');
    Route::post('/pelanggan/tambah-daya/step5', [TambahDayaController::class, 'storeStep5'])->name('tambah-daya.step5.store');
    
    // Step 5 Verifications
    Route::post('/pelanggan/tambah-daya/verify-kk', [TambahDayaController::class, 'verifyKK'])->name('tambah-daya.verify-kk');
    Route::post('/pelanggan/tambah-daya/verify-npwp', [TambahDayaController::class, 'verifyNPWP'])->name('tambah-daya.verify-npwp');
    Route::post('/pelanggan/tambah-daya/verify-idpel', [TambahDayaController::class, 'verifyIdPelanggan'])->name('tambah-daya.verify-idpel');

    // Monitoring Routes
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring');
    Route::get('/monitoring/{id}', [MonitoringController::class, 'show'])->name('monitoring.show');
    Route::post('/monitoring/{id}/pay', [MonitoringController::class, 'simulatePayment'])->name('monitoring.pay');

    // Profile Management
    Route::get('/pelanggan/profile', [PelangganProfileController::class, 'edit'])->name('pelanggan.profile');
    Route::put('/pelanggan/profile', [PelangganProfileController::class, 'update'])->name('pelanggan.profile.update');


    // Permohonan Legacy/Redirect Route
    Route::get('/permohonan/tambah-daya', [App\Http\Controllers\PermohonanTambahDayaController::class, 'index'])->name('permohonan.tambah-daya');

    // Future: Pasang Baru Wizard
    Route::get('/permohonan/pasang-baru', [App\Http\Controllers\PermohonanController::class, 'pasangBaruForm'])->name('permohonan.pasang-baru');
});

// Public Info Routes for Layanan
Route::get('/layanan/tambah-daya', [App\Http\Controllers\LayananInfoController::class, 'tambahDaya'])->name('layanan.tambah-daya.info');
Route::get('/layanan/pasang-baru', [App\Http\Controllers\LayananInfoController::class, 'pasangBaru'])->name('layanan.pasang-baru.info');

// Tutorial Route
Route::get('/tutorial/titik-koordinat', [App\Http\Controllers\TutorialController::class, 'titikKoordinat'])->name('tutorial.titik-koordinat');
