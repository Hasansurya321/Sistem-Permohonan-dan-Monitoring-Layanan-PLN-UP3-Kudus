<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ServiceRequest;
use App\Models\Payment;
use Illuminate\Support\Str;

echo "=== SETUP TC2: Gagal 1x -> Sukses ===" . PHP_EOL;

// Reset ServiceRequest ID 16
$sr = ServiceRequest::find(16);
$sr->payment_attempt_count = 0;
$sr->status_detail = \App\Enums\PermohonanDetailStatus::MENUNGGU_PEMBAYARAN;
$sr->save();

echo "ServiceRequest 16 reset: attempts=0" . PHP_EOL;

// Hapus payment lama
Payment::where('service_request_id', 16)->delete();

// Buat payment baru
$token = Str::uuid()->toString();
Payment::create([
    'service_request_id' => 16,
    'payment_token' => $token,
    'status' => 'PENDING',
    'amount' => 500000,
    'expired_at' => now()->addMinutes(30),
]);

// Verifikasi
$sr->refresh();
$payment = Payment::where('service_request_id', 16)->first();

echo PHP_EOL . "=== STATE SETELAH SETUP ===" . PHP_EOL;
echo "ID: " . $sr->id . PHP_EOL;
echo "Status: " . $sr->status->value . PHP_EOL;
echo "Detail: " . $sr->status_detail->value . PHP_EOL;
echo "Attempts: " . $sr->payment_attempt_count . PHP_EOL;
echo "Payment Token: " . $payment->payment_token . PHP_EOL;
echo "Payment Status: " . $payment->status . PHP_EOL;
echo PHP_EOL . "URL SUKSES: http://localhost:8000/pay/{$token}/success" . PHP_EOL;
echo "URL GAGAL:   http://localhost:8000/pay/{$token}/fail" . PHP_EOL;
