<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PegawaiAuthController extends Controller
{
    /**
     * Show the pegawai login form.
     */
    public function showLogin(Request $request)
    {
        // If already authenticated as employee, redirect based on role
        if (Auth::guard('employee')->check()) {
            $employee = Auth::guard('employee')->user();
            return $this->redirectToPanel($employee);
        }

        // If authenticated as customer, redirect to landing
        if (Auth::guard('web')->check()) {
            return redirect()->route('landing')->with('info', 'Anda sudah login sebagai pelanggan.');
        }

        return view('auth.pegawai-login');
    }

    /**
     * Handle pegawai login request.
     */
    public function login(Request $request)
    {
        // Validate input
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Attempt authentication via employee guard
        if (!Auth::guard('employee')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('Email atau password salah.'),
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::guard('employee')->user();

        // Check if user is active
        if (!$user->is_active) {
            $this->logoutWithInvalidSession($request);

            throw ValidationException::withMessages([
                'email' => __('Akun Anda belum aktif. Silakan hubungi administrator.'),
            ])->redirectTo(route('pegawai.login'));
        }

        $userRole = trim((string) $user->role);

        if ($userRole === '' || $userRole === 'pelanggan') {
            $this->logoutWithInvalidSession($request);

            throw ValidationException::withMessages([
                'email' => __('Akses ditolak. Role akun tidak valid.'),
            ])->redirectTo(route('pegawai.login'));
        }

        // Get role configuration
        $roleConfig = config('internal_roles');

        if (!isset($roleConfig[$userRole])) {
            $this->logoutWithInvalidSession($request);

            throw ValidationException::withMessages([
                'email' => __('Akses ditolak. Role akun tidak valid.'),
            ])->redirectTo(route('pegawai.login'));
        }

        // Soft validation: log mismatched email domains without blocking access.
        $emailDomain = explode('@', $user->email)[1] ?? '';
        $expectedDomain = $roleConfig[$userRole]['domain'];

        if ($emailDomain !== $expectedDomain) {
            Log::warning('Pegawai login domain mismatch detected.', [
                'email' => $user->email,
                'role' => $userRole,
                'email_domain' => $emailDomain,
                'expected_domain' => $expectedDomain,
            ]);
        }

        // Redirect to appropriate panel based on user's role
        return $this->redirectToPanel($user);
    }

    /**
     * Handle pegawai logout request.
     */
    public function logout(Request $request)
    {
        Auth::guard('employee')->logout();

        if (Auth::guard('web')->check()) {
            $request->session()->regenerate();
        } else {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('landing')->with('success', 'Anda telah berhasil logout.');
    }

    protected function logoutWithInvalidSession(Request $request): void
    {
        Auth::guard('employee')->logout();
        if (Auth::guard('web')->check()) {
            $request->session()->regenerate();
        } else {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }

    /**
     * Redirect user to their appropriate panel based on role.
     */
    protected function redirectToPanel($user)
    {
        $roleConfig = config('internal_roles');
        
        if (isset($roleConfig[$user->role])) {
            $path = $roleConfig[$user->role]['path'];
            return redirect($path);
        }

        // Fallback to login if role not found
        Auth::guard('employee')->logout();
        return redirect()->route('pegawai.login')->withErrors([
            'email' => 'Role tidak ditemukan dalam konfigurasi sistem.',
        ]);
    }
}
