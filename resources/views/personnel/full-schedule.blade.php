<x-app-layout>
    <div class="py-12 px-4 md:px-8 min-h-screen bg-[#F8FAFC]">
        <div class="max-w-[1600px] mx-auto"> {{-- Expanded for larger screens --}}

            {{-- Header Section --}}
            <div class="mb-10 flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div>
                    <h2 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tighter uppercase leading-none">
                        Master <span class="text-[#D4AF37]">Schedule</span>
                    </h2>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em] mt-3">
                        Cross-Facility Occupancy Overview
                    </p>
                </div>
                <a href="{{ route('personnel.index') }}" class="inline-flex items-center justify-center px-6 py-3 bg-white border border-slate-200 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Selection
                </a>
            </div>

            {{-- The Master Grid Container --}}
            <div class="space-y-12">
                @foreach($labs as $lab)
                <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden transition-all hover:shadow-md">

                    {{-- Lab Title Ribbon - Sticky on Scroll --}}
                    <div class="px-8 py-5 bg-slate-900 flex items-center justify-between sticky left-0 z-20">
                        <div class="flex items-center gap-4">
                            <div class="size-3 bg-[#D4AF37] rounded-full"></div>
                            <h3 class="text-lg font-black text-white uppercase tracking-tight">{{ $lab->lab_name }}</h3>
                        </div>
                        <span class="hidden sm:inline text-[9px] font-bold text-slate-500 uppercase tracking-[0.2em]">Facility ID: #00{{ $lab->id }}</span>
                    </div>

                    {{-- Responsive Grid Wrapper --}}
                    <div class="overflow-x-auto custom-scrollbar">
                        {{--
                            Using Grid instead of Table for better control.
                            min-w-[1000px] ensures columns don't get too thin on 10-inch screens.
                        --}}
                        <div class="min-w-[1100px] grid grid-cols-6 border-b border-slate-50">

                            {{-- Day Headers --}}
                            @foreach($days as $day)
                            <div class="p-5 bg-slate-50/50 border-r border-slate-100 last:border-0 text-center">
                                <span class="text-[10px] font-black uppercase {{ now()->format('l') == $day ? 'text-[#D4AF37]' : 'text-slate-400' }}">
                                    {{ $day }}
                                </span>
                                @if(now()->format('l') == $day)
                                <p class="text-[8px] font-bold text-[#D4AF37]/60 uppercase tracking-tighter">Current Day</p>
                                @endif
                            </div>
                            @endforeach

                            {{-- Schedule Slots --}}
                            @foreach($days as $day)
                            <div class="p-4 border-r border-slate-50 last:border-0 {{ now()->format('l') == $day ? 'bg-[#D4AF37]/[0.02]' : '' }}">
                                <div class="space-y-4">
                                    @php
                                    $daySchedules = $lab->schedules->where('day', $day)->sortBy('start_time');
                                    @endphp

                                    @forelse($daySchedules as $sched)
                                    @php
                                    $isNow = (now()->format('l') == $day && now()->between($sched->start_time, $sched->end_time));
                                    @endphp
                                    <div class="group relative p-4 rounded-2xl border transition-all duration-300
                                        {{ $isNow 
                                            ? 'bg-white border-[#D4AF37] shadow-lg shadow-[#D4AF37]/10 z-10 scale-[1.02]' 
                                            : 'bg-slate-50/50 border-slate-100 hover:border-slate-300 hover:bg-white' }}">

                                        <div class="flex justify-between items-start mb-2">
                                            <span class="text-[9px] font-black text-slate-400 uppercase font-mono group-hover:text-[#D4AF37]">
                                                {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }}
                                            </span>
                                            @if($isNow)
                                            <div class="flex gap-1">
                                                <span class="text-[8px] font-black text-[#D4AF37] uppercase">Active</span>
                                                <div class="size-2 bg-[#D4AF37] rounded-full animate-pulse"></div>
                                            </div>
                                            @endif
                                        </div>

                                        <p class="text-[11px] font-black text-slate-800 uppercase leading-tight">
                                            {{ $sched->subject_code }}
                                        </p>
                                        <p class="text-[9px] font-bold text-slate-500 uppercase mt-1">
                                            {{ $sched->user->name }}
                                        </p>
                                    </div>
                                    @empty
                                    <div class="py-10 flex flex-col items-center justify-center border-2 border-dashed border-slate-50 rounded-2xl">
                                        <p class="text-[9px] font-bold text-slate-300 uppercase tracking-widest">No Sessions</p>
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                            @endforeach

                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <style>
        /* Custom Scrollbar for the 10-13 inch screens */
        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #D4AF37;
        }
    </style>
</x-app-layout>