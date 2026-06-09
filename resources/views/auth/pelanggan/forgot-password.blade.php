@php
    $forceOpen = session('success') || $errors->any() || old('nama') || old('email') || old('nik');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - PLN UP3 Kudus</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* Pure solid white page background */
        .auth-page-bg {
            background-color: #FFFFFF !important;
            background-image: none !important;
        }

        .lupa-card {
            max-width: 32rem !important; /* Fixed width matching pegawai login */
            width: 100% !important;
            height: 560px !important; /* Fixed height constraint */
            position: relative !important;
            overflow: hidden !important;
            box-shadow: 
                0 4px 6px -1px rgba(9, 60, 93, 0.01),
                0 12px 24px -4px rgba(9, 60, 93, 0.03),
                0 20px 40px -10px rgba(9, 60, 93, 0.05),
                0 32px 64px -16px rgba(9, 60, 93, 0.08) !important;
            
            border-top: 1.5px solid rgba(0, 163, 224, 0.2) !important; /* Top light cyan border reflection */
            transition: 
                box-shadow 0.5s ease,
                border-color 0.5s ease !important;
        }

        /* Hover & Focus state for Card Glow & Shadow */
        .lupa-card:hover,
        .lupa-card:focus-within {
            border-color: rgba(0, 163, 224, 0.4) !important;
            box-shadow: 
                0 10px 20px -5px rgba(9, 60, 93, 0.05),
                0 25px 50px -10px rgba(9, 60, 93, 0.12),
                0 45px 90px -15px rgba(9, 60, 93, 0.2),
                0 70px 140px -20px rgba(9, 60, 93, 0.3),
                0 0 80px -10px rgba(0, 163, 224, 0.2) !important; /* Soft cyan glow */
        }

        /* Title Area - Centered when idle, slides up on Hover/Focus */
        .lupa-title-area {
            padding-top: 6.5rem !important;
            padding-left: 3rem !important;
            padding-right: 3rem !important;
            transform: translateY(70px) !important; /* Adjusted translation to align key logo nicely */
            transition: transform 0.7s cubic-bezier(0.75, -0.5, 0.27, 1.55) !important;
        }

        .lupa-card:hover .lupa-title-area,
        .lupa-card:focus-within .lupa-title-area,
        .lupa-card.force-open .lupa-title-area {
            transform: translateY(0) !important;
            padding-top: 3rem !important;
        }

        /* Form Drawer - Slides up from the bottom on Hover/Focus */
        .lupa-form-drawer {
            position: absolute !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            padding-left: 3rem !important;
            padding-right: 3rem !important;
            padding-bottom: 2.5rem !important;
            background-color: #093C5D !important;
            transform: translateY(355px) !important; /* Hidden at bottom when idle */
            opacity: 0 !important;
            pointer-events: none !important;
            max-height: 380px !important;
            overflow-y: auto !important;
            scrollbar-width: none !important; /* Firefox */
            transition: 
                transform 0.7s cubic-bezier(0.75, -0.5, 0.27, 1.55),
                opacity 0.6s cubic-bezier(0.25, 1, 0.5, 1) !important;
        }

        .lupa-form-drawer::-webkit-scrollbar {
            display: none !important; /* Chrome/Safari/Opera */
        }

        .lupa-card:hover .lupa-form-drawer,
        .lupa-card:focus-within .lupa-form-drawer,
        .lupa-card.force-open .lupa-form-drawer {
            transform: translateY(0) !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }

        /* Mobile: Keep form visible by default, remove absolute sliding */
        @media (max-width: 767px) {
            .lupa-card {
                height: auto !important;
                padding-bottom: 2.5rem !important;
            }
            .lupa-title-area {
                transform: translateY(0) !important;
                padding-top: 3.5rem !important;
                padding-bottom: 0 !important;
                padding-left: 1.5rem !important;
                padding-right: 1.5rem !important;
            }
            .lupa-form-drawer {
                position: relative !important;
                transform: translateY(0) !important;
                opacity: 1 !important;
                pointer-events: auto !important;
                padding-top: 2rem !important;
                padding-left: 1.5rem !important;
                padding-right: 1.5rem !important;
                padding-bottom: 1rem !important;
                max-height: none !important;
            }
        }

        /* Inputs configuration */
        .lupa-input {
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            background-color: rgba(255, 255, 255, 0.95) !important;
            color: #0F172A !important;
            border-radius: 12px !important;
            padding-left: 1rem !important;
            padding-right: 1rem !important;
            padding-top: 0.65rem !important;
            padding-bottom: 0.65rem !important;
            font-size: 0.875rem !important;
            width: 100%;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
            outline: none !important;
        }

        .lupa-input::placeholder {
            color: #64748B !important;
            opacity: 1 !important;
        }

        .lupa-input:focus {
            background-color: #FFFFFF !important;
            border-color: #00A3E0 !important;
            box-shadow: 0 0 0 3px rgba(0, 163, 224, 0.25) !important;
        }

        /* Disabled submit button state */
        .btn-submit-disabled {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: rgba(255, 255, 255, 0.3) !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            cursor: not-allowed !important;
            box-shadow: none !important;
        }
    </style>
