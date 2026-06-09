<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Str;
use App\Models\ServiceRequest;
use App\Models\Payment;
use App\Enums\PermohonanStatus;

$token = Str::uuid()->toString();

// Reset service_request
$sr = ServiceRequest::find(16);
$sr->update([
    'payment_attempt_count' => 0,
    'status' => PermohonanStatus::PEMBAYARAN,
]);

// Hapus payments lama, buat baru
Payment::where('service_request_id', 16)->delete();
Payment::create([
    'service_request_id' => 16,
    'payment_token' => $token,
    'status' => 'PENDING',
    'amount' => 500000,
    'expired_at' => now()->addMinutes(30),
]);

echo "=== TC1 INITIAL STATE ===" . PHP_EOL;
echo "ID: " . $sr->fresh()->id . PHP_EOL;
echo "payment_attempt_count: " . $sr->fresh()->payment_attempt_count . PHP_EOL;
echo "status: " . $sr->fresh()->status->value . PHP_EOL;
echo "payment_token: " . $token . PHP_EOL;
echo PHP_EOL;
echo "URL callback untuk HP:" . PHP_EOL;
echo "https://immovably-legroom-jolly.ngrok-free.dev/pay/" . $token . "/success" . PHP_EOL;
