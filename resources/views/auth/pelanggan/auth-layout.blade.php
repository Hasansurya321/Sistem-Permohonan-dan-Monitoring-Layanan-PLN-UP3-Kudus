<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ request()->routeIs('pelanggan.register') ? 'Registrasi Pelanggan - PLN UP3 Kudus' : 'Login Pelanggan - PLN UP3 Kudus' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts: Inter & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="font-sans antialiased auth-page-bg text-slate-800 select-none">

    <!-- Back Button -->
    <div class="absolute top-6 left-4 md:left-8 z-30">
        <a href="{{ route('landing') }}" class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/80 backdrop-blur border border-slate-100 shadow-sm hover:bg-white transition-all text-slate-700 font-semibold text-xs md:text-sm">
            <i class="fas fa-arrow-left text-xs"></i>
            Kembali
        </a>
    </div>

    <!-- Auth Container Card -->
    <div id="authCard" class="auth-card relative w-[85vw] max-w-5xl h-[80vh] min-h-[600px] max-h-[780px] rounded-3xl overflow-hidden flex flex-col md:flex-row z-10 {{ request()->routeIs('pelanggan.register') ? 'auth-state-register' : 'auth-state-login' }}">
        
        <!-- ==========================================
             LEFT SIDE: LOGIN FORM
             ========================================== -->
        <div class="auth-form-side auth-login-form-side flex flex-col justify-center px-6 sm:px-12 md:px-20 py-8 md:py-0 bg-white">
            <!-- Logo Brand -->
            <div class="flex items-center gap-3 mb-6 md:mb-8">
                <div class="h-10 w-auto">
                    <img src="{{ asset('images/pln-logo2.png') }}" alt="PLN Logo" class="h-full object-contain">
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-bold text-lg tracking-tight text-[#093C5D]">PLN</span>
                    <span class="text-yellow-600 font-bold text-xs tracking-wide">UP3 KUDUS</span>
                </div>
            </div>

            <!-- Heading Title -->
            <div class="mb-6 md:mb-8">
                <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">Login Pelanggan</h1>
                <p class="text-slate-500 text-sm mt-2 font-light">Masuk untuk melanjutkan ke layanan kelistrikan digital</p>
            </div>

            @if (session('success'))
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-start gap-3">
                    <div class="text-emerald-500 mt-0.5">
                        <i class="fas fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-emerald-950 text-sm">
                            {{ str_contains(session('success'), 'kirim') ? 'Email Dikirim' : 'Aktivasi Berhasil' }}
                        </h4>
                        <p class="text-emerald-800 text-xs mt-0.5 leading-relaxed">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 p-4 bg-rose-50 border border-rose-100 rounded-2xl flex items-start gap-3">
                    <div class="text-rose-500 mt-0.5">
                        <i class="fas fa-exclamation-circle text-lg"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-rose-950 text-sm">
                            {{ str_contains(session('error'), 'Cooldown') ? 'Cooldown Aktif' : 'Aktivasi Gagal' }}
                        </h4>
                        <p class="text-rose-800 text-xs mt-0.5 leading-relaxed">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <!-- Form login -->
            <form action="{{ route('pelanggan.login.submit') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Email</label>
                    <div class="auth-input-group">
                        <span class="auth-input-icon">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" name="email" id="email" required 
                               class="auth-input auth-input-has-icon @error('email') border-red-500 @enderror"
                               placeholder="nama@email.com" value="{{ old('email') }}">
                    </div>
                    @error('email')
                        <p class="text-red-500 text-xs mt-1.5 flex items-center gap-1 font-medium">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror

                    @php
                        $showResend = false;
                        $emailVal = old('email');
                        if ($emailVal) {
                            $hasRequestApproved = \App\Models\CustomerAccountRequest::where('email', $emailVal)->where('status', 'approved')->exists();
                            $hasUserApproved = \App\Models\User::where('email', $emailVal)->where('status', 'approved')->exists();
                            
                            if ($hasRequestApproved || $hasUserApproved) {
                                $userActive = \App\Models\User::where('email', $emailVal)->where('status', 'active')->exists();
                                if (!$userActive) {
                                    $showResend = true;
                                }
                            }
                        }
                    @endphp
                    @if ($showResend)
                        <div class="mt-3 p-3 bg-amber-50 border border-amber-100 rounded-2xl flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-paper-plane text-amber-500 text-xs"></i>
                                <span class="text-amber-800 text-[11px] font-semibold">Butuh kirim ulang email aktivasi?</span>
                            </div>
                            <form action="{{ route('pelanggan.activation.resend') }}" method="POST" id="resendForm" class="m-0 p-0">
                                @csrf
                                <input type="hidden" name="email" value="{{ $emailVal }}">
                                <button type="submit" class="px-3 py-1.5 bg-[#093C5D] hover:bg-[#0c4b75] text-white rounded-xl text-[10px] font-bold tracking-wider uppercase transition-all shadow-sm">
                                    Kirim Ulang
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                <!-- Password Field -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label for="password" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Password</label>
                        <a href="{{ route('pelanggan.forgot-password') }}" class="text-xs text-[#093C5D] hover:underline font-bold">
                            Lupa Password?
                        </a>
                    </div>
                    <div class="auth-input-group">
                        <span class="auth-input-icon">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="password" required 
                               class="auth-input auth-input-has-icon pr-12 @error('password') border-red-500 @enderror"
                               placeholder="••••••••">
                        <button type="button" id="togglePassword" class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors cursor-pointer" aria-label="Tampilkan password">
                            <i class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-red-500 text-xs mt-1.5 flex items-center gap-1 font-medium">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit" class="auth-btn w-full font-semibold">
                    Masuk Layanan
                </button>
            </form>
            
            <!-- Mobile Register Helper Link -->
            <div class="mt-6 text-center text-sm text-slate-500 md:hidden">
                Belum memiliki akun? <button type="button" onclick="toggleAuthState()" class="text-[#093C5D] font-bold hover:underline bg-transparent border-none p-0 cursor-pointer">Daftar di sini</button>
            </div>
        </div>

        <!-- ==========================================
             RIGHT SIDE: REGISTER FORM
             ========================================== -->
        <div class="auth-form-side auth-register-form-side flex flex-col justify-center px-6 sm:px-12 md:px-16 py-8 md:py-0 bg-white">
            <!-- Logo Brand -->
            <div class="flex items-center gap-2.5 mb-4 md:mb-5">
                <div class="h-8 w-auto">
                    <img src="{{ asset('images/pln-logo2.png') }}" alt="PLN Logo" class="h-full object-contain">
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-bold text-base tracking-tight text-[#093C5D]">PLN</span>
                    <span class="text-yellow-600 font-bold text-[10px] tracking-wide">UP3 KUDUS</span>
                </div>
            </div>

            <!-- Heading Title -->
            <div class="mb-4">
                <h1 class="text-xl md:text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">Registrasi Pelanggan</h1>
                <p class="text-slate-500 text-xs mt-1 font-light">Lengkapi data diri Anda untuk membuat akun baru</p>
            </div>

            <!-- Scrollable Form Container -->
            <form action="{{ route('pelanggan.register.submit') }}" method="POST" class="auth-form-scroll space-y-4 pr-1">
                @csrf

                <!-- Section 1: Data Diri -->
                <div>
                    <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-1 mb-2.5">
                        1. Data Diri
                    </div>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-2.5">
                        <!-- Nama Lengkap -->
                        <div class="col-span-2">
                            <label for="reg_name" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Nama Lengkap</label>
                            <input type="text" name="name" id="reg_name" required 
                                   class="auth-input @error('name') border-red-500 @enderror"
                                   placeholder="Sesuai KTP" value="{{ old('name') }}">
                            @error('name') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- NIK -->
                        <div class="col-span-2">
                            <label for="reg_nik" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">NIK</label>
                            <input type="text" name="nik" id="reg_nik" 
                                   class="auth-input @error('nik') border-red-500 @enderror"
                                   placeholder="16 digit NIK" value="{{ old('nik') }}" maxlength="16">
                            @error('nik') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Nomor NPWP -->
                        <div class="col-span-2">
                            <label for="reg_npwp" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Nomor NPWP</label>
                            <input type="text" name="nomor_npwp" id="reg_npwp" 
                                   class="auth-input @error('nomor_npwp') border-red-500 @enderror"
                                   placeholder="15 digit NPWP (angka saja)" value="{{ old('nomor_npwp') }}" maxlength="15">
                            @error('nomor_npwp') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- SLO Reg -->
                        <div>
                            <label for="reg_slo_reg" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">SLO Reg</label>
                            <input type="text" name="slo_reg" id="reg_slo_reg" 
                                   class="auth-input @error('slo_reg') border-red-500 @enderror"
                                   placeholder="SLO-REG-XXXX-XXXXX" value="{{ old('slo_reg') }}" maxlength="50">
                            @error('slo_reg') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- SLO Cert -->
                        <div>
                            <label for="reg_slo_cert" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">SLO Cert</label>
                            <input type="text" name="slo_cert" id="reg_slo_cert" 
                                   class="auth-input @error('slo_cert') border-red-500 @enderror"
                                   placeholder="SLO-CERT-XXXX-XXXX-XXXXX" value="{{ old('slo_cert') }}" maxlength="100">
                            @error('slo_cert') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- No KK -->
                        <div class="col-span-2">
                            <label for="reg_no_kk" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">No KK</label>
                            <input type="text" name="no_kk" id="reg_no_kk" 
                                   class="auth-input @error('no_kk') border-red-500 @enderror"
                                   placeholder="16 digit No KK" value="{{ old('no_kk') }}" maxlength="16">
                            @error('no_kk') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Jenis Kelamin -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Jenis Kelamin</label>
                            <div class="flex gap-4 py-1">
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="gender" value="L" class="w-3.5 h-3.5 text-[#093C5D] focus:ring-[#093C5D]/40" {{ old('gender') == 'L' ? 'checked' : '' }}>
                                    <span class="text-[11px] text-slate-600 font-medium">Laki-laki</span>
                                </label>
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="gender" value="P" class="w-3.5 h-3.5 text-[#093C5D] focus:ring-[#093C5D]/40" {{ old('gender') == 'P' ? 'checked' : '' }}>
                                    <span class="text-[11px] text-slate-600 font-medium">Perempuan</span>
                                </label>
                            </div>
                        </div>

                        <!-- No. Handphone -->
                        <div>
                            <label for="reg_phone" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">No. Handphone</label>
                            <input type="tel" name="phone" id="reg_phone" required 
                                   class="auth-input @error('phone') border-red-500 @enderror"
                                   placeholder="08xxxxxxxxxx" value="{{ old('phone') }}">
                            @error('phone') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Email -->
                        <div class="col-span-2">
                            <label for="reg_email" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Email</label>
                            <input type="email" name="email" id="reg_email" required 
                                   class="auth-input @error('email') border-red-500 @enderror"
                                   placeholder="nama@email.com" value="{{ old('email') }}">
                            @error('email') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 2: Alamat Domisili -->
                <div>
                    <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-1 mb-2.5">
                        2. Alamat Domisili
                    </div>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-2.5">
                        <!-- Provinsi -->
                        <div>
                            <label for="reg_province" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Provinsi</label>
                            <select name="province" id="reg_province" required class="auth-input">
                                <option value="">Pilih Provinsi</option>
                                <option value="Jawa Tengah" {{ old('province') == 'Jawa Tengah' ? 'selected' : '' }}>Jawa Tengah</option>
                            </select>
                        </div>

                        <!-- Kabupaten/Kota -->
                        <div>
                            <label for="reg_regency" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Kota/Kab</label>
                            <select name="regency" id="regency" required class="auth-input">
                                <option value="">Pilih Kota/Kab</option>
                                <option value="Kudus" {{ old('regency') == 'Kudus' ? 'selected' : '' }}>Kudus</option>
                            </select>
                        </div>

                        <!-- Kecamatan -->
                        <div>
                            <label for="reg_district" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Kecamatan</label>
                            <select name="district" id="reg_district" required class="auth-input">
                                <option value="">Pilih Kecamatan</option>
                                <option value="Kota Kudus" {{ old('district') == 'Kota Kudus' ? 'selected' : '' }}>Kota Kudus</option>
                                <option value="Jati" {{ old('district') == 'Jati' ? 'selected' : '' }}>Jati</option>
                            </select>
                        </div>

                        <!-- Kelurahan/Desa -->
                        <div>
                            <label for="reg_village" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Kelurahan/Desa</label>
                            <input type="text" name="village" id="reg_village" required 
                                   class="auth-input @error('village') border-red-500 @enderror"
                                   placeholder="Nama Desa" value="{{ old('village') }}">
                            @error('village') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Kode Pos -->
                        <div>
                            <label for="reg_postal" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Kode Pos</label>
                            <input type="text" name="postal_code" id="reg_postal" required 
                                   class="auth-input @error('postal_code') border-red-500 @enderror"
                                   placeholder="593xx" value="{{ old('postal_code') }}">
                            @error('postal_code') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Detail Alamat -->
                        <div>
                            <label for="reg_address" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Detail Alamat</label>
                            <input type="text" name="address_detail" id="reg_address" required 
                                   class="auth-input @error('address_detail') border-red-500 @enderror"
                                   placeholder="Jalan, RT/RW, No. Rumah" value="{{ old('address_detail') }}">
                            @error('address_detail') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Id Pelanggan -->
                        <div>
                            <label for="reg_id_pelanggan" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">ID Pelanggan</label>
                            <input type="text" name="id_pelanggan" id="reg_id_pelanggan" 
                                   class="auth-input @error('id_pelanggan') border-red-500 @enderror"
                                   placeholder="12 digit ID" value="{{ old('id_pelanggan') }}" maxlength="12">
                            @error('id_pelanggan') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Nomor Meter -->
                        <div>
                            <label for="reg_nomor_meter" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Nomor Meter</label>
                            <input type="text" name="nomor_meter" id="reg_nomor_meter" 
                                   class="auth-input @error('nomor_meter') border-red-500 @enderror"
                                   placeholder="11 digit Nomor Meter" value="{{ old('nomor_meter') }}" maxlength="11">
                            @error('nomor_meter') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 3: Keamanan Akun -->
                <div>
                    <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-1 mb-2.5">
                        3. Keamanan Akun
                    </div>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-2.5">
                        <!-- Password -->
                        <div>
                            <label for="reg_password" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Password</label>
                            <input type="password" name="password" id="reg_password" required 
                                   class="auth-input @error('password') border-red-500 @enderror"
                                   placeholder="Min. 8 karakter">
                            @error('password') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Konfirmasi Password -->
                        <div>
                            <label for="reg_password_conf" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" id="reg_password_conf" required 
                                   class="auth-input"
                                   placeholder="Ulangi password">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="auth-btn w-full font-semibold mt-4">
                    Daftar Akun Baru
                </button>
            </form>

            <!-- Mobile Login Helper Link -->
            <div class="mt-4 text-center text-sm text-slate-500 md:hidden">
                Sudah memiliki akun? <button type="button" onclick="toggleAuthState()" class="text-[#093C5D] font-bold hover:underline bg-transparent border-none p-0 cursor-pointer">Masuk di sini</button>
            </div>
        </div>

        <!-- ==========================================
             ABSOLUTE MOVING OVERLAY PANEL
             ========================================== -->
        <div id="brandingOverlay" class="auth-overlay-panel">
            
            <!-- Decorative Glow Elements inside panel -->
            <div class="auth-glow-circle-1"></div>
            <div class="auth-glow-circle-2"></div>
            
            <!-- State A: Welcome to Login (shown when overlay is on the right, covering Register) -->
            <div id="overlayContentLogin" class="auth-overlay-content absolute inset-0 flex flex-col items-center justify-center text-center px-12">
                <!-- Circular Logo Wrapper -->
                <div class="w-20 h-20 bg-white/5 rounded-3xl flex items-center justify-center border border-white/10 mb-8 backdrop-blur-md shadow-2xl">
                    <img src="{{ asset('images/pln-logo2.png') }}" alt="PLN Logo" class="h-12 w-auto">
                </div>
                
                <h2 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight leading-tight mb-4">
                    Selamat Datang
                </h2>
                <p class="text-[#B8C9D6] text-sm md:text-base font-light leading-relaxed max-w-xs mb-10">
                    Masuk kembali untuk melanjutkan layanan pelanggan PLN UP3 Kudus.
                </p>
                
                <p class="text-xs text-slate-400 mb-3">Belum memiliki akun?</p>
                <button type="button" onclick="toggleAuthState()" class="px-10 py-3.5 rounded-full border border-white/20 bg-white/5 hover:bg-white text-white hover:text-[#093C5D] font-bold text-sm tracking-wider uppercase transition-all duration-300 transform hover:scale-105 hover:shadow-2xl cursor-pointer">
                    Daftar Sekarang
                </button>
            </div>

            <!-- State B: Welcome to Register (shown when overlay is on the left, covering Login) -->
            <div id="overlayContentRegister" class="auth-overlay-content absolute inset-0 flex flex-col items-center justify-center text-center px-12 opacity-0 pointer-events-none">
                <!-- Circular Logo Wrapper -->
                <div class="w-20 h-20 bg-white/5 rounded-3xl flex items-center justify-center border border-white/10 mb-8 backdrop-blur-md shadow-2xl">
                    <img src="{{ asset('images/pln-logo2.png') }}" alt="PLN Logo" class="h-12 w-auto">
                </div>
                
                <h2 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight leading-tight mb-4">
                    Sudah Memiliki Akun?
                </h2>
                <p class="text-[#B8C9D6] text-sm md:text-base font-light leading-relaxed max-w-xs mb-10">
                    Login untuk melanjutkan akses layanan pelanggan PLN UP3 Kudus.
                </p>
                
                <p class="text-xs text-slate-400 mb-3">Sudah punya akun?</p>
                <button type="button" onclick="toggleAuthState()" class="px-10 py-3.5 rounded-full border border-white/20 bg-white/5 hover:bg-white text-white hover:text-[#093C5D] font-bold text-sm tracking-wider uppercase transition-all duration-300 transform hover:scale-105 hover:shadow-2xl cursor-pointer">
                    Masuk
                </button>
            </div>
        </div>

    </div>

    <!-- Scripts -->
    <script>
        function toggleAuthState() {
            const card = document.getElementById('authCard');
            const isLogin = card.classList.contains('auth-state-login');
            
            if (isLogin) {
                // Switch to Register
                card.classList.remove('auth-state-login');
                card.classList.add('auth-state-register');
                
                // Update URL and Title without reload
                history.pushState({ state: 'register' }, '', '{{ route("pelanggan.register") }}');
                document.title = "Registrasi Pelanggan - PLN UP3 Kudus";
            } else {
                // Switch to Login
                card.classList.remove('auth-state-register');
                card.classList.add('auth-state-login');
                
                // Update URL and Title without reload
                history.pushState({ state: 'login' }, '', '{{ route("pelanggan.login") }}');
                document.title = "Login Pelanggan - PLN UP3 Kudus";
            }
        }

        // Handle Browser Back & Forward Actions
        window.addEventListener('popstate', function (event) {
            const card = document.getElementById('authCard');
            if (window.location.pathname.includes('/register')) {
                card.classList.remove('auth-state-login');
                card.classList.add('auth-state-register');
                document.title = "Registrasi Pelanggan - PLN UP3 Kudus";
            } else {
                card.classList.remove('auth-state-register');
                card.classList.add('auth-state-login');
                document.title = "Login Pelanggan - PLN UP3 Kudus";
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            // Password visibility toggle
            const toggle = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            if (toggle && password) {
                const icon = toggle.querySelector('i');
                toggle.addEventListener('click', function () {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    
                    if (type === 'password') {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    } else {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    }
                });
            }
        });
    </script>
</body>
</html>
