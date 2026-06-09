<?php

namespace App\Services;

/**
 * DUMMY / SIMULATION Billing Service untuk Permohonan Tambah Daya.
 *
 * PERINGATAN: Service ini HANYA untuk keperluan development, testing, dan simulasi.
 * BUKAN perhitungan tarif resmi PLN. Semua nilai tarif dan komponen biaya
 * adalah data simulasi/testing.
 *
 * Saat implementasi tagihan riil tersedia, service ini dapat dilepas tanpa
 * mempengaruhi flow bisnis utama (cukup hapus pemanggilan dan file ini).
 */
class DummyTambahDayaBillingService
{
    /**
     * Biaya Dasar berdasarkan Peruntukan Koneksi.
     */
    const BIAYA_DASAR = [
        'RUMAH_TANGGA' => 50000,
        'BISNIS'       => 100000,
        'INDUSTRI'     => 250000,
        'SOSIAL'       => 40000,
        'PEMERINTAH'   => 150000,
        'RUMAH_IBADAH' => 25000,
    ];

    /**
     * Biaya Administrasi flat.
     */
    const ADMIN_FLAT = 15000;

    /**
     * Pajak PPN (11%).
     */
    const PPN_RATE = 0.11;

    /**
     * Tarif per VA berdasarkan kelompok daya.
     * Format: [minDaya, maxDaya, tarifPerVa]
     */
    const DAYA_TARIF = [
        [450, 2200, 20],    // Kelompok 1: Rp 20/VA
        [3500, 6600, 30],   // Kelompok 2: Rp 30/VA
        [7700, 16500, 40],  // Kelompok 3: Rp 40/VA
        [23000, 66000, 50], // Kelompok 4: Rp 50/VA
    ];

    /**
     * Generate dummy billing berdasarkan peruntukan dan daya.
     *
     * @param string $peruntukan Kode peruntukan (RUMAH_TANGGA, BISNIS, dll)
     * @param int    $daya       Kapasitas daya dalam VA
     * @return array ['biaya_dasar', 'biaya_daya', 'biaya_admin', 'ppn', 'subtotal', 'total']
     */
    public static function generate(string $peruntukan, int $daya): array
    {
        // 1. Biaya Dasar Peruntukan
        $biayaDasar = self::BIAYA_DASAR[$peruntukan] ?? 0;

        // 2. Biaya Kapasitas Daya
        $tarifPerVa = 20; // default fallback
        foreach (self::DAYA_TARIF as [$min, $max, $tarif]) {
            if ($daya >= $min && $daya <= $max) {
                $tarifPerVa = $tarif;
                break;
            }
        }
        $biayaDaya = $daya * $tarifPerVa;

        // 3. Biaya Administrasi (flat)
        $biayaAdmin = self::ADMIN_FLAT;

        // 4. Subtotal
        $subtotal = $biayaDasar + $biayaDaya + $biayaAdmin;

        // 5. PPN 11%
        $ppn = (int) round($subtotal * self::PPN_RATE);

        // 6. Total
        $total = $subtotal + $ppn;

        return [
            'biaya_dasar'  => $biayaDasar,
            'biaya_daya'   => $biayaDaya,
            'biaya_admin'  => $biayaAdmin,
            'ppn'          => $ppn,
            'subtotal'     => $subtotal,
            'total'        => $total,
        ];
    }

    /**
     * Format angka ke format rupiah (contoh: Rp 120.990).
     */
    public static function formatRupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}