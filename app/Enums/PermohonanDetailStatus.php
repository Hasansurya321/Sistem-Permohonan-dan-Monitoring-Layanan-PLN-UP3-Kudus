<?php

namespace App\Enums;

enum PermohonanDetailStatus: string
{
    // VERIFIKASI_SLO
    case MENUNGGU_VERIFIKASI = 'MENUNGGU_VERIFIKASI';
    case SLO_VALID = 'SLO_VALID';
    case DOKUMEN_TIDAK_VALID = 'DOKUMEN_TIDAK_VALID';
    case DITERUSKAN_UNIT_SURVEY = 'DITERUSKAN_UNIT_SURVEY'; // Renamed

    // SURVEY_LAPANGAN
    case SURVEY_BARU = 'SURVEY_BARU';
    case SURVEY_DIJADWALKAN = 'SURVEY_DIJADWALKAN';
    case SURVEY_SELESAI = 'SURVEY_SELESAI';

    // PERENCANAAN_MATERIAL
    case ANALISA_KEBUTUHAN_MATERIAL = 'ANALISA_KEBUTUHAN_MATERIAL'; // Renamed
    case MATERIAL_TERSEDIA = 'MATERIAL_TERSEDIA';
    case MATERIAL_MENUNGGU = 'MATERIAL_MENUNGGU';

    // MENUNGGU_PEMBAYARAN
    case TAGIHAN_TERBIT = 'TAGIHAN_TERBIT';
    case PEMBAYARAN_SELESAI = 'PEMBAYARAN_SELESAI';
    
    // Legacy / Safety
    case MENUNGGU_PEMBAYARAN = 'MENUNGGU_PEMBAYARAN';
    case VERIFIKASI_GAGAL = 'VERIFIKASI_GAGAL';

    // KONSTRUKSI_INSTALASI
    case KONSTRUKSI_JARINGAN = 'KONSTRUKSI_JARINGAN';
    case INSTALASI_PELANGGAN = 'INSTALASI_PELANGGAN';
    case KONSTRUKSI_PROGRESS = 'KONSTRUKSI_PROGRESS'; // Renamed case if needed, checking standard

    // ...

    public function getLabel(): string
    {
        return match($this) {
            self::MENUNGGU_VERIFIKASI => 'Menunggu Verifikasi',
            self::SLO_VALID => 'SLO di-upload & valid',
            self::DOKUMEN_TIDAK_VALID => 'Dokumen Tidak Valid',
            self::DITERUSKAN_UNIT_SURVEY => 'Diteruskan ke unit survey', // Updated label
            self::SURVEY_BARU => 'Baru', // Updated label
            self::SURVEY_DIJADWALKAN => 'Dijadwalkan', // Updated label
            self::SURVEY_SELESAI => 'Selesai', // Updated label
            self::ANALISA_KEBUTUHAN_MATERIAL => 'Analisa kebutuhan material', // Updated label
            self::MATERIAL_TERSEDIA => 'Tersedia', // Updated label
            self::MATERIAL_MENUNGGU => 'Material Menunggu',
            self::TAGIHAN_TERBIT => 'Tagihan terbit', // Updated label
            self::PEMBAYARAN_SELESAI => 'Pembayaran Selesai',
            self::MENUNGGU_PEMBAYARAN => 'Menunggu Pembayaran',
            self::VERIFIKASI_GAGAL => 'Verifikasi Gagal (Dokumen/Data)',
            self::KONSTRUKSI_JARINGAN => 'Konstruksi Jaringan',
            self::INSTALASI_PELANGGAN => 'Instalasi Pelanggan',
            self::KONSTRUKSI_PROGRESS => 'Progress Konstruksi',
            self::PENYALAAN_BERHASIL => 'Penyalaan Berhasil',
            self::KONFIRMASI_NYALA => 'Konfirmasi Nyala',
            self::ADMINISTRASI_AKHIR => 'Administrasi Akhir',
            self::FINISH => 'Finish',
            self::CLOSE => 'Close',
        };
    }
}
