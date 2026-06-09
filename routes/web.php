<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\CustomerPasswordResetController;
use App\Http\Controllers\Auth\EmployeePasswordResetController;
use App\Http\Controllers\Auth\PelangganAuthController;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\TambahDayaController;
use App\Http\Controllers\PasangBaruController;
use App\Http\Controllers\PelangganProfileController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\PembayaranController;

Route::get('/', function () {
    $customerStats = null;
    $activeRequest  = null;

    if (\Illuminate\Support\Facades\Auth::guard('web')->check()
        && \Illuminate\Support\Facades\Auth::guard('web')->user()->role === 'pelanggan') {

        $userId = \Illuminate\Support\Facades\Auth::guard('web')->id();

        $customerStats = [
            'processing' => \App\Models\ServiceRequest::where('submitter_user_id', $userId)->processing()->count(),
            'done'       => \App\Models\ServiceRequest::where('submitter_user_id', $userId)->done()->count(),
            'waiting'    => \App\Models\ServiceRequest::where('submitter_user_id', $userId)->waiting()->count(),
            'menunggu_bayar' => \App\Models\ServiceRequest::where('submitter_user_id', $userId)
                ->where('status', \App\Enums\PermohonanStatus::PEMBAYARAN)
                ->count(),
        ];

        $activeRequest = \App\Models\ServiceRequest::with(['applicant'])
            ->where('submitter_user_id', $userId)
            ->processing()
            ->latest('updated_at')
            ->first();
    }

    return view('landing', compact('customerStats', 'activeRequest'));
})->name('landing');


// Global login route (required by Laravel auth middleware)
// Global login route
Route::get('/login', function () {
    return redirect()->route('pelanggan.login');
})->name('login');

// Customer Activation Route
Route::get('/aktivasi/{token}', [PelangganAuthController::class, 'activate'])->name('pelanggan.activate');

Route::prefix('pelanggan')->name('pelanggan.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [PelangganAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [PelangganAuthController::class, 'login'])->name('login.submit'); // Explicit name
        Route::get('/register', [PelangganAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [PelangganAuthController::class, 'register'])->name('register.submit'); // Explicit name
        Route::get('/register/success', [PelangganAuthController::class, 'showRegisterPending'])->name('register.pending');
        Route::post('/aktivasi/resend', [PelangganAuthController::class, 'resendActivation'])->name('activation.resend')->middleware('throttle:3,5');

        // Forgot Password
        Route::get('/lupa-password', [CustomerPasswordResetController::class, 'show'])->name('forgot-password');
        Route::middleware('throttle:3,10')->post('/lupa-password', [CustomerPasswordResetController::class, 'store'])->name('forgot-password.store');
        Route::get('/reset-password/{token}', [CustomerPasswordResetController::class, 'showResetForm'])->name('reset-password.form');
        Route::middleware('throttle:5,1')->post('/reset-password', [CustomerPasswordResetController::class, 'reset'])->name('reset-password');

        // Verifications (Rate Limit: 10 per minute)
        Route::middleware('throttle:10,1')->group(function() {
            Route::post('/lupa-password/verify-email', [CustomerPasswordResetController::class, 'verifyEmail'])->name('forgot-password.verify-email');
            Route::post('/lupa-password/verify-nik', [CustomerPasswordResetController::class, 'verifyNik'])->name('forgot-password.verify-nik');
            Route::post('/lupa-password/verify-nama', [CustomerPasswordResetController::class, 'verifyNama'])->name('forgot-password.verify-nama');
        });
    });

    Route::middleware(['customer.only'])->group(function () {
        Route::post('/logout', [PelangganAuthController::class, 'logout'])->name('logout');
        Route::get('/profil', [App\Http\Controllers\PelangganProfileController::class, 'show'])->name('profile');
    });
});

// Admin Helper Routes — legacy apps
// 🔴 All Permintaan Akun flows have been migrated to Filament AdminLayanan.
// Route, controller, and views are kept as reference only.
// See: app/Filament/AdminLayanan/Pages/PermintaanAkun.php and DetailPermintaanAkun.php

