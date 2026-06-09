<?php
/**
 * Halaman Gagal Pembayaran
 * Blueprint: payment-failed template
 * 
 * Displayed ketika:
 * - Guard 2: attempts >= 3 (batas percobaan habis)
 * - Guard 3: Payment record tidak valid
 */
$serviceRequest = $serviceRequest ?? null;
$message = $message ?? 'Pembayaran gagal. Silakan coba lagi.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Gagal - PLN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-shake {
            animation: shake 0.5s ease-in-out;
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
<body class="bg-gradient-to-br from-red-50 to-orange-100 min-h-screen flex items-center justify-center p-4">
    
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden animate-fade-in">
        
        <!-- Header Failed -->
        <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-8 text-center">
            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 animate-shake">
                <i class="fas fa-times text-red-500 text-4xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white mb-2">Pembayaran Gagal</h1>
            <p class="text-red-100">Mohon maaf, pembayaran tidak dapat diproses</p>
        </div>

        <!-- Content -->
        <div class="px-6 py-6">
            
            <!-- Error Message -->
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4 animate-fade-in-delay">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-circle text-red-500 mt-1 mr-3"></i>
                    <div>
                        <p class="text-sm text-red-800 font-medium">Informasi Error</p>
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    </div>
                </div>
            </div>

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
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Pembayaran Gagal
                        </span>
                    </div>

                    @if($serviceRequest->payment_attempt_count !== null)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Percobaan</span>
                        <span class="font-semibold text-red-600">
                            {{ $serviceRequest->payment_attempt_count }}/3 kali
                        </span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Warning Box -->
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 animate-fade-in-delay">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-amber-500 mt-1 mr-3"></i>
                    <div>
                        <p class="text-sm text-amber-800 font-medium">Perhatian</p>
                        <p class="text-xs text-amber-600 mt-1">
                            @if($serviceRequest && $serviceRequest->payment_attempt_count >= 3)
                                Anda telah melewati batas maksimal 3 kali percobaan pembayaran. 
                                Silakan hubungi Admin PLN untuk informasi lebih lanjut.
                            @else
                                Pastikan koneksi internet stabil dan ulang pembayaran sebelum masa berlaku habis.
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3 animate-fade-in-delay">
                @if(!$serviceRequest || $serviceRequest->payment_attempt_count < 3)
                <a href="{{ route('landing') }}" 
                   class="block w-full bg-gradient-to-r from-red-500 to-red-600 text-white text-center py-3 px-4 rounded-xl font-semibold hover:from-red-600 hover:to-red-700 transition-all shadow-lg">
                    <i class="fas fa-home mr-2"></i>
                    Kembali ke Beranda
                </a>
                @endif
                
                <a href="{{ route('monitoring') }}" 
                   class="block w-full bg-gray-100 text-gray-700 text-center py-3 px-4 rounded-xl font-semibold hover:bg-gray-200 transition-all">
                    <i class="fas fa-list mr-2"></i>
                    Lihat Status Permohonan
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 text-center border-t">
            <p class="text-xs text-gray-500">
                <i class="fas fa-headset mr-1"></i>
                Butuh bantuan? Hubungi PLN Call Center 123
            </p>
        </div>
    </div>

</body>
</html>
