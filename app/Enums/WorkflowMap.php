<?php

namespace App\Enums;

/**
 * WorkflowMap — Single Source of Truth untuk mapping unit ke status_detail.
 *
 * ONE SOURCE OF TRUTH: Seluruh query monitoring dan filter harus membaca
 * dari konstanta ini. Dilarang hardcode string status di query/view.
 *
 * Setiap key adalah unit proses, setiap value adalah daftar status_detail
 * yang valid untuk unit tersebut, sesuai dengan PermohonanDetailStatus enum.
 */
class WorkflowMap
{
    const UNIT_STATUS_MAP = [
        'ADMIN_LAYANAN' => [
            'MENUNGGU_VERIFIKASI_DATA',
            'VERIFIKASI_DATA_SUKSES',
            'DIKEMBALIKAN_DENGAN_REVISI',
            'DITOLAK',
            'ADMINISTRASI_SELESAI',
        ],
        'UNIT_SURVEY' => [
            'DITERIMA_UNIT_SURVEY',
            'SURVEY_DIJADWALKAN',
            'SURVEY_LAPANGAN',
            'SURVEY_SUKSES',
            'SURVEY_GAGAL',
            'SURVEY_SELESAI',
        ],
        'UNIT_PERENCANAAN' => [
            'DITERIMA_UNIT_PERENCANAAN',
            'ANALISA_KEBUTUHAN_MATERIAL',
            'CEK_KETERSEDIAAN_MATERIAL',
            'MATERIAL_TERSEDIA',
            'MATERIAL_MENUNGGU',
            'PERENCANAAN_SELESAI',
        ],
        'PEMBAYARAN' => [
            'TAGIHAN_TERBIT',
            'MENUNGGU_PEMBAYARAN',
            'PEMBAYARAN_PENDING',
            'PEMBAYARAN_SUKSES',
            'PEMBAYARAN_GAGAL',
            'PEMBAYARAN_SELESAI',
        ],
        'UNIT_KONSTRUKSI' => [
            'DITERIMA_UNIT_KONSTRUKSI',
            'KONSTRUKSI_DIJADWALKAN',
            'PEMBANGUNAN_JARINGAN',
            'KONSTRUKSI_BERHASIL',
            'KONSTRUKSI_GAGAL',
            'KONSTRUKSI_INSTALASI_SELESAI',
        ],
        'UNIT_PENYALAAN' => [
            'DITERIMA_UNIT_PENYALAAN',
            'PENYALAAN_DIJADWALKAN',
            'PENYALAAN_BERHASIL',
            'PENYALAAN_GAGAL',
            'PENYALAAN_SELESAI',
        ],
    ];

    /**
     * Ambil daftar status_detail untuk suatu unit.
     *
     * @param string $unit Key unit (ADMIN_LAYANAN, UNIT_SURVEY, dll)
     * @return array
     */
    public static function getStatusesForUnit(string $unit): array
    {
        return self::UNIT_STATUS_MAP[$unit] ?? [];
    }

    /**
     * Ambil semua key unit yang terdefinisi.
     *
     * @return array
     */
    public static function getUnitKeys(): array
    {
        return array_keys(self::UNIT_STATUS_MAP);
    }

    /**
     * Cari unit berdasarkan status_detail.
     *
     * @param string $statusDetail
     * @return string|null Nama unit atau null jika tidak ditemukan
     */
    public static function findUnitByStatus(string $statusDetail): ?string
    {
        foreach (self::UNIT_STATUS_MAP as $unit => $statuses) {
            if (in_array($statusDetail, $statuses, true)) {
                return $unit;
            }
        }
        return null;
    }
}