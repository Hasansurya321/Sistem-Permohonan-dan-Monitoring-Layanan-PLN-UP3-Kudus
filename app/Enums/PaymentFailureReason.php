<?php

namespace App\Enums;

enum PaymentFailureReason: string
{
    case CUSTOMER_CANCELLED       = 'CUSTOMER_CANCELLED';
    case ADMIN_CANCELLED          = 'ADMIN_CANCELLED';
    case SYSTEM_CANCELLED         = 'SYSTEM_CANCELLED';
    case PAYMENT_EXPIRED          = 'PAYMENT_EXPIRED';
    case MAX_RETRY_REACHED        = 'MAX_RETRY_REACHED';
    case PAYMENT_GATEWAY_REJECTED = 'PAYMENT_GATEWAY_REJECTED';
    case UNKNOWN                  = 'UNKNOWN';

    public function getLabel(): string
    {
        return match($this) {
            self::CUSTOMER_CANCELLED       => 'Dibatalkan Pelanggan',
            self::ADMIN_CANCELLED          => 'Dibatalkan Admin',
            self::SYSTEM_CANCELLED         => 'Dibatalkan Sistem',
            self::PAYMENT_EXPIRED          => 'Tagihan Kedaluwarsa',
            self::MAX_RETRY_REACHED        => 'Batas Maksimal Percobaan Pembayaran Tercapai',
            self::PAYMENT_GATEWAY_REJECTED => 'Pembayaran Ditolak Gateway',
            self::UNKNOWN                  => 'Tidak Diketahui',
        };
    }
}