<div {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-900 border border-amber-500/40 shadow-sm']) }}>
  <svg class="w-5 h-5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
    <rect x="8.5" y="8" width="7" height="5" rx="1" fill="currentColor" fill-opacity="0.25" stroke="currentColor" stroke-width="1.5" />
    <path d="M10 16h4" stroke-width="1.5" />
  </svg>
  <!-- Green status indicator -->
  <span class="absolute -top-0.5 -right-0.5 h-2 w-2 rounded-full bg-emerald-500 ring-2 ring-slate-900"></span>
</div>