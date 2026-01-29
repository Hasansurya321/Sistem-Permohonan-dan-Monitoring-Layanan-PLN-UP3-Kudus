<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$sr = App\Models\ServiceRequest::with('applicant')->latest('id')->first();
if ($sr) {
    echo "SR_ID=" . ($sr->id ?? 'NULL') . PHP_EOL;
    echo "APPLICANT_ID=" . ($sr->applicant_id ?? 'NULL') . PHP_EOL;
    echo "NIK=" . ($sr->applicant?->nik ?? 'NULL') . PHP_EOL;
    echo "NO_KK=" . ($sr->applicant?->no_kk ?? 'NULL') . PHP_EOL;
    echo "NPWP=" . ($sr->applicant?->npwp ?? 'NULL') . PHP_EOL;
} else {
    echo "NO DATA FOUND";
}
