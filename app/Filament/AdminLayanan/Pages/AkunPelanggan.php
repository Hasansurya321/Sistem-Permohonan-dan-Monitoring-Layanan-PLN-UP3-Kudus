<?php

namespace App\Filament\AdminLayanan\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\CustomerAccountRequest;

class AkunPelanggan extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Akun Pelanggan';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.admin-layanan.pages.akun-pelanggan';
    protected static ?string $title = '';

    public function getViewData(): array
    {
        $totalPelanggan = User::where('role', 'pelanggan')->count();
        $permintaanAkun = CustomerAccountRequest::where('status', 'pending')->count();

        // Hitung permintaan lupa password (dari tabel customer/reset request jika ada, jika tidak = 0)
        $lupaPassword = 0;
        if (class_exists('\App\Models\PasswordResetRequest')) {
            $lupaPassword = \App\Models\PasswordResetRequest::count();
        } elseif (class_exists('\App\Models\CustomerPasswordResetToken')) {
            $lupaPassword = \App\Models\CustomerPasswordResetToken::whereNull('used_at')->count();
        }

        return [
            'totalPelanggan' => $totalPelanggan,
            'permintaanAkun' => $permintaanAkun,
            'lupaPassword' => $lupaPassword,
        ];
    }
}