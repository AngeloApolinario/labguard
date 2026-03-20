@props(['label', 'value', 'color', 'text'])
<div class="{{ $color }} p-6 rounded-2xl border border-slate-100">
    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3">{{ $label }}</p>
    <h3 class="text-4xl font-bold {{ $text }}">{{ $value }}</h3>
</div>