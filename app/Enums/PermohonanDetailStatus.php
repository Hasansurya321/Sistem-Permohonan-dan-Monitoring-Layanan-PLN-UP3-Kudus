<?php

namespace App\Enums;

enum PermohonanDetailStatus: string
{
    // VERIFIKASI_SLO
    case MENUNGGU_VERIFIKASI = 'MENUNGGU_VERIFIKASI';
    case SLO_VALID = 'SLO_VALID';
    case DOKUMEN_TIDAK_VALID = 'DOKUMEN_TIDAK_VALID';
    case DITERUSKAN_KE_SURVEY = 'DITERUSKAN_KE_SURVEY';

    // SURVEY_LAPANGAN
    case SURVEY_BARU = 'SURVEY_BARU';
    case SURVEY_DIJADWALKAN = 'SURVEY_DIJADWALKAN';
    case SURVEY_SELESAI = 'SURVEY_SELESAI';

    // PERENCANAAN_MATERIAL
    case MATERIAL_ANALISA = 'MATERIAL_ANALISA';
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
    case KONSTRUKSI_PROGRESS = 'KONSTRUKSI_PROGRESS';

    // PENYALAAN_TE
    case PENYALAAN_BERHASIL = 'PENYALAAN_BERHASIL';
    case KONFIRMASI_NYALA = 'KONFIRMASI_NYALA';

    // SELESAI
    case ADMINISTRASI_AKHIR = 'ADMINISTRASI_AKHIR';
    case FINISH = 'FINISH';
    case CLOSE = 'CLOSE';

    public function getLabel(): string
    {
        return match($this) {
            self::MENUNGGU_VERIFIKASI => 'Menunggu Verifikasi',
            self::SLO_VALID => 'SLO di-upload & valid',
            self::DOKUMEN_TIDAK_VALID => 'Dokumen Tidak Valid',
            self::DITERUSKAN_KE_SURVEY => 'Diteruskan ke Survey',
            self::SURVEY_BARU => 'Survey Baru',
            self::SURVEY_DIJADWALKAN => 'Survey Dijadwalkan',
            self::SURVEY_SELESAI => 'Survey Selesai',
            self::MATERIAL_ANALISA => 'Analisa Material',
            self::MATERIAL_TERSEDIA => 'Material Tersedia',
            self::MATERIAL_MENUNGGU => 'Material Menunggu',
            self::TAGIHAN_TERBIT => 'Tagihan Terbit',
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
