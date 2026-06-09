<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAccountRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'nik',
        'nomor_npwp',
        'slo_reg',
        'slo_cert',
        'no_kk',
        'id_pelanggan',
        'nomor_meter',
        'email',
        'phone',
        'gender',
        'address_text',
        'province',
        'regency', // Kabupaten/Kota
        'district', // Kecamatan
        'village', // Kelurahan
        'postal_code',
        'password_hash',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function activationTokens()
    {
        return $this->hasMany(ActivationToken::class, 'customer_account_request_id');
    }
}
