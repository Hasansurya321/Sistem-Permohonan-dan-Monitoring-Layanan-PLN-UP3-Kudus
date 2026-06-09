<?php
/**
 * Debug script - test payment flow dengan fix
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ServiceRequest;
use App\Models\Payment;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;
use Illuminate\Support\Str;

// Buat user test
$user = \App\Models\User::where('role', 'pelanggan')->first();
$userId = $user->id ?? 1;
$userNik = $user->nik ?? '1234567890123456';

$token = Str::uuid()->toString();

echo "=== DEBUG PAYMENT FLOW (FIXED) ===\n\n";

// Buat ServiceRequest
$sr = ServiceRequest::create([
    'submitter_user_id' => $userId,
    'applicant_nik' => $userNik,
    'jenis_layanan' => 'TAMBAH_DAYA',
    'nomor_permohonan' => 'DEBUG-' . time(),
    'status' => 'PEMBAYARAN',
    'status_detail' => 'MENUNGGU_PEMBAYARAN',
    'payment_attempt_count' => 0,
    'payment_status' => 'pending',
    'payment_token' => $token,
    'payload_json' => ['debug' => true],
]);

echo "1. Created ServiceRequest #{$sr->id}\n";
echo "   payment_status = {$sr->payment_status}\n";
echo "   payment_attempt_count = {$sr->payment_attempt_count}\n\n";

// Buat Payment
$payment = Payment::create([
    'service_request_id' => $sr->id,
    'payment_token' => $token,
    'amount' => 150000,
    'status' => 'PENDING',
    'expired_at' => now()->addDays(7),
]);

echo "2. Created Payment #{$payment->id}\n\n";

// Test dengan controller yang sudah difix
echo "3. === Calling successByGet via Controller ===\n\n";

try {
    $controller = app(\App\Http\Controllers\QrisSimulatorController::class);
    $response = $controller->successByGet($token);
    
    echo "   Response type: " . get_class($response) . "\n";
    
    // Check DB state
    $sr->refresh();
    $payment->refresh();
    
    echo "\n=== AFTER successByGet ===\n";
    echo "payment_status: {$sr->payment_status}\n";
    echo "Payment.status: {$payment->status}\n";
    echo "status: " . $sr->status->value . "\n";
    echo "status_detail: " . $sr->status_detail->value . "\n";
    
    if ($sr->payment_status === 'paid') {
        echo "\n✅ SUCCESS: payment_status is now 'paid'!\n";
    } else {
        echo "\n❌ FAIL: payment_status is still '{$sr->payment_status}'\n";
    }
    
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Cleanup
$payment->delete();
$sr->delete();
