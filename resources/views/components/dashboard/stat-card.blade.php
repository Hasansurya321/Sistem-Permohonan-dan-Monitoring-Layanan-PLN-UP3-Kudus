@props([
    'count' => 0,
    'label' => '',
    'icon' => 'fa-circle',
    'color' => 'slate', // blue, orange, green, slate
    'active' => false,
])

@php
    // 🔴 FIX: Gunakan inline style untuk background-color agar pasti terender
    // Tailwind tidak bisa meng-compile class dinamis bg-[#...] dari variabel PHP
    $bgColors = [
        'blue'   => $active ? '#F3F8FF' : '#FFFFFF',
        'orange' => $active ? '#FFF7ED' : '#FFFFFF',
        'green'  => $active ? '#F0FDF4' : '#FFFFFF',
        'slate'  => '#F8FAFC',
    ];
    $borderColors = [
        'blue'   => $active ? '#BFD8FF' : '#E2E8F0',
        'orange' => $active ? '#FED7AA' : '#E2E8F0',
        'green'  => $active ? '#BBF7D0' : '#E2E8F0',
        'slate'  => '#CBD5E1',
    ];
    $bgStyle = $bgColors[$color] ?? '#FFFFFF';
    $borderStyle = $borderColors[$color] ?? '#E2E8F0';

    $colorMap = [
        'blue' => [
            'iconBg'    => $active ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-400',
            'count'     => $active ? 'text-[#2F5AA8]' : 'text-slate-300',
        ],
        'orange' => [
            'iconBg'    => $active ? 'bg-orange-100 text-orange-600' : 'bg-slate-100 text-slate-400',
            'count'     => $active ? 'text-orange-600' : 'text-slate-300',
        ],
        'green' => [
            'iconBg'    => $active ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-400',
            'count'     => $active ? 'text-green-600' : 'text-slate-300',
        ],
        'slate' => [
            'iconBg'    => 'bg-slate-100 text-slate-400',
            'count'     => $count > 0 ? 'text-slate-700' : 'text-slate-300',
        ],
    ];
    $colors = $colorMap[$color] ?? $colorMap['slate'];
@endphp

<div class="rounded-2xl border p-5 shadow-sm transition-all duration-200 hover:shadow-md"
     style="background-color: {{ $bgStyle }}; border-color: {{ $borderStyle }};">
    <div class="flex items-center justify-between mb-2">
        <div class="w-9 h-9 rounded-xl {{ $colors['iconBg'] }} flex items-center justify-center transition-colors">
            <i class="fas {{ $icon }} text-sm"></i>
        </div>
        <span class="text-3xl font-black {{ $colors['count'] }}">{{ $count }}</span>
    </div>
    <p class="text-xs text-slate-500 font-medium">{{ $label }}</p>
</div>