// Pegawai Authentication (Single Door Login for Internal Staff)
Route::prefix('pegawai')->name('pegawai.')->group(function () {
    Route::middleware('guest:employee')->group(function () {
        Route::get('/login', [App\Http\Controllers\PegawaiAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [App\Http\Controllers\PegawaiAuthController::class, 'login'])->name('login.post');
        Route::get('/lupa-password', [EmployeePasswordResetController::class, 'show'])->name('forgot-password');
        Route::middleware('throttle:3,10')->post('/lupa-password', [EmployeePasswordResetController::class, 'store'])->name('forgot-password.store');
        Route::get('/reset-password/{token}', [EmployeePasswordResetController::class, 'showResetForm'])->name('reset-password.form');
        Route::middleware('throttle:5,1')->post('/reset-password', [EmployeePasswordResetController::class, 'reset'])->name('reset-password');
    });
    
    Route::middleware('auth:employee')->group(function () {
        Route::post('/logout', [App\Http\Controllers\PegawaiAuthController::class, 'logout'])->name('logout');
    });
});

// Admin Monitoring Detail Route (non-Filament, layout identik dengan pelanggan)
Route::middleware(['auth:employee'])->get('/admin/monitoring-detail/{id}', [\App\Http\Controllers\Admin\MonitoringController::class, 'showDetail'])->name('admin.monitoring.detail');

// Monitoring & Pembayaran (Protected)
Route::middleware(['customer.only'])->group(function () {
    Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran');
    
    // ============================================
    // WIZARD TAMBAH DAYA
    // ============================================
    
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
    // 🔴 DITONONAKTIFKAN: Invoice/tagihan tidak boleh ditampilkan di tengah wizard.
    // Tagihan hanya muncul setelah Admin ACC, diakses via menu monitoring.
    // Route ini diarahkan ke monitoring untuk mencegah akses langsung.
    Route::get('/pelanggan/tambah-daya/invoice', function () {
        return redirect()->route('monitoring')->with('info', 'Tagihan hanya tersedia setelah permohonan diproses.');
    })->name('tambah-daya.invoice');
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

    // ============================================
    // WIZARD PASANG BARU (Customer-Facing)
    // ============================================
    
    // Step 1: Pilih Pemohon (Tanpa ID Pelanggan)
    Route::get('/pelanggan/pasang-baru/step1', [PasangBaruController::class, 'step1'])->name('pasang-baru.step1');
    Route::post('/pelanggan/pasang-baru/step1', [PasangBaruController::class, 'storeStep1'])->name('pasang-baru.step1.store');
    
    // Step 2: Detail Lokasi
    Route::get('/pelanggan/pasang-baru/step2', [PasangBaruController::class, 'step2'])->name('pasang-baru.step2');
    Route::post('/pelanggan/pasang-baru/step2', [PasangBaruController::class, 'storeStep2'])->name('pasang-baru.step2.store');
    
    // Step 3: Detail Layanan
    Route::get('/pelanggan/pasang-baru/step3', [PasangBaruController::class, 'step3'])->name('pasang-baru.step3');
    Route::post('/pelanggan/pasang-baru/step3', [PasangBaruController::class, 'storeStep3'])->name('pasang-baru.step3.store');
    
    // Step 4: Data SLO
    Route::get('/pelanggan/pasang-baru/step4', [PasangBaruController::class, 'step4'])->name('pasang-baru.step4');
    Route::post('/pelanggan/pasang-baru/step4', [PasangBaruController::class, 'storeStep4'])->name('pasang-baru.step4.store');
    Route::post('/pelanggan/pasang-baru/check-slo', [PasangBaruController::class, 'checkSlo'])->name('pasang-baru.check-slo');
    
    // Step 5: Finalisasi
    Route::get('/pelanggan/pasang-baru/step5', [PasangBaruController::class, 'step5'])->name('pasang-baru.step5');
    Route::post('/pelanggan/pasang-baru/step5', [PasangBaruController::class, 'storeStep5'])->name('pasang-baru.step5.store');
    
    // AJAX Verifications
    Route::post('/pelanggan/pasang-baru/check-nik', [PasangBaruController::class, 'checkNik'])->name('pasang-baru.check-nik');
    Route::post('/pelanggan/pasang-baru/verify-kk', [PasangBaruController::class, 'verifyKK'])->name('pasang-baru.verify-kk');
    Route::post('/pelanggan/pasang-baru/verify-npwp', [PasangBaruController::class, 'verifyNPWP'])->name('pasang-baru.verify-npwp');
    
    // Draft Management
    Route::post('/pelanggan/pasang-baru/{id}/autosave', [PasangBaruController::class, 'autosave'])->name('pasang-baru.autosave');
    Route::get('/pelanggan/pasang-baru/{id}/resume', [PasangBaruController::class, 'resume'])->name('pasang-baru.resume');
    Route::delete('/pelanggan/pasang-baru/{id}/cancel', [PasangBaruController::class, 'cancel'])->name('pasang-baru.cancel');

    // ============================================
    // MONITORING & PEMBAYARAN
    // ============================================

    // Monitoring Routes
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring');
    Route::get('/monitoring/{id}', [MonitoringController::class, 'show'])->name('monitoring.show');
    Route::post('/monitoring/{id}/pay', [MonitoringController::class, 'simulatePayment'])->name('monitoring.pay');

    // Detail Pembayaran
    Route::get('/pembayaran/{id}/detail', [App\Http\Controllers\PembayaranController::class, 'showDetail'])->name('pembayaran.detail');

    // Pembayaran Cancel (hanya cancel, retry di-handle Livewire)
    Route::post('/pembayaran/{id}/cancel', [App\Http\Controllers\PembayaranController::class, 'cancelRequest'])->name('pembayaran.cancel');

    // Profile Management
    Route::get('/pelanggan/profile', [PelangganProfileController::class, 'edit'])->name('pelanggan.profile');
    Route::put('/pelanggan/profile', [PelangganProfileController::class, 'update'])->name('pelanggan.profile.update');


    // Permohonan Legacy/Redirect Route
    Route::get('/permohonan/tambah-daya', [App\Http\Controllers\PermohonanTambahDayaController::class, 'index'])->name('permohonan.tambah-daya');

    // Redirect old pasang baru route to new wizard
    Route::get('/permohonan/pasang-baru', fn() => redirect()->route('pasang-baru.step1'))->name('permohonan.pasang-baru');
});

// Public Info Routes for Layanan
Route::get('/layanan/tambah-daya', [App\Http\Controllers\LayananInfoController::class, 'tambahDaya'])->name('layanan.tambah-daya.info');
Route::get('/layanan/pasang-baru', [App\Http\Controllers\LayananInfoController::class, 'pasangBaru'])->name('layanan.pasang-baru.info');

// QRIS Simulator Routes (public — token UUID aman, simulasi dari HP)
Route::get('/pay/{token}', [App\Http\Controllers\QrisSimulatorController::class, 'show'])->name('qris.show');

// READ-ONLY endpoint untuk polling JavaScript — tidak mengubah state
Route::get('/pay/{token}/status', [App\Http\Controllers\QrisSimulatorController::class, 'checkStatus'])->name('qris.status');

// 🔴 FIX: GET endpoint — auto-trigger sukses saat URL dibuka (hasil scan QR dari HP).
// QR di desktop berisi URL ini. Saat HP scan QR dan membuka link, auto sukses tanpa klik tombol.
Route::get('/pay/{token}/success', [App\Http\Controllers\QrisSimulatorController::class, 'successByGet'])->name('qris.success.get');

Route::post('/pay/{token}/success', [App\Http\Controllers\QrisSimulatorController::class, 'success'])->name('qris.success');
Route::post('/pay/{token}/fail', [App\Http\Controllers\QrisSimulatorController::class, 'fail'])->name('qris.fail');

// Tutorial Route
Route::get('/tutorial/titik-koordinat', [App\Http\Controllers\TutorialController::class, 'titikKoordinat'])->name('tutorial.titik-koordinat');
