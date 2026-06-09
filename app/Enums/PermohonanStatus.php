<?php

namespace App\Enums;

enum PermohonanStatus: string
{
    case VERIFIKASI_DATA    = 'VERIFIKASI_DATA';
    case UNIT_SURVEY        = 'UNIT_SURVEY';
    case UNIT_PERENCANAAN   = 'UNIT_PERENCANAAN';
    case PEMBAYARAN         = 'PEMBAYARAN';
    case UNIT_KONSTRUKSI    = 'UNIT_KONSTRUKSI';
    case UNIT_PENYALAAN     = 'UNIT_PENYALAAN';
    case SELESAI            = 'SELESAI';

    public function getLabel(): string
    {
        return match($this) {
            self::VERIFIKASI_DATA    => 'Verifikasi Data',
            self::UNIT_SURVEY        => 'Unit Survey',
            self::UNIT_PERENCANAAN   => 'Unit Perencanaan',
            self::PEMBAYARAN         => 'Pembayaran',
            self::UNIT_KONSTRUKSI    => 'Unit Konstruksi',
            self::UNIT_PENYALAAN     => 'Unit Penyalaan (TE)',
            self::SELESAI            => 'Selesai',
        };
    }

    public function getStepIndex(): ?int
    {
        return match($this) {
            self::VERIFIKASI_DATA    => 0,
            self::UNIT_SURVEY        => 1,
            self::UNIT_PERENCANAAN   => 2,
            self::PEMBAYARAN         => 3,
            self::UNIT_KONSTRUKSI    => 4,
            self::UNIT_PENYALAAN     => 5,
            self::SELESAI            => 6,
            default                  => null,
        };
    }

    public function isProcessing(): bool
    {
        return $this !== self::SELESAI;
    }

    public static function processing(): array
    {
        return [
            self::VERIFIKASI_DATA,
            self::UNIT_SURVEY,
            self::UNIT_PERENCANAAN,
            self::PEMBAYARAN,
            self::UNIT_KONSTRUKSI,
            self::UNIT_PENYALAAN,
        ];
    }

    public static function getStepperLabels(): array
    {
        return [
            'Verifikasi Data',
            'Unit Survey',
            'Unit Perencanaan',
            'Pembayaran',
            'Unit Konstruksi',
            'Unit Penyalaan (TE)',
            'Selesai',
        ];
    }

    public function allowedDetails(): array
    {
        return match($this) {
            self::VERIFIKASI_DATA => [
                PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA,
                PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES,
                PermohonanDetailStatus::DIKEMBALIKAN_DENGAN_REVISI,
                PermohonanDetailStatus::DITOLAK,
                PermohonanDetailStatus::ADMINISTRASI_SELESAI,
            ],
            self::UNIT_SURVEY => [
                PermohonanDetailStatus::DITERIMA_UNIT_SURVEY,
                PermohonanDetailStatus::SURVEY_DIJADWALKAN,
                PermohonanDetailStatus::SURVEY_LAPANGAN,
                PermohonanDetailStatus::SURVEY_SUKSES,
                PermohonanDetailStatus::SURVEY_GAGAL,
                PermohonanDetailStatus::SURVEY_SELESAI,
            ],
            self::UNIT_PERENCANAAN => [
                PermohonanDetailStatus::DITERIMA_UNIT_PERENCANAAN,
                PermohonanDetailStatus::ANALISA_KEBUTUHAN_MATERIAL,
                PermohonanDetailStatus::CEK_KETERSEDIAAN_MATERIAL,
                PermohonanDetailStatus::MATERIAL_TERSEDIA,
                PermohonanDetailStatus::MATERIAL_MENUNGGU,
                PermohonanDetailStatus::PERENCANAAN_SELESAI,
            ],
            self::PEMBAYARAN => [
                PermohonanDetailStatus::TAGIHAN_TERBIT,
                PermohonanDetailStatus::MENUNGGU_PEMBAYARAN,
                PermohonanDetailStatus::PEMBAYARAN_PENDING,
                PermohonanDetailStatus::PEMBAYARAN_SUKSES,
                PermohonanDetailStatus::PEMBAYARAN_GAGAL,
                PermohonanDetailStatus::PEMBAYARAN_SELESAI,
            ],
            self::UNIT_KONSTRUKSI => [
                PermohonanDetailStatus::DITERIMA_UNIT_KONSTRUKSI,
                PermohonanDetailStatus::KONSTRUKSI_DIJADWALKAN,
                PermohonanDetailStatus::PEMBANGUNAN_JARINGAN,
                PermohonanDetailStatus::KONSTRUKSI_BERHASIL,
                PermohonanDetailStatus::KONSTRUKSI_GAGAL,
                PermohonanDetailStatus::KONSTRUKSI_SELESAI,
            ],
            self::UNIT_PENYALAAN => [
                PermohonanDetailStatus::DITERIMA_UNIT_PENYALAAN,
                PermohonanDetailStatus::PENYALAAN_DIJADWALKAN,
                PermohonanDetailStatus::PENYALAAN_BERHASIL,
                PermohonanDetailStatus::PENYALAAN_GAGAL,
                PermohonanDetailStatus::PENYALAAN_SELESAI,
            ],
            self::SELESAI => [
                PermohonanDetailStatus::DITOLAK,
                PermohonanDetailStatus::ADMINISTRASI_SELESAI,
                PermohonanDetailStatus::PENYALAAN_BERHASIL,
                PermohonanDetailStatus::CLOSE,
                PermohonanDetailStatus::PEMBAYARAN_GAGAL,
            ],
            default => [],
        };
    }
}