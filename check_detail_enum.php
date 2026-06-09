<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\PermohonanDetailStatus;

echo "PermohonanDetailStatus values:" . PHP_EOL;
foreach (PermohonanDetailStatus::cases() as $c) {
    echo "  - " . $c->value . PHP_EOL;
}
