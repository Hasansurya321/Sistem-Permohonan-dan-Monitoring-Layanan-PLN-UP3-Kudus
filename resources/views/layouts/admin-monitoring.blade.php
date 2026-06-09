<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLN UP3 Kudus - Detail Permohonan (Admin)</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 font-sans text-slate-800 antialiased">
    <header class="fixed top-0 inset-x-0 h-20 pln-navbar border-b border-slate-100 z-50 flex items-center shadow-sm">
        <div class="w-full px-4 md:px-6 flex items-center h-full">
            <div class="flex items-center gap-8 md:gap-10">
                <a href="{{ url('/internal/admin-layanan') }}" class="flex items-center gap-3">
                    <div class="h-10 w-auto">
                        <img src="{{ asset('images/pln-logo2.png') }}" alt="PLN Logo" class="h-full object-contain">
                    </div>
                    <div class="flex flex-col leading-none hidden md:flex">
                        <span class="font-bold text-lg tracking-tight" style="color: #0099ff;">PLN</span>
                        <span class="text-yellow-600 font-bold text-base tracking-wide">UP3 KUDUS</span>
                    </div>
                </a>
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="{{ url('/internal/admin-layanan') }}" class="text-slate-600 font-medium hover:text-[#2F5AA8] transition-colors">Dashboard Admin</a>
                    <a href="{{ url('/internal/admin-layanan/monitoring') }}" class="text-slate-600 font-medium hover:text-[#2F5AA8] transition-colors font-semibold border-b-2 border-[#2F5AA8] pb-1">Monitoring</a>
                </nav>
            </div>
            <div class="ml-auto flex items-center gap-3">
                <span class="text-sm text-slate-500">Admin Layanan</span>
                <a href="{{ route('pegawai.logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-sm text-red-600 hover:text-red-800 font-medium">Logout</a>
                <form id="logout-form" action="{{ route('pegawai.logout') }}" method="POST" class="hidden">@csrf</form>
            </div>
        </div>
    </header>

    <main class="pt-24 pb-12 px-4 md:px-6 max-w-[1200px] mx-auto min-h-screen">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @livewireScripts
</body>
</html>