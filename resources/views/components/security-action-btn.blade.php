@props(['label', 'icon'])
<button class="w-full flex items-center justify-center py-4 bg-[#1e2945] text-white rounded-xl font-bold hover:bg-[#161d31] transition shadow-lg space-x-3">
    <x-dynamic-component :component="$icon" class="size-5" />
    <span>{{ $label }}</span>
</button>