<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     * 
     * Usage: EnsureRole:pelanggan
     *        EnsureRole:admin_pelayanan,unit_survey
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Allowed roles (comma-separated or multiple args)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Normalize roles: support "role:a,b" and "role:a" formats
        $allowedRoles = [];
        foreach ($roles as $r) {
            foreach (explode(',', $r) as $part) {
                $part = trim($part);
                if ($part !== '') $allowedRoles[] = $part;
            }
        }

        // Check guards
        $user = null;
        if (Auth::guard('employee')->check()) {
            $user = Auth::guard('employee')->user();
        } elseif (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
        }

        // Guest -> redirect ke login sesuai konteks
        if (!$user) {
            if ($request->is('internal/*') || $request->is('pegawai/*')) {
                return redirect()->route('pegawai.login');
            }
            return redirect()->route('pelanggan.login');
        }

        // Role match -> allow
        if (in_array($user->role, $allowedRoles, true)) {
            return $next($request);
        }

        // Role salah -> arahkan ke tempat benar
        if ($user->role === 'pelanggan') {
            return redirect()->route('landing')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        $roleConfig = config('internal_roles');
        if (isset($roleConfig[$user->role])) {
            return redirect($roleConfig[$user->role]['path'])
                ->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        abort(403, 'Akses ditolak.');
    }
}
