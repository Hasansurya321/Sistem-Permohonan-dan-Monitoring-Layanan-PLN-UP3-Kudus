<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking Enum...\n";
try {
    echo "Value: " . \App\Enums\PermohonanDetailStatus::DITERUSKAN_UNIT_SURVEY->value . "\n";
    echo "Allowed Details for VERIFIKASI_SLO:\n";
    print_r(\App\Enums\PermohonanStatus::VERIFIKASI_SLO->allowedDetails());
    echo "SUCCESS\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
