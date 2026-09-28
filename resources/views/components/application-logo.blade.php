<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3.5 select-none']) }}>
  <!-- Shield / Terminal Security Badge -->
  <div class="relative flex items-center justify-center w-12 h-12 rounded-xl bg-slate-900 border border-amber-500/40 shadow-lg shadow-amber-500/10">
    <!-- Subtle gold glow -->
    <div class="absolute inset-0 rounded-xl bg-amber-400/5 blur-sm"></div>

    <!-- Security Shield Icon -->
    <svg class="w-6 h-6 text-amber-400 relative z-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
      <!-- Inner Terminal / Screen -->
      <rect x="8.5" y="8" width="7" height="5" rx="1" fill="currentColor" fill-opacity="0.25" stroke="currentColor" stroke-width="1.5" />
      <path d="M10 16h4" stroke-width="1.5" />
    </svg>

    <!-- Live Online Pulse Dot -->
    <span class="absolute -top-1 -right-1 flex h-3 w-3">
      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
      <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500 border-2 border-slate-900"></span>
    </span>
  </div>

  <!-- Text Branding -->
  <div class="flex flex-col text-left">
    <span class="text-xl font-black tracking-wider text-slate-100 font-sans leading-tight">
      LAB<span class="text-amber-400">GUARD</span>
    </span>
    <span class="text-[9px] font-bold tracking-widest text-slate-400 uppercase">
      Terminal Access System
    </span>
  </div>
</div>