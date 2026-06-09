<?php

namespace App\Helpers;

use Carbon\Carbon;

class WaktuHelper
{
    /**
     * Format tanggal/waktu standar aplikasi.
     * Output: "Kamis, 04 Juni 2026, 17:48 WIB"
     */
    public static function formatLengkap($date): string
    {
        if (!$date) return '-';
        if (is_string($date)) $date = Carbon::parse($date);
        return $date->translatedFormat('l, d F Y, H:i') . ' WIB';
    }

    /**
     * Format tanggal saja.
     * Output: "Kamis, 04 Juni 2026"
     */
    public static function formatTanggal($date): string
    {
        if (!$date) return '-';
        if (is_string($date)) $date = Carbon::parse($date);
        return $date->translatedFormat('l, d F Y');
    }

    /**
     * Format tanggal pendek.
     * Output: "04 Jun 2026"
     */
    public static function formatPendek($date): string
    {
        if (!$date) return '-';
        if (is_string($date)) $date = Carbon::parse($date);
        return $date->translatedFormat('d M Y');
    }

    /**
     * Format jam:menit.
     * Output: "17:48"
     */
    public static function formatJam($date): string
    {
        if (!$date) return '-';
        if (is_string($date)) $date = Carbon::parse($date);
        return $date->format('H:i');
    }

    /**
     * Format untuk JavaScript (ISO).
     * Output: "2026-06-04 17:48:00"
     */
    public static function formatJs($date): string
    {
        if (!$date) return '';
        if (is_string($date)) $date = Carbon::parse($date);
        return $date->format('Y-m-d H:i:s');
    }
}