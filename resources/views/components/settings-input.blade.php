@props(['label', 'value', 'type' => 'text', 'icon' => null])
<div class="flex flex-col space-y-2">
    <label class="text-xs font-bold text-slate-500">{{ $label }}</label>
    <div class="relative">
        <input type="{{ $type }}" value="{{ $value }}"
            class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:ring-1 focus:ring-[#D4AF37] focus:border-[#D4AF37] outline-none transition">
        @if($icon)
        <x-dynamic-component :component="$icon" class="absolute right-4 top-3 size-5 text-slate-400" />
        @endif
    </div>
</div>