@props(['steps', 'currentIndex', 'isCancelled' => false])

@php
    $totalSteps = count($steps);
@endphp

<div class="pln-timeline-card bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    {{-- Header --}}
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-[#2F5AA8]"></div>
            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-widest">Progress Permohonan</h3>
        </div>
        @if(!$isCancelled)
            @php
                $percent = $currentIndex !== null ? (int) round(($currentIndex / ($totalSteps - 1)) * 100) : 0;
            @endphp
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-[#2F5AA8]">{{ $percent }}%</span>
                <div class="w-24 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-[#2F5AA8] to-blue-400 rounded-full transition-all duration-700"
                         style="width: {{ $percent }}%"></div>
                </div>
            </div>
        @else
            <span class="text-xs font-semibold text-red-500 flex items-center gap-1">
                <i class="fas fa-times-circle"></i> Dibatalkan
            </span>
        @endif
    </div>

    {{-- Vertical Timeline --}}
    <div class="px-6 py-5">
        <div class="relative">
            {{-- Connector line --}}
            <div class="absolute left-5 top-5 bottom-5 w-px bg-slate-100" aria-hidden="true"></div>

            <div class="space-y-0">
                @foreach($steps as $index => $label)
                    @php
                        $isDone    = $index < $currentIndex;
                        $isActive  = $index === $currentIndex && !$isCancelled;
                        $isPending = $index > $currentIndex || ($index === $currentIndex && $isCancelled);
                        $isLast    = $index === $totalSteps - 1;

                        // Get corresponding PermohonanStatus for this step index
                        $stepStatus = collect(\App\Enums\PermohonanStatus::cases())
                            ->first(fn($s) => $s->getStepIndex() === $index);
                    @endphp

                    <div class="relative flex items-start gap-4 {{ !$isLast ? 'pb-6' : '' }}">
                        {{-- Step Circle --}}
                        <div class="relative z-10 shrink-0 flex items-center justify-center
                                    w-10 h-10 rounded-full border-2 transition-all duration-300
                                    {{ $isDone ? 'bg-green-500 border-green-500 shadow-green-200 shadow-md' : '' }}
                                    {{ $isActive ? 'bg-[#2F5AA8] border-[#2F5AA8] shadow-blue-200 shadow-md' : '' }}
                                    {{ $isPending && !$isActive ? 'bg-white border-slate-200' : '' }}">

                            @if($isDone)
                                <i class="fas fa-check text-white text-xs"></i>
                            @elseif($isActive)
                                {{-- Pulse ring + number --}}
                                <span class="absolute inset-0 rounded-full bg-[#2F5AA8] opacity-20 animate-ping"></span>
                                @if($stepStatus)
                                    <i class="fas {{ \App\Support\WorkflowStatusHelper::faIcon($stepStatus) }} text-white text-xs"></i>
                                @else
                                    <span class="text-white text-xs font-bold">{{ $index + 1 }}</span>
                                @endif
                            @else
                                <span class="text-slate-400 text-xs font-bold">{{ $index + 1 }}</span>
                            @endif
                        </div>

                        {{-- Step Content --}}
                        <div class="flex-1 min-w-0 pt-1.5">
                            <div class="flex items-center justify-between flex-wrap gap-1">
                                <p class="text-sm font-semibold
                                           {{ $isDone ? 'text-green-700' : '' }}
                                           {{ $isActive ? 'text-[#2F5AA8]' : '' }}
                                           {{ $isPending && !$isActive ? 'text-slate-400' : '' }}">
                                    {{ $label }}
                                </p>

                                @if($isActive && $stepStatus)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse inline-block"></span>
                                        Tahap Saat Ini
                                    </span>
                                @elseif($isDone)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium text-green-600">
                                        <i class="fas fa-check text-[10px]"></i> Selesai
                                    </span>
                                @endif
                            </div>

                            @if($isActive && $stepStatus)
                                <p class="text-xs text-slate-400 mt-0.5">
                                    <i class="fas fa-clock text-[10px] mr-1"></i>
                                    Estimasi: {{ \App\Support\WorkflowStatusHelper::estimasi($stepStatus) }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
