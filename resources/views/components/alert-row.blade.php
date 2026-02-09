@props(['pc', 'issue', 'time', 'level', 'color'])

@php
// Mapping the colors to match your high-contrast theme
$statusClasses = match ($color) {
'red' => 'border-red-500 text-red-600 bg-red-50',
'orange' => 'border-orange-500 text-orange-600 bg-orange-50',
'amber' => 'border-amber-500 text-amber-600 bg-amber-50',
'blue' => 'border-blue-500 text-blue-600 bg-blue-50',
default => 'border-slate-500 text-slate-600 bg-slate-50',
};
@endphp

<div class="flex items-center justify-between p-5 bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
    <div class="flex flex-col">
        <h5 class="font-black text-slate-800 text-sm tracking-tight">
            {{ $pc }} — {{ $issue }}
        </h5>
        <p class="text-xs font-medium text-slate-400 mt-1 uppercase tracking-wider">
            {{ $time }}
        </p>
    </div>

    <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase border {{ $statusClasses }} tracking-[0.1em]">
        {{ $level }}
    </span>
</div>