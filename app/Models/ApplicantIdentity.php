<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantIdentity extends Model
{
    use HasFactory;

    protected $fillable = [
    'user_id',
    'nik',
    'nama_lengkap',
    'no_hp',
    'no_kk',
    'npwp',
    'foto_bangunan',
    'foto_ktp_selfie',
    'id_pelanggan_12',
    'no_meter',

    'default_provinsi',
    'default_kab_kota',
    'default_kecamatan',
    'default_kelurahan',
    'default_rt',
    'default_rw',
    'default_alamat_detail',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'applicant_id');
    }
}
