@php
    $events = $events ?? $sr->events;
@endphp
@if($events && $events->count() > 0)
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden"
     x-data="{ expanded: true }">
    <button @click="expanded = !expanded"
            class="w-full flex items-center justify-between px-6 py-4 border-b border-slate-100 hover:bg-slate-50 transition-colors">
        <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-slate-400"></div>
            <span class="text-sm font-bold text-slate-700 uppercase tracking-widest">Riwayat Proses</span>
            <span class="px-2 py-0.5 bg-slate-100 text-slate-500 text-xs rounded-full font-bold">
                {{ $events->count() }} event
            </span>
        </div>
        <i class="fas text-xs text-slate-400 transition-transform duration-200"
           :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
    </button>

    <div x-show="expanded" x-collapse>
        <div class="px-6 py-5">
            <div class="relative">
                @if($events->count() > 1)
                <div class="absolute left-4 top-5 bottom-5 w-px bg-slate-100"></div>
                @endif

                <div class="space-y-5">
                    @foreach($events as $event)
                    @php
                        $isFirst   = $loop->first;
                        $evtLabel  = $event->status_detail?->getLabel() ?? $event->status->getLabel();
                        $evtColor  = $isFirst ? 'bg-[#2F5AA8]' : 'bg-slate-200';
                        $roleLabel = config('internal_roles.' . $event->updated_by_role . '.label', ucfirst(str_replace('_', ' ', $event->updated_by_role ?? 'Sistem')));
                    @endphp
                    <div class="relative flex items-start gap-4">
                        <div class="relative z-10 shrink-0 w-8 h-8 rounded-full {{ $evtColor }} flex items-center justify-center shadow-sm">
                            @if($isFirst)
                                <i class="fas fa-star text-white text-[9px]"></i>
                            @else
                                <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                            @endif
                        </div>
                        <div class="flex-1 bg-{{ $isFirst ? 'blue-50 border-blue-100' : 'slate-50 border-slate-100' }} rounded-xl border p-4">
                            <div class="flex items-start justify-between flex-wrap gap-2">
                                <div>
                                    <p class="font-bold text-sm {{ $isFirst ? 'text-blue-800' : 'text-slate-700' }}">
                                        {{ $evtLabel }}
                                    </p>
                                    @if($event->status_detail && $event->status_detail->getLabel() !== $event->status->getLabel())
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ $event->status->getLabel() }}
                                    </p>
                                    @endif
                                </div>
                                <span class="text-xs font-mono text-slate-400 whitespace-nowrap">
                                    {{ \App\Helpers\WaktuHelper::formatLengkap($event->occurred_at) }}
                                </span>
                            </div>
                            @if($event->note)
                            <div class="mt-2 flex items-start gap-2 text-xs text-slate-500">
                                <i class="fas fa-quote-left text-slate-300 mt-0.5 shrink-0"></i>
                                <span class="italic">{{ $event->note }}</span>
                            </div>
                            @endif
                            <div class="mt-2 flex items-center gap-2">
                                <div class="w-5 h-5 rounded-full bg-slate-200 flex items-center justify-center shrink-0">
                                    <i class="fas fa-user text-[8px] text-slate-500"></i>
                                </div>
                                <span class="text-xs text-slate-500">
                                    <span class="font-medium">{{ $event->updated_by_name ?? 'Sistem' }}</span>
                                    @if($event->updated_by_role)
                                    <span class="ml-1 px-1.5 py-0.5 bg-slate-200 text-slate-600 rounded text-[10px] font-medium">
                                        {{ $roleLabel }}
                                    </span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif