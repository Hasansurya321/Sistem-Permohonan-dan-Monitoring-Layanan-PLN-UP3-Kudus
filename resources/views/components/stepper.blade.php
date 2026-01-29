@props(['currentStep' => 1])

@php
    $steps = [
        1 => 'Pilih Pemohon',
        2 => 'Detail Lokasi',
        3 => 'Detail Layanan',
        4 => 'Data SLO',
        5 => 'Pengajuan Instalasi',
    ];
@endphp

<div class="w-full max-w-4xl mx-auto mb-8 px-4">
    <div class="relative flex items-center justify-between w-full">
        <!-- Progress Bar Background -->
        <div class="absolute top-1/2 transform -translate-y-1/2 left-0 w-full h-1 bg-slate-200 -z-10 rounded-full"></div>
        
        <!-- Active Progress Bar -->
        @php
            $progressPercentage = ($currentStep - 1) / (count($steps) - 1) * 100;
        @endphp
        <div class="absolute top-1/2 transform -translate-y-1/2 left-0 h-1 bg-[#2F5AA8] -z-10 rounded-full transition-all duration-300 ease-in-out" style="width: {{ $progressPercentage }}%;"></div>

        @foreach ($steps as $stepNum => $label)
            @php
                $isCompleted = $stepNum < $currentStep;
                $isCurrent = $stepNum == $currentStep;
                $isDisabled = false; // All steps enabled in this flow
            @endphp

            <div class="flex flex-col items-center relative group">
                <!-- Step Circle -->
                <div class="w-10 h-10 rounded-full flex items-center justify-center border-2 z-10 bg-white transition-all duration-300
                    {{ $isCompleted ? 'border-green-500 bg-green-500 text-white' : '' }}
                    {{ $isCurrent ? 'border-[#2F5AA8] text-[#2F5AA8] shadow-[0_0_0_4px_rgba(47,90,168,0.2)] scale-110' : '' }}
                    {{ !$isCompleted && !$isCurrent ? 'border-slate-300 text-slate-400' : '' }}
                ">
                    @if ($isCompleted)
                        <i class="fas fa-check text-sm"></i>
                    @else
                        <span class="text-sm font-bold">{{ $stepNum }}</span>
                    @endif
                </div>

                <!-- Label -->
                <div class="absolute top-12 whitespace-nowrap text-xs font-semibold px-2 py-1 rounded-md transition-all duration-300
                    {{ $isCurrent ? 'text-[#2F5AA8] bg-blue-50' : 'text-slate-500' }}
                    {{ $isDisabled ? 'opacity-50' : '' }}
                ">
                    {{ $label }}
                </div>
            </div>
        @endforeach
    </div>
    <!-- Spacing for labels -->
    <div class="h-8"></div>
</div>
