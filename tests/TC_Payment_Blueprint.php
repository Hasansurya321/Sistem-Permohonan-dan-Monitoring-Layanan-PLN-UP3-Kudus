<?php

/**
 * Test Case TC1-TC4 - Blueprint Pembayaran
 * 
 * Berdasarkan dokumen: ARSITEKTUR SISTEM PEMBAYARAN
 * 
 * Aturan:
 * - Maksimal 3 percobaan pembayaran
 * - Percobaan dihitung dari aksi GAGAL (bukan sukses)
 * - Sukses di attempt berapapun tidak mengubah attempts count
 * - Gagal ke-3 adalah final
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ServiceRequest;
use App\Models\Payment;
use Illuminate\Support\Str;

// Helper function untuk print
function printStep(string $text) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "STEP: $text\n";
    echo str_repeat("=", 60) . "\n";
}

function printState(ServiceRequest $sr, ?Payment $payment = null) {
    echo "\n--- STATE DB ---\n";
    echo "ServiceRequest #{$sr->id}:\n";
    echo "  payment_attempt_count: {$sr->payment_attempt_count}\n";
    echo "  payment_status: " . ($sr->payment_status ?? 'null') . "\n";
    echo "  status: " . ($sr->status?->value ?? $sr->status) . "\n";
    echo "  status_detail: " . ($sr->status_detail?->value ?? $sr->status_detail) . "\n";
    
    if ($payment) {
        echo "\nPayment #{$payment->id}:\n";
        echo "  status: {$payment->status}\n";
        echo "  payment_token: {$payment->payment_token}\n";
    }
}

function getValue($val) {
    if (is_object($val)) {
        // Handle Enum objects with $value property
        if (property_exists($val, 'value')) {
            return $val->value;
        }
        // Handle objects with getValue() method (rare)
        if (method_exists($val, 'getValue')) {
            return $val->getValue();
        }
        // Handle other objects - return class name for debugging
        return get_class($val);
    }
    return $val;
}

function assertEqual(string $label, $actual, $expected): bool {
    $actualVal = getValue($actual);
    $expectedVal = getValue($expected);
    $pass = $actualVal == $expectedVal;
    $status = $pass ? "✅ PASS" : "❌ FAIL";
    echo "  {$status}: {$label} = {$actualVal} (expected: {$expectedVal})\n";
    return $pass;
}

// ============================================================
// TC1: SUKSES LANGSUNG
// ============================================================
function runTC1(): bool {
    printStep("TC1 - SUKSES LANGSUNG (attempts=0)");
    
    // Setup: Buat ServiceRequest dengan payment_attempt_count = 0
    $user = \App\Models\User::where('role', 'pelanggan')->first();
    $userId = $user->id ?? 1;
    $userNik = $user->nik ?? '1234567890123456';
    
    $token = Str::uuid()->toString();
    
    // Buat ServiceRequest dengan semua field yang diperlukan
    $sr = ServiceRequest::create([
        'submitter_user_id' => $userId,
        'applicant_nik' => $userNik,
        'jenis_layanan' => 'TAMBAH_DAYA',
        'nomor_permohonan' => 'TC1-' . time(),
        'status' => 'PEMBAYARAN',
        'status_detail' => 'MENUNGGU_PEMBAYARAN',
        'payment_attempt_count' => 0,
        'payment_status' => 'pending',
        'payment_token' => $token,
        'payload_json' => ['test' => 'TC1'],
    ]);
    
    // Buat Payment
    $payment = Payment::create([
        'service_request_id' => $sr->id,
        'payment_token' => $token,
        'amount' => 150000,
        'status' => 'PENDING',
        'expired_at' => now()->addDays(7),
    ]);
    
    echo "Created ServiceRequest #{$sr->id} with token: {$token}\n";
    printState($sr, $payment);
    
    // Action: Call successByGet via HTTP
    $response = app(\App\Http\Controllers\QrisSimulatorController::class)
        ->successByGet($token);
    
    $sr->refresh();
    $payment->refresh();
    
    echo "\n--- RESULT ---\n";
    
    $pass = true;
    $pass &= assertEqual("payment_attempt_count", $sr->payment_attempt_count, 0);
    $pass &= assertEqual("payment_status", $sr->payment_status, 'paid');
    $pass &= assertEqual("status", $sr->status, 'PEMBAYARAN');
    $pass &= assertEqual("Payment.status", $payment->status, 'SUCCESS');
    
    printState($sr, $payment);
    
    // Cleanup
    $payment->delete();
    $sr->delete();
    
    return $pass;
}

// ============================================================
// TC2: GAGAL 1x → SUKSES
// ============================================================
function runTC2(): bool {
    printStep("TC2 - GAGAL 1x → SUKSES");
    
    $user = \App\Models\User::where('role', 'pelanggan')->first();
    $userId = $user->id ?? 1;
    $userNik = $user->nik ?? '1234567890123456';
    
    $token = Str::uuid()->toString();
    
    // Setup
    $sr = ServiceRequest::create([
        'submitter_user_id' => $userId,
        'applicant_nik' => $userNik,
        'jenis_layanan' => 'TAMBAH_DAYA',
        'nomor_permohonan' => 'TC2-' . time(),
        'status' => 'PEMBAYARAN',
        'status_detail' => 'MENUNGGU_PEMBAYARAN',
        'payment_attempt_count' => 0,
        'payment_status' => 'pending',
        'payment_token' => $token,
        'payload_json' => ['test' => 'TC2'],
    ]);
    
    $payment = Payment::create([
        'service_request_id' => $sr->id,
        'payment_token' => $token,
        'amount' => 150000,
        'status' => 'PENDING',
        'expired_at' => now()->addDays(7),
    ]);
    
    echo "Created ServiceRequest #{$sr->id}\n";
    
    // Action 1: Gagal
    printStep("Action 1: Gagal (fail)");
    $failResponse = app(\App\Http\Controllers\QrisSimulatorController::class)
        ->fail($token);
    
    $sr->refresh();
    $payment->refresh();
    
    echo "After fail:\n";
    printState($sr, $payment);
    
    $pass = true;
    $pass &= assertEqual("payment_attempt_count (after 1 fail)", $sr->payment_attempt_count, 1);
    
    // Action 2: Sukses
    printStep("Action 2: Sukses (successByGet)");
    $successResponse = app(\App\Http\Controllers\QrisSimulatorController::class)
        ->successByGet($token);
    
    $sr->refresh();
    $payment->refresh();
    
    echo "After success:\n";
    printState($sr, $payment);
    
    // ATTENTION: Blueprint says attempts should REMAIN 1 after success
    $pass &= assertEqual("payment_attempt_count (after success)", $sr->payment_attempt_count, 1);
    $pass &= assertEqual("payment_status", $sr->payment_status, 'paid');
    $pass &= assertEqual("Payment.status", $payment->status, 'SUCCESS');
    
    // Cleanup
    $payment->delete();
    $sr->delete();
    
    return $pass;
}

// ============================================================
// TC3: GAGAL 2x → SUKSES
// ============================================================
function runTC3(): bool {
    printStep("TC3 - GAGAL 2x → SUKSES");
    
    $user = \App\Models\User::where('role', 'pelanggan')->first();
    $userId = $user->id ?? 1;
    $userNik = $user->nik ?? '1234567890123456';
    
    $token = Str::uuid()->toString();
    
    // Setup
    $sr = ServiceRequest::create([
        'submitter_user_id' => $userId,
        'applicant_nik' => $userNik,
        'jenis_layanan' => 'TAMBAH_DAYA',
        'nomor_permohonan' => 'TC3-' . time(),
        'status' => 'PEMBAYARAN',
        'status_detail' => 'MENUNGGU_PEMBAYARAN',
        'payment_attempt_count' => 0,
        'payment_status' => 'pending',
        'payment_token' => $token,
        'payload_json' => ['test' => 'TC3'],
    ]);
    
    $payment = Payment::create([
        'service_request_id' => $sr->id,
        'payment_token' => $token,
        'amount' => 150000,
        'status' => 'PENDING',
        'expired_at' => now()->addDays(7),
    ]);
    
    echo "Created ServiceRequest #{$sr->id}\n";
    
    // Action 1: Gagal
    printStep("Action 1: Gagal (fail)");
    app(\App\Http\Controllers\QrisSimulatorController::class)->fail($token);
    $sr->refresh();
    $pass = assertEqual("payment_attempt_count (after 1 fail)", $sr->payment_attempt_count, 1);
    
    // Action 2: Gagal
    printStep("Action 2: Gagal (fail)");
    app(\App\Http\Controllers\QrisSimulatorController::class)->fail($token);
    $sr->refresh();
    $pass &= assertEqual("payment_attempt_count (after 2 fails)", $sr->payment_attempt_count, 2);
    
    // Action 3: Sukses
    printStep("Action 3: Sukses (successByGet)");
    app(\App\Http\Controllers\QrisSimulatorController::class)->successByGet($token);
    $sr->refresh();
    $payment->refresh();
    
    echo "\n--- FINAL RESULT ---\n";
    printState($sr, $payment);
    
    // Attempts should remain 2 after success
    $pass &= assertEqual("payment_attempt_count (after success)", $sr->payment_attempt_count, 2);
    $pass &= assertEqual("payment_status", $sr->payment_status, 'paid');
    
    // Cleanup
    $payment->delete();
    $sr->delete();
    
    return $pass;
}

// ============================================================
// TC4: GAGAL 3x → FINAL
// ============================================================
function runTC4(): bool {
    printStep("TC4 - GAGAL 3x → FINAL + Attempt ke-4 DITOLAK");
    
    $user = \App\Models\User::where('role', 'pelanggan')->first();
    $userId = $user->id ?? 1;
    $userNik = $user->nik ?? '1234567890123456';
    
    $token = Str::uuid()->toString();
    
    // Setup
    $sr = ServiceRequest::create([
        'submitter_user_id' => $userId,
        'applicant_nik' => $userNik,
        'jenis_layanan' => 'TAMBAH_DAYA',
        'nomor_permohonan' => 'TC4-' . time(),
        'status' => 'PEMBAYARAN',
        'status_detail' => 'MENUNGGU_PEMBAYARAN',
        'payment_attempt_count' => 0,
        'payment_status' => 'pending',
        'payment_token' => $token,
        'payload_json' => ['test' => 'TC4'],
    ]);
    
    $payment = Payment::create([
        'service_request_id' => $sr->id,
        'payment_token' => $token,
        'amount' => 150000,
        'status' => 'PENDING',
        'expired_at' => now()->addDays(7),
    ]);
    
    echo "Created ServiceRequest #{$sr->id}\n";
    
    // Action 1-3: Gagal
    for ($i = 1; $i <= 3; $i++) {
        printStep("Action {$i}: Gagal (fail)");
        app(\App\Http\Controllers\QrisSimulatorController::class)->fail($token);
        $sr->refresh();
        echo "After fail #{$i}: attempts = {$sr->payment_attempt_count}\n";
    }
    
    $pass = true;
    $pass &= assertEqual("payment_attempt_count (after 3 fails)", $sr->payment_attempt_count, 3);
    $pass &= assertEqual("status", $sr->status, 'SELESAI');
    $pass &= assertEqual("status_detail", $sr->status_detail, 'PERMOHONAN_GAGAL');
    $pass &= assertEqual("payment_status", $sr->payment_status, 'failed');
    
    echo "\n--- AFTER 3 FAILS ---\n";
    printState($sr, $payment);
    
    // Action 4: Try success (should be REJECTED)
    printStep("Action 4: Try successByGet (should be REJECTED)");
    $beforeAttempts = $sr->payment_attempt_count;
    $beforeStatus = $sr->payment_status;
    
    app(\App\Http\Controllers\QrisSimulatorController::class)->successByGet($token);
    $sr->refresh();
    
    echo "\n--- AFTER ATTEMPT 4 (should be REJECTED) ---\n";
    printState($sr, $payment);
    
    // DB should NOT change
    $pass &= assertEqual("payment_attempt_count (unchanged)", $sr->payment_attempt_count, $beforeAttempts);
    $pass &= assertEqual("payment_status (unchanged)", $sr->payment_status, $beforeStatus);
    
    // Cleanup
    $payment->delete();
    $sr->delete();
    
    return $pass;
}

// ============================================================
// RUN ALL TEST CASES
// ============================================================
echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     TC1-TC4: BLUEPRINT PAYMENT SYSTEM TEST SUITE        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";

$results = [];

$results['TC1'] = runTC1();
$results['TC2'] = runTC2();
$results['TC3'] = runTC3();
$results['TC4'] = runTC4();

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    FINAL SUMMARY                         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";

foreach ($results as $tc => $pass) {
    $status = $pass ? "✅ PASS" : "❌ FAIL";
    echo "  {$tc}: {$status}\n";
}

$allPass = !in_array(false, $results, true);
echo "\n";
if ($allPass) {
    echo "🎉 ALL TEST CASES PASSED! Blueprint implementation is correct.\n";
} else {
    echo "⚠️  SOME TESTS FAILED. Please review the implementation.\n";
}
