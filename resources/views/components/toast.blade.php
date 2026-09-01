@if (session('toast'))
@php
$toast = session('toast');
$type = is_array($toast) ? ($toast['type'] ?? 'success') : 'success';
$title = is_array($toast) ? ($toast['title'] ?? 'System Notice') : 'Success';
$message = is_array($toast) ? ($toast['message'] ?? '') : $toast;

$styles = [
'success' => ['border' => 'border-emerald-500', 'bg_icon' => 'bg-emerald-500/20 text-emerald-400', 'title' => 'text-emerald-400'],
'danger' => ['border' => 'border-rose-500', 'bg_icon' => 'bg-rose-500/20 text-rose-400', 'title' => 'text-rose-400'],
'warning' => ['border' => 'border-[#D4AF37]', 'bg_icon' => 'bg-[#D4AF37]/20 text-[#D4AF37]', 'title' => 'text-[#D4AF37]'],
][$type] ?? ['border' => 'border-blue-500', 'bg_icon' => 'bg-blue-500/20 text-blue-400', 'title' => 'text-blue-400'];
@endphp

<div x-data="{ show: true }"
    x-init="setTimeout(() => show = false, 5000)"
    x-show="show"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 -translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-4"
    class="fixed top-6 right-6 z-50 max-w-md bg-slate-900 border {{ $styles['border'] }} text-white p-4 rounded-2xl shadow-2xl flex items-start gap-3">

    <div class="size-8 rounded-xl {{ $styles['bg_icon'] }} border border-current/30 flex items-center justify-center shrink-0 mt-0.5">
        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>

    <div class="flex-1">
        <h4 class="text-xs font-black uppercase tracking-widest {{ $styles['title'] }}">{{ $title }}</h4>
        <p class="text-xs font-semibold text-slate-300 mt-0.5 leading-relaxed">{{ $message }}</p>
    </div>

    <!-- Manual Close Button -->
    <button @click="show = false" class="text-slate-500 hover:text-white transition-colors focus:outline-none">
        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
</div>
@endif