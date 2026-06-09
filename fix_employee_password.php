<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FIX EMPLOYEE PASSWORDS ===\n\n";

$employees = [
    ['email' => 'affan@adminlayanan.com', 'role' => 'admin_pelayanan'],
    ['email' => 'hasan@supervisor.com', 'role' => 'supervisor'],
    ['email' => 'budi@unitsurvey.com', 'role' => 'unit_survey'],
    ['email' => 'citra@unitperencanaan.com', 'role' => 'unit_perencanaan'],
    ['email' => 'dedi@unitkonstruksi.com', 'role' => 'unit_konstruksi'],
    ['email' => 'eka@unitte.com', 'role' => 'unit_te'],
];

foreach ($employees as $emp) {
    $employee = \App\Models\Employee::where('email', $emp['email'])->first();
    if ($employee) {
        $employee->password = Hash::make('password123');
        $employee->is_active = true;
        $employee->save();
        echo "✅ Updated: {$emp['email']} (role: {$emp['role']})\n";
    } else {
        echo "❌ Not found: {$emp['email']}\n";
    }
}

echo "\n=== VERIFY ===\n";
foreach ($employees as $emp) {
    $employee = \App\Models\Employee::where('email', $emp['email'])->first();
    if ($employee) {
        $valid = Hash::check('password123', $employee->password);
        echo "{$emp['email']}: " . ($valid ? "✅ OK" : "❌ FAILED") . "\n";
    }
}

echo "\n=== DONE ===\n";