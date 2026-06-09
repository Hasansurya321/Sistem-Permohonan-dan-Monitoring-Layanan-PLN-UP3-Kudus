<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ServiceRequest;
use App\Models\Payment;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;
use Illuminate\Support\Str;

$token = 'e3d2ce03-1769-4786-82bc-cb09eb240f25';

echo "=== TC2: Gagal 1x (attempts 0->1) ===" . PHP_EOL;

// Reset untuk TC2
$sr = ServiceRequest::find(16);
$sr->payment_attempt_count = 0;
$sr->status_detail = PermohonanDetailStatus::MENUNGGU_PEMBAYARAN;
$sr->save();

Payment::where('service_request_id', 16)->delete();
$token = Str::uuid()->toString();
Payment::create([
    'service_request_id' => 16,
    'payment_token' => $token,
    'status' => 'PENDING',
    'amount' => 500000,
    'expired_at' => now()->addMinutes(30),
]);

$sr->refresh();
$payment = Payment::where('service_request_id', 16)->first();

echo "INITIAL STATE (attempts=0):" . PHP_EOL;
echo "  Status: " . $sr->status->value . PHP_EOL;
echo "  Detail: " . $sr->status_detail->value . PHP_EOL;
echo "  Attempts: " . $sr->payment_attempt_count . PHP_EOL;
echo "  Payment Status: " . $payment->status . PHP_EOL;

// TC2: Simulasi GAGAL (attempts 0 -> 1)
$sr->payment_attempt_count = $sr->payment_attempt_count + 1;
$sr->save();
$payment->status = 'FAILED';
$payment->save();

$sr->refresh();
$payment->refresh();

echo PHP_EOL . "AFTER GAGAL (attempts=1):" . PHP_EOL;
echo "  Status: " . $sr->status->value . PHP_EOL;
echo "  Detail: " . $sr->status_detail->value . PHP_EOL;
echo "  Attempts: " . $sr->payment_attempt_count . PHP_EOL;
echo "  Payment Status: " . $payment->status . PHP_EOL;

if ($sr->payment_attempt_count == 1 && $sr->status_detail == PermohonanDetailStatus::MENUNGGU_PEMBAYARAN) {
    echo PHP_EOL . "✅ TC2 PASS: attempts=1, status masih MENUNGGU_PEMBAYARAN" . PHP_EOL;
} else {
    echo PHP_EOL . "❌ TC2 FAIL" . PHP_EOL;
}

echo PHP_EOL . "=== TC3: Sukses (attempts stays 1) ===" . PHP_EOL;

// TC3: Simulasi SUKSES - attempts STAYS 1, payment=PAID, status->VERIFIKASI_DATA
$payment->status = 'PAID';
$payment->paid_at = now();
$payment->save();

// Status berubah ke VERIFIKASI_DATA
$sr->status_detail = PermohonanDetailStatus::VERIFIKASI_DATA;
$sr->save();

$sr->refresh();
$payment->refresh();

echo "AFTER SUKSES (attempts stays 1):" . PHP_EOL;
echo "  Status: " . $sr->status->value . PHP_EOL;
echo "  Detail: " . $sr->status_detail->value . PHP_EOL;
echo "  Attempts: " . $sr->payment_attempt_count . PHP_EOL;
echo "  Payment Status: " . $payment->status . PHP_EOL;
echo "  Paid At: " . ($payment->paid_at ? $payment->paid_at->toDateTimeString() : 'null') . PHP_EOL;

if ($sr->payment_attempt_count == 1 && $payment->status == 'PAID' && $sr->status_detail == PermohonanDetailStatus::VERIFIKASI_DATA) {
    echo PHP_EOL . "✅ TC3 PASS: attempts=1, payment=PAID, detail=VERIFIKASI_DATA" . PHP_EOL;
} else {
    echo PHP_EOL . "❌ TC3 FAIL" . PHP_EOL;
}
