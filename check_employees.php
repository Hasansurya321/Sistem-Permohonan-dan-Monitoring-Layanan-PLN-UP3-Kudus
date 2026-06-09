<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECK EMPLOYEES TABLE ===\n";

$cols = Schema::getColumnListing('employees');
echo "Columns: " . implode(', ', $cols) . "\n";

echo "\n=== CHECK ALL USERS ===\n";
$users = DB::table('users')->get(['id', 'email', 'name', 'role']);
foreach ($users as $u) {
    echo "{$u->id}: {$u->email} ({$u->role}) - {$u->name}\n";
}

echo "\n=== DONE ===\n";