</head>
<body class="font-sans antialiased auth-page-bg text-slate-800 min-h-screen flex items-center justify-center p-4">

    <!-- Back Button -->
    <div class="absolute top-8 left-4 md:left-8 z-20">
        <a href="{{ route('pelanggan.login') }}" class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/70 backdrop-blur border border-slate-100 shadow-sm hover:bg-white transition text-slate-700 font-semibold text-sm">
            <i class="fas fa-arrow-left text-xs"></i>
            Kembali Login
        </a>
    </div>

    <div class="w-full max-w-lg lupa-card {{ $forceOpen ? 'force-open' : '' }} bg-[#093C5D] rounded-3xl border border-white/10 relative">
        <!-- Title Area (Centered when Idle, Slides up on Hover) -->
        <div class="lupa-title-area text-center">
            <div class="w-16 h-16 bg-white/10 text-yellow-400 border border-white/10 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl shadow-inner">
                <i class="fas fa-unlock-alt"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">Lupa Password</h1>
            <p class="text-white/70 text-sm mt-1">Verifikasi identitas untuk reset password</p>
        </div>

        <!-- Form Drawer (Slides up from bottom) -->
        <div class="lupa-form-drawer">
            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-green-500/10 border border-green-500/20 flex items-start gap-3">
                    <i class="fas fa-check-circle text-green-400 mt-0.5"></i>
                    <div>
                        <h4 class="font-bold text-green-300 text-sm">Permintaan Terkirim!</h4>
                        <p class="text-green-200 text-sm mt-1">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @error('global')
                <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 flex items-start gap-3">
                    <i class="fas fa-exclamation-circle text-red-400 mt-0.5"></i>
                    <p class="text-red-200 text-sm">{{ $message }}</p>
                </div>
            @enderror

            <form action="{{ route('pelanggan.forgot-password.store') }}" method="POST" class="space-y-5">
                @csrf

                <!-- 1. NAMA LENGKAP -->
                <div>
                    <label class="block text-sm font-bold text-white/80 mb-2">Nama Lengkap</label>
                    <div class="flex gap-2">
                        <input type="text" id="nama" name="nama" value="{{ old('nama') }}" 
                               class="flex-1 lupa-input"
                               placeholder="Sesuai nama di akun">
                        <button type="button" onclick="verifyField('nama')" 
                                class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white border border-white/10 font-semibold rounded-xl transition text-sm cursor-pointer">
                            Verifikasi
                        </button>
                    </div>
                    <div id="status-nama" class="hidden mt-2 text-xs flex items-center gap-1 font-medium"></div>
                </div>

                <!-- 2. EMAIL -->
                <div>
                    <label class="block text-sm font-bold text-white/80 mb-2">Email Terdaftar</label>
                    <div class="flex gap-2">
                        <input type="email" id="email" name="email" value="{{ old('email') }}" 
                               class="flex-1 lupa-input"
                               placeholder="Contoh: nama@domain.com">
                        <button type="button" onclick="verifyField('email')" 
                                class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white border border-white/10 font-semibold rounded-xl transition text-sm cursor-pointer">
                            Verifikasi
                        </button>
                    </div>
                    <div id="status-email" class="hidden mt-2 text-xs flex items-center gap-1 font-medium"></div>
                </div>

                <!-- 3. NIK -->
                <div>
                    <label class="block text-sm font-bold text-white/80 mb-2">NIK (16 Digit)</label>
                    <div class="flex gap-2">
                        <input type="text" id="nik" name="nik" value="{{ old('nik') }}" maxlength="16"
                               class="flex-1 lupa-input"
                               placeholder="16 Digit Angka">
                        <button type="button" onclick="verifyField('nik')" 
                                class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white border border-white/10 font-semibold rounded-xl transition text-sm cursor-pointer">
                            Verifikasi
                        </button>
                    </div>
                    <div id="status-nik" class="hidden mt-2 text-xs flex items-center gap-1 font-medium"></div>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="pt-4">
                    <button type="submit" id="btn-submit" disabled
                            class="w-full py-3.5 px-4 btn-submit-disabled font-bold rounded-xl transition-all shadow-none">
                        Kirim Link Reset
                    </button>
                    <p class="text-xs text-center text-white/50 mt-2">Tombol aktif jika semua data terverifikasi.</p>
                </div>
            </form>
        </div>
    </div>

    <script>
        const verificationState = {
            nama: false,
            email: false,
            nik: false
        };

        const routes = {
            nama: "{{ route('pelanggan.forgot-password.verify-nama') }}",
            email: "{{ route('pelanggan.forgot-password.verify-email') }}",
            nik: "{{ route('pelanggan.forgot-password.verify-nik') }}"
        };

        async function verifyField(field) {
            const input = document.getElementById(field);
            const statusDiv = document.getElementById('status-' + field);
            const value = input.value;

            // Basic client validation
            if (!value) {
                showStatus(field, false, 'Field tidak boleh kosong');
                return;
            }

            // UI Loading
            statusDiv.classList.remove('hidden');
            statusDiv.className = 'mt-2 text-xs flex items-center gap-1 font-medium text-white/50';
            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memeriksa...';

            try {
                const response = await fetch(routes[field], {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ [field]: value })
                });

                const data = await response.json();

                if (response.ok && data.exists) {
                    showStatus(field, true, data.message);
                    verificationState[field] = true;
                } else {
                    const msg = data.message || 'Data tidak ditemukan';
                    showStatus(field, false, msg);
                    verificationState[field] = false;
                }
            } catch (error) {
                console.error(error);
                showStatus(field, false, 'Gagal memverifikasi. Coba lagi.');
                verificationState[field] = false;
            }

            updateSubmitButton();
        }

        function showStatus(field, success, message) {
            const statusDiv = document.getElementById('status-' + field);
            statusDiv.classList.remove('hidden');
            
            if (success) {
                statusDiv.className = 'mt-2 text-xs flex items-center gap-1 font-medium text-green-400';
                statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
            } else {
                statusDiv.className = 'mt-2 text-xs flex items-center gap-1 font-medium text-red-400';
                statusDiv.innerHTML = '<i class="fas fa-times-circle"></i> ' + message;
            }
        }

        function updateSubmitButton() {
            const btn = document.getElementById('btn-submit');
            if (verificationState.nama && verificationState.email && verificationState.nik) {
                btn.disabled = false;
                btn.className = "w-full py-3.5 px-4 bg-[#00A3E0] hover:bg-[#0092C9] text-white font-bold rounded-xl shadow-md hover:shadow-lg transition-all cursor-pointer";
            } else {
                btn.disabled = true;
                btn.className = "w-full py-3.5 px-4 btn-submit-disabled font-bold rounded-xl transition-all shadow-none";
            }
        }

        // Input listeners to reset verification if changed
        ['nama', 'email', 'nik'].forEach(field => {
            document.getElementById(field).addEventListener('input', () => {
                if (verificationState[field]) {
                    verificationState[field] = false;
                    document.getElementById('status-' + field).classList.add('hidden');
                    updateSubmitButton();
                }
            });
        });
    </script>
</body>
</html>
