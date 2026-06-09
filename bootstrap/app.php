<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'login.focus' => \App\Http\Middleware\RequireLoginAndFocus::class,
            'customer.only' => \App\Http\Middleware\RequireCustomerAuth::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);

        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('internal/*') || $request->is('internal')) {
                return route('pegawai.login');
            }
            return route('pelanggan.login');
        });

        $middleware->redirectUsersTo(function () {
            if (\Illuminate\Support\Facades\Auth::guard('employee')->check()) {
                $employee = \Illuminate\Support\Facades\Auth::guard('employee')->user();
                $roleConfig = config('internal_roles');
                if ($employee && isset($roleConfig[$employee->role])) {
                    return $roleConfig[$employee->role]['path'];
                }
            }
            return route('landing');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
