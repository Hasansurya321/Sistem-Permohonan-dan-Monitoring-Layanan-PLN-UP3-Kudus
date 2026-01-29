@props(['activeTab', 'counts'])

<div class="bg-white rounded-xl border border-slate-200 p-2 mb-6 shadow-sm">
    <div class="flex items-center gap-2">
        <a href="{{ route('monitoring', ['tab' => 'waiting']) }}" 
           class="flex-1 px-6 py-3 rounded-lg font-bold text-center transition relative
                  {{ $activeTab === 'waiting' ? 'bg-yellow-50 text-yellow-700 border border-yellow-200' : 'text-slate-600 hover:bg-slate-50' }}">
            <div class="flex items-center justify-center gap-2">
                {{-- Pencil Icon (Edit) --}}
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M16.862 3.487a2.5 2.5 0 0 1 3.536 3.536L7.5 19.92l-4.5 1 1-4.5L16.862 3.487Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Menunggu Eksekusi</span>
                @if($counts['waiting'] > 0)
                    <span class="ml-1 px-2 py-0.5 bg-yellow-500 text-white text-xs rounded-full">{{ $counts['waiting'] }}</span>
                @endif
            </div>
        </a>
        
        <a href="{{ route('monitoring', ['tab' => 'processing']) }}" 
           class="flex-1 px-6 py-3 rounded-lg font-bold text-center transition relative
                  {{ $activeTab === 'processing' ? 'bg-blue-50 text-[#2F5AA8] border border-blue-200' : 'text-slate-600 hover:bg-slate-50' }}">
            <div class="flex items-center justify-center gap-2">
                {{-- Clock Icon (Processing) --}}
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 8v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span>Sedang Berjalan</span>
                @if($counts['processing'] > 0)
                    <span class="ml-1 px-2 py-0.5 bg-[#2F5AA8] text-white text-xs rounded-full">{{ $counts['processing'] }}</span>
                @endif
            </div>
        </a>
        
        <a href="{{ route('monitoring', ['tab' => 'done']) }}" 
           class="flex-1 px-6 py-3 rounded-lg font-bold text-center transition relative
                  {{ $activeTab === 'done' ? 'bg-green-50 text-green-700 border border-green-200' : 'text-slate-600 hover:bg-slate-50' }}">
            <div class="flex items-center justify-center gap-2">
                {{-- Check Circle Icon (Done) --}}
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span>Selesai</span>
                @if($counts['done'] > 0)
                    <span class="ml-1 px-2 py-0.5 bg-green-600 text-white text-xs rounded-full">{{ $counts['done'] }}</span>
                @endif
            </div>
        </a>
    </div>
</div>
