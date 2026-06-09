<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CEK USER PELANGGAN ===\n\n";

$users = DB::table('users')
    ->whereIn('email', ['suryadharmahasan@gmail.com', 'ayundagembul@gmail.com'])
    ->get(['id', 'email', 'name', 'role', 'is_active']);

if ($users->isEmpty()) {
    echo "❌ User TIDAK ditemukan di database!\n";
    echo "\nDaftar semua user dengan role 'pelanggan':\n";
    $allPelanggan = DB::table('users')->where('role', 'pelanggan')->get(['email', 'name', 'is_active']);
    foreach ($allPelanggan as $p) {
        echo "  - $p->email ($p->name) - Active: $p->is_active\n";
    }
} else {
    foreach ($users as $u) {
        echo "✅ Ditemukan:\n";
        echo "   Email: $u->email\n";
        echo "   Name: $u->name\n";
        echo "   Role: $u->role\n";
        echo "   Active: $u->is_active\n";
        
        // Check password hash
        $hash = DB::table('users')->where('email', $u->email)->value('password');
        echo "   Password Hash: " . substr($hash, 0, 30) . "...\n";
        
        // Verify password
        if (Hash::check('Password123!', $hash)) {
            echo "   Password: ✅ BENAR ('Password123!')\n";
        } else {
            echo "   Password: ❌ SALAH ('Password123!')\n";
        }
    }
}

echo "\n=== DONE ===\n";