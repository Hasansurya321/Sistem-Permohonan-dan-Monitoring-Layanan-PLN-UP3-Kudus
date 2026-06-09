<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password Pegawai - PLN UP3 Kudus</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background: #FFFFFF; color: #0F172A; font-family: Inter, sans-serif; }
        .card { max-width: 32rem; margin: 4rem auto; padding: 2rem; border-radius: 28px; background: #093C5D; box-shadow: 0 20px 50px rgba(9, 60, 93, 0.12); color: #FFFFFF; }
        .input { width: 100%; padding: 0.95rem 1rem; border-radius: 14px; border: 1px solid rgba(255,255,255,0.16); background: #FFFFFF; color: #0F172A; }
        .button { width: 100%; padding: 0.95rem 1rem; border-radius: 14px; background: #FFFFFF; color: #093C5D; font-weight: 700; border: none; cursor: pointer; }
        .button:hover { background: #E2E8F0; }
        .alert { padding: 1rem; border-radius: 14px; background: rgba(16, 185, 129, 0.12); color: #DCFCE7; margin-bottom: 1rem; }
        .error { padding: 1rem; border-radius: 14px; background: rgba(239, 68, 68, 0.14); color: #FEE2E2; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1 class="text-3xl font-bold mb-3">Reset Password Pegawai</h1>
        <p class="text-white/70 mb-6">Masukkan email pegawai untuk menerima link reset password.</p>

        @if(session('success'))
            <div class="alert">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="error">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('pegawai.forgot-password.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-white/85 mb-2">Email Pegawai</label>
                <input type="email" name="email" value="{{ old('email') }}" class="input" required autocomplete="email" placeholder="nama@domain.com">
            </div>

            <button type="submit" class="button">Kirim Link Reset</button>
        </form>

        <p class="text-sm text-white/60 mt-4">Link berlaku 60 menit. Token reset hanya berlaku untuk akun pegawai aktif.</p>
        <p class="text-sm text-white/60 mt-2"><a href="{{ route('pegawai.login') }}" class="text-white underline">Kembali ke login pegawai</a></p>
    </div>
</body>
</html>
