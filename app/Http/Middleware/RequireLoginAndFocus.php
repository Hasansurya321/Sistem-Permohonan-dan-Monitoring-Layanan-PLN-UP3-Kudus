<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireLoginAndFocus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('landing', [
                'need_login' => 1,
                'focus' => 'login',
                // Use safe navigation operator or default to avoid errors if route is null
                'from' => $request->route()?->getName() ?? 'unknown',
            ]);
        }

        return $next($request);
    }
}
