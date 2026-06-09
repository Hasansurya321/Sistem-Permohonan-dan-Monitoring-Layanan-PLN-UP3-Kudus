<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLN UP3 Kudus - Informasi Layanan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 font-sans text-slate-800 antialiased">
    
    <!-- Fixed Header -->
    <header class="fixed top-0 inset-x-0 h-20 pln-navbar border-b border-slate-100 z-50 flex items-center shadow-sm">
        <div class="w-full px-4 md:px-6 flex items-center h-full">
             <div class="flex items-center gap-8 md:gap-10">
                <a href="{{ route('landing') }}" class="flex items-center gap-3">
                    <div class="h-10 w-auto">
                        <img src="{{ asset('images/pln-logo2.png') }}" alt="PLN Logo" class="h-full object-contain">
                    </div>
                    <div class="flex flex-col leading-none hidden md:flex">
                        <span class="font-bold text-lg tracking-tight" style="color: #0099ff;">PLN</span>
                        <span class="text-yellow-600 font-bold text-base tracking-wide">UP3 KUDUS</span>
                    </div>
                </a>
                
                <!-- Navigation Menu -->
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('landing') }}" class="text-slate-600 font-medium hover:text-[#2F5AA8] transition-colors">Dashboard</a>
                    <a href="{{ route('monitoring') }}" class="text-slate-600 font-medium hover:text-[#2F5AA8] transition-colors {{ request()->routeIs('monitoring') ? 'text-[#2F5AA8] font-semibold border-b-2 border-[#2F5AA8] pb-1' : '' }}">Monitoring</a>
                    <a href="{{ route('pembayaran') }}" class="text-slate-600 font-medium hover:text-[#2F5AA8] transition-colors {{ request()->routeIs('pembayaran') ? 'text-[#2F5AA8] font-semibold border-b-2 border-[#2F5AA8] pb-1' : '' }}">Pembayaran</a>
                </nav>
            </div>
            
            <!-- User Section (Auth Aware) -->
            <div class="ml-auto flex items-center gap-3 relative">
                @if(Auth::guard('web')->check())
                    <!-- Authenticated User Dropdown -->
                    <div id="userDropdownToggle" class="flex items-center gap-3 cursor-pointer hover:bg-slate-50 p-2 rounded-full transition-colors border border-transparent hover:border-slate-100">
                        <span class="hidden sm:block text-sm font-semibold text-slate-700">{{ Auth::guard('web')->user()->name }}</span>
                        <div class="w-9 h-9 rounded-full bg-slate-200 overflow-hidden flex items-center justify-center text-slate-500 border border-slate-200 shadow-sm">
                            @if(Auth::guard('web')->user()->profile_photo)
                                <img src="{{ asset('storage/' . Auth::guard('web')->user()->profile_photo) }}" class="w-full h-full object-cover">
                            @else
                                <i class="fas fa-user text-sm"></i>
                            @endif
                        </div>
                        <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                    </div>
                    <!-- Dropdown Menu -->
                    <div id="userDropdownMenu" class="hidden absolute top-full right-0 mt-2 w-48 bg-white border border-slate-100 rounded-xl shadow-lg p-1 z-50">
                         <a href="{{ route('pelanggan.profile') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 rounded-lg">Lihat Profil Saya</a>
                         <form action="{{ route('pelanggan.logout') }}" method="POST" class="w-full">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg font-medium">Logout</button>
                        </form>
                    </div>
                @elseif(Auth::guard('employee')->check())
                    <!-- Authenticated Pegawai Dropdown -->
                    <div id="userDropdownToggle" class="flex items-center gap-3 cursor-pointer hover:bg-slate-50 p-2 rounded-full transition-colors border border-transparent hover:border-slate-100">
                        <span class="hidden sm:block text-sm font-semibold text-slate-700">{{ Auth::guard('employee')->user()->name }}</span>
                        <div class="w-9 h-9 rounded-full bg-slate-200 overflow-hidden flex items-center justify-center text-slate-500 border border-slate-200 shadow-sm">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                    </div>
                    <!-- Dropdown Menu -->
                    <div id="userDropdownMenu" class="hidden absolute top-full right-0 mt-2 w-48 bg-white border border-slate-100 rounded-xl shadow-lg p-1 z-50">
                         <form action="{{ route('pegawai.logout') }}" method="POST" class="w-full">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg font-medium">Logout</button>
                        </form>
                    </div>
                @else
                    <!-- Guest Dropdown -->
                    <div id="guestDropdownToggle" class="flex items-center gap-2 cursor-pointer hover:bg-slate-50 p-2 rounded-full transition-colors border border-transparent hover:border-slate-100">
                        <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                            <i class="fas fa-user"></i>
                        </div>
                        <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                    </div>
                    <!-- Guest Menu -->
                    <div id="guestDropdownMenu" class="hidden absolute top-full right-0 mt-2 w-48 bg-white border border-slate-100 rounded-xl shadow-lg p-1 z-50">
                        <a href="{{ route('pelanggan.login') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 rounded-lg">Login Pelanggan</a>
                        <a href="{{ route('pegawai.login') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 rounded-lg">Login Pegawai</a>
                    </div>
                @endif
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <!-- Main Content -->
    @if(isset($no_container) && $no_container)
        <main class="pt-20 min-h-screen">
            @yield('content')
        </main>
    @else
        <main class="pt-24 pb-12 px-4 md:px-6 max-w-[1200px] mx-auto min-h-screen">
            @yield('content')
        </main>
    @endif

    <x-footer />

    <!-- Use app.js for dropdown logic -->
</body>
</html>
