<div class="max-w-7xl mx-auto px-6 py-8" wire:poll.5s>

    {{-- COMMAND CENTER HEADER --}}
    <div class="relative mb-12 overflow-hidden rounded-[2.5rem] bg-white border border-slate-100 shadow-2xl shadow-slate-200/50">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-rose-500 via-amber-400 to-emerald-500"></div>
        <div class="p-8 md:flex md:items-center md:justify-between">
            <div class="flex items-center gap-6">
                <div class="relative flex items-center justify-center size-16 bg-slate-50 rounded-2xl border border-slate-100 shadow-inner">
                    <svg class="size-8 text-slate-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <div class="absolute -top-1 -right-1 size-3 bg-emerald-500 rounded-full animate-pulse border-2 border-white"></div>
                </div>
                <div>
                    <h2 class="text-3xl font-black text-slate-900 uppercase tracking-tighter leading-none">
                        {{ $labName }}
                    </h2>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em] mt-1">Realtime Surveillance</p>
                </div>
            </div>

            <div class="mt-6 md:mt-0 flex gap-4">
                <div class="px-6 py-3 bg-slate-50 rounded-2xl border border-slate-100 flex flex-col items-center min-w-[100px]">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Active</span>
                    <span class="text-xl font-black text-rose-500">{{ $computers->where('status', 'active')->count() }}</span>
                </div>
                <div class="px-6 py-3 bg-slate-50 rounded-2xl border border-slate-100 flex flex-col items-center min-w-[100px]">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Time</span>
                    <span class="text-xl font-black text-slate-800 font-mono">{{ now()->format('h:i A') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- THE GRID --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
        @foreach($computers as $pc)
        @php
        $isActive = ($pc->status === 'active');
        $session = $isActive ? $pc->activeSession : null;
        $hasData = ($isActive && $session);
        $name = $hasData ? $session->student_name : null;
        $initial = $name ? strtoupper(substr($name, 0, 1)) : '?';
        @endphp

        <div class="group relative aspect-[4/5] bg-white rounded-[2rem] border transition-all duration-500 overflow-hidden
                {{ $hasData ? 'border-rose-100 shadow-xl shadow-rose-100/50' : 'border-slate-100 shadow-sm hover:border-emerald-200' }}">

            <div class="absolute inset-0 p-5 flex flex-col items-center justify-between z-10">
                <div class="w-full flex justify-between items-center">
                    <span class="text-[10px] font-black {{ $hasData ? 'text-rose-400' : 'text-slate-300' }} uppercase tracking-wider">
                        {{ $pc->pc_number }}
                    </span>
                    <div class="flex items-center gap-1.5">
                        <div class="size-1.5 rounded-full {{ $hasData ? 'bg-rose-500 animate-pulse' : 'bg-emerald-400' }}"></div>
                        <span class="text-[8px] font-bold {{ $hasData ? 'text-rose-500' : 'text-emerald-500' }} uppercase">
                            {{ $hasData ? 'LIVE' : 'READY' }}
                        </span>
                    </div>
                </div>

                <div class="relative">
                    @if($hasData)
                    <div class="size-20 rounded-[1.5rem] bg-gradient-to-br from-rose-500 to-rose-600 shadow-lg shadow-rose-200 flex items-center justify-center">
                        <span class="text-3xl font-black text-white">{{ $initial }}</span>
                    </div>
                    @else
                    <div class="size-20 rounded-[1.5rem] bg-slate-50 flex items-center justify-center border border-slate-100">
                        <svg class="size-8 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    @endif
                </div>

                <div class="w-full text-center pb-2">
                    @if($hasData)
                    <p class="text-[11px] font-black text-slate-800 truncate uppercase">{{ $name }}</p>
                    <p class="text-[9px] font-bold text-slate-400 font-mono mt-0.5">{{ $session->student_number }}</p>
                    @else
                    <p class="text-[9px] font-bold text-slate-300 uppercase tracking-widest">Available</p>
                    @endif
                </div>
            </div>

            @if($hasData)
            <div class="absolute inset-x-0 bottom-0 bg-slate-900/95 backdrop-blur-md p-5 transform translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-out z-20">
                <div class="space-y-3 mb-5 text-left">
                    <div>
                        <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest">In-Time</p>
                        <p class="text-xs font-mono text-[#D4AF37]">{{ $session->time_in->format('h:i A') }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('personnel.release', $pc->id) }}">
                    @csrf
                    <button type="submit" class="w-full py-3 bg-white text-slate-900 text-[10px] font-black uppercase rounded-xl hover:bg-rose-500 hover:text-white transition-all">
                        Force Release
                    </button>
                </form>
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>