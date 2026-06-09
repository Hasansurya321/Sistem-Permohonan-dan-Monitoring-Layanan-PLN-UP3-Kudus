<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'expired_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * Cek apakah payment session sudah expired.
     */
    public function isExpired(): bool
    {
        if (!$this->expired_at) {
            return false;
        }
        return now()->greaterThan($this->expired_at);
    }

    /**
     * Cek apakah payment session masih aktif (belum expired dan belum dibayar).
     */
    public function isSessionActive(): bool
    {
        return $this->payment_token !== null
            && !$this->isExpired()
            && $this->status === 'PENDING';
    }
}