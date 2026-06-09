<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterPelanggan extends Model
{
    use HasFactory;

    protected $table = 'master_pelanggan';
    protected $guarded = ['id'];

    /**
     * ONE SOURCE OF TRUTH — Relasi ke user autentikasi.
     * Setiap pelanggan bisnis terhubung ke satu akun user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
