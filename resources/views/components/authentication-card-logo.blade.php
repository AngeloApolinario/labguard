<a href="/" {{ $attributes->merge(['class' => 'relative flex items-center justify-center rounded-2xl bg-slate-900 border border-[#D4AF37]/40 shadow-inner group transition-transform hover:scale-105 select-none']) }}>
    <!-- Soft Gold Ambient Glow -->
    <div class="absolute inset-0 rounded-2xl bg-[#D4AF37]/10 blur-[8px] group-hover:bg-[#D4AF37]/20 transition-colors"></div>

    <!-- Shield + Workstation Monitor Emblem -->
    <svg class="w-8 h-8 text-[#D4AF37] relative z-10 transition-transform group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <!-- Security Shield Base -->
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" fill="#D4AF37" fill-opacity="0.08" />

        <!-- Computer Monitor Inside Shield -->
        <rect x="7.5" y="7.5" width="9" height="6" rx="1" fill="#D4AF37" fill-opacity="0.2" stroke="currentColor" stroke-width="1.5" />
        <path d="M10 16.5h4" stroke-width="1.5" />
        <path d="M12 13.5v3" stroke-width="1.5" />
    </svg>

    <!-- Live Status Pulse Indicator
    <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5 z-20">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 border-2 border-[#0f172a]"></span>
    </span> -->
</a>