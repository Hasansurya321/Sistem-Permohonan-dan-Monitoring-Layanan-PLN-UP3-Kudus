<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (!$request->expectsJson()) {
            // Check if the request is for internal panel routes
            if ($request->is('internal/*') || $request->is('internal') || $request->is('admin/*') || $request->is('admin')) {
                return route('pegawai.login');
            }
            
            // Default to pelanggan login for other routes
            return route('pelanggan.login');
        }

        return null;
    }
}
