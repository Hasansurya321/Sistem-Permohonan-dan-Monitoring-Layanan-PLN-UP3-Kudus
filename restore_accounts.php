<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== RESTORE ALL ACCOUNTS ===\n\n";

// ============================================
// AKUN ADMIN & STAFF (tabel employees)
// ============================================
$employees = [
    ['email' => 'affan@adminlayanan.com', 'name' => 'Affan Admin', 'role' => 'admin_pelayanan', 'unit' => 'admin_layanan', 'jabatan' => 'Admin'],
    ['email' => 'hasan@supervisor.com', 'name' => 'Hasan Supervisor', 'role' => 'supervisor', 'unit' => 'supervisor', 'jabatan' => 'Supervisor'],
    ['email' => 'budi@unitsurvey.com', 'name' => 'Budi Surveyor', 'role' => 'unit_survey', 'unit' => 'survey', 'jabatan' => 'Surveyor'],
    ['email' => 'citra@unitperencanaan.com', 'name' => 'Citra Planner', 'role' => 'unit_perencanaan', 'unit' => 'perencanaan', 'jabatan' => 'Planner'],
    ['email' => 'dedi@unitkonstruksi.com', 'name' => 'Dedi Konstruktor', 'role' => 'unit_konstruksi', 'unit' => 'konstruksi', 'jabatan' => 'Konstruktor'],
    ['email' => 'eka@unitte.com', 'name' => 'Eka Teknisi', 'role' => 'unit_te', 'unit' => 'te', 'jabatan' => 'Teknisi'],
];

echo "Membuat akun Employee...\n";
foreach ($employees as $emp) {
    $user = \App\Models\User::where('email', $emp['email'])->first();
    if (!$user) {
        $user = \App\Models\User::create([
            'name' => $emp['name'],
            'email' => $emp['email'],
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);
        echo "  ✅ Created user: {$emp['email']}\n";
    } else {
        echo "  ⚠️  Exists: {$emp['email']}\n";
    }
}

// ============================================
// AKUN PELANGGAN (tanpa master_pelanggan untuk sekarang)
// ============================================
$pelanggan = [
    ['email' => 'suryadharmahasan@gmail.com', 'name' => 'Suryadharma Hasan', 'nik' => '3312345678900001'],
    ['email' => 'ayundagembul@gmail.com', 'name' => 'Ayunda Gembul', 'nik' => '3312345678900002'],
    ['email' => 'pelanggan1@kudus.id', 'name' => 'Budi Santoso', 'nik' => '3312345678900003'],
    ['email' => 'pelanggan2@kudus.id', 'name' => 'Siti Aminah', 'nik' => '3312345678900004'],
    ['email' => 'pelanggan3@kudus.id', 'name' => 'Rudi Hartono', 'nik' => '3312345678900005'],
    ['email' => 'pelanggan4@kudus.id', 'name' => 'Rina Wati', 'nik' => '3312345678900006'],
    ['email' => 'pelanggan5@kudus.id', 'name' => 'Agus Salim', 'nik' => '3312345678900007'],
];

echo "\nMembuat/Update akun Pelanggan...\n";
foreach ($pelanggan as $pel) {
    $user = \App\Models\User::where('email', $pel['email'])->first();
    if (!$user) {
        $user = \App\Models\User::create([
            'name' => $pel['name'],
            'email' => $pel['email'],
            'password' => Hash::make('Password123!'),
            'role' => 'pelanggan',
            'is_active' => true,
            'nik' => $pel['nik'],
        ]);
        echo "  ✅ Created: {$pel['email']}\n";
    } else {
        // Update password if needed
        if (!Hash::check('Password123!', $user->password)) {
            $user->password = Hash::make('Password123!');
            $user->save();
        }
        echo "  ⚠️  Updated password: {$pel['email']}\n";
    }
}

echo "\n========================================\n";
echo "=== AKUN YANG TERSEDIA ===\n";
echo "========================================\n";
echo "\n🔐 ADMIN LAYANAN:\n";
echo "   Email: affan@adminlayanan.com\n";
echo "   Password: password123\n";
echo "   URL: /internal/admin-layanan\n";

echo "\n🔐 SUPERVISOR:\n";
echo "   Email: hasan@supervisor.com\n";
echo "   Password: password123\n";

echo "\n🔐 PELANGGAN (SILAKAN LOGIN):\n";
echo "   Email: suryadharmahasan@gmail.com\n";
echo "   Password: Password123!\n";
echo "   Email: ayundagembul@gmail.com\n";
echo "   Password: Password123!\n";

echo "\n========================================\n";
echo "=== DONE ===\n";