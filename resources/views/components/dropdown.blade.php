@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1', 'dropdownClasses' => ''])

@php
$alignmentClasses = match ($align) {
'left' => 'start-0 origin-top-left',
'top' => 'bottom-full mb-3 left-0 origin-bottom',
'up' => 'bottom-full mb-3 left-0 origin-bottom',
default => 'end-0 origin-top-right',
};

$width = match ($width) {
'48' => 'w-48',
'60' => 'w-60',
'full' => 'w-full',
default => 'w-48',
};
@endphp

<div class="relative" x-data="{ open: false }" @click.away="open = false" @close.stop="open = false">
    <div @click="open = ! open" class="group">
        {{ $trigger }}
    </div>

    <div x-show="open"
        x-transition:enter="transition cubic-bezier(0.4, 0, 0.2, 1) duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-[100] {{ $width }} {{ $alignmentClasses }} {{ $dropdownClasses }}"
        style="display: none;"
        @click="open = false">

        <div class="relative overflow-hidden rounded-xl border border-white/10 bg-[#1e2945]/90 backdrop-blur-xl shadow-[0_20px_50px_rgba(0,0,0,0.5)]">

            <div class="absolute inset-0 border-t border-white/20 pointer-events-none rounded-xl"></div>

            <div class="relative z-10 {{ $contentClasses }}">
                <div class="text-slate-200 antialiased">
                    {{ $content }}
                </div>
            </div>
        </div>
    </div>
</div>