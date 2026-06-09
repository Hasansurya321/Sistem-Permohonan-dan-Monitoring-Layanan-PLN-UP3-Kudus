<?php
/**
 * Script untuk cek record PASANG_BARU vs TAMBAH_DAYA di database
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ServiceRequest;
use App\Enums\PermohonanStatus;

echo "=== AUDIT JENIS LAYANAN DI DATABASE ===\n\n";

// Count by jenis_layanan
echo "--- Count by jenis_layanan ---\n";
$counts = ServiceRequest::selectRaw('jenis_layanan, COUNT(*) as total')
    ->groupBy('jenis_layanan')
    ->pluck('total', 'jenis_layanan');

foreach ($counts as $jenis => $count) {
    echo "  {$jenis}: {$count}\n";
}

echo "\n--- Count by jenis_layanan AND is_draft ---\n";
$countsDraft = ServiceRequest::selectRaw('jenis_layanan, is_draft, COUNT(*) as total')
    ->groupBy('jenis_layanan', 'is_draft')
    ->get();

foreach ($countsDraft as $row) {
    $draft = $row->is_draft ? 'DRAFT' : 'SUBMITTED';
    echo "  {$row->jenis_layanan} [{$draft}]: {$row->total}\n";
}

echo "\n--- Sample PASANG_BARU records ---\n";
$pasangBaruRecords = ServiceRequest::where('jenis_layanan', 'PASANG_BARU')
    ->orderByDesc('created_at')
    ->limit(5)
    ->get(['id', 'nomor_permohonan', 'jenis_layanan', 'status', 'is_draft', 'created_at']);

if ($pasangBaruRecords->isEmpty()) {
    echo "  TIDAK ADA record PASANG_BARU!\n";
} else {
    foreach ($pasangBaruRecords as $sr) {
        echo "  ID: {$sr->id}\n";
        echo "    Nomor: {$sr->nomor_permohonan}\n";
        echo "    Jenis: {$sr->jenis_layanan}\n";
        echo "    Status: {$sr->status}\n";
        echo "    Draft: " . ($sr->is_draft ? 'Ya' : 'Tidak') . "\n";
        echo "    Created: {$sr->created_at}\n";
        echo "\n";
    }
}

echo "\n--- Sample TAMBAH_DAYA records ---\n";
$tambahDayaRecords = ServiceRequest::where('jenis_layanan', 'TAMBAH_DAYA')
    ->orderByDesc('created_at')
    ->limit(3)
    ->get(['id', 'nomor_permohonan', 'jenis_layanan', 'status', 'is_draft', 'created_at']);

foreach ($tambahDayaRecords as $sr) {
    echo "  ID: {$sr->id} | {$sr->nomor_permohonan} | {$sr->jenis_layanan}\n";
}

echo "\n--- All unique jenis_layanan values ---\n";
$allJenis = ServiceRequest::distinct()->pluck('jenis_layanan');
foreach ($allJenis as $jenis) {
    echo "  - {$jenis}\n";
}

echo "\n=== END AUDIT ===\n";
