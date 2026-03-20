@props(['label', 'value', 'change', 'icon', 'color'])
<div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center justify-between">
    <div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">{{ $label }}</p>
        <h3 class="text-3xl font-black text-slate-900">{{ $value }}</h3>
        <p class="text-[10px] font-bold mt-1 {{ str_contains($change, '+') ? 'text-green-500' : 'text-red-500' }}">{{ $change }}</p>
    </div>
    <div class="p-4 bg-{{ $color }}-50 text-{{ $color }}-500 rounded-2xl">
        <x-dynamic-component :component="$icon" class="size-8" />
    </div>
</div>                   