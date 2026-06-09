<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\PelangganProfile;

class PelangganProfileController extends Controller
{
    public function show()
    {
        $user = Auth::guard('web')->user();

        $profile = PelangganProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'nama_lengkap' => $user->name,
                'nik' => $user->nik,
            ]
        );

        return view('pelanggan.profil', compact('user', 'profile'));
    }
}
