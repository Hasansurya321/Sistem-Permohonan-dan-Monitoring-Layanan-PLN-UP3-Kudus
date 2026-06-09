<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'phone',
        'gender',
        'address_text',
        'address',
        'status',
        'approved_by',
        'approved_at',
        'activated_at',
        'nik',
        'nomor_npwp',
        'slo_reg',
        'slo_cert',
        'no_kk',
        'id_pelanggan',
        'nomor_meter',
    ];

    public function pelangganProfile()
    {
        return $this->hasOne(PelangganProfile::class);
    }

    /**
     * ONE SOURCE OF TRUTH — Relasi ke data bisnis pelanggan.
     * master_pelanggan adalah sumber data utama untuk semua informasi pelanggan.
     */
    public function masterPelanggan()
    {
        return $this->hasOne(MasterPelanggan::class, 'user_id');
    }

    public function applicantIdentity()
    {
        return $this->hasOne(ApplicantIdentity::class);
    }

    public function submittedRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'submitter_user_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Determine if the user can access the given Filament panel.
     * This method is called by Filament to check panel access.
     *
     * @param \Filament\Panel $panel
     * @return bool
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        // Check if user is active
        if (!$this->is_active) {
            return false;
        }

        // Get role configuration
        $roleConfig = config('internal_roles');
        
        // Check if role exists in configuration
        if (!isset($roleConfig[$this->role])) {
            return false;
        }

        // Get the panel ID that this user's role is allowed to access
        $allowedPanelId = $roleConfig[$this->role]['panel'];

        // Check if the current panel matches the allowed panel
        return $panel->getId() === $allowedPanelId;
    }
}
