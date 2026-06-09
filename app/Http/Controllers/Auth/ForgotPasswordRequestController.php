<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ForgotPasswordRequestController extends Controller
{
    // ==========================================
    // PELANGGAN METHODS
    // ==========================================

    public function show()
    {
        return view('auth.pelanggan.forgot-password');
    }

    public function verifyEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        $exists = User::where('email', $request->email)
            ->where('role', 'pelanggan')
            ->exists();
        
        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Email terdaftar' : 'Email tidak ditemukan'
        ]);
    }

    public function verifyNik(Request $request)
    {
        $request->validate(['nik' => 'required|digits:16']);
        
        // Cek user by NIK column in users table
        $exists = User::where('nik', $request->nik)
            ->where('role', 'pelanggan')
            ->exists();
        
        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'NIK terdaftar' : 'NIK tidak ditemukan'
        ]);
    }

    public function verifyNama(Request $request)
    {
        $request->validate(['nama' => 'required|string']);
        
        $nama = trim($request->nama);
        
        // Case insensitive check
        $exists = User::where('role', 'pelanggan')
            ->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper($nama)])
            ->exists();
        
        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Nama terdaftar' : 'Nama tidak ditemukan'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'nik' => 'required|digits:16',
        ]);
        
        // Validasi Gabungan (Strict)
        $user = User::where('email', $request->email)
            ->where('nik', $request->nik)
            ->where('role', 'pelanggan')
            ->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper(trim($request->nama))])
            ->first();
        
        if (!$user) {
            return back()->withErrors([
                'global' => 'Data akun tidak sesuai. Pastikan Nama, Email, dan NIK benar dan saling cocok.'
            ])->withInput();
        }
        
        // Create Request
        PasswordResetRequest::create([
            'user_id' => $user->id,
            'nama_input' => $request->nama,
            'email_input' => $request->email,
            'nik_input' => $request->nik,
            'status' => 'pending',
            'request_token' => Str::uuid(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        return back()->with('success', 
            'Permintaan reset password telah dikirim ke Admin Layanan. Silakan tunggu instruksi melalui email terdaftar.');
    }

    // ==========================================
    // ADMIN METHODS
    // ==========================================

    public function indexAdmin()
    {
        $requests = PasswordResetRequest::where('status', 'pending')
            ->latest()
            ->paginate(10);
            
        // Assuming a simple view for now, or return JSON if view doesn't exist yet
        // Since we are in "Minimal dulu" mode, I'll create a simple blade for this.
        if (view()->exists('admin.password_reset_requests.index')) {
             return view('admin.password_reset_requests.index', compact('requests'));
        }

        // Fallback if view not created yet (for testing purposes)
        return response()->json($requests);
    }

    public function approve($id)
    {
        $request = PasswordResetRequest::findOrFail($id);
        
        if ($request->status !== 'pending') {
            return back()->with('error', 'Request sudah diproses sebelumnya.');
        }

        if (!$request->user) {
             return back()->with('error', 'User tidak ditemukan.');
        }
        
        // Generate Token & Send Email using Laravel Password Broker
        // This sends the standard Laravel reset link
        $status = Password::broker()->sendResetLink(
            ['email' => $request->user->email]
        );

        if ($status == Password::RESET_LINK_SENT) {
            $request->update([
                'status' => 'sent',
                'processed_by' => Auth::guard('employee')->id() ?? Auth::id() ?? 1, // Fallback to 1 if no auth (e.g. testing)
                'processed_at' => now(),
            ]);
            return back()->with('success', 'Link reset password telah dikirim ke email pelanggan.');
        } else {
            return back()->with('error', 'Gagal mengirim email: ' . __($status));
        }
    }

    public function reject(Request $request, $id)
    {
        $resetRequest = PasswordResetRequest::findOrFail($id);
        
        if ($resetRequest->status !== 'pending') {
            return back()->with('error', 'Request sudah diproses sebelumnya.');
        }
        
        $resetRequest->update([
            'status' => 'rejected',
            'admin_notes' => $request->input('notes', 'Rejected by admin'),
            'processed_by' => Auth::guard('employee')->id() ?? Auth::id() ?? 1,
            'processed_at' => now(),
        ]);
        
        return back()->with('success', 'Permintaan ditolak.');
    }
}
