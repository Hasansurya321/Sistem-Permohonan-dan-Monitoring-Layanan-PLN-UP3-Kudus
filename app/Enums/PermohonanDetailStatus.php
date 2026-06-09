<?php

namespace App\Enums;

enum PermohonanDetailStatus: string
{
    // === ADMIN LAYANAN ===
    case MENUNGGU_VERIFIKASI_DATA    = 'MENUNGGU_VERIFIKASI_DATA';
    case VERIFIKASI_DATA_SUKSES       = 'VERIFIKASI_DATA_SUKSES';
    case DIKEMBALIKAN_DENGAN_REVISI   = 'DIKEMBALIKAN_DENGAN_REVISI';
    case DITOLAK                      = 'DITOLAK';
    case ADMINISTRASI_SELESAI         = 'ADMINISTRASI_SELESAI';

    // === UNIT SURVEY ===
    case DITERIMA_UNIT_SURVEY        = 'DITERIMA_UNIT_SURVEY';
    case SURVEY_DIJADWALKAN          = 'SURVEY_DIJADWALKAN';
    case SURVEY_LAPANGAN             = 'SURVEY_LAPANGAN';
    case SURVEY_SUKSES               = 'SURVEY_SUKSES';
    case SURVEY_GAGAL                = 'SURVEY_GAGAL';
    case SURVEY_SELESAI              = 'SURVEY_SELESAI';

    // === UNIT PERENCANAAN ===
    case DITERIMA_UNIT_PERENCANAAN   = 'DITERIMA_UNIT_PERENCANAAN';
    case ANALISA_KEBUTUHAN_MATERIAL  = 'ANALISA_KEBUTUHAN_MATERIAL';
    case CEK_KETERSEDIAAN_MATERIAL   = 'CEK_KETERSEDIAAN_MATERIAL';
    case MATERIAL_TERSEDIA           = 'MATERIAL_TERSEDIA';
    case MATERIAL_MENUNGGU           = 'MATERIAL_MENUNGGU';
    case PERENCANAAN_SELESAI         = 'PERENCANAAN_SELESAI';

    // === PEMBAYARAN ===
    case TAGIHAN_TERBIT              = 'TAGIHAN_TERBIT';
    case MENUNGGU_PEMBAYARAN         = 'MENUNGGU_PEMBAYARAN';
    case PEMBAYARAN_PENDING          = 'PEMBAYARAN_PENDING';
    case PEMBAYARAN_SUKSES           = 'PEMBAYARAN_SUKSES';
    case PEMBAYARAN_GAGAL            = 'PEMBAYARAN_GAGAL';
    case PEMBAYARAN_SELESAI          = 'PEMBAYARAN_SELESAI';

    // === UNIT KONSTRUKSI ===
    case DITERIMA_UNIT_KONSTRUKSI    = 'DITERIMA_UNIT_KONSTRUKSI';
    case KONSTRUKSI_DIJADWALKAN      = 'KONSTRUKSI_DIJADWALKAN';
    case PEMBANGUNAN_JARINGAN        = 'PEMBANGUNAN_JARINGAN';
    case KONSTRUKSI_BERHASIL         = 'KONSTRUKSI_BERHASIL';
    case KONSTRUKSI_GAGAL            = 'KONSTRUKSI_GAGAL';
    case KONSTRUKSI_SELESAI          = 'KONSTRUKSI_SELESAI';

    // === UNIT PENYALAAN ===
    case DITERIMA_UNIT_PENYALAAN     = 'DITERIMA_UNIT_PENYALAAN';
    case PENYALAAN_DIJADWALKAN       = 'PENYALAAN_DIJADWALKAN';
    case PENYALAAN_BERHASIL          = 'PENYALAAN_BERHASIL';
    case PENYALAAN_GAGAL             = 'PENYALAAN_GAGAL';
    case PENYALAAN_SELESAI           = 'PENYALAAN_SELESAI';

    // === FINAL (SELESAI) ===
    case CLOSE                       = 'CLOSE';
    case PERMOHONAN_SUKSES           = 'PERMOHONAN_SUKSES';
    case PERMOHONAN_GAGAL            = 'PERMOHONAN_GAGAL';

    public function getLabel(): string
    {
        return match($this) {
            // Admin Layanan
            self::MENUNGGU_VERIFIKASI_DATA    => 'Menunggu Verifikasi Data',
            self::VERIFIKASI_DATA_SUKSES      => 'Verifikasi Data Sukses',
            self::DIKEMBALIKAN_DENGAN_REVISI  => 'Dikembalikan Dengan Revisi',
            self::DITOLAK                     => 'Ditolak',
            self::ADMINISTRASI_SELESAI        => 'Administrasi Selesai',

            // Unit Survey
            self::DITERIMA_UNIT_SURVEY        => 'Diterima Unit Survey',
            self::SURVEY_DIJADWALKAN          => 'Survey Dijadwalkan',
            self::SURVEY_LAPANGAN             => 'Survey Lapangan',
            self::SURVEY_SUKSES               => 'Survey Sukses',
            self::SURVEY_GAGAL                => 'Survey Gagal',
            self::SURVEY_SELESAI              => 'Survey Selesai',

            // Unit Perencanaan
            self::DITERIMA_UNIT_PERENCANAAN   => 'Diterima Unit Perencanaan',
            self::ANALISA_KEBUTUHAN_MATERIAL  => 'Analisa Kebutuhan Material',
            self::CEK_KETERSEDIAAN_MATERIAL   => 'Cek Ketersediaan Material',
            self::MATERIAL_TERSEDIA           => 'Material Tersedia',
            self::MATERIAL_MENUNGGU           => 'Material Menunggu',
            self::PERENCANAAN_SELESAI         => 'Perencanaan Selesai',

            // Pembayaran
            self::TAGIHAN_TERBIT              => 'Tagihan Terbit',
            self::MENUNGGU_PEMBAYARAN         => 'Menunggu Pembayaran',
            self::PEMBAYARAN_PENDING          => 'Pending Pembayaran',
            self::PEMBAYARAN_SUKSES           => 'Pembayaran Sukses',
            self::PEMBAYARAN_GAGAL            => 'Pembayaran Gagal',
            self::PEMBAYARAN_SELESAI          => 'Pembayaran Selesai',

            // Unit Konstruksi
            self::DITERIMA_UNIT_KONSTRUKSI    => 'Diterima Unit Konstruksi',
            self::KONSTRUKSI_DIJADWALKAN      => 'Konstruksi Dijadwalkan',
            self::PEMBANGUNAN_JARINGAN        => 'Pembangunan Jaringan',
            self::KONSTRUKSI_BERHASIL         => 'Konstruksi Berhasil',
            self::KONSTRUKSI_GAGAL            => 'Konstruksi Gagal',
            self::KONSTRUKSI_SELESAI          => 'Konstruksi Selesai',

            // Unit Penyalaan
            self::DITERIMA_UNIT_PENYALAAN     => 'Diterima Unit Penyalaan',
            self::PENYALAAN_DIJADWALKAN       => 'Penyalaan Dijadwalkan',
            self::PENYALAAN_BERHASIL          => 'Penyalaan Berhasil',
            self::PENYALAAN_GAGAL             => 'Penyalaan Gagal',
            self::PENYALAAN_SELESAI           => 'Penyalaan Selesai',

            // Final
            self::CLOSE                       => 'Close',
            self::PERMOHONAN_SUKSES            => 'Permohonan Sukses',
            self::PERMOHONAN_GAGAL             => 'Permohonan Gagal',
        };
    }
}