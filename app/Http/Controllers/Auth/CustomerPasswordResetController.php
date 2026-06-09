<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\CustomerResetPasswordMail;
use App\Models\CustomerPasswordResetToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CustomerPasswordResetController extends Controller
{
    public function show()
    {
        return view('auth.pelanggan.forgot-password');
    }

    public function verifyEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $exists = User::where('email', $request->email)
            ->where('role', 'pelanggan')
            ->where('is_active', true)
            ->where('status', 'active')
            ->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Email valid untuk reset password.' : 'Email tidak ditemukan atau akun belum aktif.'
        ]);
    }

    public function verifyNik(Request $request)
    {
        $request->validate(['nik' => 'required|digits:16']);

        $exists = User::where('nik', $request->nik)
            ->where('role', 'pelanggan')
            ->where('is_active', true)
            ->where('status', 'active')
            ->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'NIK valid untuk reset password.' : 'NIK tidak ditemukan atau akun belum aktif.'
        ]);
    }

    public function verifyNama(Request $request)
    {
        $request->validate(['nama' => 'required|string']);

        $nama = trim($request->nama);

        $exists = User::where('role', 'pelanggan')
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper($nama)])
            ->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Nama valid untuk reset password.' : 'Nama tidak ditemukan atau akun belum aktif.'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'nik' => 'required|digits:16',
        ]);

        $user = User::where('email', $request->email)
            ->where('nik', $request->nik)
            ->where('role', 'pelanggan')
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper(trim($request->nama))])
            ->first();

        if (!$user) {
            return back()->withErrors([
                'global' => 'Data akun tidak sesuai atau akun belum aktif. Pastikan Nama, Email, dan NIK benar.'
            ])->withInput();
        }

        $email = $user->email;
        $now = now();

        // Cleanup expired tokens and invalidate old tokens before new generation.
        CustomerPasswordResetToken::where('email', $email)
            ->where('expires_at', '<=', $now)
            ->delete();

        $recentToken = CustomerPasswordResetToken::where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', $now)
            ->latest('created_at')
            ->first();

        if ($recentToken && $recentToken->created_at->greaterThan($now->subMinutes(5))) {
            return back()->withErrors([
                'global' => 'Silakan tunggu 5 menit sebelum meminta link reset password lagi.'
            ])->withInput();
        }

        CustomerPasswordResetToken::where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', $now)
            ->update(['used_at' => $now]);

        $rawToken = Str::random(64);
        $hashedToken = Hash::make($rawToken);
        $expiresAt = $now->copy()->addMinutes(60);

        CustomerPasswordResetToken::create([
            'email' => $email,
            'token' => $hashedToken,
            'expires_at' => $expiresAt,
        ]);

        $resetUrl = route('pelanggan.reset-password.form', ['token' => $rawToken, 'email' => $email]);

        Mail::to($email)->send(new CustomerResetPasswordMail($resetUrl, $user->name, $rawToken));

        return back()->with('success', 'Link reset password telah dikirim ke email terdaftar. Silakan periksa inbox atau folder spam.');
    }

    public function showResetForm(Request $request, string $token)
    {
        $request->validate(['email' => 'required|email']);

        return view('auth.pelanggan.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $tokenRecord = CustomerPasswordResetToken::where('email', $request->email)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();

        if (!$tokenRecord || !Hash::check($request->token, $tokenRecord->token)) {
            return back()->withErrors(['token' => 'Token reset tidak valid atau telah digunakan.'])->withInput();
        }

        $user = User::where('email', $request->email)
            ->where('role', 'pelanggan')
            ->where('is_active', true)
            ->where('status', 'active')
            ->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Akun tidak ditemukan atau belum aktif.'])->withInput();
        }

        $user->password = $request->password;
        $user->save();

        $tokenRecord->update(['used_at' => now()]);

        return redirect()->route('pelanggan.login')->with('success', 'Password berhasil diubah. Silakan login menggunakan password baru.');
    }
}
