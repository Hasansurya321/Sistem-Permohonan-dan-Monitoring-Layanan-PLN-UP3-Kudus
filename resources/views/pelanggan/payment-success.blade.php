<?php
/**
 * Halaman Sukses Pembayaran
 * Blueprint: payment-success template
 * 
 * Displayed ketika:
 * - Guard 1 (idempotency) lolos - sudah pernah bayar
 * - Guard 2 (attempts < 3) + proses sukses
 */
$serviceRequest = $serviceRequest ?? null;
$payment = $payment ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil - PLN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes checkmark {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-checkmark {
            animation: checkmark 0.6s ease-out forwards;
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out 0.3s forwards;
            opacity: 0;
        }
        .animate-fade-in-delay {
            animation: fadeIn 0.5s ease-out 0.5s forwards;
            opacity: 0;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 to-emerald-100 min-h-screen flex items-center justify-center p-4">
    
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden animate-fade-in">
        
        <!-- Header Success -->
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-8 text-center">
            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 animate-checkmark">
                <i class="fas fa-check text-green-500 text-4xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white mb-2">Pembayaran Berhasil!</h1>
            <p class="text-green-100">Terima kasih atas pembayaran Anda</p>
        </div>

        <!-- Content -->
        <div class="px-6 py-6">
            
            @if($serviceRequest)
            <!-- Info Permohonan -->
            <div class="bg-gray-50 rounded-xl p-4 mb-4 animate-fade-in-delay">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Detail Permohonan</h3>
                
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Nomor</span>
                        <span class="font-semibold text-gray-800">{{ $serviceRequest->nomor_permohonan ?? '-' }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Jenis Layanan</span>
                        <span class="font-semibold text-gray-800">
                            @switch($serviceRequest->jenis_layanan)
                                @case('TAMBAH_DAYA')
                                    Tambah Daya
                                    @break
                                @case('PASANG_BARU')
                                    Pasang Baru
                                    @break
                                @default
                                    {{ $serviceRequest->jenis_layanan }}
                            @endswitch
                        </span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Status</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-clock mr-1"></i>
                            Menunggu Konfirmasi
                        </span>
                    </div>
                </div>
            </div>
            @endif

            @if($payment)
            <!-- Info Pembayaran -->
            <div class="bg-gray-50 rounded-xl p-4 mb-4 animate-fade-in-delay">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Detail Pembayaran</h3>
                
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Metode</span>
                        <span class="font-semibold text-gray-800">QRIS</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Nomor Referensi</span>
                        <span class="font-semibold text-gray-800">{{ $payment->ref_no ?? '-' }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Jumlah</span>
                        <span class="font-bold text-lg text-green-600">
                            Rp {{ number_format($payment->amount ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Waktu</span>
                        <span class="text-gray-800">{{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : '-' }}</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 animate-fade-in-delay">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                    <div>
                        <p class="text-sm text-blue-800 font-medium">Konfirmasi Pembayaran</p>
                        <p class="text-xs text-blue-600 mt-1">
                            Pembayaran Anda sedang dalam proses verifikasi. 
                            Status akan diperbarui secara otomatis setelah admin mengkonfirmasi.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3 animate-fade-in-delay">
                <a href="{{ route('monitoring') }}" 
                   class="block w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white text-center py-3 px-4 rounded-xl font-semibold hover:from-green-600 hover:to-emerald-700 transition-all shadow-lg">
                    <i class="fas fa-list mr-2"></i>
                    Lihat Status Permohonan
                </a>
                
                <a href="{{ route('landing') }}" 
                   class="block w-full bg-gray-100 text-gray-700 text-center py-3 px-4 rounded-xl font-semibold hover:bg-gray-200 transition-all">
                    <i class="fas fa-home mr-2"></i>
                    Kembali ke Beranda
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 text-center border-t">
            <p class="text-xs text-gray-500">
                <i class="fas fa-shield-alt mr-1"></i>
                Sistem Pembayaran PLN dilindungi dengan enkripsi
            </p>
        </div>
    </div>

</body>
</html>
