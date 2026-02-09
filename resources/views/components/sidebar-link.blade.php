@props(['active', 'icon'])

@php
$classes = ($active ?? false)
? 'group flex items-center px-4 py-3 text-sm font-bold text-white bg-gradient-to-r from-[#D4AF37]/20 to-transparent border-l-4 border-[#D4AF37] shadow-[0_0_15px_rgba(212,175,55,0.1)] transition-all duration-300 ease-in-out'
: 'group flex items-center px-4 py-3 text-sm font-medium text-slate-400 hover:text-white hover:bg-white/5 border-l-4 border-transparent transition-all duration-200 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @if(isset($icon))
    <x-dynamic-component :component="$icon"
        class="size-5 mr-3 transition-colors duration-200 {{ $active ? 'text-[#D4AF37]' : 'text-slate-500 group-hover:text-slate-300' }}" />
    @endif

    <span class="{{ $active ? 'tracking-wide' : '' }}">
        {{ $slot }}
    </span>

    @if($active)
    <span class="ml-auto size-1.5 rounded-full bg-[#D4AF37] shadow-[0_0_8px_#D4AF37]"></span>
    @endif
</a>