<?php

namespace App\Enums;

enum PermohonanStatus: string
{
    // Draft state
    case DRAFT = 'DRAFT';
    
    // Processing states (in order)
    case DITERIMA_PLN = 'DITERIMA_PLN';
    case VERIFIKASI_SLO = 'VERIFIKASI_SLO';
    case SURVEY_LAPANGAN = 'SURVEY_LAPANGAN';
    case PERENCANAAN_MATERIAL = 'PERENCANAAN_MATERIAL';
    case MENUNGGU_PEMBAYARAN = 'MENUNGGU_PEMBAYARAN';
    case KONSTRUKSI_INSTALASI = 'KONSTRUKSI_INSTALASI';
    case PENYALAAN_TE = 'PENYALAAN_TE';
    
    // Final states
    case SELESAI = 'SELESAI';
    case DIBATALKAN_ADMIN = 'DIBATALKAN_ADMIN';
    
    /**
     * Get human-readable label
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Menunggu Diselesaikan',
            self::DITERIMA_PLN => 'Diterima PLN',
            self::VERIFIKASI_SLO => 'Verifikasi Data dan Dokumen SLO',
            self::SURVEY_LAPANGAN => 'Survey Lapangan',
            self::PERENCANAAN_MATERIAL => 'Perencanaan & Material',
            self::MENUNGGU_PEMBAYARAN => 'Menunggu Pembayaran',
            self::KONSTRUKSI_INSTALASI => 'Konstruksi & Instalasi',
            self::PENYALAAN_TE => 'Penyalaan (TE)',
            self::SELESAI => 'Selesai',
            self::DIBATALKAN_ADMIN => 'Dibatalkan',
        };
    }
    
    /**
     * Get step index for stepper (0-7)
     * Returns null for non-processing statuses
     */
    public function getStepIndex(): ?int
    {
        return match($this) {
            self::DITERIMA_PLN => 0,
            self::VERIFIKASI_SLO => 1,
            self::SURVEY_LAPANGAN => 2,
            self::PERENCANAAN_MATERIAL => 3,
            self::MENUNGGU_PEMBAYARAN => 4,
            self::KONSTRUKSI_INSTALASI => 5,
            self::PENYALAAN_TE => 6,
            self::SELESAI => 7,
            default => null,
        };
    }
    
    /**
     * Check if status is in processing state
     */
    public function isProcessing(): bool
    {
        return in_array($this, [
            self::DITERIMA_PLN,
            self::VERIFIKASI_SLO,
            self::SURVEY_LAPANGAN,
            self::PERENCANAAN_MATERIAL,
            self::MENUNGGU_PEMBAYARAN,
            self::KONSTRUKSI_INSTALASI,
            self::PENYALAAN_TE,
        ]);
    }
    
    /**
     * Get all processing statuses
     */
    public static function processing(): array
    {
        return [
            self::DITERIMA_PLN,
            self::VERIFIKASI_SLO,
            self::SURVEY_LAPANGAN,
            self::PERENCANAAN_MATERIAL,
            self::MENUNGGU_PEMBAYARAN,
            self::KONSTRUKSI_INSTALASI,
            self::PENYALAAN_TE,
        ];
    }
    
    /**
     * Get stepper labels (for UI)
     */
    public static function getStepperLabels(): array
    {
        return [
            'Diterima PLN',
            'Verifikasi Data dan Dokumen SLO',
            'Survey Lapangan',
            'Perencanaan & Material',
            'Menunggu Pembayaran',
            'Konstruksi & Instalasi',
            'Penyalaan (TE)',
            'Selesai',
        ];
    }

    /**
     * Get allowed details for this status
     */
    public function allowedDetails(): array
    {
        return match($this) {
            self::DITERIMA_PLN => [], // Transisi awal, belum ada detail
            self::VERIFIKASI_SLO => [
                PermohonanDetailStatus::MENUNGGU_VERIFIKASI,
                PermohonanDetailStatus::SLO_VALID,
                PermohonanDetailStatus::DOKUMEN_TIDAK_VALID,
                PermohonanDetailStatus::DITERUSKAN_UNIT_SURVEY,
            ],
            self::SURVEY_LAPANGAN => [
                PermohonanDetailStatus::SURVEY_BARU,
                PermohonanDetailStatus::SURVEY_DIJADWALKAN,
                PermohonanDetailStatus::SURVEY_SELESAI,
            ],
            self::PERENCANAAN_MATERIAL => [
                PermohonanDetailStatus::ANALISA_KEBUTUHAN_MATERIAL,
                PermohonanDetailStatus::MATERIAL_TERSEDIA,
                PermohonanDetailStatus::MATERIAL_MENUNGGU,
            ],
            self::MENUNGGU_PEMBAYARAN => [
                PermohonanDetailStatus::TAGIHAN_TERBIT,
                PermohonanDetailStatus::PEMBAYARAN_SELESAI,
            ],
            self::KONSTRUKSI_INSTALASI => [
                PermohonanDetailStatus::KONSTRUKSI_JARINGAN,
                PermohonanDetailStatus::INSTALASI_PELANGGAN,
                PermohonanDetailStatus::KONSTRUKSI_PROGRESS,
            ],
            self::PENYALAAN_TE => [
                PermohonanDetailStatus::PENYALAAN_BERHASIL,
                PermohonanDetailStatus::KONFIRMASI_NYALA,
            ],
            self::SELESAI => [
                PermohonanDetailStatus::ADMINISTRASI_AKHIR,
                PermohonanDetailStatus::FINISH,
                PermohonanDetailStatus::CLOSE,
            ],
            default => [],
        };
    }
}
