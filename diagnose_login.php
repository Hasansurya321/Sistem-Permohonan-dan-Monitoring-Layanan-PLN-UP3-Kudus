<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DIAGNOSE LOGIN ===\n\n";

echo "=== SEMUA USER DI DATABASE ===\n";
$users = DB::table('users')->get(['id', 'email', 'name', 'role', 'is_active']);
foreach ($users as $u) {
    echo "{$u->id}: {$u->email} ({$u->role}) - Active: {$u->is_active}\n";
}

echo "\n=== VERIFIKASI PASSWORD ===\n";

// Test login credentials
$testCreds = [
    ['email' => 'affan@adminlayanan.com', 'password' => 'password123'],
    ['email' => 'hasan@supervisor.com', 'password' => 'password123'],
    ['email' => 'suryadharmahasan@gmail.com', 'password' => 'Password123!'],
    ['email' => 'ayundagembul@gmail.com', 'password' => 'Password123!'],
    ['email' => 'pelanggan1@kudus.id', 'password' => 'password123'],
];

foreach ($testCreds as $cred) {
    $user = DB::table('users')->where('email', $cred['email'])->first();
    if ($user) {
        $hash = $user->password;
        $valid = Hash::check($cred['password'], $hash);
        echo "{$cred['email']}: " . ($valid ? "✅ Password BENAR" : "❌ Password SALAH") . "\n";
    } else {
        echo "{$cred['email']}: ❌ USER TIDAK ADA\n";
    }
}

echo "\n=== EMPLOYEE TABLE ===\n";
$employees = DB::table('employees')->get(['id', 'email', 'name', 'role']);
foreach ($employees as $e) {
    echo "{$e->id}: {$e->email} ({$e->role}) - {$e->name}\n";
}

echo "\n=== DONE ===\n";