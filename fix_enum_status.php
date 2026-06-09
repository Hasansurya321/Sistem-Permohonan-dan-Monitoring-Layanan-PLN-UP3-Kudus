<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FIX ENUM STATUS ===\n\n";

// Check current enum
$cols = DB::select("SHOW COLUMNS FROM customer_account_requests WHERE Field = 'status'");
echo "Current ENUM: " . $cols[0]->Type . "\n";

// Update ENUM to include 'activated'
DB::statement("ALTER TABLE customer_account_requests MODIFY COLUMN status ENUM('pending','approved','rejected','activated') NOT NULL DEFAULT 'pending'");

echo "✅ ENUM updated to include 'activated'\n";

// Verify
$cols = DB::select("SHOW COLUMNS FROM customer_account_requests WHERE Field = 'status'");
echo "New ENUM: " . $cols[0]->Type . "\n";

echo "\n=== DONE ===\n";