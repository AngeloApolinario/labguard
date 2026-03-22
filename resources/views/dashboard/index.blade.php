<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-4xl text-slate-800 tracking-tighter uppercase">
                    LabGuard <span class="text-[#D4AF37]">Command</span>
                </h2>
                <div class="flex items-center space-x-2 mt-1">
                    <div class="size-2 bg-green-500 rounded-full animate-pulse"></div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">
                        Root Administrator • Secure Session
                    </p>
                </div>
            </div>

            <div class="bg-white px-6 py-2 rounded-2xl border border-slate-100 shadow-sm text-right">
                <p class="text-[9px] font-black text-[#D4AF37] uppercase tracking-widest">System Date</p>
                <p class="text-sm font-black text-slate-700 uppercase">{{ now()->format('D, M d, Y') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12 px-6 min-h-screen bg-[#F8FAFC]" x-data="{ activeLab: 'all' }">
        <div class="max-w-7xl mx-auto space-y-10">

            {{-- HUD Statistics --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-slate-800 rounded-[2rem] p-6 shadow-xl relative overflow-hidden group border border-white/5">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Total Terminals</p>
                        <h3 class="text-4xl font-black text-white italic">{{ $totalComputers ?? 0 }}</h3>
                    </div>
                </div>

                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-200 relative overflow-hidden">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Active Sessions</p>
                        <h3 class="text-4xl font-black text-slate-800 italic">{{ $activeStations ?? 0 }}</h3>
                    </div>
                </div>

                <div class="bg-rose-500 rounded-[2rem] p-6 shadow-xl shadow-rose-200 relative overflow-hidden">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-white/70 uppercase tracking-[0.2em] mb-1">Alerts Detected</p>
                        <h3 class="text-4xl font-black text-white italic">{{ $alertsToday ?? 0 }}</h3>
                    </div>
                </div>

                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-200 flex flex-col items-center justify-center">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Network Health</p>
                    <div class="px-5 py-1.5 rounded-full bg-green-500/10 border border-green-500/20 text-green-500 font-black text-sm uppercase">Optimal</div>
                </div>
            </div>

            {{-- Lab Filter --}}
            <div class="flex items-center space-x-3 bg-slate-200/50 p-1.5 rounded-2xl w-fit">
                <button @click="activeLab = 'all'"
                    :class="activeLab === 'all' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    class="px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">All Nodes</button>
                @foreach($labs as $lab)
                <button @click="activeLab = '{{ $lab }}'"
                    :class="activeLab === '{{ $lab }}' ? 'bg-[#D4AF37] text-white shadow-lg' : 'text-slate-500 hover:text-slate-800'"
                    class="px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">{{ $lab }}</button>
                @endforeach
            </div>

            {{-- Topology Map --}}
            <div class="bg-white rounded-[3rem] p-10 border border-slate-200 shadow-sm">
                <div class="flex justify-between items-center mb-10 pb-6 border-b border-slate-50">
                    <h4 class="text-2xl font-black text-slate-800 tracking-tighter uppercase">Terminal <span class="text-[#D4AF37]">Topology</span></h4>
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center space-x-2">
                            <div class="size-2 bg-slate-100 border border-slate-300 rounded-full"></div>
                            <span class="text-[9px] font-black text-slate-400 uppercase">Available</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="size-2 bg-rose-500 rounded-full"></div>
                            <span class="text-[9px] font-black text-slate-400 uppercase">Active</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-5 sm:grid-cols-8 md:grid-cols-10 lg:grid-cols-12 gap-4">
                    @foreach($computers as $pc)
                    {{-- THE WRAPPER: relative and isolate prevent mouse axis issues --}}
                    <div
                        x-show="activeLab === 'all' || activeLab === '{{ $pc->lab_name }}'"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-90"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="group relative z-10 aspect-square rounded-2xl border-2 flex flex-col items-center justify-center transition-all duration-300 transform-gpu
                            {{ $pc->status === 'active' ? 'bg-rose-500 border-rose-600 text-white shadow-lg scale-105' : '' }}
                            {{ $pc->status === 'available' ? 'bg-slate-50 border-slate-100 text-slate-400 hover:border-[#D4AF37] hover:bg-white' : '' }}
                            {{ $pc->status === 'maintinance' ? 'bg-amber-500 border-amber-600 text-white' : '' }}">

                        <span class="text-[10px] font-black tracking-tighter">{{ $pc->pc_number }}</span>

                        @if($pc->status === 'active')
                        <svg class="size-4 mt-1 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        @endif

                        {{-- THE TOOLTIP: pointer-events-none by default fixes the 'offset' hover bug --}}
                        <div class="absolute bottom-[110%] left-1/2 -translate-x-1/2 w-48 p-4 bg-slate-800 rounded-2xl 
                                    opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto 
                                    transition-all duration-200 z-[100] shadow-2xl border border-white/10">

                            <div class="text-[8px] font-black text-[#D4AF37] uppercase mb-1 tracking-widest">{{ $pc->lab_name }}</div>

                            @if($pc->status === 'active' && $pc->activeSession)
                            <div class="text-[11px] font-bold text-white truncate">{{ $pc->activeSession->student_name ?? 'Logged In' }}</div>
                            <div class="text-[8px] text-slate-400 mt-0.5 uppercase tracking-tighter">
                                Since {{ $pc->activeSession->login_at?->format('h:i A') ?? 'N/A' }}
                            </div>

                            <div class="mt-3 pt-3 border-t border-white/10">
                                <form method="POST" action="{{ route('dashboard.sessions.terminate', $pc->activeSession->id) }}"
                                    onsubmit="return confirm('Kill session for {{ $pc->activeSession->student_name }}?')">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="w-full py-2 bg-rose-600 hover:bg-rose-700 text-white text-[9px] font-black uppercase tracking-widest rounded-lg transition-colors shadow-lg">Kill Session</button>
                                </form>
                            </div>
                            @else
                            <div class="text-[10px] font-bold text-white uppercase tracking-widest">{{ $pc->status }}</div>
                            <div class="text-[7px] text-slate-500 mt-1 uppercase">Ready for login</div>
                            @endif

                            {{-- Tooltip Arrow --}}
                            <div class="absolute top-full left-1/2 -translate-x-1/2 border-8 border-transparent border-t-slate-800"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>