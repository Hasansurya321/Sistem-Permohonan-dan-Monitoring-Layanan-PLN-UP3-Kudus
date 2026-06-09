<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FIX ALL PELANGGAN PASSWORDS ===\n\n";

$pelangganEmails = [
    'pelanggan1@kudus.id',
    'pelanggan2@kudus.id',
    'pelanggan3@kudus.id',
    'pelanggan4@kudus.id',
    'pelanggan5@kudus.id',
];

foreach ($pelangganEmails as $email) {
    $user = App\Models\User::where('email', $email)->first();
    if ($user) {
        $user->password = Hash::make('Password123!');
        $user->save();
        echo "✅ Updated: {$email}\n";
    } else {
        echo "❌ Not found: {$email}\n";
    }
}

echo "\n=== VERIFY ===\n";
foreach ($pelangganEmails as $email) {
    $user = App\Models\User::where('email', $email)->first();
    if ($user) {
        $valid = Hash::check('Password123!', $user->password);
        echo "{$email}: " . ($valid ? "✅ OK" : "❌ FAILED") . "\n";
    }
}

echo "\n=== DONE ===\n";