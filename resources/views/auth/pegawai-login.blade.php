<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pegawai - PLN UP3 Kudus</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Background Halaman - Clean Minimalist Premium with subtle luxury radial gradient */
        .pegawai-auth-bg {
            background-color: #FFFFFF !important;
            background-image: radial-gradient(circle at 50% 50%, rgba(9, 60, 93, 0.015) 0%, transparent 80%) !important;
            position: relative;
        }

        /* Interactive Revealing Card - Fixed Size, Overflow Hidden Drawer */
        .pegawai-login-card {
            background-color: #093C5D !important; /* Luxury dark navy tone */
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-top: 1.5px solid rgba(0, 163, 224, 0.25) !important; /* Elegant cyan top-light reflection */
            border-radius: 28px !important;
            max-width: 32rem !important; /* Fixed width in luxury enterprise range */
            width: 100% !important;
            height: 560px !important; /* Fixed height for hidden drawer constraint */
            position: relative !important;
            overflow: hidden !important;
            pointer-events: auto !important; /* Explicitly enable pointer-events */
            box-shadow: 
                0 4px 6px -1px rgba(9, 60, 93, 0.01),
                0 12px 24px -4px rgba(9, 60, 93, 0.03),
                0 20px 40px -10px rgba(9, 60, 93, 0.05),
                0 32px 64px -16px rgba(9, 60, 93, 0.08) !important;
            
            transition: 
                box-shadow 0.5s ease,
                border-color 0.5s ease !important;
        }

        /* Hover & Focus state for Card Glow & Shadow */
        .pegawai-login-card:hover,
        .pegawai-login-card:focus-within {
            border-color: rgba(0, 163, 224, 0.4) !important;
            box-shadow: 
                0 10px 20px -5px rgba(9, 60, 93, 0.05),
                0 25px 50px -10px rgba(9, 60, 93, 0.12),
                0 45px 90px -15px rgba(9, 60, 93, 0.2),
                0 70px 140px -20px rgba(9, 60, 93, 0.3),
                0 0 80px -10px rgba(0, 163, 224, 0.2) !important; /* Soft cyan glow */
        }

        /* Title Area - Centered when idle, slides up on Hover/Focus */
        .pegawai-title-area {
            padding-top: 5.5rem !important;
            padding-left: 3rem !important;
            padding-right: 3rem !important;
            transform: translateY(92px) !important; /* Adjusted from 110px to prevent clipping of bottom subtitle */
            transition: transform 0.7s cubic-bezier(0.75, -0.5, 0.27, 1.55) !important;
        }

        .pegawai-login-card:hover .pegawai-title-area,
        .pegawai-login-card:focus-within .pegawai-title-area,
        .pegawai-login-card.force-open .pegawai-title-area {
            transform: translateY(0) !important;
        }

        /* Form Drawer - Slides up from the bottom on Hover/Focus */
        .pegawai-form-drawer {
            position: absolute !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            padding-left: 3rem !important;
            padding-right: 3rem !important;
            padding-bottom: 4rem !important;
            background-color: #093C5D !important;
            transform: translateY(320px) !important; /* Hidden at bottom when idle */
            opacity: 0 !important;
            pointer-events: none !important;
            overflow-y: auto !important;
            transition: 
                transform 0.7s cubic-bezier(0.75, -0.5, 0.27, 1.55),
                opacity 0.6s cubic-bezier(0.25, 1, 0.5, 1) !important;
        }

        .pegawai-login-card:hover .pegawai-form-drawer,
        .pegawai-login-card:focus-within .pegawai-form-drawer,
        .pegawai-login-card.force-open .pegawai-form-drawer {
            transform: translateY(0) !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }

        /* Mobile: Keep form visible by default, remove absolute sliding */
        @media (max-width: 767px) {
            .pegawai-login-card {
                height: auto !important;
                padding-bottom: 2.5rem !important;
            }
            .pegawai-title-area {
                transform: translateY(0) !important;
                padding-top: 2.5rem !important;
                padding-bottom: 0 !important;
                padding-left: 1.5rem !important;
                padding-right: 1.5rem !important;
            }
            .pegawai-form-drawer {
                position: relative !important;
                transform: translateY(0) !important;
                opacity: 1 !important;
                pointer-events: auto !important;
                padding-top: 2rem !important;
                padding-left: 1.5rem !important;
                padding-right: 1.5rem !important;
                padding-bottom: 1rem !important;
            }
        }

        /* Clean Rounded Inputs (Off-white with high contrast dark text) */
        .pegawai-input {
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            background-color: rgba(255, 255, 255, 0.95) !important;
            color: #0F172A !important;
            border-radius: 12px !important;
            padding-left: 2.75rem !important;
            padding-right: 3rem !important; /* Adjusted to 3rem to prevent text from overlapping eye icon button */
            padding-top: 0.75rem !important;
            padding-bottom: 0.75rem !important;
            font-size: 0.875rem !important;
            width: 100%;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .pegawai-input::placeholder {
            color: #64748B !important;
            opacity: 1 !important;
        }

        .pegawai-input:focus {
            background-color: #FFFFFF !important;
            border-color: #00A3E0 !important;
            box-shadow: 0 0 0 3px rgba(0, 163, 224, 0.25) !important;
            outline: none !important;
        }

        /* Premium White/Contrast Button */
        .pegawai-btn {
            background-color: #FFFFFF !important;
            color: #093C5D !important;
            border-radius: 12px !important;
            font-weight: 700 !important;
            padding-top: 0.75rem !important;
            padding-bottom: 0.75rem !important;
            width: 100%;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
        }

        .pegawai-btn:hover {
            background-color: #F8FAFC !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 20px -5px rgba(0, 0, 0, 0.25) !important;
        }

        .pegawai-btn:active {
            transform: translateY(0) !important;
        }

        /* Back Button */
        .pegawai-back-btn {
            background-color: #FFFFFF !important;
            border: 1px solid #E2E8F0 !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05) !important;
            transition: all 0.2s ease !important;
        }

        .pegawai-back-btn:hover {
            background-color: #F8FAFC !important;
            transform: translateX(-3px) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
        }
    </style>
</head>
<body class="font-sans text-slate-800 antialiased bg-white selection:bg-blue-100 selection:text-blue-900">

    <!-- A. TOPBAR (Fixed Header) -->
    <header class="fixed top-0 inset-x-0 h-20 pln-navbar border-b border-slate-100 z-50 flex items-center shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07)]">
        <div class="w-full px-4 md:px-6 flex items-center h-full">
            <div class="flex items-center gap-8 md:gap-10">
                <!-- Logo -->
                <a href="{{ route('landing') }}" class="flex items-center gap-3">
                    <div class="h-10 w-auto">
                        <img src="{{ asset('images/pln-logo2.png') }}" alt="PLN Logo" class="h-full object-contain">
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="font-bold text-lg tracking-tight" style="color: #0099ff;">PLN</span>
                        <span class="text-yellow-600 font-bold text-base tracking-wide">UP3 KUDUS</span>
                    </div>
                </a>
            </div>
        </div>
    </header>

    <main class="pt-20 bg-white">
        <!-- Hero Section -->
        <section class="relative w-full overflow-hidden pegawai-auth-bg min-h-[calc(100vh-5rem)] flex items-center justify-center py-12 z-10">
            
            <!-- Back Button inside Hero -->
            <div class="absolute top-8 left-4 md:left-8 z-20">
                <a href="{{ route('landing') }}" class="pegawai-back-btn flex items-center gap-2 px-4 py-2 rounded-full text-slate-700 font-medium text-sm">
                    <i class="fas fa-arrow-left text-xs"></i>
                    Kembali
                </a>
            </div>

            <!-- Login Form Container -->
            <div class="relative z-10 w-full flex justify-center px-4">
                <div class="pegawai-login-card relative @if($errors->any() || session('success') || old('email')) force-open @endif">
                    <!-- Title Area -->
                    <div class="pegawai-title-area text-center">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-white/90 text-xs font-bold uppercase tracking-wider mb-4">
                            <span class="w-2 h-2 rounded-full bg-[#00A3E0]"></span>
                            Login Area
                        </div>
                        <h2 class="text-2xl md:text-3xl font-extrabold text-white leading-tight">
                            Login sebagai: <span class="text-[#00A3E0]">Pegawai</span>
                        </h2>
                        <p class="text-white/70 text-sm mt-3 leading-normal tracking-wide block">Masuk ke panel internal Anda</p>
                    </div>

                    <!-- Form Drawer -->
                    <div class="pegawai-form-drawer">
                        <!-- Success Message -->
                        @if (session('success'))
                            <div class="mb-5 p-4 rounded-xl bg-green-500/20 border border-green-500/30 text-green-200 text-sm">
                                <i class="fas fa-check-circle mr-2"></i>
                                {{ session('success') }}
                            </div>
                        @endif

                        <!-- Error Messages -->
                        @if ($errors->any())
                            <div class="mb-5 p-4 rounded-xl bg-red-500/20 border border-red-500/30 text-red-200 text-sm">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Login Form -->
                        <form method="POST" action="{{ route('pegawai.login.post') }}" class="space-y-5">
                            @csrf

                            <!-- Email Field -->
                            <div>
                                <label for="email" class="block text-sm font-semibold text-white/80 mb-2">
                                    Email
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-white/40"></i>
                                    </div>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        value="{{ old('email') }}"
                                        required 
                                        class="block w-full pegawai-input @error('email') border-red-500 @enderror"
                                        placeholder="nama@domain.com"
                                    >
                                </div>
                            </div>

                            <!-- Password Field -->
                            <div>
                                <label for="password" class="block text-sm font-semibold text-white/80 mb-2">
                                    Password
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-white/40"></i>
                                    </div>
                                    <input 
                                        type="password" 
                                        id="password" 
                                        name="password" 
                                        required
                                        class="block w-full pegawai-input @error('password') border-red-500 @enderror"
                                        placeholder="........"
                                    >
                                    <button
                                        type="button"
                                        id="togglePassword"
                                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
                                        aria-label="Tampilkan password"
                                        aria-controls="password"
                                        aria-pressed="false"
                                    >
                                        <i id="togglePasswordIcon" class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Remember Me -->
                            <div class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="remember" 
                                    name="remember"
                                    class="w-4 h-4 text-[#00A3E0] border-white/20 rounded bg-white/10 focus:ring-[#00A3E0]"
                                >
                                <label for="remember" class="ml-2 text-sm text-white/80">
                                    Ingat saya
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <button 
                                type="submit"
                                class="w-full pegawai-btn flex items-center justify-center"
                            >
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                Masuk
                            </button>
                        </form>

                        <!-- Footer Info -->
                        <div class="mt-6 text-center text-xs text-white/40">
                            <p>Hanya untuk pegawai internal PLN UP3 Kudus</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            // Reset focus otomatis pada page load jika tidak ada error/alert untuk mengaktifkan state hover langsung
            const hasError = @json($errors->any() || session('success') || old('email'));
            if (!hasError) {
                const activeEl = document.activeElement;
                if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'BUTTON')) {
                    activeEl.blur();
                }
            }

            // Inisialisasi visual interaktif hover
            const card = document.querySelector('.pegawai-login-card');
            if (card) {
                card.style.pointerEvents = 'auto';
            }

            // Setup Show/Hide Password Toggle
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('togglePassword');
            const toggleIcon = document.getElementById('togglePasswordIcon');
 
            if (passwordInput && toggleButton && toggleIcon) {
                toggleButton.addEventListener('click', function () {
                    const isHidden = passwordInput.type === 'password';
     
                    passwordInput.type = isHidden ? 'text' : 'password';
                    toggleIcon.classList.toggle('fa-eye', !isHidden);
                    toggleIcon.classList.toggle('fa-eye-slash', isHidden);
                    toggleButton.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                    toggleButton.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
                });
            }
        });
    </script>
</body>
</html>
