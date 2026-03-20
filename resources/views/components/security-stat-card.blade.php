@props(['label', 'value', 'sub', 'icon', 'iconColor'])
<div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 flex items-start space-x-6">
    <div class="p-4 bg-slate-50 rounded-2xl">
        <x-dynamic-component :component="$icon" class="size-8 {{ $iconColor }}" />
    </div>
    <div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">{{ $label }}</p>
        <h3 class="text-4xl font-black text-slate-900 mb-1">{{ $value }}</h3>
        <p class="text-xs text-slate-400 font-medium italic">{{ $sub }}</p>
    </div>
</div>