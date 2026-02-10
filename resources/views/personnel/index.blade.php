<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-4xl text-slate-800 tracking-tighter uppercase">
                    Facility <span class="text-[#D4AF37]">Selection</span>
                </h2>
                <div class="flex items-center space-x-2 mt-1">
                    <div class="size-2 bg-green-500 rounded-full animate-pulse"></div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">
                        System Online • Liability Tracking Active
                    </p>
                </div>
            </div>

            {{-- Quick Summary Stats --}}
            <div class="flex items-center space-x-6 bg-white px-6 py-3 rounded-2xl border border-slate-100 shadow-sm">
                <div class="text-center">
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Total Labs</p>
                    <p class="text-lg font-black text-slate-800">{{ $labs->count() }}</p>
                </div>
                <div class="w-px h-8 bg-slate-100"></div>
                <div class="text-center">
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Global Occupancy</p>
                    <p class="text-lg font-black text-[#D4AF37]">
                        {{ $labs->sum('occupied') }} <span class="text-slate-300 text-xs">/ {{ $labs->sum('total') }}</span>
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12 px-6 min-h-screen bg-[#F8FAFC] relative overflow-hidden">
        {{-- High-End Ambient Background --}}
        <div class="absolute top-0 left-0 w-full h-full opacity-30 pointer-events-none">
            <div class="absolute top-[-5%] right-[-2%] w-[500px] h-[500px] bg-[#D4AF37]/10 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[5%] left-[-2%] w-[400px] h-[400px] bg-blue-100/50 rounded-full blur-[100px]"></div>
        </div>

        <div class="max-w-7xl mx-auto relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($labs as $lab)
                <a href="{{ route('personnel.lab.show', $lab->lab_name) }}" class="group">
                    <div class="relative bg-white border border-slate-200/60 rounded-[3rem] p-1 shadow-[0_10px_30px_rgba(0,0,0,0.02)] transition-all duration-500 group-hover:shadow-[0_40px_80px_rgba(212,175,55,0.15)] group-hover:-translate-y-3">

                        {{-- Inner Card Container --}}
                        <div class="bg-white rounded-[2.8rem] p-8 border border-transparent group-hover:border-[#D4AF37]/20 transition-all duration-500">

                            <div class="flex justify-between items-start mb-10">
                                <div class="relative">
                                    <div class="p-5 bg-slate-50 rounded-[1.5rem] border border-slate-100 group-hover:bg-[#D4AF37] transition-all duration-500 ease-out">
                                        <svg class="size-7 text-slate-400 group-hover:text-white transition-colors duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    {{-- Micro-badge for active lab --}}
                                    @if($lab->occupied > 0)
                                    <div class="absolute -top-1 -right-1 size-4 bg-[#D4AF37] border-2 border-white rounded-full"></div>
                                    @endif
                                </div>

                                <div class="text-right">
                                    <span class="text-[8px] font-black text-slate-300 uppercase tracking-[0.2em]">Node ID</span>
                                    <p class="text-xs font-black text-slate-800 uppercase tracking-tighter">00{{ $loop->iteration }}</p>
                                </div>
                            </div>

                            <div class="mb-8">
                                <h3 class="text-3xl font-black text-slate-800 tracking-tighter uppercase group-hover:text-[#D4AF37] transition-colors">
                                    {{ $lab->lab_name }}
                                </h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Standard Instructional Facility</p>
                            </div>

                            <div class="space-y-6">
                                {{-- Occupancy Visualizer --}}
                                <div>
                                    <div class="flex justify-between text-[10px] font-black uppercase mb-3">
                                        <span class="text-slate-400">Load Factor</span>
                                        <span class="text-slate-800 italic">{{ floor(($lab->occupied / $lab->total) * 100) }}% Occupied</span>
                                    </div>
                                    <div class="h-3 w-full bg-slate-100 rounded-full p-0.5 border border-slate-50 overflow-hidden">
                                        <div class="h-full bg-[#D4AF37] rounded-full transition-all duration-[1500ms] ease-out shadow-[0_0_15px_rgba(212,175,55,0.3)]"
                                            style="width: {{ ($lab->occupied / $lab->total) * 100 }}%">
                                        </div>
                                    </div>
                                    <div class="flex justify-between mt-2 text-[9px] font-bold text-slate-400 uppercase">
                                        <span>0 Units</span>
                                        <span>{{ $lab->total }} Units</span>
                                    </div>
                                </div>

                                {{-- Action Button --}}
                                <div class="pt-4 flex items-center justify-between border-t border-slate-50">
                                    <span class="text-[10px] font-black text-[#D4AF37] uppercase tracking-widest group-hover:translate-x-1 transition-transform">
                                        Open Monitor
                                    </span>
                                    <div class="size-10 bg-slate-800 rounded-2xl flex items-center justify-center text-white group-hover:bg-[#D4AF37] transition-all duration-300">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>