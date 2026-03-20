@props(['label', 'percent', 'color', 'suffix' => '%'])
<div>
    <div class="flex justify-between mb-2">
        <span class="text-sm font-bold text-slate-600 uppercase tracking-tight">{{ $label }}</span>
        <span class="text-sm font-black text-slate-800">{{ $percent }}{{ $suffix }}</span>
    </div>
    <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden">
        <div class="{{ $color }} h-full transition-all duration-1000" style="width: {{ $percent }}%"></div>
    </div>
</div>