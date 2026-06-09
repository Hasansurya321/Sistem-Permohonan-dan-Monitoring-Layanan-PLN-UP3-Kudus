<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ServiceRequest;
use App\Models\Payment;
use App\Enums\PermohonanDetailStatus;

$token = 'e3d2ce03-1769-4786-82bc-cb09eb240f25';

echo "=== TC2 STEP 1: Simulasi GAGAL (Direct DB) ===" . PHP_EOL;

// Cek state SEBELUM
$payment = Payment::where('payment_token', $token)->first();
$sr = ServiceRequest::find($payment->service_request_id);

echo "SEBELUM GAGAL:" . PHP_EOL;
echo "  Attempts: " . $sr->payment_attempt_count . PHP_EOL;
echo "  Payment Status: " . $payment->status . PHP_EOL;
echo "  Detail: " . $sr->status_detail->value . PHP_EOL;

// Simulasi logic gagal dari QrisSimulatorController::fail
// Increment attempts
$sr->payment_attempt_count = $sr->payment_attempt_count + 1;
$sr->save();

// Update payment status
$payment->status = 'FAILED';
$payment->failure_reason = 'CUSTOMER_CANCELLED';
$payment->paid_at = null;
$payment->save();

echo PHP_EOL . "SETELAH GAGAL (direct update):" . PHP_EOL;

// Refresh state
$sr->refresh();
$payment->refresh();

echo "  Attempts: " . $sr->payment_attempt_count . PHP_EOL;
echo "  Payment Status: " . $payment->status . PHP_EOL;
echo "  Detail: " . $sr->status_detail->value . PHP_EOL;

// Verifikasi
if ($sr->payment_attempt_count == 1) {
    echo PHP_EOL . "✅ GAGAL berhasil: attempts=1" . PHP_EOL;
} else {
    echo PHP_EOL . "❌ GAGAL gagal: attempts tidak berubah!" . PHP_EOL;
}
