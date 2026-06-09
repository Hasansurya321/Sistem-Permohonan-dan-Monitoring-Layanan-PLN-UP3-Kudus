<?php

namespace App\Support;

use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;

class WorkflowStatusHelper
{
    /**
     * Returns Filament badge color string for a PermohonanStatus.
     */
    public static function filamentColor(PermohonanStatus $status): string
    {
        return match($status) {
            PermohonanStatus::VERIFIKASI_DATA    => 'info',
            PermohonanStatus::UNIT_SURVEY        => 'warning',
            PermohonanStatus::UNIT_PERENCANAAN   => 'warning',
            PermohonanStatus::PEMBAYARAN         => 'danger',
            PermohonanStatus::UNIT_KONSTRUKSI    => 'primary',
            PermohonanStatus::UNIT_PENYALAAN     => 'primary',
            PermohonanStatus::SELESAI            => 'success',
        };
    }

    /**
     * Returns Filament badge color string for a PermohonanDetailStatus.
     */
    public static function filamentDetailColor(PermohonanDetailStatus $detail): string
    {
        return match($detail) {
            // Admin Layanan
            PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA    => 'gray',
            PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES      => 'success',
            PermohonanDetailStatus::DIKEMBALIKAN_DENGAN_REVISI  => 'warning',
            PermohonanDetailStatus::DITOLAK                     => 'danger',
            PermohonanDetailStatus::ADMINISTRASI_SELESAI        => 'info',

            // Unit Survey
            PermohonanDetailStatus::DITERIMA_UNIT_SURVEY        => 'gray',
            PermohonanDetailStatus::SURVEY_DIJADWALKAN          => 'warning',
            PermohonanDetailStatus::SURVEY_LAPANGAN             => 'warning',
            PermohonanDetailStatus::SURVEY_SUKSES               => 'success',
            PermohonanDetailStatus::SURVEY_GAGAL                => 'danger',
            PermohonanDetailStatus::SURVEY_SELESAI              => 'success',

            // Unit Perencanaan
            PermohonanDetailStatus::DITERIMA_UNIT_PERENCANAAN   => 'gray',
            PermohonanDetailStatus::ANALISA_KEBUTUHAN_MATERIAL  => 'gray',
            PermohonanDetailStatus::CEK_KETERSEDIAAN_MATERIAL   => 'warning',
            PermohonanDetailStatus::MATERIAL_TERSEDIA           => 'success',
            PermohonanDetailStatus::MATERIAL_MENUNGGU           => 'warning',
            PermohonanDetailStatus::PERENCANAAN_SELESAI         => 'success',

            // Pembayaran
            PermohonanDetailStatus::TAGIHAN_TERBIT              => 'warning',
            PermohonanDetailStatus::MENUNGGU_PEMBAYARAN         => 'danger',
            PermohonanDetailStatus::PEMBAYARAN_PENDING          => 'warning',
            PermohonanDetailStatus::PEMBAYARAN_SUKSES           => 'success',
            PermohonanDetailStatus::PEMBAYARAN_GAGAL            => 'danger',
            PermohonanDetailStatus::PEMBAYARAN_SELESAI          => 'success',

            // Unit Konstruksi
            PermohonanDetailStatus::DITERIMA_UNIT_KONSTRUKSI    => 'gray',
            PermohonanDetailStatus::KONSTRUKSI_DIJADWALKAN      => 'warning',
            PermohonanDetailStatus::PEMBANGUNAN_JARINGAN        => 'warning',
            PermohonanDetailStatus::KONSTRUKSI_BERHASIL         => 'success',
            PermohonanDetailStatus::KONSTRUKSI_GAGAL            => 'danger',
            PermohonanDetailStatus::KONSTRUKSI_SELESAI          => 'success',

            // Unit Penyalaan
            PermohonanDetailStatus::DITERIMA_UNIT_PENYALAAN     => 'gray',
            PermohonanDetailStatus::PENYALAAN_DIJADWALKAN       => 'warning',
            PermohonanDetailStatus::PENYALAAN_BERHASIL          => 'success',
            PermohonanDetailStatus::PENYALAAN_GAGAL             => 'danger',
            PermohonanDetailStatus::PENYALAAN_SELESAI           => 'success',

            // Final
            PermohonanDetailStatus::CLOSE                       => 'gray',
        };
    }

    /**
     * Tailwind CSS badge class for status display on frontend (non-Filament).
     */
    public static function tailwindBadgeClass(PermohonanStatus $status): string
    {
        return match($status) {
            PermohonanStatus::VERIFIKASI_DATA    => 'bg-blue-50 text-blue-700 border-blue-200',
            PermohonanStatus::UNIT_SURVEY        => 'bg-amber-50 text-amber-700 border-amber-200',
            PermohonanStatus::UNIT_PERENCANAAN   => 'bg-amber-50 text-amber-700 border-amber-200',
            PermohonanStatus::PEMBAYARAN         => 'bg-orange-50 text-orange-700 border-orange-200',
            PermohonanStatus::UNIT_KONSTRUKSI    => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            PermohonanStatus::UNIT_PENYALAAN     => 'bg-violet-50 text-violet-700 border-violet-200',
            PermohonanStatus::SELESAI            => 'bg-green-50 text-green-700 border-green-200',
        };
    }

    /**
     * CSS class for timeline dot (monitoring page).
     */
    public static function timelineDotClass(PermohonanStatus $status, bool $isActive): string
    {
        if ($status === PermohonanStatus::SELESAI) {
            return 'w-6 h-6 rounded-full flex items-center justify-center shadow-md ' . ($isActive ? 'bg-green-500' : 'bg-gray-300');
        }
        return 'w-6 h-6 rounded-full flex items-center justify-center shadow-md ' . ($isActive ? 'bg-amber-500' : 'bg-gray-300');
    }

    /**
     * Progress percentage based on step index.
     */
    public static function progressPercent(PermohonanStatus $status): int
    {
        if ($status === PermohonanStatus::SELESAI) return 100;
        $step = $status->getStepIndex();
        if ($step === null) return 0;
        $total = 6; // 0..6 = 7 steps incl SELESAI
        return (int) round(($step / $total) * 100);
    }

    /**
     * Font Awesome icon for status.
     */
    public static function faIcon(PermohonanStatus $status): string
    {
        return match($status) {
            PermohonanStatus::VERIFIKASI_DATA    => 'fa-file-check',
            PermohonanStatus::UNIT_SURVEY        => 'fa-map-location-dot',
            PermohonanStatus::UNIT_PERENCANAAN   => 'fa-clipboard-list',
            PermohonanStatus::PEMBAYARAN         => 'fa-credit-card',
            PermohonanStatus::UNIT_KONSTRUKSI    => 'fa-wrench',
            PermohonanStatus::UNIT_PENYALAAN     => 'fa-bolt',
            PermohonanStatus::SELESAI            => 'fa-circle-check',
        };
    }

    /**
     * Estimated time for each processing status.
     */
    public static function estimasi(PermohonanStatus $status): string
    {
        return match($status) {
            PermohonanStatus::VERIFIKASI_DATA    => '1–2 hari kerja',
            PermohonanStatus::UNIT_SURVEY        => '3–5 hari kerja',
            PermohonanStatus::UNIT_PERENCANAAN   => '3–7 hari kerja',
            PermohonanStatus::PEMBAYARAN         => 'Segera setelah pembayaran',
            PermohonanStatus::UNIT_KONSTRUKSI    => '7–14 hari kerja',
            PermohonanStatus::UNIT_PENYALAAN     => '1–3 hari kerja',
            PermohonanStatus::SELESAI            => '—',
        };
    }
}