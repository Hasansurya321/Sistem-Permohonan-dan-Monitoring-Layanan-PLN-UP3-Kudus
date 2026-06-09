<?php
/**
 * Script untuk cek detail record PASANG_BARU
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ServiceRequest;

echo "=== DETAIL RECORD PASANG_BARU ===\n\n";

$pasangBaruRecords = ServiceRequest::where('jenis_layanan', 'PASANG_BARU')
    ->orderByDesc('created_at')
    ->get();

foreach ($pasangBaruRecords as $sr) {
    echo "--- Record ID: {$sr->id} ---\n";
    echo "  Nomor Permohonan: {$sr->nomor_permohonan}\n";
    echo "  Jenis Layanan: {$sr->jenis_layanan}\n";
    echo "  Status: " . $sr->status->value . "\n";
    echo "  Status Label: " . $sr->status->getLabel() . "\n";
    echo "  is_draft: " . ($sr->is_draft ? 'Ya' : 'Tidak') . "\n";
    echo "  submitted_at: {$sr->submitted_at}\n";
    echo "  daya_baru: {$sr->daya_baru}\n";
    echo "  peruntukan_koneksi: {$sr->peruntukan_koneksi}\n";
    
    // Cek payload_json
    $payload = $sr->payload_json ?? [];
    echo "  payload_json['jenis_layanan']: " . ($payload['jenis_layanan'] ?? 'TIDAK ADA') . "\n";
    
    echo "\n";
}

echo "=== END ===\n";
