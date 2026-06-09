<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MasterPelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use App\Models\CustomerAccountRequest;

class PelangganAuthController extends Controller
{
    public function showLogin()
    {
        // If already authenticated, redirect based on role
        if (Auth::guard('web')->check()) {
            return redirect()->route('landing');
        }

        if (Auth::guard('employee')->check()) {
            $employee = Auth::guard('employee')->user();
            $roleConfig = config('internal_roles');
            if ($employee && isset($roleConfig[$employee->role])) {
                $path = $roleConfig[$employee->role]['path'];
                return redirect($path)->with('info', 'Anda sudah login sebagai pegawai.');
            }
        }

        return view('auth.pelanggan.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // ONE SOURCE OF TRUTH: Login hanya membaca tabel users.
        // Tidak ada fallback ke customer_account_requests.
        // Status aktivasi ditentukan oleh is_active = 1 dan email_verified_at terisi.
        $user = User::where('email', $request->email)->where('role', 'pelanggan')->first();
        if ($user) {
            if (!$user->is_active) {
                return back()->withErrors(['email' => 'Akun Anda tidak aktif. Silakan hubungi administrator.']);
            }
            if (!$user->email_verified_at) {
                return back()->withErrors(['email' => 'Akun belum diaktivasi. Silakan gunakan token aktivasi yang dikirimkan ke email Anda.']);
            }
        }

        // Attempt login via Users table (Active accounts only) with explicit web guard
        if (Auth::guard('web')->attempt([
            'email' => $request->email,
            'password' => $request->password,
            'role' => 'pelanggan',
            'is_active' => 1
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            // Redirect to 'next' URL if exists, otherwise to landing
            $next = $request->input('next');
            if ($next && str_starts_with($next, '/') && !str_starts_with($next, '//')) {
                 return redirect()->to($next);
            }
            return redirect()->route('landing');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.pelanggan.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'string', 'regex:/^\d{16}$/', 'unique:users,nik', 'unique:customer_account_requests,nik'],
            'nomor_npwp' => ['nullable', 'string', 'regex:/^\d{15}$/'],
            'slo_reg' => ['nullable', 'string', 'max:50'],
            'slo_cert' => ['nullable', 'string', 'max:100'],
            'no_kk' => ['nullable', 'string', 'regex:/^\d{16}$/'],
            'id_pelanggan' => ['nullable', 'string', 'regex:/^\d{12}$/'],
            'nomor_meter' => ['nullable', 'string', 'regex:/^\d{11}$/'],
            'gender' => ['required', 'in:L,P'],
            'phone' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users', 'unique:customer_account_requests,email'], // Check unique in both
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'province' => ['required'],
            'regency' => ['required'],
            'district' => ['required'],
            'village' => ['required'],
            'postal_code' => ['required'],
        ]);

        CustomerAccountRequest::create([
            'full_name' => $request->name,
            'nik' => $request->nik,
            'nomor_npwp' => $request->nomor_npwp,
            'slo_reg' => $request->slo_reg,
            'slo_cert' => $request->slo_cert,
            'no_kk' => $request->no_kk,
            'id_pelanggan' => $request->id_pelanggan,
            'nomor_meter' => $request->nomor_meter,
            'email' => $request->email,
            'phone' => $request->phone,
            'gender' => $request->gender,
            'address_text' => $request->address_detail, // Optional detail
            'province' => $request->province,
            'regency' => $request->regency,
            'district' => $request->district,
            'village' => $request->village,
            'postal_code' => $request->postal_code,
            'password_hash' => Hash::make($request->password),
            'status' => 'pending',
        ]);

        // Do NOT login
        return redirect()->route('pelanggan.register.pending');
    }

    public function showRegisterPending()
    {
        return view('auth.pelanggan.register-pending');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        if (Auth::guard('employee')->check()) {
            $request->session()->regenerate();
        } else {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('landing');
    }

    public function activate($token)
    {
        $activationToken = \App\Models\ActivationToken::where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$activationToken) {
            return redirect()->route('pelanggan.login')->with('error', 'Token aktivasi tidak valid atau telah kedaluwarsa.');
        }

        // Backward compatibility:
        // - Token baru: punya customer_account_request_id
        // - Token lama (jika ada): punya user_id saja, tanpa customer_account_request_id
        $request = $activationToken->customerAccountRequest;
        if (!$request) {
            // Fallback untuk token lama: cari request via email user
            $user = $activationToken->user;
            if ($user) {
                $request = \App\Models\CustomerAccountRequest::where('email', $user->email)->first();
            }
        }

        if (!$request || !in_array($request->status, ['pending', 'approved'])) {
            return redirect()->route('pelanggan.login')->with('error', 'Permintaan aktivasi tidak valid.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($activationToken, $request) {
            // Cek apakah user dengan email ini SUDAH ADA
            $existingUser = \App\Models\User::where('email', $request->email)->first();
            
            if ($existingUser) {
                // User sudah ada → UPDATE saja
                $user = $existingUser;
            } else {
                // User belum ada → CREATE baru
                $user = new \App\Models\User();
            }
            
            // Set semua field
            $user->name = $request->full_name;
            $user->email = $request->email;
            $user->password = $request->password_hash;
            $user->role = 'pelanggan';
            $user->phone = $request->phone;
            $user->gender = $request->gender;
            $user->nik = $request->nik;
            $user->nomor_npwp = $request->nomor_npwp;
            $user->slo_reg = $request->slo_reg;
            $user->slo_cert = $request->slo_cert;
            $user->no_kk = $request->no_kk;
            $user->id_pelanggan = $request->id_pelanggan;
            $user->nomor_meter = $request->nomor_meter;
            $user->address_text = $request->address_text;
            $user->address = $request->address_text;
            $user->status = 'active';
            $user->is_active = 1;
            $user->approved_by = $request->reviewed_by ?? 1;
            $user->approved_at = $request->reviewed_at ?? now();
            $user->activated_at = now();
            $user->email_verified_at = now();
            $user->save();

            // Sinkronisasi WAJIB data pelanggan ke master_pelanggan (ONE SOURCE OF TRUTH).
            // Berlaku untuk SEMUA pelanggan baru tanpa terkecuali.
            // Semua modul layanan (Tambah Daya, Pasang Baru, dll) membaca master_pelanggan.
            $master = \App\Services\PelangganSyncService::syncAfterActivation($request, $user->id);

            // Mark token as used and assign user_id
            $activationToken->update([
                'user_id' => $user->id,
                'used_at' => now(),
            ]);

            // Update request status — 'activated' berarti sudah selesai aktivasi
            $request->update([
                'status' => 'activated',
            ]);
        });

        return redirect()->route('pelanggan.login')->with('success', 'Akun Anda berhasil diaktifkan! Silakan masuk.');
    }

    public function resendActivation(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->email;

        // 1. Rate Limiting / Cooldown
        $cooldownKey = 'resend_cooldown_' . md5($email);
        if (\Illuminate\Support\Facades\Cache::has($cooldownKey)) {
            $remaining = \Illuminate\Support\Facades\Cache::get($cooldownKey) - time();
            if ($remaining > 0) {
                $minutes = ceil($remaining / 60);
                return back()->with('error', "Cooldown aktif. Silakan tunggu {$minutes} menit sebelum mengirim ulang email aktivasi.")
                             ->withInput();
            }
        }

        // 2. Lookup CustomerAccountRequest or User with status approved
        $req = \App\Models\CustomerAccountRequest::where('email', $email)->first();
        
        if (!$req) {
            return back()->with('error', 'Permintaan akun tidak ditemukan.')->withInput();
        }

        // 3. Check status — hanya 'approved' yang boleh kirim ulang aktivasi
        if ($req->status !== 'approved') {
            return back()->with('error', 'Permintaan registrasi belum disetujui atau status tidak valid.')->withInput();
        }

        // 4. Check if already active — cek is_active dan email_verified_at
        $userActive = \App\Models\User::where('email', $email)
            ->where('is_active', 1)
            ->whereNotNull('email_verified_at')
            ->exists();
        if ($userActive) {
            return back()->with('error', 'Akun sudah aktif. Silakan masuk.')->withInput();
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($req) {
            // 5. Invalidate old tokens for this request
            \App\Models\ActivationToken::where('customer_account_request_id', $req->id)
                ->whereNull('used_at')
                ->delete();

            // 6. Expired token cleanup
            \App\Models\ActivationToken::where('expires_at', '<', now())
                ->whereNull('used_at')
                ->delete();

            // 7. Generate new token
            $token = \Illuminate\Support\Str::random(60);

            \App\Models\ActivationToken::create([
                'customer_account_request_id' => $req->id,
                'token' => $token,
                'expires_at' => now()->addHours(24),
            ]);

            // 8. Send new activation email
            \Illuminate\Support\Facades\Mail::to($req->email)->send(new \App\Mail\CustomerActivationMail($req, $token));
        });

        // 9. Set cooldown in Cache for 5 minutes (300 seconds)
        \Illuminate\Support\Facades\Cache::put($cooldownKey, time() + 300, 300);

        return back()->with('success', 'Email aktivasi baru berhasil dikirim. Silakan periksa kotak masuk Anda.')
                     ->withInput();
    }
}
