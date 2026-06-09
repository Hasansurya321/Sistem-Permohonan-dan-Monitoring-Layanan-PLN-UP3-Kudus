<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECK PAYMENT STATUS ===\n\n";

// Get latest pending payment
$payments = \App\Models\Payment::where('status', 'PENDING')
    ->with('serviceRequest')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($payments->isEmpty()) {
    echo "❌ Tidak ada payment PENDING\n";
} else {
    echo "📋 DAFTAR PAYMENT PENDING:\n\n";
    foreach ($payments as $p) {
        echo "ID: {$p->id}\n";
        echo "Token: {$p->payment_token}\n";
        echo "Amount: Rp " . number_format($p->amount, 0, ',', '.') . "\n";
        echo "Expired: {$p->expired_at}\n";
        echo "Status: {$p->status}\n";
        
        // Check if expired
        $isExpired = $p->expired_at && now()->greaterThan($p->expired_at);
        echo "Is Expired: " . ($isExpired ? "❌ YES" : "✅ NO") . "\n";
        
        // Generate URLs
        $baseUrl = config('app.url', 'http://127.0.0.1:8000');
        echo "\n🔗 URLs untuk Test:\n";
        echo "   Show QR: {$baseUrl}/pay/{$p->payment_token}\n";
        echo "   Auto Sukses: {$baseUrl}/pay/{$p->payment_token}/success\n";
        echo "\n---\n\n";
    }
}

echo "\n=== CHECK APP CONFIG ===\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "NGROK_URL: " . config('app.ngrok_url', 'NOT SET') . "\n";

echo "\n=== DONE ===\n";