<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmployeeResetPasswordMail;
use App\Models\Employee;
use App\Models\EmployeePasswordResetToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmployeePasswordResetController extends Controller
{
    public function show()
    {
        return view('auth.pegawai.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $employee = Employee::where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if (!$employee) {
            return back()->withErrors([
                'global' => 'Email pegawai tidak ditemukan atau akun tidak aktif.'
            ])->withInput();
        }

        $email = $employee->email;
        $now = now();

        EmployeePasswordResetToken::where('email', $email)
            ->where('expires_at', '<=', $now)
            ->delete();

        $recentToken = EmployeePasswordResetToken::where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', $now)
            ->latest('created_at')
            ->first();

        if ($recentToken && $recentToken->created_at->greaterThan($now->subMinutes(5))) {
            return back()->withErrors([
                'global' => 'Silakan tunggu 5 menit sebelum meminta link reset password lagi.'
            ])->withInput();
        }

        EmployeePasswordResetToken::where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', $now)
            ->update(['used_at' => $now]);

        $rawToken = Str::random(64);
        $hashedToken = Hash::make($rawToken);
        $expiresAt = $now->copy()->addMinutes(60);

        EmployeePasswordResetToken::create([
            'email' => $email,
            'token' => $hashedToken,
            'expires_at' => $expiresAt,
        ]);

        $resetUrl = route('pegawai.reset-password.form', ['token' => $rawToken, 'email' => $email]);

        Mail::to($email)->send(new EmployeeResetPasswordMail($resetUrl, $employee->name, $rawToken));

        return back()->with('success', 'Link reset password pegawai telah dikirim ke email terdaftar.');
    }

    public function showResetForm(Request $request, string $token)
    {
        $request->validate(['email' => 'required|email']);

        return view('auth.pegawai.reset-password', [
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

        $tokenRecord = EmployeePasswordResetToken::where('email', $request->email)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();

        if (!$tokenRecord || !Hash::check($request->token, $tokenRecord->token)) {
            return back()->withErrors(['token' => 'Token reset tidak valid atau telah digunakan.'])->withInput();
        }

        $employee = Employee::where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if (!$employee) {
            return back()->withErrors(['email' => 'Akun pegawai tidak ditemukan atau tidak aktif.'])->withInput();
        }

        $employee->password = $request->password;
        $employee->save();

        $tokenRecord->update(['used_at' => now()]);

        return redirect()->route('pegawai.login')->with('success', 'Password pegawai berhasil diubah. Silakan login ulang menggunakan password baru.');
    }
}
