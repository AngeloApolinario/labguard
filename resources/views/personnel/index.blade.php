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

            <div class="flex items-center gap-4">
                {{-- Master Schedule Link --}}
                <a href="{{ route('personnel.full-schedule') }}" class="px-5 py-3 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-[#D4AF37] transition-all flex items-center gap-3 shadow-lg shadow-slate-200">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Weekly Master Grid
                </a>

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
        </div>
    </x-slot>

    <div class="py-12 px-6 min-h-screen bg-[#F8FAFC] relative overflow-hidden">

        {{-- ACCESS DENIED ALERT --}}
        @if(session('error'))
        <div class="max-w-7xl mx-auto mb-8 animate-bounce">
            <div class="bg-rose-50 border border-rose-200 p-4 rounded-2xl flex items-center gap-4">
                <div class="size-10 bg-rose-500 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-black text-rose-500 uppercase tracking-widest">Access Restricted</p>
                    <p class="text-sm font-bold text-rose-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
        @endif

        {{-- High-End Ambient Background --}}
        <div class="absolute top-0 left-0 w-full h-full opacity-30 pointer-events-none">
            <div class="absolute top-[-5%] right-[-2%] w-[500px] h-[500px] bg-[#D4AF37]/10 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[5%] left-[-2%] w-[400px] h-[400px] bg-blue-100/50 rounded-full blur-[100px]"></div>
        </div>

        <div class="max-w-7xl mx-auto relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($labs as $lab)
                @php
                // Check Current Occupancy Logic
                $currentTime = now()->format('H:i:s');
                $currentDay = now()->format('l');
                $activeSched = $lab->schedules
                ->where('day', $currentDay)
                ->where('start_time', '<=', $currentTime)
                    ->where('end_time', '>=', $currentTime)
                    ->first();

                    // Access is locked if a schedule exists and the user is NOT the owner (and not admin)
                    $isLocked = $activeSched && auth()->id() !== $activeSched->user_id && auth()->user()->role !== 'admin';
                    @endphp

                    <a href="{{ $isLocked ? '#' : route('personnel.lab.show', $lab->id) }}"
                        class="group {{ $isLocked ? 'cursor-not-allowed' : '' }}">

                        <div class="relative bg-white border {{ $isLocked ? 'border-slate-100 opacity-75 grayscale' : 'border-slate-200/60 transition-all duration-500 group-hover:shadow-[0_40px_80px_rgba(212,175,55,0.15)] group-hover:-translate-y-3' }} rounded-[3rem] p-1 shadow-[0_10px_30px_rgba(0,0,0,0.02)]">

                            {{-- Inner Card Container --}}
                            <div class="bg-white rounded-[2.8rem] p-8 border border-transparent {{ !$isLocked ? 'group-hover:border-[#D4AF37]/20' : '' }} transition-all duration-500">

                                <div class="flex justify-between items-start mb-10">
                                    <div class="relative">
                                        <div class="p-5 {{ $isLocked ? 'bg-slate-100 text-slate-300' : 'bg-slate-50 text-slate-400 group-hover:bg-[#D4AF37] group-hover:text-white' }} rounded-[1.5rem] border border-slate-100 transition-all duration-500 ease-out">
                                            @if($isLocked)
                                            <svg class="size-7" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z" />
                                            </svg>
                                            @else
                                            <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                            @endif
                                        </div>
                                        @if($lab->occupied > 0 && !$isLocked)
                                        <div class="absolute -top-1 -right-1 size-4 bg-[#D4AF37] border-2 border-white rounded-full"></div>
                                        @endif
                                    </div>

                                    <div class="text-right">
                                        <span class="text-[8px] font-black text-slate-300 uppercase tracking-[0.2em]">Node ID</span>
                                        <p class="text-xs font-black text-slate-800 uppercase tracking-tighter">00{{ $loop->iteration }}</p>
                                    </div>
                                </div>

                                <div class="mb-8">
                                    <h3 class="text-3xl font-black {{ $isLocked ? 'text-slate-400' : 'text-slate-800' }} tracking-tighter uppercase transition-colors">
                                        {{ $lab->lab_name }}
                                    </h3>
                                    @if($isLocked)
                                    <p class="text-[10px] font-bold text-rose-400 uppercase tracking-widest mt-1 italic">
                                        Reserved: {{ $activeSched->user->name }}
                                    </p>
                                    @else
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Standard Instructional Facility</p>
                                    @endif
                                </div>

                                <div class="space-y-6">
                                    {{-- Occupancy Visualizer --}}
                                    <div>
                                        <div class="flex justify-between text-[10px] font-black uppercase mb-3">
                                            <span class="text-slate-400">Load Factor</span>
                                            <span class="text-slate-800 italic">{{ floor(($lab->occupied / $lab->total) * 100) }}% Occupied</span>
                                        </div>
                                        <div class="h-3 w-full bg-slate-100 rounded-full p-0.5 border border-slate-50 overflow-hidden">
                                            <div class="h-full {{ $isLocked ? 'bg-slate-300' : 'bg-[#D4AF37]' }} rounded-full transition-all duration-[1500ms] ease-out shadow-[0_0_15px_rgba(212,175,55,0.3)]"
                                                style="width: {{ ($lab->occupied / $lab->total) * 100 }}%">
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Action Button --}}
                                    <div class="pt-4 flex items-center justify-between border-t border-slate-50">
                                        <span class="text-[10px] font-black {{ $isLocked ? 'text-slate-300' : 'text-[#D4AF37]' }} uppercase tracking-widest">
                                            {{ $isLocked ? 'Access Locked' : 'Open Monitor' }}
                                        </span>
                                        <div class="size-10 {{ $isLocked ? 'bg-slate-100 text-slate-300' : 'bg-slate-800 text-white group-hover:bg-[#D4AF37]' }} rounded-2xl flex items-center justify-center transition-all duration-300">
                                            @if($isLocked)
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                            @else
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                            </svg>
                                            @endif
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