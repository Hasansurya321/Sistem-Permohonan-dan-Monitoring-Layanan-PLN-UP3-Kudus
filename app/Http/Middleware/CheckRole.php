<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (\Illuminate\Support\Facades\Auth::guard('employee')->check() && !\Illuminate\Support\Facades\Auth::guard('web')->check()) {
            $employee = \Illuminate\Support\Facades\Auth::guard('employee')->user();
            $roleConfig = config('internal_roles');
            if (isset($roleConfig[$employee->role])) {
                return redirect($roleConfig[$employee->role]['path'])
                    ->with('error', 'Anda tidak memiliki akses ke halaman ini.');
            }
        }

        if (!\Illuminate\Support\Facades\Auth::guard('web')->check() || \Illuminate\Support\Facades\Auth::guard('web')->user()->role !== $role) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
